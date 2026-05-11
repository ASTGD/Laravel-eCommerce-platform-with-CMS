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
        background: #0f172a;
        border-radius: 48px;
        padding: 80px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #ffffff;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .banner-aura {
        position: absolute;
        inset: 0;
        z-index: 1;
        pointer-events: none;
    }

    .aura-blob {
        position: absolute;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, transparent 70%);
        border-radius: 50%;
        filter: blur(80px);
        animation: auraMove 15s infinite alternate ease-in-out;
    }

    .aura-1 { top: -20%; right: -10%; background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%); }
    .aura-2 { bottom: -20%; left: -10%; background: radial-gradient(circle, rgba(139, 92, 246, 0.15) 0%, transparent 70%); }

    @keyframes auraMove {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(50px, 30px) scale(1.1); }
    }

    .gadget-order-card {
        position: relative;
        z-index: 5;
        max-width: 550px;
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(25px);
        padding: 60px;
        border-radius: 36px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 40px 100px rgba(0, 0, 0, 0.3);
        transition: 0.4s cubic-bezier(0.23, 1, 0.32, 1);
    }

    .gadget-order-card:hover {
        transform: translateY(-10px);
        border-color: rgba(59, 130, 246, 0.3);
        background: rgba(255, 255, 255, 0.05);
    }

    .gadget-order-card p {
        color: #3b82f6;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        font-size: 14px;
        margin-bottom: 20px;
    }

    .gadget-order-card h3 {
        font-size: 48px;
        font-weight: 950;
        line-height: 1.1;
        margin-bottom: 40px;
        letter-spacing: -0.04em;
    }

    .btn-order-now {
        background: #3b82f6;
        color: #ffffff !important;
        padding: 22px 50px;
        border-radius: 18px;
        font-weight: 800;
        text-decoration: none !important;
        font-size: 18px;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        transition: 0.4s;
        box-shadow: 0 15px 30px rgba(59, 130, 246, 0.4);
    }

    .btn-order-now:hover {
        background: #2563eb;
        transform: scale(1.05);
        box-shadow: 0 20px 40px rgba(59, 130, 246, 0.6);
    }

    .gadget-order-image {
        position: relative;
        z-index: 5;
        flex: 1;
        display: flex;
        justify-content: flex-end;
        padding-right: 20px;
    }

    .banner-product-img {
        max-width: 450px;
        filter: drop-shadow(0 40px 80px rgba(0,0,0,0.4));
        animation: floatBanner 6s infinite ease-in-out;
    }

    @keyframes floatBanner {
        0%, 100% { transform: translateY(0) rotate(-2deg); }
        50% { transform: translateY(-20px) rotate(2deg); }
    }

    @media (max-width: 1200px) {
        .gadget-order-banner { padding: 60px; }
        .gadget-order-card h3 { font-size: 38px; }
        .banner-product-img { max-width: 350px; }
    }

    @media (max-width: 991px) {
        .gadget-category-grid { grid-template-columns: repeat(2, 1fr); }
        .gadget-order-banner { flex-direction: column; text-align: center; padding: 60px 30px; }
        .gadget-order-card { margin-bottom: 50px; padding: 40px; }
        .gadget-order-image { justify-content: center; padding-right: 0; }
        .banner-product-img { max-width: 300px; }
    }
    .image-glow-ring {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 120%;
        height: 120%;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 60%);
        border-radius: 50%;
        z-index: 4;
        pointer-events: none;
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
            <!-- Animated Background Aura -->
            <div class="banner-aura">
                <div class="aura-blob aura-1"></div>
                <div class="aura-blob aura-2"></div>
            </div>

            <div class="gadget-order-card">
                <p>Seamless Experience</p>
                <h3>Order Premium Tech Without Any Hassle.</h3>
                <a href="{{ route('shop.search.index') }}" class="btn-order-now">
                    <span>Shop Now</span>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
            
            <div class="gadget-order-image">
                <div class="image-glow-ring"></div>
                <img 
                    src="https://images.unsplash.com/photo-1546868871-7041f2a55e12?auto=format&fit=crop&q=80&w=800" 
                    alt="Premium Tech" 
                    class="banner-product-img"
                    style="-webkit-mask-image: radial-gradient(circle, black 50%, transparent 95%); mask-image: radial-gradient(circle, black 50%, transparent 95%);"
                    onerror="this.src='https://via.placeholder.com/800x800/0f172a/3b82f6?text=Premium+Tech'"
                >
            </div>
        </div>
    </div>
</section>
