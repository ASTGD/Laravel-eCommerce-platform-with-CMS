@php
    $heroProducts = collect($products)->take(5)->values();
    $cmsHeroSlides = collect(data_get($hero ?? null, 'slides', []))
        ->filter(fn ($slide) => ! empty($slide['image']))
        ->take(5)
        ->values();
    $heroImageUrl = fn ($path) => \Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '/']) ? $path : asset($path);
    
    // Premium Light-Themed Gadget Images from Unsplash
    $placeholderImages = [
        'https://images.unsplash.com/photo-1546868871-7041f2a55e12?auto=format&fit=crop&q=80&w=1200', // Apple Watch
        'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&q=80&w=1200', // Headphones
        'https://images.unsplash.com/photo-1622979135225-d2ba269cf1ac?auto=format&fit=crop&q=80&w=1200', // VR
        'https://images.unsplash.com/photo-1583394838336-acd977736f90?auto=format&fit=crop&q=80&w=1200', // Controller
        'https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?auto=format&fit=crop&q=80&w=1200', // Laptop
    ];

    $heroData = $cmsHeroSlides->isNotEmpty()
        ? $cmsHeroSlides->map(function ($slide) use ($heroImageUrl) {
            return [
                'cms' => true,
                'name' => $slide['title'] ?: ($slide['headline'] ?: 'Hero slide'),
                'image' => $heroImageUrl($slide['image']),
                'url' => $slide['primary_cta_url'] ?: '#',
                'headline' => $slide['headline'] ?: ($slide['title'] ?: 'Featured collection'),
                'body' => $slide['body'] ?: '',
                'primary_label' => $slide['primary_cta_label'] ?: 'Explore Now',
                'primary_url' => $slide['primary_cta_url'] ?: '#',
                'secondary_label' => $slide['secondary_cta_label'] ?: '',
                'secondary_url' => $slide['secondary_cta_url'] ?: '#',
            ];
        })
        : $heroProducts->map(function($product, $index) use ($placeholderImages) {
            return [
                'cms' => false,
                'name' => $product['name'] ?? 'Next-Gen Gadget',
                'image' => $placeholderImages[$index % count($placeholderImages)],
                'url_key' => $product['url_key'] ?? '#',
                'formatted_price' => $product['formatted_price'] ?? '$199.00',
            ];
        });
@endphp

