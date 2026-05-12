@php
    $heroProducts = collect($products)->take(5)->values();
    $cmsHeroSlides = collect(data_get($hero ?? null, 'slides', []))
        ->filter(fn ($slide) => ! empty($slide['image']))
        ->take(5)
        ->values();
    $heroImageUrl = fn ($path) => \Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '/']) ? $path : asset($path);
    $placeholderImages = [
        'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=1400',
        'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&q=80&w=1400',
        'https://images.unsplash.com/photo-1509631179647-0177331693ae?auto=format&fit=crop&q=80&w=1400',
        'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&q=80&w=1400',
        'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&q=80&w=1400',
    ];

    if ($heroProducts->isEmpty()) {
        $heroProducts = collect([
            ['name' => 'Color Pop Collection', 'url' => route('shop.search.index'), 'final_price' => 'Shop Now'],
        ]);
    }

    $heroData = $cmsHeroSlides->isNotEmpty()
        ? $cmsHeroSlides->map(function ($slide) use ($heroImageUrl) {
            return [
                'cms' => true,
                'name' => $slide['title'] ?: ($slide['headline'] ?: 'Hero slide'),
                'image' => $heroImageUrl($slide['image']),
                'url' => $slide['primary_cta_url'] ?: route('shop.search.index'),
                'price' => $slide['primary_cta_label'] ?: 'Shop Now',
                'headline' => $slide['headline'] ?: ($slide['title'] ?: 'Color Pop Collection'),
                'body' => $slide['body'] ?: '',
                'primary_label' => $slide['primary_cta_label'] ?: 'Shop the Look',
                'primary_url' => $slide['primary_cta_url'] ?: route('shop.search.index'),
                'secondary_label' => $slide['secondary_cta_label'] ?: '',
                'secondary_url' => $slide['secondary_cta_url'] ?: route('shop.search.index'),
            ];
        })
        : $heroProducts->map(function($product, $index) use ($placeholderImages) {
            return [
                'cms' => false,
                'name' => $product['name'] ?? 'Color Pop Collection',
                'image' => $placeholderImages[$index % count($placeholderImages)],
                'url' => $product['url'] ?? route('shop.search.index'),
                'price' => $product['final_price'] ?? ($product['formatted_price'] ?? 'Shop Now'),
            ];
        })->values();
@endphp

