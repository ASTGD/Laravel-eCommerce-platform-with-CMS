<?php

namespace Platform\CommerceCore\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Platform\CommerceCore\Support\CheckoutMode;

class CheckoutController
{
    public function __construct(protected CheckoutMode $checkoutMode) {}

    public function index(Request $request): RedirectResponse
    {
        return redirect()->to($this->checkoutMode->entryUrl($request->query()));
    }

    public function success(Request $request): RedirectResponse
    {
        return redirect()->to($this->checkoutMode->successUrl($request->only('order')));
    }
}
