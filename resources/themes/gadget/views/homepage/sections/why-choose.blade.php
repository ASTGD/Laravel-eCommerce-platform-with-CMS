@php
$_fallbackJfyProds = [
    ['name' => 'Quantum VR Headset', 'formatted_price' => '$499.00', 'image_url' => 'https://images.unsplash.com/photo-1622979135225-d2ba269cf1ac?auto=format&fit=crop&q=80&w=500', 'url_key' => '#'],
    ['name' => 'Sonic ANC Earbuds', 'formatted_price' => '$199.00', 'image_url' => 'https://images.unsplash.com/photo-1590658268037-6f1164d5cd5a?auto=format&fit=crop&q=80&w=500', 'url_key' => '#'],
    ['name' => 'Holographic Smartwatch', 'formatted_price' => '$299.00', 'image_url' => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?auto=format&fit=crop&q=80&w=500', 'url_key' => '#'],
    ['name' => 'Aero Drone Pro', 'formatted_price' => '$899.00', 'image_url' => 'https://images.unsplash.com/photo-1507580461461-8f55e09f58ea?auto=format&fit=crop&q=80&w=500', 'url_key' => '#'],
    ['name' => 'Nebula Smart Speaker', 'formatted_price' => '$149.00', 'image_url' => 'https://images.unsplash.com/photo-1543512214-318c7553f230?auto=format&fit=crop&q=80&w=500', 'url_key' => '#'],
    ['name' => 'Cyber Mechanical Keyboard', 'formatted_price' => '$249.00', 'image_url' => 'https://images.unsplash.com/photo-1595225476474-87563907a212?auto=format&fit=crop&q=80&w=500', 'url_key' => '#'],
];
$_realJfy = collect($products ?? []);
$displayProducts = $_realJfy->isNotEmpty()
    ? $_realJfy->map(fn($p) => [
        'id'            => $p['id'] ?? 0,
        'name'          => $p['name'] ?? '',
        'short_name'    => $p['short_name'] ?? $p['name'] ?? '',
        'url'           => $p['url'] ?? $p['url_key'] ?? '#',
        'image'         => $p['image_url'] ?? $p['image'] ?? $p['base_image_url'] ?? 'https://via.placeholder.com/500',
        'regular_price' => $p['regular_price'] ?? '',
        'final_price'   => $p['final_price'] ?? $p['formatted_price'] ?? $p['price'] ?? '',
        'has_discount'  => $p['has_discount'] ?? false,
        'badge'         => $p['badge'] ?? '',
        'is_saleable'   => $p['is_saleable'] ?? true,
    ])->values()->all()
    : array_map(fn($p) => [
        'id'            => 0,
        'name'          => $p['name'],
        'short_name'    => $p['name'],
        'url'           => $p['url_key'],
        'image'         => $p['image_url'],
        'regular_price' => '',
        'final_price'   => $p['formatted_price'],
        'has_discount'  => false,
        'badge'         => '',
        'is_saleable'   => true,
    ], $_fallbackJfyProds);
@endphp

@pushOnce('styles')
<style>
    .gadget-jfy {
        padding: 100px 0;
        background: #f8fafc;
        position: relative;
        overflow: hidden;
    }

    .gadget-jfy-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 60px;
        position: relative;
        z-index: 10;
    }

    .gadget-jfy-title p {
        color: #3b82f6;
        font-weight: normal;
        text-transform: uppercase;
        letter-spacing: 0.35em;
        font-size: 13px;
        margin-bottom: 15px;
    }

    .gadget-jfy-title h2 {
        font-size: 56px;
        font-weight: normal;
        letter-spacing: -0.05em;
        line-height: 1.1;
        background: linear-gradient(135deg, #0f172a 0%, #3b82f6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .gadget-jfy-controls {
        display: flex;
        gap: 15px;
    }

    .jfy-nav-btn {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #0f172a;
        transition: 0.4s cubic-bezier(0.23, 1, 0.32, 1);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    .jfy-nav-btn:hover {
        background: #3b82f6;
        color: #ffffff;
        border-color: #3b82f6;
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 15px 30px rgba(59, 130, 246, 0.3);
    }

    /* Slider Track */
    .jfy-slider-container {
        position: relative;
        width: 100%;
        max-width: 1920px;
        margin: 0 auto;
        padding: 20px 5% 40px;
    }

    .jfy-slider-track {
        display: flex;
        gap: 30px;
        overflow-x: auto;
        scroll-behavior: smooth;
        -ms-overflow-style: none; /* IE and Edge */
        scrollbar-width: none; /* Firefox */
        padding-bottom: 20px; /* Space for shadow */
    }

    .jfy-slider-track::-webkit-scrollbar {
        display: none;
    }

    /* Product Card */
    .jfy-product-card {
        flex: 0 0 320px;
        background: #ffffff;
        border-radius: 30px;
        padding: 20px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        transition: 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        box-shadow: 0 15px 35px rgba(15, 23, 42, 0.03);
        display: flex;
        flex-direction: column;
        group;
    }

    .jfy-product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 30px 60px rgba(15, 23, 42, 0.08);
        border-color: rgba(59, 130, 246, 0.2);
    }

    .jfy-product-img-wrap {
        width: 100%;
        height: 280px;
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        background: #f1f5f9;
        margin-bottom: 20px;
    }

    .jfy-product-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: 0.8s cubic-bezier(0.23, 1, 0.32, 1);
    }

    .jfy-product-card:hover .jfy-product-img {
        transform: scale(1.1);
    }

    .jfy-product-info {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .jfy-product-title {
        font-size: 20px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 10px;
        line-height: 1.3;
        letter-spacing: -0.02em;
        text-decoration: none;
        transition: 0.3s;
    }

    .jfy-product-title:hover {
        color: #3b82f6;
    }

    .jfy-product-price {
        font-size: 18px;
        font-weight: 900;
        color: #3b82f6;
        margin-bottom: 20px;
    }

    .jfy-btn-add {
        margin-top: auto;
        background: #f8fafc;
        color: #0f172a;
        padding: 15px 0;
        border-radius: 16px;
        font-weight: 800;
        font-size: 15px;
        text-align: center;
        text-decoration: none;
        transition: 0.4s cubic-bezier(0.23, 1, 0.32, 1);
        border: 1px solid rgba(15, 23, 42, 0.05);
    }

    .jfy-product-card:hover .jfy-btn-add {
        background: #0f172a;
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.15);
    }

    @media (max-width: 991px) {
        .gadget-jfy-header { flex-direction: column; align-items: flex-start; gap: 30px; }
        .gadget-jfy-title h2 { font-size: 42px; }
        .jfy-product-card { flex: 0 0 280px; }
    }
