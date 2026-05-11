@php($fallbackCategories = collect([
    ['name' => 'Earphones', 'url' => route('shop.search.index', ['query' => 'earphone']), 'image' => null],
    ['name' => 'Smartwatches', 'url' => route('shop.search.index', ['query' => 'smartwatch']), 'image' => null],
    ['name' => 'Accessories', 'url' => route('shop.search.index', ['query' => 'charging']), 'image' => null],
    ['name' => 'Power Banks', 'url' => route('shop.search.index', ['query' => 'powerbank']), 'image' => null],
]))
@php($categoryItems = collect($categories)->isNotEmpty() ? collect($categories) : $fallbackCategories)

@pushOnce('styles')
<style>
    .gadget-categories {
        background: #f8fafc;
        padding: 100px 0;
    }

    .gadget-category-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 24px;
        margin-bottom: 80px;
    }

    .gadget-order-banner {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-radius: 32px;
        padding: 60px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #ffffff;
        position: relative;
        overflow: hidden;
    }

    .gadget-order-banner::before {
        content: "";
        position: absolute;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%);
        top: -100px;
        right: -100px;
    }

    .gadget-order-card {
        position: relative;
        z-index: 2;
        max-width: 500px;
    }

    .gadget-order-card p {
        color: #3b82f6;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-size: 13px;
        margin-bottom: 12px;
    }

    .gadget-order-card h3 {
        font-size: 36px;
        font-weight: 900;
        line-height: 1.2;
        margin-bottom: 32px;
    }

    .btn-order-now {
        background: #3b82f6;
        color: #ffffff !important;
        padding: 18px 40px;
        border-radius: 14px;
        font-weight: 700;
        text-decoration: none !important;
        font-size: 16px;
        display: inline-block;
        transition: 0.3s;
    }

    .btn-order-now:hover {
        background: #ffffff;
        color: #0f172a !important;
        transform: translateY(-4px);
    }

    @media (max-width: 991px) {
        .gadget-category-grid { grid-template-columns: repeat(2, 1fr); }
        .gadget-order-banner { flex-direction: column; text-align: center; padding: 40px 20px; }
        .gadget-order-card h3 { font-size: 28px; }
    }
</style>
@endpushOnce

<section class="gadget-section gadget-categories" aria-labelledby="gadget-categories-title">
    <div class="gadget-container">
        <div class="gadget-section-heading">
            <div>
                <h2 id="gadget-categories-title">Smart Categories</h2>
                <p style="color: #64748b; font-size: 18px; margin-top: 10px;">Find your next essential tech upgrade</p>
            </div>
        </div>

        <div class="gadget-category-grid">
            @foreach ($categoryItems->take(4) as $category)
                @include('shop::homepage.partials.category-card', ['category' => $category])
            @endforeach
        </div>

        <div class="gadget-order-banner">
            <div class="gadget-order-card">
                <p>Seamless Experience</p>
                <h3>Order Premium Tech Without Any Hassle.</h3>
                <a href="{{ route('shop.search.index') }}" class="btn-order-now">
                    Shop Now
                </a>
            </div>
            
            <div class="gadget-order-image" style="position: relative; z-index: 2;">
                <!-- Modern Visual Placeholder -->
                <div style="background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); padding: 30px; border-radius: 20px;">
                    <div style="width: 200px; height: 120px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 40px;">🛒</div>
                </div>
            </div>
        </div>
    </div>
</section>
