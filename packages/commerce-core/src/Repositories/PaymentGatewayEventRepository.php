<?php

namespace Platform\CommerceCore\Repositories;

use Webkul\Core\Eloquent\Repository;

class PaymentGatewayEventRepository extends Repository
{
    public function model(): string
    {
        return \Platform\CommerceCore\Models\PaymentGatewayEvent::class;
    }
}
