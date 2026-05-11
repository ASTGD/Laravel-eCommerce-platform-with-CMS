@pushOnce('styles')
<style>
    .gadget-featured-dark {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at 12% 18%, rgba(255,79,112,.32), transparent 24%),
            radial-gradient(circle at 82% 10%, rgba(85,215,255,.22), transparent 22%),
            #171114;
        color: #fffdfa;
        padding: clamp(70px, 8vw, 112px) 0;
    }

    .gadget-featured-dark .gadget-section-heading h2 { color: #fffdfa; }
    .gadget-featured-dark .gadget-section-heading p { color: rgba(255,253,250,.68); }
    .gadget-featured-dark .gadget-text-link { color: #c8ff4d; }

    .carousel-container { position: relative; margin-top: 34px; }
    .carousel-track-wrap { overflow: hidden; margin-inline: -14px; padding: 10px 14px 24px; }
    .carousel-track { display: flex; transition: transform .7s cubic-bezier(.23,1,.32,1); gap: 28px; }
    .carousel-item { flex: 0 0 calc(25% - 21px); min-width: calc(25% - 21px); }

    .gadget-featured-dark .gadget-product-card__body h3 a,
    .gadget-featured-dark .gadget-price--final { color: #fffdfa; }
    .gadget-featured-dark .gadget-price--regular { color: rgba(255,253,250,.45); }

    .carousel-nav-btn {
        position: absolute;
        top: 38%;
        width: 52px;
        height: 52px;
        border: 0;
        border-radius: 50%;
        color: #171114;
        background: #c8ff4d;
        display: grid;
        place-items: center;
        cursor: pointer;
        z-index: 5;
        transition: transform .25s ease, opacity .25s ease;
        box-shadow: 0 16px 34px rgba(0,0,0,.18);
    }

    .carousel-nav-btn:hover:not(:disabled) { transform: translateY(-2px) scale(1.05); }
    .carousel-nav-btn:disabled { opacity: .25; cursor: default; }
    .btn-prev { left: -26px; }
    .btn-next { right: -26px; }

    .fade-enter-active, .fade-leave-active { transition: opacity .35s ease; }
    .fade-enter-from, .fade-leave-to { opacity: 0; }

    @media (max-width: 1200px) { .carousel-item { flex-basis: calc(33.333% - 19px); min-width: calc(33.333% - 19px); } }
    @media (max-width: 860px) { .carousel-item { flex-basis: calc(50% - 14px); min-width: calc(50% - 14px); } .btn-prev { left: 8px; } .btn-next { right: 8px; } }
    @media (max-width: 560px) { .carousel-item { flex-basis: 100%; min-width: 100%; } }
</style>
@endPushOnce

<section class="gadget-featured-dark" aria-labelledby="gadget-featured-title">
    <div class="gadget-container">
        <div class="gadget-section-heading">
            <div>
                <h2 id="gadget-featured-title">Editor’s rack</h2>
                <p>Color-forward favorites chosen for effortless styling and repeat wear.</p>
            </div>

            <a href="{{ route('shop.search.index') }}" class="gadget-text-link">
                Explore All
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>

        @php
            $displayProducts = count($products) > 0 ? $products : array_fill(0, 8, [
                'id' => 0,
                'name' => 'Color Block Outfit',
                'short_name' => 'Color Block Outfit',
                'url' => '#',
                'image' => bagisto_asset('images/medium-product-placeholder.webp', 'shop'),
                'regular_price' => '৳2,200',
                'final_price' => '৳1,790',
                'has_discount' => true,
                'badge' => 'Sale',
                'is_saleable' => false,
            ]);
        @endphp

        <v-gadget-product-carousel :item-count="{{ count($displayProducts) }}">
            <template v-slot:default="{ currentIndex, step, next, prev, maxIndex, showNav }">
                <div class="carousel-container">
                    <transition name="fade">
                        <button v-if="showNav" class="carousel-nav-btn btn-prev" @click="prev" :disabled="currentIndex === 0" aria-label="Previous"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
                    </transition>

                    <div class="carousel-track-wrap">
                        <div class="carousel-track" :style="{ transform: `translateX(-${currentIndex * step}%)` }">
                            @foreach ($displayProducts as $product)
                                <div class="carousel-item">
                                    @include('shop::homepage.partials.product-card', ['product' => $product])
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <transition name="fade">
                        <button v-if="showNav" class="carousel-nav-btn btn-next" @click="next" :disabled="currentIndex >= maxIndex" aria-label="Next"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
                    </transition>
                </div>
            </template>
        </v-gadget-product-carousel>
    </div>
</section>

@pushOnce('scripts')
<script type="module">
    app.component('v-gadget-product-carousel', {
        props: ['itemCount'],
        data() { return { currentIndex: 0, itemsPerView: 4 } },
        computed: {
            maxIndex() { return Math.max(0, this.itemCount - this.itemsPerView); },
            step() { return 100 / this.itemsPerView; },
            showNav() { return this.itemCount > this.itemsPerView; }
        },
        mounted() { this.updateItemsPerView(); window.addEventListener('resize', this.updateItemsPerView); },
        unmounted() { window.removeEventListener('resize', this.updateItemsPerView); },
        methods: {
            updateItemsPerView() {
                const width = window.innerWidth;
                if (width < 560) this.itemsPerView = 1;
                else if (width < 860) this.itemsPerView = 2;
                else if (width < 1200) this.itemsPerView = 3;
                else this.itemsPerView = 4;
                if (this.currentIndex > this.maxIndex) this.currentIndex = this.maxIndex;
            },
            next() { if (this.currentIndex < this.maxIndex) this.currentIndex++; },
            prev() { if (this.currentIndex > 0) this.currentIndex--; }
        },
        render() { return this.$slots.default({ currentIndex: this.currentIndex, step: this.step, maxIndex: this.maxIndex, showNav: this.showNav, next: this.next, prev: this.prev }); }
    });
</script>
@endPushOnce
