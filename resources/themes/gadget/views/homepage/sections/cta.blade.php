@php($featureProduct = collect($products)->first())

<section class="gadget-section gadget-cta" aria-label="Featured gadget promotions">
    <div class="gadget-container">
        <div class="gadget-experience-card">
            <div>
                <h2>The All-New Experience</h2>
                <p>Spark your imagination and redefine reality with the revolutionary spatial experience</p>
                <a href="{{ route('shop.checkout.cart.index') }}" class="gadget-button gadget-button--primary">
                    <span></span>
                    Place Order
                </a>
            </div>

            <div class="gadget-experience-card__image">
                <img src="{{ $featureProduct['image'] ?? bagisto_asset('images/medium-product-placeholder.webp', 'shop') }}" alt="">
            </div>
        </div>

        <div class="gadget-cta-grid">
            <article>
                <p>Something completely new</p>
                <h3>Smart Gadgets</h3>
                <a href="{{ route('shop.search.index') }}" class="gadget-button gadget-button--dark">
                    <span></span>
                    View Products
                </a>
            </article>

            <article class="gadget-cta-grid__wide">
                <div>
                    <p>Take Care Yourself</p>
                    <h3>Personal Care Gadgets</h3>
                    <a href="{{ route('shop.search.index', ['query' => 'personal care']) }}" class="gadget-button gadget-button--dark">
                        <span></span>
                        View Products
                    </a>
                </div>

                <div class="gadget-cta-grid__media"></div>
            </article>
        </div>
    </div>
</section>
