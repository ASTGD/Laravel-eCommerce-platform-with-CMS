<?php

namespace Platform\CommerceCore\ShipmentBooking\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Platform\CommerceCore\Contracts\CarrierBookingProvider;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\ShipmentBooking\CarrierBookingResult;
use Platform\CommerceCore\ShipmentCarriers\SteadfastApiSupport;
use Throwable;

class SteadfastCarrierBookingProvider implements CarrierBookingProvider
{
    protected const CONSIGNMENT_ID_PATHS = [
        'consignment.consignment_id',
        'consignment_id',
        'data.consignment_id',
    ];

    protected const INVOICE_PATHS = [
        'consignment.invoice',
        'invoice',
        'data.invoice',
    ];

    protected const TRACKING_NUMBER_PATHS = [
        'consignment.tracking_code',
        'tracking_code',
        'data.tracking_code',
    ];

    protected const BOOKED_AT_PATHS = [
        'consignment.created_at',
        'created_at',
        'data.created_at',
    ];

    public function __construct(
        protected SteadfastApiSupport $apiSupport,
        protected string $driver = 'steadfast',
        protected string $label = 'Steadfast',
    ) {}

    public function driver(): string
    {
        return $this->driver;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function createBooking(ShipmentCarrier $carrier, ShipmentRecord $shipmentRecord, array $context = []): CarrierBookingResult
    {
        $endpoint = $this->apiSupport->resolveBookingEndpoint($carrier);

        if (! $endpoint) {
            return CarrierBookingResult::failed(sprintf(
                '%s booking requires a carrier API base URL.',
                $this->label,
            ));
        }

        $payload = $this->payload($shipmentRecord, $context);

        try {
            $response = Http::acceptJson()
                ->timeout(20)
                ->withHeaders($this->apiSupport->headers($carrier))
                ->post($endpoint, $payload);
        } catch (Throwable $exception) {
            return CarrierBookingResult::failed(sprintf(
                '%s booking request failed: %s',
                $this->label,
                $exception->getMessage(),
            ));
        }

        if (! $response->successful()) {
            return CarrierBookingResult::failed(sprintf(
                '%s booking request failed with HTTP %s.',
                $this->label,
                $response->status(),
            ));
        }

        $responsePayload = $response->json();

        if (! is_array($responsePayload)) {
            return CarrierBookingResult::failed(sprintf(
                '%s booking response did not return a JSON object.',
                $this->label,
            ));
        }

        $consignmentId = $this->extractFirstValue($responsePayload, self::CONSIGNMENT_ID_PATHS);
        $invoiceReference = $this->extractFirstValue($responsePayload, self::INVOICE_PATHS) ?: $payload['invoice'];
        $trackingNumber = $this->extractFirstValue($responsePayload, self::TRACKING_NUMBER_PATHS);
        $bookedAt = $this->parseBookedAt($this->extractFirstValue($responsePayload, self::BOOKED_AT_PATHS));
        $message = trim((string) data_get($responsePayload, 'message', ''));

        if (! $consignmentId && ! $trackingNumber) {
            return CarrierBookingResult::failed(sprintf(
                '%s booking response did not include a consignment id or tracking code.%s',
                $this->label,
                $message !== '' ? ' '.$message : '',
            ));
        }

        return CarrierBookingResult::booked(
            message: sprintf(
                '%s booking created successfully for invoice "%s".',
                $this->label,
                $invoiceReference,
            ),
            bookingReference: null,
            consignmentId: $consignmentId,
            invoiceReference: $invoiceReference,
            trackingNumber: $trackingNumber,
            bookedAt: $bookedAt ?? now(),
            meta: [
                'external_message' => $message,
                'request_payload' => $payload,
                'response_payload' => $responsePayload,
            ],
        );
    }

    protected function payload(ShipmentRecord $shipmentRecord, array $context = []): array
    {
        $invoice = trim((string) ($shipmentRecord->carrier_invoice_reference ?: $this->defaultInvoice($shipmentRecord)));
        $recipientAddress = $this->recipientAddress($shipmentRecord);
        $note = trim((string) ($context['note'] ?? ''));

        return array_filter([
            'invoice' => $invoice,
            'recipient_name' => trim((string) $shipmentRecord->recipient_name),
            'recipient_phone' => trim((string) $shipmentRecord->recipient_phone),
            'recipient_address' => $recipientAddress,
            'cod_amount' => (float) ($shipmentRecord->cod_amount_expected ?? 0),
            'note' => $note !== '' ? $note : null,
        ], static fn ($value) => ! is_null($value) && $value !== '');
    }

    protected function recipientAddress(ShipmentRecord $shipmentRecord): string
    {
        $parts = [
            trim((string) $shipmentRecord->recipient_address),
            trim((string) $shipmentRecord->destination_city),
            trim((string) $shipmentRecord->destination_region),
            trim((string) $shipmentRecord->destination_country),
        ];

        $parts = array_values(array_filter($parts, static fn (string $part) => $part !== ''));

        return implode(', ', array_unique($parts));
    }

    protected function defaultInvoice(ShipmentRecord $shipmentRecord): string
    {
        $orderIncrementId = trim((string) $shipmentRecord->order?->increment_id);
        $shipmentReference = $shipmentRecord->native_shipment_id ?: $shipmentRecord->id;

        return sprintf(
            '%s-S%s',
            $orderIncrementId !== '' ? $orderIncrementId : 'ORDER-'.$shipmentRecord->order_id,
            $shipmentReference,
        );
    }

    protected function extractFirstValue(array $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (is_string($value) || is_numeric($value)) {
                $value = trim((string) $value);

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    protected function parseBookedAt(?string $value): ?CarbonImmutable
    {
        if (! $value) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