@push('styles')
<style>
    /* LIGHT FUTURISTIC DESIGN - "AURA" */
    .gadget-hero-wrapper {
        position: relative !important;
        min-height: 800px !important;
        background: #f8fafc !important;
        overflow: hidden !important;
        display: flex !important;
        align-items: center !important;
        width: 100% !important;
        color: #0f172a !important;
    }

    .hero-mesh {
        position: absolute;
        inset: 0;
        z-index: 1;
        opacity: 0.4;
        pointer-events: none;
    }
    .mesh-blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(100px);
        animation: meshMove 25s infinite alternate ease-in-out;
    }
    .mesh-1 { width: 700px; height: 700px; background: radial-gradient(circle, #60a5fa 0%, transparent 70%); top: -10%; right: -5%; opacity: 0.4; }
    .mesh-2 { width: 500px; height: 500px; background: radial-gradient(circle, #a78bfa 0%, transparent 70%); bottom: -5%; left: -5%; opacity: 0.3; }
    .mesh-3 { width: 400px; height: 400px; background: radial-gradient(circle, #fcd34d 0%, transparent 70%); top: 30%; left: 20%; opacity: 0.2; }

    @keyframes meshMove {
        0% { transform: translate(0, 0) scale(1) rotate(0deg); }
        100% { transform: translate(60px, 30px) scale(1.1) rotate(10deg); }
    }

    .gadget-hero-slider { 
        position: relative !important; 
        z-index: 2 !important; 
        width: 100% !important;
    }

    .hero-slides-inner {
        position: relative;
        width: 100%;
        min-height: 650px;
        display: flex;
        align-items: center;
    }

    .hero-slide {
        width: 100%;
        flex-shrink: 0;
    }

    .slide-fade-enter-active, .slide-fade-leave-active { 
        position: absolute !important;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
        width: 100%;
        transition: all 0.8s cubic-bezier(0.23, 1, 0.32, 1); 
    }
    
    .slide-fade-enter-from { opacity: 0; transform: translateY(-50%) translateX(100px); }
    .slide-fade-leave-to { opacity: 0; transform: translateY(-50%) translateX(-100px); }

    .hero-inner-grid {
        display: grid !important;
        grid-template-columns: 1.1fr 0.9fr !important;
        align-items: center !important;
        gap: 80px !important;
        width: 100%;
        max-width: 1320px !important;
        margin: 0 auto !important;
        padding: 0 40px !important;
    }

    @media (max-width: 991px) {
        .gadget-hero-wrapper { min-height: auto !important; padding-block: 80px !important; }
        .hero-slides-inner { min-height: auto !important; }
        .hero-inner-grid { grid-template-columns: 1fr !important; text-align: center !important; gap: 50px !important; padding: 0 24px !important; }
        .hero-image-content { order: -1 !important; }
        .hero-main-title { font-size: 42px !important; }
        .hero-description { margin-inline: auto !important; }
    }

    /* Text Content */
    .hero-tag-wrap { margin-bottom: 28px; }
    .hero-tag {
        background: rgba(59, 130, 246, 0.08);
        color: #2563eb;
        padding: 8px 20px;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.2em;
        border: 1px solid rgba(59, 130, 246, 0.15);
    }

    .hero-main-title {
        font-size: clamp(54px, 7vw, 92px);
        font-weight: 900;
        line-height: 0.95;
        color: #0f172a !important;
        margin-bottom: 35px;
        letter-spacing: -0.05em;
    }

    .gradient-title {
        background: linear-gradient(135deg, #0f172a 30%, #475569 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .highlight-text {
        color: #3b82f6 !important;
        position: relative;
    }
    .highlight-text::after {
        content: "";
        position: absolute;
        bottom: 15%;
        left: 0;
        width: 100%;
        height: 12px;
        background: rgba(59, 130, 246, 0.1);
        z-index: -1;
    }

    .hero-description {
        font-size: 20px;
        color: #475569;
        max-width: 560px;
        line-height: 1.7;
        margin-bottom: 45px;
        font-weight: 450;
    }

    .hero-cta-row { display: flex; gap: 20px; flex-wrap: wrap; }
    @media (max-width: 991px) { .hero-cta-row { justify-content: center; } }

    .btn-aura {
        background: #3b82f6;
        color: #ffffff !important;
        padding: 20px 44px;
        border-radius: 18px;
        font-weight: 800;
        text-decoration: none !important;
        font-size: 17px;
        box-shadow: 0 12px 30px -5px rgba(59, 130, 246, 0.35);
        transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        gap: 12px;
    }
    .btn-aura:hover { transform: translateY(-5px); box-shadow: 0 25px 40px -10px rgba(59, 130, 246, 0.4); background: #2563eb; }

    .btn-glass-light {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        color: #1e293b !important;
        padding: 20px 40px;
        border-radius: 18px;
        font-weight: 800;
        text-decoration: none !important;
        font-size: 17px;
        border: 1px solid rgba(226, 232, 240, 1);
        transition: 0.3s;
    }
    .btn-glass-light:hover { background: #ffffff; border-color: #3b82f6; transform: translateY(-3px); }

    .hero-image-content { position: relative; z-index: 5; }
    .image-stage {
        position: relative;
        width: 100%;
        max-width: 600px;
        aspect-ratio: 1;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .stage-glow {
        position: absolute;
        width: 80%;
        height: 80%;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%);
        filter: blur(60px);
        border-radius: 50%;
        animation: glowPulse 8s infinite ease-in-out;
    }

    @keyframes glowPulse { 0%, 100% { transform: scale(1); opacity: 0.4; } 50% { transform: scale(1.2); opacity: 0.7; } }

    .product-hero-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        filter: drop-shadow(0 30px 60px rgba(0,0,0,0.12));
        transition: 0.8s cubic-bezier(0.23, 1, 0.32, 1);
        animation: floatImg 7s infinite ease-in-out;
        z-index: 2;
    }

    @keyframes floatImg { 0%, 100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-30px) rotate(3deg); } }

    .hero-stat-card {
        position: absolute;
        top: 15%;
        right: -10%;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(25px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        padding: 18px 28px;
        border-radius: 24px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 15px 45px rgba(0,0,0,0.06);
        animation: floatStat 8s ease-in-out infinite;
        z-index: 10;
    }
    @keyframes floatStat { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(-15px, -20px); } }

    .stat-icon { font-size: 22px; background: #eff6ff; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 14px; }
    .stat-text { color: #1e293b; font-weight: 800; font-size: 15px; }

    .hero-dots {
        position: absolute;
        bottom: 50px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 14px;
        z-index: 100;
    }
    .dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #cbd5e1;
        border: none;
        cursor: pointer;
        transition: 0.4s;
    }
    .dot.active { background: #3b82f6; width: 40px; border-radius: 6px; }

    .hero-ssr-placeholder { min-height: 800px; display: flex; align-items: center; background: #f8fafc; }
</style>
@endpush

<v-gadget-hero :products='@json($heroData)'>
    <div class="hero-ssr-placeholder">
        <div class="gadget-container">
            <h1 style="color: #1e293b; font-size: 60px; font-weight: 900; opacity: 0.5; text-align: center; width: 100%;">Innovating...</h1>
        </div>
    </div>
</v-gadget-hero>

@pushOnce('scripts')
<script type="text/x-template" id="v-gadget-hero-template">
    <div class="gadget-hero-wrapper" @mouseenter="pause" @mouseleave="start">
        <div class="hero-mesh">
            <div class="mesh-blob mesh-1"></div>
            <div class="mesh-blob mesh-2"></div>
            <div class="mesh-blob mesh-3"></div>
        </div>

        <div class="gadget-hero-slider">
            <transition-group name="slide-fade" tag="div" class="hero-slides-inner">
                <div class="hero-slide" v-for="(p, i) in products" :key="i" v-show="currentIndex === i">
                    <div class="hero-inner-grid">
                        <div class="hero-text-content">
                            <div class="hero-tag-wrap"><span class="hero-tag">Future Tech 2026</span></div>
                            <h1 v-if="p.cms" class="hero-main-title">
                                <span class="gradient-title">@{{ p.headline }}</span>
                            </h1>
                            <h1 v-else class="hero-main-title">
                                <span class="gradient-title">Limitless</span><br/>
                                <span class="highlight-text">Innovation.</span>
                            </h1>
                            <p class="hero-description">@{{ p.body || `The future is here with ${p.name}. Designed for those who demand excellence, precision, and unparalleled style.` }}</p>
                            <div class="hero-cta-row">
                                <a :href="p.primary_url || ('{{ url('/') }}/products/' + p.url_key)" class="btn-aura">
                                    @{{ p.primary_label || 'Explore Now' }}
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                </a>
                                <a :href="p.secondary_url || '{{ route('shop.search.index') }}'" class="btn-glass-light">@{{ p.secondary_label || 'View Catalog' }}</a>
                            </div>
                        </div>
                        <div class="hero-image-content">
                            <div class="image-stage">
                                <div class="stage-glow"></div>
                                <img :src="p.image" :alt="p.name" class="product-hero-img">
                                <div class="hero-stat-card">
                                    <div class="stat-icon">✨</div>
                                    <div class="stat-text">Certified Premium</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition-group>

            <div class="hero-dots">
                <button v-for="(p, i) in products" :key="i" @click="currentIndex = i" :class="{ active: currentIndex === i }" class="dot"></button>
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
        methods: {
            start() { this.timer = setInterval(() => { this.currentIndex = (this.currentIndex + 1) % this.products.length; }, 7000); },
            pause() { clearInterval(this.timer); }
        }
    });
</script>
@endpushOnce
