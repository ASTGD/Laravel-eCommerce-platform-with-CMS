@php
$_fallbackCats = [
    ['id'=>1,'name'=>'Headphones','url'=>route('shop.search.index',['query'=>'headphones']),'count'=>124,'image'=>'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=500&auto=format&fit=crop'],
    ['id'=>2,'name'=>'Smart Watches','url'=>route('shop.search.index',['query'=>'smartwatch']),'count'=>86,'image'=>'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=500&auto=format&fit=crop'],
    ['id'=>3,'name'=>'Laptops','url'=>route('shop.search.index',['query'=>'laptop']),'count'=>210,'image'=>'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?q=80&w=500&auto=format&fit=crop'],
    ['id'=>4,'name'=>'Smartphones','url'=>route('shop.search.index',['query'=>'smartphone']),'count'=>342,'image'=>'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?q=80&w=500&auto=format&fit=crop'],
    ['id'=>5,'name'=>'Keyboards','url'=>route('shop.search.index',['query'=>'keyboard']),'count'=>54,'image'=>'https://images.unsplash.com/photo-1511467687858-23d96c32e4ae?q=80&w=500&auto=format&fit=crop'],
    ['id'=>6,'name'=>'Power Banks','url'=>route('shop.search.index',['query'=>'power bank']),'count'=>72,'image'=>'https://images.unsplash.com/photo-1625517431411-3007ca5f8f3c?q=80&w=500&auto=format&fit=crop'],
    ['id'=>7,'name'=>'Cameras','url'=>route('shop.search.index',['query'=>'camera']),'count'=>43,'image'=>'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?q=80&w=500&auto=format&fit=crop'],
    ['id'=>8,'name'=>'Gaming Gear','url'=>route('shop.search.index',['query'=>'gaming']),'count'=>156,'image'=>'https://images.unsplash.com/photo-1542751371-adc38448a05e?q=80&w=500&auto=format&fit=crop'],
    ['id'=>9,'name'=>'Audio Systems','url'=>route('shop.search.index',['query'=>'audio']),'count'=>89,'image'=>'https://images.unsplash.com/photo-1558089623-9030abfe53f1?q=80&w=500&auto=format&fit=crop'],
    ['id'=>10,'name'=>'Storage Devices','url'=>route('shop.search.index',['query'=>'storage']),'count'=>112,'image'=>'https://images.unsplash.com/photo-1597740985671-2a8a3b80502e?q=80&w=500&auto=format&fit=crop'],
    ['id'=>11,'name'=>'Monitors','url'=>route('shop.search.index',['query'=>'monitor']),'count'=>67,'image'=>'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?q=80&w=500&auto=format&fit=crop'],
    ['id'=>12,'name'=>'Tablets','url'=>route('shop.search.index',['query'=>'tablet']),'count'=>98,'image'=>'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?q=80&w=500&auto=format&fit=crop'],
];
$_realCats = collect($categories ?? []);
$categoryData = $_realCats->isNotEmpty()
    ? $_realCats->map(fn($c) => [
        'id'    => $c['id'] ?? 0,
        'name'  => $c['name'] ?? '',
        'url'   => $c['url'] ?? route('shop.search.index'),
        'count' => $c['products_count'] ?? $c['count'] ?? 0,
        'image' => $c['image'] ?? $c['logo_path'] ?? 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=500&auto=format&fit=crop',
    ])->values()->all()
    : $_fallbackCats;
