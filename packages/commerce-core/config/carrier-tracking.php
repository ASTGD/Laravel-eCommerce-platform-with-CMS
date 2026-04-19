<?php

use Platform\CommerceCore\ShipmentTracking\Providers\ManualCarrierTrackingProvider;
use Platform\CommerceCore\ShipmentTracking\Providers\PlaceholderApiCarrierTrackingProvider;

return [
    'drivers' => [
        'manual' => [
            'label' => 'Manual',
            'provider' => ManualCarrierTrackingProvider::class,
        ],
        'steadfast' => [
            'label' => 'Steadfast',
            'provider' => PlaceholderApiCarrierTrackingProvider::class,
        ],
        'pathao' => [
            'label' => 'Pathao',
            'provider' => PlaceholderApiCarrierTrackingProvider::class,
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