@pushOnce('styles')
<style>
    .gadget-hero-wrapper {
        position: relative;
        min-height: clamp(720px, 88vh, 920px);
        display: flex;
        align-items: center;
        overflow: hidden;
        background:
            linear-gradient(90deg, rgba(255,253,250,.96), rgba(255,253,250,.78) 48%, rgba(255,253,250,.1)),
            radial-gradient(circle at 13% 22%, rgba(200,255,77,.48), transparent 23%),
            radial-gradient(circle at 56% 18%, rgba(255,79,112,.25), transparent 25%),
            #fffdfa;
    }

    .gadget-hero-wrapper::before {
        content: '';
        position: absolute;
        inset: 0;
        opacity: .34;
        background-image: linear-gradient(rgba(23,17,20,.08) 1px, transparent 1px), linear-gradient(90deg, rgba(23,17,20,.08) 1px, transparent 1px);
        background-size: 46px 46px;
        mask-image: linear-gradient(to bottom, #000, transparent 84%);
        pointer-events: none;
    }

    .gadget-hero-slider { width: 100%; position: relative; z-index: 1; }
    .hero-slides-inner { position: relative; min-height: 640px; display: flex; align-items: center; }
    .hero-slide { width: 100%; flex-shrink: 0; }

    .slide-fade-enter-active,
    .slide-fade-leave-active { position: absolute; inset: 0; transition: opacity .65s ease, transform .65s ease; }
    .slide-fade-enter-from { opacity: 0; transform: translateX(40px); }
    .slide-fade-leave-to { opacity: 0; transform: translateX(-40px); }

    .hero-inner-grid {
        width: min(1220px, calc(100% - 40px));
        margin: 0 auto;
        display: grid;
        grid-template-columns: minmax(0, .94fr) minmax(0, 1.06fr);
        gap: clamp(36px, 7vw, 92px);
        align-items: center;
    }

    .hero-tag {
        display: inline-flex;
        background: #171114;
        color: #c8ff4d;
        border-radius: 999px;
        padding: 10px 16px;
        font-size: 12px;
        font-weight: 950;
        letter-spacing: .16em;
        text-transform: uppercase;
        margin-bottom: 22px;
    }

    .hero-main-title {
        font-family: 'Fraunces', serif;
        font-size: clamp(58px, 8.8vw, 130px);
        line-height: .82;
        letter-spacing: -0.075em;
        margin: 0 0 28px;
        color: #171114;
        max-width: 760px;
    }

    .highlight-text {
        display: inline-block;
        color: #ff4f70;
        text-shadow: 4px 4px 0 #c8ff4d;
        transform: rotate(-1.5deg);
    }

    .hero-description {
        max-width: 560px;
        font-size: clamp(18px, 2vw, 22px);
        line-height: 1.55;
        color: #5f5559;
        margin: 0 0 34px;
    }

    .hero-cta-row { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; }

    .btn-glass-light {
        background: rgba(255,255,255,.72);
        color: #171114 !important;
        border: 1px solid rgba(23,17,20,.12);
        padding: 16px 24px;
        border-radius: 999px;
        font-weight: 900;
        text-decoration: none !important;
        backdrop-filter: blur(14px);
    }

    .hero-image-content { position: relative; }
    .image-stage {
        position: relative;
        min-height: 620px;
        border-radius: 46px;
        overflow: hidden;
        box-shadow: var(--fashion-shadow);
        background: #fff1f3;
        isolation: isolate;
    }

    .image-stage::before {
        content: '';
        position: absolute;
        inset: 20px;
        border: 1px solid rgba(255,255,255,.75);
        border-radius: 32px;
        z-index: 2;
        pointer-events: none;
    }

    .product-hero-img { width: 100%; height: 100%; min-height: 620px; object-fit: cover; display: block; transition: transform .8s ease; }
    .hero-slide:hover .product-hero-img { transform: scale(1.035); }

    .hero-stat-card {
        position: absolute;
        left: -24px;
        bottom: 54px;
        z-index: 3;
        display: flex;
        align-items: center;
        gap: 12px;
        background: #fffdfa;
        color: #171114;
        border-radius: 999px;
        padding: 14px 18px;
        box-shadow: 0 18px 45px rgba(23,17,20,.14);
        font-weight: 950;
    }

    .stat-icon { width: 38px; height: 38px; border-radius: 50%; display: grid; place-items: center; background: #c8ff4d; }
    .hero-price-chip { position: absolute; top: 34px; right: 34px; z-index: 3; background: #171114; color: #fff; border-radius: 999px; padding: 12px 17px; font-weight: 950; }

    .hero-dots { position: absolute; left: 50%; bottom: 30px; transform: translateX(-50%); display: flex; gap: 10px; z-index: 4; }
    .dot { width: 12px; height: 12px; border: 0; border-radius: 999px; background: rgba(23,17,20,.25); cursor: pointer; transition: .25s ease; }
    .dot.active { width: 42px; background: #ff4f70; }
    .hero-ssr-placeholder { min-height: 760px; display: grid; place-items: center; }

    @media (max-width: 980px) {
        .gadget-hero-wrapper { padding: 56px 0 82px; min-height: auto; }
        .hero-slides-inner { min-height: auto; }
        .hero-inner-grid { width: min(100% - 28px, 1220px); grid-template-columns: 1fr; }
        .image-stage, .product-hero-img { min-height: 430px; }
        .hero-stat-card { left: 18px; bottom: 24px; }
        .hero-main-title { font-size: clamp(54px, 18vw, 92px); }
    }
</style>
@endPushOnce

<v-gadget-hero :products='@json($heroData)'>
    <div class="hero-ssr-placeholder">
        <div class="gadget-container">
            <h1 style="font-family: Fraunces, serif; font-size: clamp(54px, 10vw, 110px); letter-spacing: -0.07em;">Style is loading...</h1>
        </div>
    </div>
</v-gadget-hero>

@pushOnce('scripts')
<script type="text/x-template" id="v-gadget-hero-template">
    <div class="gadget-hero-wrapper" @mouseenter="pause" @mouseleave="start">
        <div class="gadget-hero-slider">
            <transition-group name="slide-fade" tag="div" class="hero-slides-inner">
                <div class="hero-slide" v-for="(p, i) in products" :key="i" v-show="currentIndex === i">
                    <div class="hero-inner-grid">
                        <div class="hero-text-content">
                            <span class="hero-tag">New season drop</span>
                            <h1 v-if="p.cms" class="hero-main-title">@{{ p.headline }}</h1>
                            <h1 v-else class="hero-main-title">Wear the<br><span class="highlight-text">bright side.</span></h1>
                            <p class="hero-description">@{{ p.body || `Build a wardrobe that feels fresh, expressive, and easy to wear. Start with ${p.name} and make every day look styled.` }}</p>
                            <div class="hero-cta-row">
                                <a :href="p.primary_url || p.url" class="fashion-button fashion-button--color">@{{ p.primary_label || 'Shop the Look' }}</a>
                                <a :href="p.secondary_url || '{{ route('shop.search.index') }}'" class="btn-glass-light">@{{ p.secondary_label || 'Browse Collection' }}</a>
                            </div>
                        </div>
                        <div class="hero-image-content">
                            <div class="image-stage">
                                <img :src="p.image" :alt="p.name" class="product-hero-img">
                                <div class="hero-price-chip">@{{ p.price }}</div>
                                <div class="hero-stat-card"><div class="stat-icon">✦</div><div>Fresh arrival</div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition-group>

            <div class="hero-dots" v-if="products.length > 1">
                <button v-for="(p, i) in products" :key="i" @click="currentIndex = i" :class="{ active: currentIndex === i }" class="dot" :aria-label="`Show slide ${i + 1}`"></button>
            </div>
        </div>
    </div>
</script>

<script type="module">
    app.component('v-gadget-hero', {
        template: '#v-gadget-hero-template',
        props: ['products'],
        data() { return { currentIndex: 0, timer: null } },
        mounted() { this.start(); },
        beforeUnmount() { clearInterval(this.timer); },
        methods: {
            start() {
                if (!this.products || this.products.length <= 1) return;
                clearInterval(this.timer);
                this.timer = setInterval(() => { this.currentIndex = (this.currentIndex + 1) % this.products.length; }, 6500);
            },
            pause() { clearInterval(this.timer); }
        }
    });
</script>
@endPushOnce
