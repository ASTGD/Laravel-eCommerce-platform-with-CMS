<?php

namespace Platform\CommerceCore\Transformers;

use Webkul\Sales\Transformers\OrderAddressResource as BaseOrderAddressResource;

class OrderAddressResource extends BaseOrderAddressResource
{
    public function toArray($request)
    {
        return array_merge(parent::toArray($request), [
            'pickup_point_id' => $this->pickup_point_id,
            'additional'      => $this->additional,
        ]);
    }
}