@endphp
@pushOnce('styles')
<style>
    .gadget-categories {
        background: #f8fafc;
        /* Very light subtle background */
        padding: 120px 0;
        width: 100%;
        overflow: hidden;
    }

    .gadget-categories__header {
        text-align: center;
        margin-bottom: 70px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .gadget-categories__title {
        font-size: 52px;
        font-weight: 950;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: -0.04em;
        margin-bottom: 20px;
        position: relative;
    }

    .gadget-categories__title::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 5px;
        background: #3b82f6;
        border-radius: 10px;
    }

    .gadget-categories__subtitle {
        color: #64748b;
        font-size: 18px;
        font-weight: 500;
    }

    /* Category Grid */
    .gadget-category-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 24px;
        margin-bottom: 60px;
        width: 100%;
    }

    /* Premium Category Card */
    .category-card {
        background: #ffffff;
        border-radius: 32px;
        padding: 12px;
        border: 1px solid #f1f5f9;
        transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        text-decoration: none !important;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        overflow: hidden;
        height: 100%;
    }

    .category-card:hover {
        transform: translateY(-12px);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.06);
        border-color: #3b82f6;
    }

    .category-card__media {
        width: 100%;
        aspect-ratio: 1;
        background: #f1f5f9;
        border-radius: 24px;
        overflow: hidden;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .category-card__media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: 0.8s ease;
    }

    .category-card:hover .category-card__media img {
        transform: scale(1.15);
    }

    .category-card__name {
        font-size: 18px;
        font-weight: 850;
        color: #0f172a;
        margin-bottom: 8px;
    }

    .category-card__count {
        font-size: 13px;
        color: #94a3b8;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* Action Buttons */
    .gadget-categories__actions {
        display: flex;
        justify-content: center;
        margin-bottom: 100px;
    }

    .btn-cat-load {
        background: #ffffff;
        color: #0f172a;
        padding: 18px 50px;
        border-radius: 18px;
        font-weight: 900;
        font-size: 15px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        border: 2px solid #e2e8f0;
        cursor: pointer;
        transition: 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 12px;
    }

    .btn-cat-load:hover {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
        box-shadow: 0 15px 30px rgba(15, 23, 42, 0.2);
    }

    /* Full Width Promo Banner */
    .gadget-seamless-dark {
        background: #0f172a;
        padding: 100px 0 50px 0;
        color: #ffffff;
        position: relative;
        overflow: hidden;
    }

    .gadget-seamless-dark .gadget-container {
        display: block;
    }

    .gadget-seamless-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 50px;
        position: relative;
        margin-top: 60px;
    }

    .gadget-order-card {
        position: relative;
        z-index: 5;
        max-width: 500px;
        background: transparent;
        border: none;
        padding: 0;
    }

    .gadget-order-card p {
        color: #3b82f6;
        font-weight: normal;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        font-size: 14px;
        margin-bottom: 20px;
    }

    .gadget-order-card h3 {
        font-size: 48px;
        font-weight: normal;
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
    }

    .btn-order-now:hover {
        background: #1d4ed8;
        box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
    }

    .banner-product-img {
        max-width: 280px; /* Slightly smaller to fit perfectly above the text */
        filter: drop-shadow(0 40px 80px rgba(0, 0, 0, 0.4));
        animation: floatBanner 6s infinite ease-in-out;
        z-index: 2;
        position: absolute;
        left: 20px;
        top: -70px;
        pointer-events: none;
        opacity: 1;
    }

    @keyframes floatBanner {
        0%, 100% {
            transform: translateY(0) rotate(-2deg);
        }
        50% {
            transform: translateY(-20px) rotate(2deg);
        }
    }

    .gadget-banner-products {
        display: flex;
        gap: 30px;
        flex: 1;
        justify-content: flex-end;
        max-width: 650px;
    }

    .gadget-banner-products > div {
        flex: 1;
        max-width: 300px;
    }

    @media (max-width: 991px) {
        .gadget-seamless-dark .gadget-container {
            flex-direction: column;
            text-align: center;
        }

        .gadget-banner-products {
            justify-content: center;
            width: 100%;
        }
    }

    @media (max-width: 1440px) {
        .gadget-category-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    @media (max-width: 991px) {
        .gadget-category-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .gadget-order-banner {
            flex-direction: column;
            text-align: center;
            padding: 40px;
        }

        .gadget-order-card {
            margin-bottom: 40px;
        }

        .banner-product-img {
            max-width: 300px;
        }
    }
</style>
@endpushOnce

<section class="gadget-categories">
    <div class="gadget-container">
        <div class="gadget-categories__header">
            <h2 class="gadget-categories__title">Smart Categories</h2>
            <p class="gadget-categories__subtitle">Explore our universe of premium tech solutions</p>
        </div>

        <v-gadget-categories :categories="{{ json_encode($categoryData) }}"></v-gadget-categories>

    </div>
</section>

<!-- Full Width Seamless Experience Banner -->
<section class="gadget-seamless-dark">
    <div class="gadget-container">
        <!-- Moved Featured Picks Header -->
        <div class="gadget-section-heading" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px;">
            <div>
                <h2 id="gadget-featured-title" style="color: #ffffff; font-size: 48px; font-weight: normal; letter-spacing: -0.04em; margin: 0;">Featured Picks</h2>
            </div>
            <a href="{{ route('shop.search.index') }}" class="gadget-text-link" style="color: #3b82f6; text-decoration: none; font-size: 18px;">
                Explore All
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 5px; vertical-align: text-bottom;">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </a>
        </div>

        <div class="gadget-seamless-content">
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

        <!-- Big Floating Product Image -->
        <img src="images/1.png" alt="Premium Tech" class="banner-product-img">

        <div class="gadget-banner-products">
            @php
                // Use real products if available, or fallbacks
                $_bannerProds = isset($products) && count($products) > 0 ? $products : [
                    [
                        'id' => 1,
                        'name' => 'Premium Headset',
                        'short_name' => 'Premium Headset',
                        'url' => '#',
                        'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=500&auto=format&fit=crop',
                        'regular_price' => '$1,350.00',
                        'final_price' => '$1,000.00',
                        'has_discount' => true,
                        'badge' => 'New',
                        'is_saleable' => true,
                    ],
                    [
                        'id' => 2,
                        'name' => 'Smart Watch Pro',
                        'short_name' => 'Smart Watch Pro',
                        'url' => '#',
                        'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=500&auto=format&fit=crop',
                        'regular_price' => '$450.00',
                        'final_price' => '$350.00',
                        'has_discount' => true,
                        'badge' => 'Hot',
                        'is_saleable' => true,
                    ]
                ];
                
                $bannerProducts = collect($_bannerProds)->take(2);
            @endphp

            @foreach ($bannerProducts as $product)
                <div>
                    @include('shop::homepage.partials.product-card', ['product' => $product, 'mode' => 'banner', 'showAction' => true])
                </div>
            @endforeach
        </div>
        </div>
    </div>
</section>

@pushOnce('scripts')
<script type="text/x-template" id="v-gadget-categories-template">
    <div style="width: 100%;">
        <div class="gadget-category-grid">
            <a v-for="category in visibleCategories" :key="category.id" :href="category.url" class="category-card">
                <div class="category-card__media">
                    <img :src="category.image" :alt="category.name">
                </div>
                <div class="category-card__name">@{{ category.name }}</div>
                <div class="category-card__count">@{{ category.count }} Items</div>
            </a>
        </div>

        <div class="gadget-categories__actions">
            <button v-if="hasMore" type="button" class="btn-cat-load" @click="loadMore" :disabled="loading">
                <span v-if="!loading">Load More Categories</span>
                <span v-else>Loading...</span>
            </button>
            <a v-else href="/categories" class="btn-cat-load">
                <span>View All Categories</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>
    </div>
</script>

<script type="module">
    app.component('v-gadget-categories', {
        template: '#v-gadget-categories-template',
        props: {
            categories: { type: Array, default: () => [] }
        },
        data() {
            return {
                visibleCount: 12,
                loading: false
            }
        },
        computed: {
            visibleCategories() {
                return this.categories.slice(0, this.visibleCount);
            },
            hasMore() {
                return this.visibleCount < this.categories.length;
            }
        },
        methods: {
            loadMore() {
                this.loading = true;
                setTimeout(() => {
                    this.visibleCount += 6;
                    this.loading = false;
                }, 600);
            }
        }
    });
</script>
@endpushOnce