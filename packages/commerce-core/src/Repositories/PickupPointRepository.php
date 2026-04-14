<?php

namespace Platform\CommerceCore\Repositories;

use Webkul\Core\Eloquent\Repository;

class PickupPointRepository extends Repository
{
    public function model(): string
    {
        return \Platform\CommerceCore\Models\PickupPoint::class;
    }
}
