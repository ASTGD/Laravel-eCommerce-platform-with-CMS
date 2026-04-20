<?php

namespace Platform\CommerceCore\Services;

use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\ShipmentBooking\CarrierBookingResult;
use Platform\CommerceCore\Support\CarrierBookingProviderRegistry;

class CarrierBookingService
{
    public function __construct(
        protected CarrierBookingProviderRegistry $providerRegistry,
        protected ShipmentRecordService $shipmentRecordService,
    ) {}

    public function bookShipmentRecord(ShipmentRecord $shipmentRecord, array $context = []): CarrierBookingResult
    {
        $shipmentRecord->loadMissing(['carrier', 'nativeShipment', 'order']);

        if (! $shipmentRecord->carrier) {
            return CarrierBookingResult::failed('Shipment carrier is missing.');
        }

        if ($shipmentRecord->carrier_consignment_id || $shipmentRecord->carrier_booking_reference) {
            return CarrierBookingResult::skipped('Carrier booking already exists for this shipment.');
        }

        $result = $this->providerRegistry
            ->forCarrier($shipmentRecord->carrier)
            ->createBooking($shipmentRecord->carrier, $shipmentRecord, $context);

        if (! $result->successful()) {
            return $result;
        }

        $this->shipmentRecordService->captureCarrierBooking(
            $shipmentRecord,
            [
                'carrier_booking_reference' => $result->bookingReference,
                'carrier_consignment_id' => $result->consignmentId,
                'carrier_invoice_reference' => $result->invoiceReference,
                'carrier_booked_at' => $result->bookedAt,
                'tracking_number' => $result->trackingNumber,
            ],
            $result->message,
            $context['actor_admin_id'] ?? null,
            $result->meta,
        );

        return $result;
    }
}
