<?php

namespace Platform\CommerceCore\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Platform\CommerceCore\Services\SettlementBatchImportService;

class ImportCodSettlementsCommand extends Command
{
    protected $signature = 'platform:cod-settlements:import
        {file : CSV file path}
        {reference : Settlement batch reference}
        {--carrier-id= : Restrict rows to a specific shipment carrier id}
        {--payout-method= : Optional payout method for the imported batch}
        {--status=reconciled : Settlement batch status}
        {--notes= : Optional batch-level note}
        {--admin-id= : Admin id used in audit columns}';

    protected $description = 'Import courier remittance CSV rows into one settlement batch and auto-sync linked COD settlements.';

    public function __construct(protected SettlementBatchImportService $importService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $resolvedFilePath = $this->resolveFilePath((string) $this->argument('file'));

        if (! $resolvedFilePath) {
            $this->components->error('CSV file path does not exist.');

            return self::FAILURE;
        }

        try {
            $result = $this->importService->importFromCsvPath($resolvedFilePath, [
                'reference' => (string) $this->argument('reference'),
                'shipment_carrier_id' => $this->option('carrier-id') !== null
                    ? (int) $this->option('carrier-id')
                    : null,
                'payout_method' => $this->option('payout-method') ?: null,
                'status' => (string) $this->option('status'),
                'notes' => $this->option('notes') ?: null,
            ], $this->option('admin-id') !== null ? (int) $this->option('admin-id') : null);
        } catch (ValidationException $exception) {
            $this->components->error('COD settlement import failed.');

            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->line('- '.$message);
                }
            }

            return self::FAILURE;
        }

        $batch = $result['batch'];

        $this->components->info('COD settlement import completed.');
        $this->line('Batch ID: '.$batch->id);
        $this->line('Batch Reference: '.$batch->reference);
        $this->line('Rows Read: '.$result['rows_total']);
        $this->line('Rows Imported: '.$result['rows_imported']);

        return self::SUCCESS;
    }

    protected function resolveFilePath(string $inputPath): ?string
    {
        if (is_file($inputPath)) {
            return $inputPath;
        }

        $relativeToBase = base_path($inputPath);

        if (is_file($relativeToBase)) {
            return $relativeToBase;
        }

        return null;
    }
}
