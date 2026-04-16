<?php

namespace Platform\CommerceCore\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Payment\Facades\Payment;
use Webkul\Shop\Http\Resources\CartResource;

class CheckoutStateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $isCustomerAuthenticated = auth()->guard('customer')->check();

        return [
            'single_flow' => true,

            'cart' => new CartResource($this->resource),

            'checkout' => [
                'allow_guest_checkout' => (bool) core()->getConfigData('sales.checkout.shopping_cart.allow_guest_checkout'),

                'single_screen' => true,
            ],

            'customer' => [
                'is_authenticated' => $isCustomerAuthenticated,

                'draft' => $this->customerDraft(),
            ],

            'form' => [
                'mode' => $isCustomerAuthenticated ? 'customer' : 'guest',

                'single_address' => [
                    'visible_fields' => [
                        ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                        ['name' => 'phone', 'label' => 'Mobile Number', 'type' => 'text', 'required' => true],
                        ['name' => 'country', 'label' => 'Country / Region', 'type' => 'select', 'required' => true],
                        ['name' => 'state', 'label' => 'District / Region', 'type' => 'text', 'required' => true],
                        ['name' => 'address', 'label' => 'Full Address', 'type' => 'textarea', 'required' => true],
                        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                    ],

                    'hidden_fields' => [
                        ['name' => 'city', 'source' => 'state'],
                        ['name' => 'postcode', 'source' => 'state'],
                    ],
                ],

                'guest' => [
                    'show_create_account' => (bool) core()->getConfigData('sales.checkout.shopping_cart.allow_guest_checkout'),

                    'create_account_field' => [
                        'name' => 'create_account',
                        'label' => 'Create an account?',
                        'type' => 'checkbox',
                    ],
                ],

                'customer' => [
                    'draft' => $this->customerDraft(),
                ],
            ],

            'district_shipping' => [
                'carrier' => 'courier',

                'title' => core()->getConfigData('sales.carriers.courier.title') ?: 'Courier',

                'description' => core()->getConfigData('sales.carriers.courier.description'),

                'district_field' => 'state',

                'dhaka_district' => core()->getConfigData('sales.carriers.courier.dhaka_district') ?: 'Dhaka',

                'dhaka_title' => core()->getConfigData('sales.carriers.courier.dhaka_title') ?: 'Dhaka Delivery',

                'dhaka_rate' => (float) core()->getConfigData('sales.carriers.courier.dhaka_rate') ?: 60,

                'outside_dhaka_title' => core()->getConfigData('sales.carriers.courier.outside_dhaka_title') ?: 'Outside Dhaka Delivery',

                'outside_dhaka_rate' => (float) core()->getConfigData('sales.carriers.courier.outside_dhaka_rate') ?: 120,
            ],

            'payment_methods' => Payment::getSupportedPaymentMethods()['payment_methods'] ?? [],
        ];
    }

    /**
     * Build the customer draft used by the checkout form.
     */
    protected function customerDraft(): array
    {
        $customer = auth()->guard('customer')->user();
        $name = trim(implode(' ', array_filter([
            $customer->first_name ?? null,
            $customer->last_name ?? null,
        ])));

        return [
            'id' => 0,
            'company_name' => '',
            'name' => $name,
            'full_name' => $name,
            'first_name' => $customer?->first_name ?? '',
            'last_name' => $customer?->last_name ?? '',
            'email' => $customer?->email ?? '',
            'address' => [''],
            'country' => strtoupper(config('app.default_country') ?? ''),
            'state' => '',
            'city' => '',
            'postcode' => '',
            'phone' => $customer?->phone ?? '',
        ];
    }
}
