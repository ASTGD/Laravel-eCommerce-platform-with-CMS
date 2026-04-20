<?php

namespace Platform\CommerceCore\Support;

use Illuminate\Contracts\Container\Container;
use Platform\CommerceCore\ShipmentBooking\Providers\ManualCarrierBookingProvider;
use Platform\CommerceCore\Contracts\CarrierBookingProvider;
use Platform\CommerceCore\Models\ShipmentCarrier;

class CarrierBookingProviderRegistry
{
    public function __construct(protected Container $container) {}

    public function supportsCarrier(ShipmentCarrier $carrier): bool
    {
        $driver = $carrier->trackingDriver();
        $config = config("carrier_booking.drivers.{$driver}");

        if (! $config) {
            return false;
        }

        return ($config['provider'] ?? null) !== ManualCarrierBookingProvider::class;
    }

    public function forCarrier(ShipmentCarrier $carrier): CarrierBookingProvider
    {
        $driver = $carrier->trackingDriver();
        $manualConfig = config('carrier_booking.drivers.manual', []);
        $config = config("carrier_booking.drivers.{$driver}") ?? $manualConfig;
        $providerClass = $config['provider'] ?? $manualConfig['provider'];
        $label = $config['label'] ?? str($driver)->replace('_', ' ')->title()->value();

        return $this->container->makeWith($providerClass, [
            'driver' => $driver,
            'label' => $label,
        ]);
    }
}
