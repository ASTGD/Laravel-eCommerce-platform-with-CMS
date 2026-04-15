<?php

use Platform\CommerceCore\Payment\Bkash;
use Platform\CommerceCore\Payment\SslCommerzBkash;
use Platform\CommerceCore\Payment\SslCommerzCard;
use Platform\CommerceCore\Payment\SslCommerzNagad;

return [
    'sslcommerz_card' => [
        'class' => SslCommerzCard::class,
        'code' => 'sslcommerz_card',
        'title' => 'Bank Card',
        'description' => 'Pay securely with Bangladeshi cards and online banking',
        'active' => false,
        'sort' => 1,
    ],

    'sslcommerz_bkash' => [
        'class' => SslCommerzBkash::class,
        'code' => 'sslcommerz_bkash',
        'title' => 'bKash',
        'description' => 'Pay with bKash via SSLCOMMERZ',
        'active' => false,
        'sort' => 2,
    ],

    'sslcommerz_nagad' => [
        'class' => SslCommerzNagad::class,
        'code' => 'sslcommerz_nagad',
        'title' => 'Nagad',
        'description' => 'Pay with Nagad via SSLCOMMERZ',
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
