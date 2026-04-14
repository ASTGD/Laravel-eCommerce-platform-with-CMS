<?php

use Platform\CommerceCore\Shipping\Carriers\Courier;

return [
    'courier' => [
        'code' => 'courier',
        'title' => 'Courier',
        'description' => 'Courier delivery options',
        'active' => false,
        'class' => Courier::class,
    ],
];
