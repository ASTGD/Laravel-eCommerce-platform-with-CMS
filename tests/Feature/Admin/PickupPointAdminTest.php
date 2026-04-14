<?php

use Platform\CommerceCore\Models\PickupPoint;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Customer\Models\Customer;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(AdminTestCase::class);

it('shows the pickup point admin index', function () {
    $this->loginAsAdmin();

    get(route('admin.sales.pickup-points.index'))
        ->assertOk()
        ->assertSeeText('Pickup Points')
        ->assertSeeText('Add Pickup Point');
});

it('creates and updates a pickup point from admin', function () {
    $this->loginAsAdmin();

    post(route('admin.sales.pickup-points.store'), [
        'code' => 'dhaka-banani',
        'name' => 'Banani Hub',
        'courier_name' => 'ASTGD Courier',
        'address_line_1' => 'Road 11, Banani',
        'city' => 'Dhaka',
        'country' => 'BD',
        'sort_order' => 10,
        'is_active' => 1,
    ])->assertRedirect();

    $pickupPoint = PickupPoint::query()->where('code', 'dhaka-banani')->firstOrFail();

    expect($pickupPoint->name)->toBe('Banani Hub');

    put(route('admin.sales.pickup-points.update', $pickupPoint), [
        'code' => 'dhaka-banani',
        'name' => 'Banani Branch',
        'slug' => 'banani-branch',
        'courier_name' => 'ASTGD Courier',
        'address_line_1' => 'Road 11, Banani',
        'city' => 'Dhaka',
        'country' => 'BD',
        'sort_order' => 5,
        'is_active' => 1,
    ])->assertRedirect(route('admin.sales.pickup-points.edit', $pickupPoint));

    expect($pickupPoint->fresh()->name)->toBe('Banani Branch');
});

it('blocks deleting a pickup point that is already used by checkout addresses', function () {
    $pickupPoint = PickupPoint::query()->create([
        'code' => 'uttara-hub',
        'name' => 'Uttara Hub',
        'slug' => 'uttara-hub',
        'courier_name' => 'ASTGD Courier',
        'address_line_1' => 'Sector 7',
        'city' => 'Dhaka',
        'country' => 'BD',
        'is_active' => true,
    ]);

    $customer = Customer::factory()->create();

    $cart = Cart::factory()->create([
        'customer_id' => $customer->id,
        'customer_first_name' => $customer->first_name,
        'customer_last_name' => $customer->last_name,
        'customer_email' => $customer->email,
        'is_guest' => 0,
    ]);

    CartAddress::factory()->create([
        'cart_id' => $cart->id,
        'customer_id' => $customer->id,
        'address_type' => CartAddress::ADDRESS_TYPE_SHIPPING,
        'pickup_point_id' => $pickupPoint->id,
        'additional' => [
            'pickup_point_id' => $pickupPoint->id,
            'pickup_point' => [
                'name' => $pickupPoint->name,
            ],
        ],
    ]);

    $this->loginAsAdmin();

    delete(route('admin.sales.pickup-points.destroy', $pickupPoint))
        ->assertRedirect(route('admin.sales.pickup-points.index'));

    expect($pickupPoint->fresh())->not->toBeNull();
});
