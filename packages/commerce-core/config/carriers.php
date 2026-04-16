<?php

use Platform\CommerceCore\Shipping\Carriers\Courier;

return [
    'courier' => [
        'code' => 'courier',
        'title' => 'Courier',
        'description' => 'District-based delivery charges',
        'active' => true,
        'class' => Courier::class,
    ],
];
