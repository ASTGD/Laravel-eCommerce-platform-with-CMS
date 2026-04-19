<?php

namespace Platform\CommerceCore\Repositories;

use Webkul\Core\Eloquent\Repository;

class ShipmentRecordRepository extends Repository
{
    public function model(): string
    {
        return \Platform\CommerceCore\Models\ShipmentRecord::class;
    }
}
