<?php

use Webkul\Payment\Payment\CashOnDelivery;
use Webkul\Payment\Payment\MoneyTransfer;
use Platform\CommerceCore\Payment\Bkash;
use Platform\CommerceCore\Payment\SslCommerz;

return [
    'cashondelivery' => [
        'class' => CashOnDelivery::class,
        'code' => 'cashondelivery',
        'title' => 'Cash On Delivery',
        'description' => 'Cash On Delivery',
        'active' => true,
        'generate_invoice' => false,
        'sort' => 6,
    ],

    'moneytransfer' => [
        'class' => MoneyTransfer::class,
        'code' => 'moneytransfer',
        'title' => 'Money Transfer',
        'description' => 'Money Transfer',
        'active' => true,
        'generate_invoice' => false,
        'sort' => 7,
    ],

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
