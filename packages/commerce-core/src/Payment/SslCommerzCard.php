<?php

namespace Platform\CommerceCore\Payment;

class SslCommerzCard extends AbstractSslCommerzPayment
{
    protected $code = 'sslcommerz_card';

    protected array $gatewayTypes = [
        'visa',
        'master',
        'amex',
        'othercards',
        'internetbanking',
    ];
}
