<?php

namespace Platform\CommerceCore\ShipmentTracking;

use Platform\CommerceCore\Models\ShipmentRecord;

class SteadfastTrackingStatusMapper
{
    /**
     * Steadfast's public statuses are coarse. We intentionally map early states
     * conservatively so we do not overstate delivery progress locally.
     */
    protected const STATUS_MAP = [
        'created' => ShipmentRecord::STATUS_READY_FOR_PICKUP,
        'booked' => ShipmentRecord::STATUS_READY_FOR_PICKUP,
        'ready_for_pickup' => ShipmentRecord::STATUS_READY_FOR_PICKUP,
        'in_review' => ShipmentRecord::STATUS_READY_FOR_PICKUP,
        'picked_up' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'picked' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'handed_to_carrier' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'received_by_carrier' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'pending' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'hold' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'in_transit' => ShipmentRecord::STATUS_IN_TRANSIT,
        'transit' => ShipmentRecord::STATUS_IN_TRANSIT,
        'at_hub' => ShipmentRecord::STATUS_IN_TRANSIT,
        'arrived_at_hub' => ShipmentRecord::STATUS_IN_TRANSIT,
        'on_the_way' => ShipmentRecord::STATUS_IN_TRANSIT,
        'out_for_delivery' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'delivery_ongoing' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'on_delivery' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'partial_delivered_approval_pending' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'partial_delivered' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'delivered_approval_pending' => ShipmentRecord::STATUS_DELIVERED,
        'delivered' => ShipmentRecord::STATUS_DELIVERED,
        'delivery_failed' => ShipmentRecord::STATUS_DELIVERY_FAILED,
        'failed' => ShipmentRecord::STATUS_DELIVERY_FAILED,
        'undelivered' => ShipmentRecord::STATUS_DELIVERY_FAILED,
        'attempted' => ShipmentRecord::STATUS_DELIVERY_FAILED,
        'returned' => ShipmentRecord::STATUS_RETURNED,
        'return_completed' => ShipmentRecord::STATUS_RETURNED,
        'rto' => ShipmentRecord::STATUS_RETURNED,
        'returned_to_origin' => ShipmentRecord::STATUS_RETURNED,
        'cancelled_approval_pending' => ShipmentRecord::STATUS_CANCELED,
        'cancelled' => ShipmentRecord::STATUS_CANCELED,
        'canceled' => ShipmentRecord::STATUS_CANCELED,
    ];

    protected const STATUS_RANKS = [
        ShipmentRecord::STATUS_DRAFT => 0,
        ShipmentRecord::STATUS_READY_FOR_PICKUP => 1,
        ShipmentRecord::STATUS_HANDED_TO_CARRIER => 2,
        ShipmentRecord::STATUS_IN_TRANSIT => 3,
        ShipmentRecord::STATUS_OUT_FOR_DELIVERY => 4,
        ShipmentRecord::STATUS_DELIVERED => 5,
        ShipmentRecord::STATUS_DELIVERY_FAILED => 5,
        ShipmentRecord::STATUS_RETURNED => 5,
        ShipmentRecord::STATUS_CANCELED => 5,
    ];

    public function map(?string $externalStatus): ?string
    {
        $normalized = $this->normalize($externalStatus);

        if (! $normalized) {
            return null;
        }

        return self::STATUS_MAP[$normalized] ?? null;
    }

    public function normalize(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($status));

        if ($normalized === '') {
            return null;
        }

        $normalized = preg_replace('/[^a-z0-9]+/u', '_', $normalized) ?: $normalized;

        return trim($normalized, '_') ?: null;
    }

    public function wouldDowngrade(string $currentStatus, string $mappedStatus): bool
    {
        return (self::STATUS_RANKS[$mappedStatus] ?? -1) < (self::STATUS_RANKS[$currentStatus] ?? -1);
    }
}