</style>
@endpushOnce

<section class="gadget-section gadget-jfy" aria-labelledby="gadget-jfy-title">
    <div class="gadget-container">
        <div class="gadget-jfy-header">
            <div class="gadget-jfy-title">
                <p>Personalized Picks</p>
                <h2 id="gadget-jfy-title">Just For You</h2>
            </div>
            <a href="{{ route('shop.search.index') }}" style="color: #3b82f6; text-decoration: none; font-size: 18px; display: inline-flex; align-items: center; gap: 8px; font-weight: 500; transition: 0.3s;" onmouseover="this.style.color='#1d4ed8'" onmouseout="this.style.color='#3b82f6'">
                View all product
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </a>
        </div>

    <!-- Product Slider -->
    <v-gadget-product-carousel :item-count="{{ count($displayProducts) }}">
        <template v-slot:default="{ currentIndex, next, prev, maxIndex, showNav, translateStyle, stopAutoplay, startAutoplay }">
            <div class="carousel-container" @mouseenter="stopAutoplay" @mouseleave="startAutoplay">
                <button v-if="showNav" class="carousel-nav-btn btn-prev" @click="prev" :disabled="currentIndex === 0" aria-label="Previous">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>

                <div class="carousel-track-wrap">
                    <div class="carousel-track" :style="[translateStyle, { '--items-per-view': itemsPerView }]">
                        @foreach ($displayProducts as $product)
                            <div class="carousel-item">
                                @include('shop::homepage.partials.product-card', ['product' => $product, 'mode' => 'jfy', 'showAction' => true])
                            </div>
                        @endforeach
                    </div>
                </div>

                <button v-if="showNav" class="carousel-nav-btn btn-next" @click="next" :disabled="currentIndex >= maxIndex" aria-label="Next">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
        </template>
    </v-gadget-product-carousel>
    </div>
</section>

@pushOnce('scripts')
<script>
    (function () {
        let autoPlayInterval;

        function getScrollAmount(track) {
            const card = track.querySelector('.jfy-product-card');
            return card ? card.offsetWidth + 30 : 350;
        }

        function startAutoPlay() {
            clearInterval(autoPlayInterval);
            autoPlayInterval = setInterval(() => {
                const track = document.getElementById('jfy-slider-track');
                if (!track) return;
                
                const amt = getScrollAmount(track);
                if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 10) {
                    track.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    track.scrollBy({ left: amt, behavior: 'smooth' });
                }
            }, 4000);
        }

        // Initialize autoplay
        startAutoPlay();

        // Event Delegation for clicks (survives Vue re-renders)
        document.addEventListener('click', (e) => {
            const btnPrev = e.target.closest('#jfy-btn-prev');
            const btnNext = e.target.closest('#jfy-btn-next');
            
            if (btnPrev || btnNext) {
                const track = document.getElementById('jfy-slider-track');
                if (!track) return;
                
                if (btnPrev) track.scrollBy({ left: -getScrollAmount(track), behavior: 'smooth' });
                if (btnNext) track.scrollBy({ left: getScrollAmount(track), behavior: 'smooth' });
                
                startAutoPlay(); // Reset timer on manual navigation
            }
        });

        // Pause on hover
        document.addEventListener('mouseover', (e) => {
            if (e.target.closest('#jfy-slider-track')) clearInterval(autoPlayInterval);
        });

        // Resume on hover out
        document.addEventListener('mouseout', (e) => {
            if (e.target.closest('#jfy-slider-track')) startAutoPlay();
        });
    })();
</script>
@endpushOnce
