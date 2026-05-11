@push('styles')
<style>
    .gadget-featured-dark {
        background: #0f172a !important;
        padding: 100px 0 !important;
        position: relative;
        overflow: hidden;
        color: #ffffff !important;
    }

    .gadget-featured-dark .gadget-section-heading h2 {
        color: #ffffff !important;
        font-size: 48px !important;
        font-weight: 950 !important;
        letter-spacing: -0.04em !important;
    }

    .gadget-featured-dark .gadget-text-link {
        color: #3b82f6 !important;
        font-weight: 700;
        text-decoration: none;
    }

    .carousel-container {
        position: relative;
        margin-top: 50px;
    }

    .carousel-track-wrap {
        overflow: hidden;
        margin: 0 -15px;
        padding: 20px 0;
    }

    .carousel-track {
        display: flex;
        transition: transform 0.8s cubic-bezier(0.23, 1, 0.32, 1);
        gap: 30px;
        padding: 0 15px;
        width: 100%;
    }

    .carousel-item {
        flex: 0 0 calc((100% / var(--items-per-view)) - (30px * (var(--items-per-view) - 1) / var(--items-per-view)));
        min-width: calc((100% / var(--items-per-view)) - (30px * (var(--items-per-view) - 1) / var(--items-per-view)));
        transition: 0.4s;
    }

    @media (max-width: 1200px) {
        .carousel-track { --items-per-view: 3; }
    }
    @media (min-width: 1201px) {
        .carousel-track { --items-per-view: 4; }
    }
    @media (max-width: 991px) {
        .carousel-track { --items-per-view: 2; }
    }
    @media (max-width: 600px) {
        .carousel-track { --items-per-view: 1; }
    }

    .carousel-nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 60px;
        height: 60px;
        background: rgba(68, 25, 255, 1);
        border: 1px solid rgba(35, 255, 255, 0.2);
        border-radius: 50%;
        color: #ffffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 50;
        transition: 0.4s;
        backdrop-filter: blur(15px);
    }

    .carousel-nav-btn:hover:not(:disabled) {
        background: #3b82f6;
        border-color: #3b82f6;
        box-shadow: 0 15px 35px rgba(59, 130, 246, 0.4);
        transform: translateY(-50%) scale(1.1);
    }

    .carousel-nav-btn:disabled {
        opacity: 0.2;
        cursor: default;
        pointer-events: none;
    }

    .btn-prev { left: -30px; }
    .btn-next { right: -30px; }

    @media (max-width: 1400px) {
        .btn-prev { left: 10px; }
        .btn-next { right: 10px; }
    }

    .fade-enter-active, .fade-leave-active { transition: opacity 0.5s ease; }
    .fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
@endpush

<section class="gadget-featured-dark" aria-labelledby="gadget-featured-title">
    <div class="gadget-container">
        <div class="gadget-section-heading">
            <div>
                <h2 id="gadget-featured-title">Featured Picks</h2>
            </div>

            <a href="{{ route('shop.search.index') }}" class="gadget-text-link">
                Explore All
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </a>
        </div>

        @php
            $displayProducts = count($products) > 0 ? $products : array_fill(0, 8, [
                'id' => 0,
                'name' => 'Premium Gadget Spec',
                'short_name' => 'Premium Gadget Spec',
                'url' => '#',
                'image' => bagisto_asset('images/medium-product-placeholder.webp', 'shop'),
                'regular_price' => '$1,350.00',
                'final_price' => '$1,000.00',
                'has_discount' => true,
                'badge' => 'Sale',
                'is_saleable' => false,
            ]);
        @endphp

        <v-gadget-product-carousel :item-count="{{ count($displayProducts) }}">
            <template v-slot:default="{ currentIndex, next, prev, maxIndex, showNav, translateStyle }">
                <div class="carousel-container">
                    <button v-if="showNav" class="carousel-nav-btn btn-prev" @click="prev" :disabled="currentIndex === 0" aria-label="Previous">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>

                    <div class="carousel-track-wrap">
                        <div class="carousel-track" :style="[translateStyle, { '--items-per-view': itemsPerView }]">
                            @foreach ($displayProducts as $product)
                                <div class="carousel-item">
                                    @include('shop::homepage.partials.product-card', ['product' => $product, 'showAction' => true])
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
<script type="module">
    app.component('v-gadget-product-carousel', {
        props: ['itemCount'],
        data() {
            return {
                currentIndex: 0,
                itemsPerView: 4,
            }
        },
        computed: {
            maxIndex() {
                return Math.max(0, this.itemCount - this.itemsPerView);
            },
            showNav() {
                return this.itemCount > this.itemsPerView;
            },
            translateStyle() {
                // Shift by index * (itemWidth + gap)
                return {
                    transform: `translateX(calc(-${this.currentIndex} * (100% / ${this.itemsPerView} + (30px / ${this.itemsPerView} * (${this.itemsPerView} - 1)) )))`
                };
            }
        },
        mounted() {
            this.updateItemsPerView();
            window.addEventListener('resize', this.updateItemsPerView);
        },
        unmounted() {
            window.removeEventListener('resize', this.updateItemsPerView);
        },
        methods: {
            updateItemsPerView() {
                const width = window.innerWidth;
                if (width < 600) this.itemsPerView = 1;
                else if (width < 991) this.itemsPerView = 2;
                else if (width < 1200) this.itemsPerView = 3;
                else this.itemsPerView = 4;
                
                if (this.currentIndex > this.maxIndex) this.currentIndex = this.maxIndex;
            },
            next() {
                if (this.currentIndex < this.maxIndex) this.currentIndex++;
            },
            prev() {
                if (this.currentIndex > 0) this.currentIndex--;
            }
        },
        render() {
            return this.$slots.default({
                currentIndex: this.currentIndex,
                itemsPerView: this.itemsPerView,
                maxIndex: this.maxIndex,
                showNav: this.showNav,
                translateStyle: this.translateStyle,
                next: this.next,
                prev: this.prev
            });
        }
    });
</script>
@endpushOnce