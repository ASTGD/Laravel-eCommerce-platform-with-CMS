<?php

namespace Platform\CommerceCore\Support;

use Illuminate\Contracts\Container\Container;
use Platform\CommerceCore\Contracts\CarrierTrackingProvider;
use Platform\CommerceCore\Models\ShipmentCarrier;

class CarrierTrackingProviderRegistry
{
    public function __construct(protected Container $container) {}

    public function driverLabels(): array
    {
        return collect(config('carrier_tracking.drivers', []))
            ->mapWithKeys(fn (array $config, string $driver) => [$driver => $config['label'] ?? str($driver)->replace('_', ' ')->title()->value()])
            ->all();
    }

    public function forCarrier(ShipmentCarrier $carrier): CarrierTrackingProvider
    {
        $driver = $carrier->trackingDriver();
        $manualConfig = config('carrier_tracking.drivers.manual', []);
        $config = config("carrier_tracking.drivers.{$driver}") ?? $manualConfig;
        $providerClass = $config['provider'] ?? $manualConfig['provider'];
        $label = $config['label'] ?? str($driver)->replace('_', ' ')->title()->value();

        return $this->container->makeWith($providerClass, [
            'driver' => $driver,
            'label' => $label,
        ]);
    }
}
