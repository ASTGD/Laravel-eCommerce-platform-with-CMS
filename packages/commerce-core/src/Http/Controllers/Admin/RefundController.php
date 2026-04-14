<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use Illuminate\Http\Response;

class RefundController extends \Webkul\Admin\Http\Controllers\Sales\RefundController
{
    public function store(int $orderId)
    {
        try {
            return parent::store($orderId);
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', $e->getMessage());

            return redirect()->back();
        }
    }
}
