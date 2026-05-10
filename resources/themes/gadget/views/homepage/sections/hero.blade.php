@php
    $heroProducts = collect($products)->take(5)->values();
    
    // Premium Gadget Images from Unsplash
    $placeholderImages = [
        'https://images.unsplash.com/photo-1546868871-7041f2a55e12?auto=format&fit=crop&q=80&w=1200', // Apple Watch
        'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&q=80&w=1200', // Headphones
        'https://images.unsplash.com/photo-1622979135225-d2ba269cf1ac?auto=format&fit=crop&q=80&w=1200', // VR
        'https://images.unsplash.com/photo-1583394838336-acd977736f90?auto=format&fit=crop&q=80&w=1200', // Controller
        'https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?auto=format&fit=crop&q=80&w=1200', // Laptop
    ];

    $heroData = $heroProducts->map(function($product, $index) use ($placeholderImages) {
        return [
            'name' => $product['name'] ?? 'Next-Gen Gadget',
            'image' => $placeholderImages[$index % count($placeholderImages)],
            'url_key' => $product['url_key'] ?? '#',
            'formatted_price' => $product['formatted_price'] ?? '$199.00',
        ];
    });
@endphp

@push('styles')
<style>
    /* DARK PREMIUM DESIGN - "ECLIPSE" */
    .gadget-hero-wrapper {
        position: relative !important;
        min-height: 800px !important;
        background: #050505 !important; /* Deepest black */
        overflow: hidden !important;
        display: flex !important;
        align-items: center !important;
        width: 100% !important;
        color: #ffffff !important;
    }

    /* Mesh Gradient Background */
    .hero-mesh {
        position: absolute;
        inset: 0;
        z-index: 1;
        opacity: 0.6;
        pointer-events: none;
    }
    .mesh-blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(140px);
        animation: meshMove 30s infinite alternate;
    }
    .mesh-1 { width: 800px; height: 800px; background: radial-gradient(circle, #ff4b37 0%, transparent 70%); top: -20%; right: -10%; opacity: 0.3; }
    .mesh-2 { width: 600px; height: 600px; background: radial-gradient(circle, #33d6c5 0%, transparent 70%); bottom: -10%; left: -5%; opacity: 0.2; }
    .mesh-3 { width: 400px; height: 400px; background: radial-gradient(circle, #f6cc3d 0%, transparent 70%); top: 40%; left: 30%; opacity: 0.15; }

    @keyframes meshMove {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(100px, 50px) scale(1.2); }
    }

    .gadget-hero-slider { position: relative !important; z-index: 2 !important; width: 100% !important; }

    .hero-inner-grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        align-items: center !important;
        gap: 80px !important;
        padding-block: 100px !important;
    }

    @media (max-width: 991px) {
        .gadget-hero-wrapper { min-height: auto !important; padding-block: 60px !important; }
        .hero-inner-grid { grid-template-columns: 1fr !important; text-align: center !important; gap: 40px !important; }
        .hero-image-content { order: -1 !important; }
        .hero-main-title { font-size: 42px !important; }
        .hero-description { font-size: 16px !important; margin-inline: auto !important; }
    }

    /* Text Content */
    .hero-tag-wrap { margin-bottom: 32px; }
    .hero-tag {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        color: #ff4b37;
        padding: 8px 24px;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.2em;
        border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .hero-main-title {
        font-size: clamp(52px, 7vw, 100px);
        font-weight: 950;
        line-height: 0.88;
        color: #ffffff !important;
        margin-bottom: 36px;
        letter-spacing: -0.06em;
    }

    .gradient-title {
        background: linear-gradient(135deg, #ffffff 30%, rgba(255,255,255,0.4) 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .highlight-text {
        color: #ff4b37 !important;
        position: relative;
        display: inline-block;
    }
    .highlight-text::after {
        content: "";
        position: absolute;
        bottom: 10%;
        left: 0;
        width: 100%;
        height: 12px;
        background: rgba(255, 75, 55, 0.2);
        z-index: -1;
    }

    .hero-description {
        font-size: 20px;
        color: rgba(255, 255, 255, 0.7);
        max-width: 580px;
        line-height: 1.7;
        margin-bottom: 50px;
        font-weight: 400;
    }

    .hero-cta-row { display: flex; gap: 20px; flex-wrap: wrap; }
    @media (max-width: 991px) { .hero-cta-row { justify-content: center; } }

    .btn-glow {
        background: #ff4b37;
        color: #ffffff !important;
        padding: 22px 48px;
        border-radius: 100px;
        font-weight: 800;
        text-decoration: none !important;
        font-size: 17px;
        box-shadow: 0 0 40px rgba(255, 75, 55, 0.4);
        transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .btn-glow:hover { transform: translateY(-8px) scale(1.05); box-shadow: 0 0 60px rgba(255, 75, 55, 0.6); }

    .btn-glass {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        color: #ffffff !important;
        padding: 22px 40px;
        border-radius: 100px;
        font-weight: 800;
        text-decoration: none !important;
        font-size: 17px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: 0.3s;
    }
    .btn-glass:hover { background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.3); }

    /* Art Side */
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
        width: 70%;
        height: 70%;
        background: #ff4b37;
        filter: blur(100px);
        opacity: 0.3;
        border-radius: 50%;
        animation: glowPulse 5s infinite ease-in-out;
    }

    @keyframes glowPulse { 0%, 100% { transform: scale(1); opacity: 0.3; } 50% { transform: scale(1.2); opacity: 0.5; } }

    .product-hero-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        filter: drop-shadow(0 0 50px rgba(0,0,0,0.5));
        transition: 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    /* Floating Stats in Glass */
    .hero-stat-card {
        position: absolute;
        bottom: 10%;
        right: 0;
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(25px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 20px 30px;
        border-radius: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
        animation: floatStat 5s ease-in-out infinite;
    }
    @keyframes floatStat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }

    .stat-icon { font-size: 24px; color: #ff4b37; }
    .stat-text { color: #fff; font-weight: 800; font-size: 15px; }

    /* Pagination */
    .hero-dots {
        position: absolute;
        bottom: 50px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 15px;
        z-index: 100;
    }
    .dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        border: none;
        cursor: pointer;
        transition: 0.4s;
    }
    .dot.active { background: #ff4b37; width: 40px; border-radius: 10px; box-shadow: 0 0 20px rgba(255, 75, 55, 0.5); }

    /* Transitions */
    .slide-up-enter-active, .slide-up-leave-active { transition: all 1s ease; position: absolute; width: 100%; }
    .slide-up-enter-from { opacity: 0; transform: translateY(100px); }
    .slide-up-leave-to { opacity: 0; transform: translateY(-100px); }

    .hero-ssr-placeholder { min-height: 800px; display: flex; align-items: center; background: #050505; }
</style>
@endpush

<v-gadget-hero :products="{{ json_encode($heroData) }}">
    <div class="hero-ssr-placeholder">
        <div class="gadget-container">
            <h1 style="color: #fff; font-size: 80px; font-weight: 950;">Loading Tech...</h1>
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
            <transition-group name="slide-up" tag="div" class="slides">
                <div class="hero-slide" v-for="(p, i) in products" :key="i" v-show="currentIndex === i">
                    <div class="gadget-container hero-inner-grid">
                        <div class="hero-text-content">
                            <div class="hero-tag-wrap"><span class="hero-tag">Edition 2026</span></div>
                            <h1 class="hero-main-title">
                                <span class="gradient-title">Smarter Tech</span><br/>
                                <span class="highlight-text">Better Life.</span>
                            </h1>
                            <p class="hero-description">Discover @{{ p.name }}. Experience the fusion of art and technology with our latest gadget lineup.</p>
                            <div class="hero-cta-row">
                                <a :href="'{{ url('/') }}/products/' + p.url_key" class="btn-glow">Explore Now</a>
                                <a href="{{ route('shop.search.index') }}" class="btn-glass">All Gadgets</a>
                            </div>
                        </div>
                        <div class="hero-image-content">
                            <div class="image-stage">
                                <div class="stage-glow"></div>
                                <img :src="p.image" :alt="p.name" class="product-hero-img">
                                <div class="hero-stat-card">
                                    <span class="stat-icon">⚡</span>
                                    <span class="stat-text">Premium Grade</span>
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
            start() { this.timer = setInterval(() => { this.currentIndex = (this.currentIndex + 1) % this.products.length; }, 6000); },
            pause() { clearInterval(this.timer); }
        }
    });
</script>
@endpushOnce
