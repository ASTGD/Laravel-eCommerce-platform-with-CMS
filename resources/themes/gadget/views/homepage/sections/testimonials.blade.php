@pushOnce('styles')
<style>
    .gadget-testimonials {
        padding: 120px 0;
        background: #ffffff;
        overflow: hidden;
    }

    .testimonial-carousel-container {
        position: relative;
        margin-top: 60px;
    }

    .testimonial-track-wrap {
        overflow: visible; /* Allow cards to show shadows while clipping track */
    }

    .testimonial-track {
        display: flex;
        transition: transform 0.8s cubic-bezier(0.23, 1, 0.32, 1);
        gap: 30px;
    }

    .testimonial-item {
        flex: 0 0 calc((100% / var(--items-per-view)) - (30px * (var(--items-per-view) - 1) / var(--items-per-view)));
        min-width: calc((100% / var(--items-per-view)) - (30px * (var(--items-per-view) - 1) / var(--items-per-view)));
        transition: 0.5s;
    }

    @media (min-width: 1201px) { .testimonial-track { --items-per-view: 3; } }
    @media (max-width: 1200px) { .testimonial-track { --items-per-view: 2; } }
    @media (max-width: 600px) { .testimonial-track { --items-per-view: 1; } }

    .gadget-testimonial-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 45px;
        border-radius: 40px;
        position: relative;
        transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .gadget-testimonial-card:hover {
        background: #ffffff;
        border-color: #3b82f6;
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 30px 60px rgba(59, 130, 246, 0.08);
    }

    .gadget-quote {
        font-size: 80px;
        color: #3b82f6;
        position: absolute;
        top: 10px;
        right: 40px;
        font-family: serif;
        opacity: 0.1;
        line-height: 1;
    }

    .gadget-testimonial-card p {
        font-size: 17px;
        color: #475569;
        line-height: 1.8;
        margin-bottom: 40px;
        font-style: italic;
        font-weight: 500;
        flex: 1;
    }

    .gadget-testimonial-card__buyer {
        display: flex;
        align-items: center;
        gap: 16px;
        padding-top: 30px;
        border-top: 1px solid #f1f5f9;
    }

    .buyer-avatar {
        width: 54px;
        height: 54px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-weight: 900;
        font-size: 15px;
        box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);
    }

    .gadget-testimonial-card__buyer strong {
        display: block;
        color: #0f172a;
        font-size: 17px;
        font-weight: 800;
    }

    .gadget-testimonial-card__buyer small {
        color: #3b82f6;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* Dots */
    .testimonial-dots {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-top: 50px;
    }

    .testimonial-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #cbd5e1;
        border: none;
        padding: 0;
        cursor: pointer;
        transition: 0.4s;
    }

    .testimonial-dot.active {
        background: #3b82f6;
        width: 35px;
        border-radius: 5px;
    }
</style>
@endpushOnce

<section class="gadget-section gadget-testimonials" aria-labelledby="gadget-testimonials-title">
    <div class="gadget-container">
        <div class="gadget-section-heading" style="text-align: center; justify-content: center; flex-direction: column; align-items: center;">
            <h2 id="gadget-testimonials-title">Trusted by Tech Enthusiasts</h2>
            <p style="color: #64748b; font-size: 18px; margin-top: 10px;">Hear from our community about their experience</p>
        </div>

        @php
            $testimonials = [
                ['Sarah Jenkins', 'SJ', 'The build quality is incredible. Fast delivery and the attention to detail in the design is exactly what I was looking for.'],
                ['Michael Chen', 'MC', 'Ordering was seamless. The premium gadget I received surpassed my expectations in every technical aspect.'],
                ['Elena Rodriguez', 'ER', 'A true futuristic shopping experience. Their customer ecosystem and support are top-notch in the industry.'],
                ['David Smith', 'DS', 'I was skeptical at first, but the delivery speed and product authenticity are unmatched. Highly recommended.'],
                ['Aisha Khan', 'AK', 'The most beautiful tech interface I have used. My smartwatch arrived perfectly calibrated and ready to go.'],
            ];
        @endphp

        <v-testimonial-carousel :count="{{ count($testimonials) }}">
            <template v-slot:default="{ currentIndex, translateStyle, itemsPerView }">
                <div class="testimonial-carousel-container">
                    <div class="testimonial-track-wrap">
                        <div class="testimonial-track" :style="[translateStyle, { '--items-per-view': itemsPerView }]">
                            @foreach ($testimonials as $t)
                                <div class="testimonial-item">
                                    <article class="gadget-testimonial-card">
                                        <span class="gadget-quote">“</span>
                                        <p>{{ $t[2] }}</p>
                                        <div class="gadget-testimonial-card__buyer">
                                            <div class="buyer-avatar">{{ $t[1] }}</div>
                                            <div>
                                                <strong>{{ $t[0] }}</strong>
                                                <small>Verified Explorer</small>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="testimonial-dots">
                        <button 
                            v-for="n in Math.ceil({{ count($testimonials) }} / itemsPerView)" 
                            :key="n"
                            @click="setIndex((n-1) * itemsPerView)"
                            class="testimonial-dot"
                            :class="{ active: Math.floor(currentIndex / itemsPerView) === (n-1) }"
                        ></button>
                    </div>
                </div>
            </template>
        </v-testimonial-carousel>
    </div>
</section>

@pushOnce('scripts')
<script type="module">
    app.component('v-testimonial-carousel', {
        props: ['count'],
        data() {
            return {
                currentIndex: 0,
                itemsPerView: 3,
                timer: null
            }
        },
        computed: {
            maxIndex() {
                return Math.max(0, this.count - this.itemsPerView);
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
            this.startAutoPlay();
        },
        unmounted() {
            window.removeEventListener('resize', this.updateItemsPerView);
            this.stopAutoPlay();
        },
        methods: {
            updateItemsPerView() {
                const width = window.innerWidth;
                if (width < 600) this.itemsPerView = 1;
                else if (width < 1201) this.itemsPerView = 2;
                else this.itemsPerView = 3;
                
                if (this.currentIndex > this.maxIndex) this.currentIndex = this.maxIndex;
            },
            startAutoPlay() {
                this.timer = setInterval(() => {
                    if (this.currentIndex >= this.maxIndex) {
                        this.currentIndex = 0;
                    } else {
                        this.currentIndex++;
                    }
                }, 5000);
            },
            stopAutoPlay() {
                clearInterval(this.timer);
            },
            setIndex(index) {
                this.currentIndex = Math.min(index, this.maxIndex);
                this.stopAutoPlay();
                this.startAutoPlay();
            }
        },
        render() {
            return this.$slots.default({
                currentIndex: this.currentIndex,
                itemsPerView: this.itemsPerView,
                translateStyle: this.translateStyle,
                setIndex: this.setIndex
            });
        }
    });
</script>
@endpushOnce
