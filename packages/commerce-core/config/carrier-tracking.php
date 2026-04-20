<?php

use Platform\CommerceCore\ShipmentTracking\Providers\ManualCarrierTrackingProvider;
use Platform\CommerceCore\ShipmentTracking\Providers\PathaoCarrierTrackingProvider;
use Platform\CommerceCore\ShipmentTracking\Providers\PlaceholderApiCarrierTrackingProvider;
use Platform\CommerceCore\ShipmentTracking\Providers\SteadfastCarrierTrackingProvider;

return [
    'drivers' => [
        'manual' => [
            'label' => 'Manual',
            'provider' => ManualCarrierTrackingProvider::class,
        ],
        'steadfast' => [
            'label' => 'Steadfast',
            'provider' => SteadfastCarrierTrackingProvider::class,
        ],
        'pathao' => [
            'label' => 'Pathao',
            'provider' => PathaoCarrierTrackingProvider::class,
        ],
        'redx' => [
            'label' => 'RedX',
            'provider' => PlaceholderApiCarrierTrackingProvider::class,
        ],
        'paperfly' => [
            'label' => 'Paperfly',
            'provider' => PlaceholderApiCarrierTrackingProvider::class,
        ],
        'custom_api' => [
            'label' => 'Custom API',
            'provider' => PlaceholderApiCarrierTrackingProvider::class,
        ],
    ],
];
