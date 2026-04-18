<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Webkul\Checkout\Models\Cart;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerGroup;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\Customer\Repositories\CustomerRepository;

class CheckoutGuestAccountService
{
    protected const SESSION_KEY = 'checkout.guest_account';

    public function __construct(
        protected CustomerRepository $customerRepository,
        protected CustomerAddressRepository $customerAddressRepository,
    ) {}

    public function storeIntent(array $billing, bool $createAccount): void
    {
        if (! $createAccount) {
            $this->clearIntent();

            return;
        }

        session()->put(self::SESSION_KEY, [
            'password' => Hash::make((string) ($billing['password'] ?? '')),
            'email'    => (string) ($billing['email'] ?? ''),
        ]);
    }

    public function clearIntent(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function createCustomerFromIntent(Cart $cart): ?Customer
    {
        if (Auth::guard('customer')->check()) {
            $this->clearIntent();

            return Auth::guard('customer')->user();
        }

        $intent = session(self::SESSION_KEY);
        $billingAddress = $cart->billing_address;

        if (
            empty($intent['password'])
            || ! $billingAddress
            || blank($billingAddress->email)
        ) {
            return null;
        }

        $existingCustomer = Customer::query()
            ->where('email', $billingAddress->email)
            ->where('channel_id', core()->getCurrentChannel()->id)
            ->first();

        if ($existingCustomer) {
            throw ValidationException::withMessages([
                'billing.email' => 'This email is already registered. Please sign in instead.',
            ]);
        }

        $existingCustomerByPhone = Customer::query()
            ->where('phone', $billingAddress->phone)
            ->first();

        if ($existingCustomerByPhone) {
            throw ValidationException::withMessages([
                'billing.phone' => 'This phone number is already registered. Please sign in instead.',
            ]);
        }

        $customerGroupId = $this->resolveCustomerGroupId();

        Event::dispatch('customer.registration.before');

        $customer = $this->customerRepository->create([
            'first_name'               => $billingAddress->first_name,
            'last_name'                => $billingAddress->last_name,
            'email'                    => $billingAddress->email,
            'phone'                    => $billingAddress->phone,
            'password'                 => $intent['password'],
            'api_token'                => Str::random(80),
            'customer_group_id'        => $customerGroupId,
            'channel_id'               => core()->getCurrentChannel()->id,
            'token'                    => md5(uniqid(rand(), true)),
            'status'                   => 1,
            'is_verified'              => 1,
            'is_suspended'             => 0,
            'subscribed_to_news_letter'=> false,
        ]);

        Event::dispatch('customer.create.after', $customer);
        Event::dispatch('customer.registration.after', $customer);

        Auth::guard('customer')->login($customer);

        $this->attachCustomerToCart($cart, $customer);
        $this->createCustomerAddresses($cart, $customer);
        $this->customerRepository->syncNewRegisteredCustomerInformation($customer);

        session()->flash('checkout_account_created', [
            'email' => $customer->email,
        ]);

        $this->clearIntent();

        return $customer;
    }

    public function attachMatchingExistingCustomerToCart(Cart $cart): ?Customer
    {
        if (Auth::guard('customer')->check() || session()->has(self::SESSION_KEY)) {
            return null;
        }

        $billingAddress = $cart->billing_address;

        if (
            ! $billingAddress
            || blank($billingAddress->email)
            || blank($billingAddress->phone)
        ) {
            return null;
        }

        $customer = Customer::query()
            ->where('channel_id', core()->getCurrentChannel()->id)
            ->where('email', $billingAddress->email)
            ->first();

        if (! $customer) {
            $customer = Customer::query()
                ->where('channel_id', core()->getCurrentChannel()->id)
                ->where('phone', $billingAddress->phone)
                ->first();
        }

        if (! $customer) {
            return null;
        }

        $this->attachCustomerToCart($cart, $customer);

        return $customer;
    }

    protected function resolveCustomerGroupId(): int
    {
        $defaultGroupCode = (string) (core()->getConfigData('customer.settings.create_new_account_options.default_group') ?: 'general');

        if ($defaultGroupCode === 'guest') {
            $defaultGroupCode = 'general';
        }

        $customerGroup = CustomerGroup::query()
            ->where('code', $defaultGroupCode)
            ->first()
            ?: CustomerGroup::query()->where('code', 'general')->first()
            ?: CustomerGroup::query()->where('code', '!=', 'guest')->orderBy('id')->first()
            ?: CustomerGroup::query()->where('code', 'guest')->first();

        if (! $customerGroup) {
            throw ValidationException::withMessages([
                'billing.email' => 'Customer group configuration is incomplete. Please contact support.',
            ]);
        }

        return (int) $customerGroup->id;
    }

    protected function attachCustomerToCart(Cart $cart, Customer $customer): void
    {
        $cart->customer_id = $customer->id;
        $cart->is_guest = 0;
        $cart->customer_first_name = $customer->first_name;
        $cart->customer_last_name = $customer->last_name;
        $cart->customer_email = $customer->email;
        $cart->save();

        if ($cart->billing_address) {
            $cart->billing_address->update(['customer_id' => $customer->id]);
        }

        if ($cart->shipping_address) {
            $cart->shipping_address->update(['customer_id' => $customer->id]);
        }
    }

    protected function createCustomerAddresses(Cart $cart, Customer $customer): void
    {
        if (! $cart->billing_address) {
            return;
        }

        if (! $customer->addresses()->exists()) {
            $this->customerAddressRepository->create([
                'customer_id'      => $customer->id,
                'company_name'     => $cart->billing_address->company_name,
                'first_name'       => $cart->billing_address->first_name,
                'last_name'        => $cart->billing_address->last_name,
                'vat_id'           => $cart->billing_address->vat_id,
                'email'            => $cart->billing_address->email,
                'address'          => $cart->billing_address->address,
                'country'          => $cart->billing_address->country,
                'state'            => $cart->billing_address->state,
                'city'             => $cart->billing_address->city,
                'postcode'         => $cart->billing_address->postcode,
                'phone'            => $cart->billing_address->phone,
                'default_address'  => 1,
            ]);
        }
    }
}
