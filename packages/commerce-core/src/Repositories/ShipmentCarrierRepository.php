<?php

namespace Platform\CommerceCore\Repositories;

use Webkul\Core\Eloquent\Repository;

class ShipmentCarrierRepository extends Repository
{
    public function model(): string
    {
        return \Platform\CommerceCore\Models\ShipmentCarrier::class;
    }
}
