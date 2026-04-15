<?php

use Platform\CommerceCore\Payment\Bkash;
use Platform\CommerceCore\Payment\SslCommerz;

return [
    'sslcommerz' => [
        'class' => SslCommerz::class,
        'code' => 'sslcommerz',
        'title' => 'Pay Online',
        'description' => 'Pay online with cards, mobile banking, and internet banking via SSLCommerz',
        'active' => false,
        'sort' => 3,
    ],

    'bkash' => [
        'class' => Bkash::class,
        'code' => 'bkash',
        'title' => 'bKash',
        'description' => 'Pay directly with bKash',
        'active' => false,
        'sort' => 2,
    ],
];
