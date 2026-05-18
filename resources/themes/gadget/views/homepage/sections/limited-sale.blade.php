@php
$_fallbackSaleProducts = [
['id'=>1,'name'=>'Premium TWS Earbuds Pro','url'=>'#','image'=>'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?q=80&w=600&auto=format&fit=crop','regular_price'=>'৳4,100.00','final_price'=>'৳3,750.00','badge'=>'Sale'],
['id'=>2,'name'=>'Ultra High Capacity Power Bank','url'=>'#','image'=>'https://images.unsplash.com/photo-1629131726692-1accd0c93fd0?q=80&w=600&auto=format&fit=crop','regular_price'=>'৳6,900.00','final_price'=>'৳4,899.00','badge'=>'Sale'],
['id'=>3,'name'=>'Fast Charging Power Bank 10K','url'=>'#','image'=>'https://images.unsplash.com/photo-1585333120111-96531985392d?q=80&w=600&auto=format&fit=crop','regular_price'=>'৳1,350.00','final_price'=>'৳1,050.00','badge'=>'Hot'],
['id'=>4,'name'=>'Portable Handheld USB Fan','url'=>'#','image'=>'https://images.unsplash.com/photo-1591123120675-6f7f1aae0e5b?q=80&w=600&auto=format&fit=crop','regular_price'=>'৳799.00','final_price'=>'৳545.00','badge'=>'Sale'],
['id'=>5,'name'=>'Magnetic Wireless Power Bank','url'=>'#','image'=>'https://images.unsplash.com/photo-1616348436168-de43ad0db179?q=80&w=600&auto=format&fit=crop','regular_price'=>'৳3,550.00','final_price'=>'৳2,750.00','badge'=>'Sale'],
];
$_realProds = collect($products ?? []);
$saleProducts = $_realProds->isNotEmpty()
? $_realProds->map(fn($p) => [
'id' => $p['id'] ?? 0,
'name' => $p['name'] ?? '',
'url' => $p['url'] ?? '#',
'image' => $p['image'] ?? '',
'regular_price' => $p['regular_price'] ?? $p['final_price'] ?? '',
'final_price' => $p['final_price'] ?? '',
'badge' => $p['badge'] ?? ($p['has_discount'] ?? false ? 'Sale' : 'New'),
])->values()->all()
: $_fallbackSaleProducts;
@endphp
@pushOnce('styles')
<style>
    .gadget-sale {
        padding: 100px 0;
        background: #ffffff;
        position: relative;
        width: 100%;
    }

    .gadget-sale__container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 0 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .gadget-sale__header {
        text-align: center;
        margin-bottom: 70px;
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
    }

    .gadget-sale__title {
        font-size: 52px;
        font-weight: 950;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: -0.04em;
        margin-bottom: 30px;
        position: relative;
    }

    .gadget-sale__title::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 120px;
        height: 6px;
        background: linear-gradient(90deg, #3b82f6, #60a5fa);
        border-radius: 10px;
    }

    .gadget-sale__timer-wrap {
        display: flex;
        align-items: center;
        gap: 25px;
        background: #f8fafc;
        padding: 15px 40px;
        border-radius: 100px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04);
    }

    .gadget-sale__timer-label {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 900;
        color: #ef4444;
        text-transform: uppercase;
        font-size: 14px;
        border-right: 2px solid #e2e8f0;
        padding-right: 25px;
    }

    .gadget-sale__digit-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 50px;
    }

    .gadget-sale__number {
        font-size: 28px;
        font-weight: 950;
        color: #0f172a;
        line-height: 1;
    }

    .gadget-sale__label {
        font-size: 10px;
        color: #94a3b8;
        text-transform: uppercase;
        font-weight: 800;
        margin-top: 6px;
    }

    .gadget-sale__colon {
        font-size: 24px;
        font-weight: 900;
        color: #cbd5e1;
        margin-bottom: 18px;
    }

    /* Grid Layout - 5 Items Per Line */
    .gadget-sale__grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 24px;
        margin-bottom: 60px;
        width: 100%;
        justify-items: center;
    }

    /* Premium Product Card - Increased Size */
    .sale-card {
        background: #ffffff;
        border-radius: 36px;
        padding: 24px;
        border: 1px solid #f1f5f9;
        transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        text-decoration: none !important;
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
        overflow: hidden;
        width: 100%;
        min-height: 450px;
        /* Increased from 380px for better proportion with 5 columns */
    }

    .sale-card:hover {
        transform: translateY(-15px);
        box-shadow: 0 40px 80px rgba(0, 0, 0, 0.08);
        border-color: #3b82f6;
    }

    .sale-card__media {
        aspect-ratio: 1;
        background: #f8fafc;
        border-radius: 28px;
        overflow: hidden;
        margin-bottom: 20px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sale-card__media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: 0.8s ease;
    }

    .sale-card:hover .sale-card__media img {
        transform: scale(1.1);
    }

    .sale-card__badge {
        position: absolute;
        top: 20px;
        left: 20px;
        padding: 6px 16px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        color: #ffffff;
        z-index: 10;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .badge--sale {
        background: #3b82f6;
    }

    .badge--hot {
        background: #ef4444;
    }

    .sale-card__info {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        width: 100%;
    }

    /* Smaller Title, Normal Weight */
    .sale-card__info h3 {
        font-size: 16px;
        font-weight: 500;
        color: #0f172a;
        margin-bottom: 12px;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 48px;
    }

    .sale-card__rating {
        display: flex;
        gap: 3px;
        margin-bottom: 15px;
        transition: opacity 0.3s;
    }

    .sale-card__prices {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: auto;
        transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
        padding-bottom: 5px;
        width: 100%;
        flex-wrap: wrap;
    }

    .price--final {
        font-size: 24px;
        font-weight: 950;
        color: #2563eb;
        white-space: nowrap;
    }

    .price--regular {
        font-size: 15px;
        color: #94a3b8;
        text-decoration: line-through;
        white-space: nowrap;
    }

    /* Hover Buy Now Button */
    .sale-card__actions {
        position: absolute;
        bottom: -70px;
        left: 24px;
        right: 24px;
        transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        opacity: 0;
        z-index: 5;
    }

    .sale-card:hover .sale-card__actions {
        bottom: 30px;
        opacity: 1;
    }

    .sale-card:hover .sale-card__prices {
        opacity: 0;
        transform: translateY(30px);
    }

    .sale-card:hover .sale-card__rating {
        opacity: 0.1;
    }

    .btn-buy-now {
        width: 100%;
        background: #0f172a;
        color: #ffffff !important;
        padding: 16px;
        border-radius: 18px;
        font-weight: 900;
        text-align: center;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 14px;
        transition: 0.3s;
    }

    .btn-buy-now:hover {
        background: #3b82f6;
        box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: #ffffff;
        color: #0f172a !important;
        padding: 18px 50px;
        border-radius: 18px;
        font-weight: 900;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        border: 2px solid #e2e8f0;
        cursor: pointer;
        transition: 0.3s;
        text-decoration: none !important;
    }

    .btn-action:hover {
        background: #0f172a;
        color: #ffffff !important;
        border-color: #0f172a;
        box-shadow: 0 15px 30px rgba(15, 23, 42, 0.2);
    }

    @media (max-width: 1600px) {
        .gadget-sale__grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    @media (max-width: 1200px) {
        .gadget-sale__grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .gadget-sale__grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .gadget-sale__title {
            font-size: 38px;
        }
    }

    @media (max-width: 480px) {
        .gadget-sale__grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpushOnce

<section class="gadget-sale">
    <div class="gadget-sale__container">
        <div class="gadget-sale__header">
            <h2 class="gadget-sale__title">Limited Sale</h2>
            <v-gadget-timer end-date="{{ now()->addDays(2)->toIso8601String() }}"></v-gadget-timer>
        </div>

        <v-limited-sale-grid :products="{{ json_encode($saleProducts) }}"></v-limited-sale-grid>
    </div>
</section>

@pushOnce('scripts')
<script type="text/x-template" id="v-gadget-timer-template">
    <div class="gadget-sale__timer-wrap">
        <div class="gadget-sale__timer-label">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span>Ends In:</span>
        </div>
        <div class="gadget-sale__digit-box">
            <span class="gadget-sale__number">@{{ timeLeft.days }}</span>
            <span class="gadget-sale__label">Days</span>
        </div>
        <div class="gadget-sale__colon">:</div>
        <div class="gadget-sale__digit-box">
            <span class="gadget-sale__number">@{{ timeLeft.hours }}</span>
            <span class="gadget-sale__label">Hr</span>
        </div>
        <div class="gadget-sale__colon">:</div>
        <div class="gadget-sale__digit-box">
            <span class="gadget-sale__number">@{{ timeLeft.minutes }}</span>
            <span class="gadget-sale__label">Min</span>
        </div>
        <div class="gadget-sale__colon">:</div>
        <div class="gadget-sale__digit-box">
            <span class="gadget-sale__number">@{{ timeLeft.seconds }}</span>
            <span class="gadget-sale__label">Sc</span>
        </div>
    </div>
</script>

<script type="text/x-template" id="v-limited-sale-grid-template">
    <div style="display: flex; flex-direction: column; align-items: center; width: 100%;">
        <div class="gadget-sale__grid">
            <div v-for="product in visibleProducts" :key="product.id" class="sale-card">
                <a :href="product.url" class="sale-card__media">
                    <span :class="['sale-card__badge', product.badge === 'Hot' ? 'badge--hot' : 'badge--sale']">
                        @{{ product.badge }}
                    </span>
                    <img :src="product.image" :alt="product.name" onerror="this.src='https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600&auto=format&fit=crop'">
                </a>
                <div class="sale-card__info">
                    <h3>
                        <a :href="product.url" style="color: inherit; text-decoration: none;">@{{ product.name }}</a>
                    </h3>
                    <div class="sale-card__rating">
                        <svg v-for="i in 5" :key="i" style="color: #fbbf24; fill: #fbbf24;" width="14" height="14" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    
                    <div class="sale-card__prices">
                        <span class="price--regular">@{{ product.regular_price }}</span>
                        <span class="price--final">@{{ product.final_price }}</span>
                    </div>

                    <div class="sale-card__actions">
                        <a :href="product.url" class="btn-buy-now" style="text-decoration: none;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                            <span>Buy Now</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="hasMore">
            <button type="button" class="btn-action" @click="loadMore" :disabled="loading">
                <span v-if="!loading">Load More Products</span>
                <span v-else>Loading...</span>
            </button>
        </div>
        
        <div v-else>
            <a href="/search" class="btn-action">
                <span>Explore All Products</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>
    </div>
</script>

<script type="module">
    app.component('v-gadget-timer', {
        template: '#v-gadget-timer-template',
        props: ['endDate'],
        data() {
            return {
                timeLeft: {
                    days: '00',
                    hours: '00',
                    minutes: '00',
                    seconds: '00'
                },
                interval: null
            }
        },
        mounted() {
            this.updateTimer();
            this.interval = setInterval(() => this.updateTimer(), 1000);
        },
        beforeUnmount() {
            if (this.interval) clearInterval(this.interval);
        },
        methods: {
            updateTimer() {
                const end = new Date(this.endDate).getTime();
                const now = new Date().getTime();
                const diff = end - now;

                if (diff <= 0) {
                    this.timeLeft = {
                        days: '00',
                        hours: '00',
                        minutes: '00',
                        seconds: '00'
                    };
                    return;
                }

                this.timeLeft = {
                    days: Math.floor(diff / (1000 * 60 * 60 * 24)).toString().padStart(2, '0'),
                    hours: Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)).toString().padStart(2, '0'),
                    minutes: Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)).toString().padStart(2, '0'),
                    seconds: Math.floor((diff % (1000 * 60)) / 1000).toString().padStart(2, '0')
                };
            }
        }
    });

    app.component('v-limited-sale-grid', {
        template: '#v-limited-sale-grid-template',
        props: {
            products: {
                type: Array,
                default: () => []
            }
        },
        data() {
            return {
                visibleCount: 5,
                loading: false
            }
        },
        computed: {
            visibleProducts() {
                return this.products.slice(0, this.visibleCount);
            },
            hasMore() {
                return this.visibleCount < this.products.length;
            }
        },
        methods: {
            loadMore() {
                this.loading = true;
                setTimeout(() => {
                    this.visibleCount += 5;
                    this.loading = false;
                }, 600);
            }
        }
    });
</script>
@endpushOnce