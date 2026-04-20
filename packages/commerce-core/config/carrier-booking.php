<?php

use Platform\CommerceCore\ShipmentBooking\Providers\ManualCarrierBookingProvider;
use Platform\CommerceCore\ShipmentBooking\Providers\PathaoCarrierBookingProvider;
use Platform\CommerceCore\ShipmentBooking\Providers\SteadfastCarrierBookingProvider;

return [
    'drivers' => [
        'manual' => [
            'label' => 'Manual',
            'provider' => ManualCarrierBookingProvider::class,
        ],
        'steadfast' => [
            'label' => 'Steadfast',
            'provider' => SteadfastCarrierBookingProvider::class,
        ],
        'pathao' => [
            'label' => 'Pathao',
            'provider' => PathaoCarrierBookingProvider::class,
        ],
    ],
];
