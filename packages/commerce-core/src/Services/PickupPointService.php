<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Platform\CommerceCore\Models\PickupPoint;
use Platform\CommerceCore\Repositories\PickupPointRepository;
use Webkul\Core\Models\Address;

class PickupPointService
{
    public const SHIPPING_METHOD_CODE = 'courier_pickup';

    public function __construct(protected PickupPointRepository $pickupPointRepository) {}

    public function checkoutOptions(): Collection
    {
        return $this->pickupPointRepository
            ->scopeQuery(fn ($query) => $query->active()->orderBy('sort_order')->orderBy('name'))
            ->all()
            ->map(fn (PickupPoint $pickupPoint) => array_merge(
                [
                    'id' => $pickupPoint->id,
                ],
                $this->snapshot($pickupPoint),
            ));
    }

    public function findActive(int|string $pickupPointId): ?PickupPoint
    {
        return $this->pickupPointRepository
            ->scopeQuery(fn ($query) => $query->active())
            ->find($pickupPointId);
    }

    public function requireActive(int|string|null $pickupPointId): PickupPoint
    {
        $pickupPoint = $pickupPointId ? $this->findActive($pickupPointId) : null;

        if (! $pickupPoint) {
            throw ValidationException::withMessages([
                'pickup_point_id' => 'Select an active courier pick-up point before continuing.',
            ]);
        }

        return $pickupPoint;
    }

    public function assignToAddress(Address $address, PickupPoint $pickupPoint): void
    {
        $additional = $this->normalizeAdditional($address->additional);

        $additional['pickup_point_id'] = $pickupPoint->id;
        $additional['pickup_point'] = $this->snapshot($pickupPoint);

        $address->forceFill([
            'pickup_point_id' => $pickupPoint->id,
            'additional'      => $additional,
        ])->save();
    }

    public function clearFromAddress(?Address $address): void
    {
        if (! $address) {
            return;
        }

        $additional = $this->normalizeAdditional($address->additional);

        unset($additional['pickup_point_id'], $additional['pickup_point']);

        $address->forceFill([
            'pickup_point_id' => null,
            'additional'      => $additional,
        ])->save();
    }

    public function hasSelection(?Address $address): bool
    {
        if (! $address) {
            return false;
        }

        $additional = $this->normalizeAdditional($address->additional);

        return (bool) ($address->pickup_point_id ?: Arr::get($additional, 'pickup_point_id'));
    }

    public function assertValidSelection(?Address $address): void
    {
        if (! $address || ! $this->hasSelection($address)) {
            throw new \Exception('Select a courier pick-up point before placing the order.');
        }

        $pickupPointId = $address->pickup_point_id ?: Arr::get($this->normalizeAdditional($address->additional), 'pickup_point_id');

        if (! $pickupPointId || ! $this->findActive($pickupPointId)) {
            throw new \Exception('The selected courier pick-up point is no longer available. Please choose another point.');
        }
    }

    public function snapshot(PickupPoint $pickupPoint): array
    {
        return [
            'code'          => $pickupPoint->code,
            'name'          => $pickupPoint->name,
            'slug'          => $pickupPoint->slug,
            'courier_name'  => $pickupPoint->courier_name,
            'phone'         => $pickupPoint->phone,
            'email'         => $pickupPoint->email,
            'address_line_1'=> $pickupPoint->address_line_1,
            'address_line_2'=> $pickupPoint->address_line_2,
            'city'          => $pickupPoint->city,
            'state'         => $pickupPoint->state,
            'postcode'      => $pickupPoint->postcode,
            'country'       => $pickupPoint->country,
            'country_name'  => core()->country_name($pickupPoint->country),
            'landmark'      => $pickupPoint->landmark,
            'opening_hours' => $pickupPoint->opening_hours,
            'notes'         => $pickupPoint->notes,
        ];
    }

    public function isPickupMethod(?string $shippingMethod): bool
    {
        return $shippingMethod === self::SHIPPING_METHOD_CODE;
    }

    public function formatSummary(?array $snapshot): ?string
    {
        if (! $snapshot) {
            return null;
        }

        return collect([
            $snapshot['courier_name'] ?? null,
            $snapshot['name'] ?? null,
            $snapshot['address_line_1'] ?? null,
            $snapshot['city'] ?? null,
        ])->filter()->implode(', ');
    }

    public function normalizeAdditional(mixed $additional): array
    {
        if (is_array($additional)) {
            return $additional;
        }

        if ($additional instanceof Collection) {
            return $additional->toArray();
        }

        if (is_string($additional) && $additional !== '') {
            $decoded = json_decode($additional, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    public function isInUse(PickupPoint|int $pickupPoint): bool
    {
        $pickupPointId = $pickupPoint instanceof PickupPoint ? $pickupPoint->id : $pickupPoint;

        return DB::table('addresses')->where('pickup_point_id', $pickupPointId)->exists();
    }
}
