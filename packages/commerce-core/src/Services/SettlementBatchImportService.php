<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Platform\CommerceCore\Models\CodSettlement;
use Platform\CommerceCore\Models\SettlementBatch;
use SplFileObject;

class SettlementBatchImportService
{
    public function __construct(protected SettlementBatchService $settlementBatchService) {}

    public function importFromUploadedFile(UploadedFile $file, array $payload, ?int $actorAdminId = null): array
    {
        $path = $file->getRealPath();

        if (! $path) {
            throw ValidationException::withMessages([
                'import_file' => 'The uploaded CSV file could not be read.',
            ]);
        }

        return $this->importFromCsvPath($path, $payload, $actorAdminId);
    }

    public function importFromCsvPath(string $path, array $payload, ?int $actorAdminId = null): array
    {
        if (! is_file($path)) {
            throw ValidationException::withMessages([
                'import_file' => 'The provided CSV file path does not exist.',
            ]);
        }

        $rows = $this->parseRows($path);
        $normalized = $this->normalizeRows(
            rows: $rows,
            shipmentCarrierId: Arr::get($payload, 'shipment_carrier_id')
                ? (int) Arr::get($payload, 'shipment_carrier_id')
                : null,
        );

        if (count($normalized['errors']) > 0) {
            throw ValidationException::withMessages([
                'import_file' => $this->formatImportErrors($normalized['errors']),
            ]);
        }

        if (count($normalized['settlement_ids']) === 0) {
            throw ValidationException::withMessages([
                'import_file' => 'The CSV file did not contain any valid settlement rows.',
            ]);
        }

        $batch = $this->settlementBatchService->createBatch([
            'reference' => Arr::get($payload, 'reference'),
            'shipment_carrier_id' => Arr::get($payload, 'shipment_carrier_id'),
            'payout_method' => Arr::get($payload, 'payout_method'),
            'status' => Arr::get($payload, 'status', SettlementBatch::STATUS_RECONCILED),
            'notes' => Arr::get($payload, 'notes'),
            'settlement_ids' => $normalized['settlement_ids'],
            'remitted_amounts' => $normalized['remitted_amounts'],
            'adjustment_amounts' => $normalized['adjustment_amounts'],
            'item_notes' => $normalized['item_notes'],
        ], $actorAdminId);

        return [
            'batch' => $batch,
            'rows_total' => count($rows),
            'rows_imported' => count($normalized['settlement_ids']),
        ];
    }

    protected function parseRows(string $path): array
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $headerRow = $file->fgetcsv();

        if (! is_array($headerRow) || $headerRow === [null]) {
            throw ValidationException::withMessages([
                'import_file' => 'CSV header row is missing.',
            ]);
        }

        $headers = array_map(fn ($value) => $this->normalizeHeader($value), $headerRow);

        if (! in_array('remitted_amount', $headers, true)) {
            throw ValidationException::withMessages([
                'import_file' => 'CSV must include a remitted_amount column.',
            ]);
        }

        if (
            ! in_array('tracking_number', $headers, true)
            && ! in_array('order_increment_id', $headers, true)
        ) {
            throw ValidationException::withMessages([
                'import_file' => 'CSV must include tracking_number or order_increment_id.',
            ]);
        }

        $rows = [];
        $line = 2;

        while (! $file->eof()) {
            $raw = $file->fgetcsv();

            if (! is_array($raw)) {
                $line++;

                continue;
            }

            if (count($raw) === 1 && trim((string) ($raw[0] ?? '')) === '') {
                $line++;

                continue;
            }

            $row = ['__line' => $line];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = $raw[$index] ?? null;
            }

            $rows[] = $row;
            $line++;
        }

        return $rows;
    }

    protected function normalizeRows(array $rows, ?int $shipmentCarrierId = null): array
    {
        $errors = [];
        $settlementIds = [];
        $remittedAmounts = [];
        $adjustmentAmounts = [];
        $itemNotes = [];
        $seenSettlementIds = [];

        foreach ($rows as $row) {
            $line = (int) ($row['__line'] ?? 0);
            $trackingNumber = trim((string) ($row['tracking_number'] ?? ''));
            $orderIncrementId = trim((string) ($row['order_increment_id'] ?? ''));

            if ($trackingNumber === '' && $orderIncrementId === '') {
                $errors[] = "Line {$line}: tracking_number or order_increment_id is required.";

                continue;
            }

            $remittedAmount = $this->parseMoney($row['remitted_amount'] ?? null);

            if ($remittedAmount === null || $remittedAmount < 0) {
                $errors[] = "Line {$line}: remitted_amount must be a valid number greater than or equal to zero.";

                continue;
            }

            $adjustmentValue = $row['adjustment_amount'] ?? null;
            $adjustmentAmount = trim((string) $adjustmentValue) === ''
                ? 0.0
                : $this->parseMoney($adjustmentValue);

            if ($adjustmentAmount === null) {
                $errors[] = "Line {$line}: adjustment_amount must be a valid numeric value when provided.";

                continue;
            }

            $query = CodSettlement::query()
                ->with(['shipmentRecord', 'order'])
                ->whereDoesntHave('batchItem')
                ->when(
                    $shipmentCarrierId,
                    fn ($builder) => $builder->where('shipment_carrier_id', $shipmentCarrierId),
                );

            if ($trackingNumber !== '') {
                $query->whereHas('shipmentRecord', fn ($builder) => $builder->where('tracking_number', $trackingNumber));
            }

            if ($orderIncrementId !== '') {
                $query->whereHas('order', fn ($builder) => $builder->where('increment_id', $orderIncrementId));
            }

            $matches = $query->get();

            if ($matches->isEmpty()) {
                $errors[] = "Line {$line}: no eligible COD settlement matched the provided identifier(s).";

                continue;
            }

            if ($matches->count() > 1) {
                $errors[] = "Line {$line}: multiple COD settlements matched; provide both tracking_number and order_increment_id for exact matching.";

                continue;
            }

            $settlement = $matches->first();

            if (isset($seenSettlementIds[$settlement->id])) {
                $errors[] = "Line {$line}: duplicate settlement match detected in CSV for settlement #{$settlement->id}.";

                continue;
            }

            $settlementIds[] = $settlement->id;
            $remittedAmounts[$settlement->id] = $remittedAmount;
            $adjustmentAmounts[$settlement->id] = $adjustmentAmount;

            $itemNote = trim((string) ($row['item_note'] ?? $row['note'] ?? ''));

            if ($itemNote !== '') {
                $itemNotes[$settlement->id] = $itemNote;
            }

            $seenSettlementIds[$settlement->id] = true;
        }

        return [
            'errors' => $errors,
            'settlement_ids' => $settlementIds,
            'remitted_amounts' => $remittedAmounts,
            'adjustment_amounts' => $adjustmentAmounts,
            'item_notes' => $itemNotes,
        ];
    }

    protected function parseMoney(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized) ?? '';

        if ($normalized === '' || $normalized === '-' || $normalized === '.') {
            return null;
        }

        if (! is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 2);
    }

    protected function normalizeHeader(mixed $value): string
    {
        return str((string) $value)
            ->trim()
            ->lower()
            ->replace([' ', '-'], '_')
            ->value();
    }

    protected function formatImportErrors(array $errors): string
    {
        $max = 8;
        $visible = array_slice($errors, 0, $max);
        $message = implode(' ', $visible);

        if (count($errors) > $max) {
            $message .= ' Additional row errors were found. Fix the CSV and retry.';
        }

        return $message;
    }
}
