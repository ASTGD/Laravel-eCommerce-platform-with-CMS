<?php

namespace Platform\CommerceCore\Repositories;

use Webkul\Core\Eloquent\Repository;

class PaymentRefundRepository extends Repository
{
    public function model(): string
    {
        return \Platform\CommerceCore\Models\PaymentRefund::class;
    }
}
