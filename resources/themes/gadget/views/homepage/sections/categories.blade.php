@php($fallbackCategories = collect([
    ['name' => 'Earphone', 'url' => route('shop.search.index', ['query' => 'earphone']), 'image' => null],
    ['name' => 'Smartwatch', 'url' => route('shop.search.index', ['query' => 'smartwatch']), 'image' => null],
    ['name' => 'Charging Accessories', 'url' => route('shop.search.index', ['query' => 'charging']), 'image' => null],
    ['name' => 'Powerbank', 'url' => route('shop.search.index', ['query' => 'powerbank']), 'image' => null],
]))
@php($categoryItems = collect($categories)->isNotEmpty() ? collect($categories) : $fallbackCategories)

<section class="gadget-section gadget-categories" aria-labelledby="gadget-categories-title">
    <div class="gadget-container">
        <div class="gadget-section-heading">
            <div>
                <h2 id="gadget-categories-title">Smart Gadgets Categories</h2>
                <p>Explore essential device segments built for modern digital efficiency</p>
            </div>
        </div>

        <div class="gadget-category-grid">
            @foreach ($categoryItems->take(4) as $category)
                @include('shop::homepage.partials.category-card', ['category' => $category])
            @endforeach
        </div>

        <div class="gadget-order-banner">
            <div class="gadget-order-banner__media"></div>
            <div class="gadget-order-card">
                <p>Seamless Ordering</p>
                <h3>Order Products Without Any Hassle.</h3>
                <a href="{{ route('shop.checkout.cart.index') }}" class="gadget-button gadget-button--primary">
                    <span></span>
                    Place Order Now
                </a>
            </div>
        </div>
    </div>
</section>
