@php
$_heroProds = collect($products ?? [])->take(3)->values();
$_searchUrl  = route('shop.search.index');
$heroSlides = [
[
'theme'      => 'dark',
'tag'        => 'Future Tech 2026',
'headline'   => 'See Beyond',
'highlight'  => 'Reality.',
'sub'        => 'Immerse yourself in next-generation VR. Experience worlds crafted for the bold and the visionary.',
'cta_label'  => 'Explore Now',
'cta2_label' => 'View Catalog',
'badge'      => '🏆 Editor\'s Choice',
'image'      => 'images/1.png',
'url'        => $_heroProds->get(0)['url'] ?? $_searchUrl,
'bg_from'    => '#0d0221',
'bg_to'      => '#1a0533',
'accent'     => '#a855f7',
],
[
'theme'      => 'light',
'tag'        => 'Sonic Excellence',
'headline'   => 'Hear Every',
'highlight'  => 'Detail.',
'sub'        => 'Studio-grade audio meets everyday comfort. The pro headphones that redefine what music feels like.',
'cta_label'  => 'Shop Now',
'cta2_label' => 'Compare Models',
'badge'      => '⭐ #1 Bestseller',
'image'      => 'images/2.png',
'url'        => $_heroProds->get(1)['url'] ?? $_searchUrl,
'bg_from'    => '#f0f9ff',
'bg_to'      => '#dbeafe',
'accent'     => '#0ea5e9',
],
[
'theme'      => 'dark',
'tag'        => 'Precision Wearables',
'headline'   => 'Time, Elevated',
'highlight'  => 'Differently.',
'sub'        => 'Track performance, monitor health, and stay connected — all from the most intelligent device on your wrist.',
'cta_label'  => 'Discover More',
'cta2_label' => 'See Features',
'badge'      => '💎 Premium Pick',
'image'      => 'images/3.png',
'url'        => $_heroProds->get(2)['url'] ?? $_searchUrl,
'bg_from'    => '#0f172a',
'bg_to'      => '#1e293b',
'accent'     => '#38bdf8',
],
];
@endphp

@push('styles')
<style>
    /* ── HERO WRAPPER ── */
    .gh-wrapper {
        position: relative;
        min-height: 90vh;
        overflow: hidden;
        display: flex;
        align-items: center;
        transition: background 0.9s cubic-bezier(0.4, 0, 0.2, 1);
        font-family: 'Inter', system-ui, sans-serif;
    }

    /* ── NOISE TEXTURE OVERLAY ── */
    .gh-noise {
        position: absolute;
        inset: 0;
        opacity: 0.04;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
        pointer-events: none;
        z-index: 1;
    }

    /* ── GRADIENT ORBS ── */
    .gh-orbs {
        position: absolute;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        overflow: hidden;
    }

    .gh-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(120px);
        opacity: 0;
        transition: opacity 0.9s ease, background 0.9s ease;
        animation: orbDrift 20s infinite alternate ease-in-out;
    }

    .gh-orb-1 {
        width: 600px;
        height: 600px;
        top: -15%;
        right: -5%;
    }

    .gh-orb-2 {
        width: 400px;
        height: 400px;
        bottom: -10%;
        left: -5%;
        animation-delay: -8s;
    }

    .gh-orbs.active .gh-orb {
        opacity: 1;
    }

    @keyframes orbDrift {
        0% {
            transform: translate(0, 0) scale(1);
        }

        100% {
            transform: translate(40px, 30px) scale(1.15);
        }
    }

    /* ── SLIDE CONTAINER ── */
    .gh-slider {
        position: relative;
        z-index: 10;
        width: 100%;
    }

    .gh-slides-track {
        position: relative;
        min-height: 600px;
        display: flex;
        align-items: center;
    }

    /* ── INDIVIDUAL SLIDE ── */
    .gh-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0s;
    }

    .gh-slide.is-active {
        opacity: 1;
        position: relative;
        pointer-events: auto;
        transition: opacity 0.5s ease;
    }

    /* ── GRID ── */
    .gh-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        align-items: center;
        gap: 60px;
        width: 100%;
        max-width: 1300px;
        margin: 0 auto;
        padding: 80px 48px;
    }

    @media (max-width: 991px) {
        .gh-wrapper {
            min-height: auto;
        }

        .gh-grid {
            grid-template-columns: 1fr;
            text-align: center;
            gap: 40px;
            padding: 60px 24px;
        }

        .gh-img-col {
            order: -1;
        }

        .gh-cta-row {
            justify-content: center;
        }
    }

    /* ── TEXT COLUMN ANIMATIONS ── */
    .gh-tag-wrap {
        margin-bottom: 20px;
    }

    .gh-tag {
        display: inline-block;
        padding: 6px 18px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        border: 1px solid currentColor;
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s 0.1s ease, transform 0.5s 0.1s ease;
    }

    .gh-slide.is-active .gh-tag {
        opacity: 1;
        transform: translateY(0);
    }

    .gh-headline {
        font-size: clamp(48px, 6vw, 88px);
        font-weight: 900;
        line-height: 0.95;
        letter-spacing: -0.04em;
        margin: 0 0 12px;
        opacity: 0;
        transform: translateY(30px);
        transition: opacity 0.55s 0.2s ease, transform 0.55s 0.2s ease;
    }

    .gh-slide.is-active .gh-headline {
        opacity: 1;
        transform: translateY(0);
    }

    .gh-highlight {
        display: block;
        opacity: 0;
        transform: translateY(30px);
        transition: opacity 0.55s 0.3s ease, transform 0.55s 0.3s ease;
    }

    .gh-slide.is-active .gh-highlight {
        opacity: 1;
        transform: translateY(0);
    }

    .gh-sub {
        font-size: 18px;
        line-height: 1.75;
        max-width: 520px;
        margin-bottom: 40px;
        font-weight: 400;
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.55s 0.4s ease, transform 0.55s 0.4s ease;
    }

    .gh-slide.is-active .gh-sub {
        opacity: 1;
        transform: translateY(0);
    }

    /* ── CTA ROW ── */
    .gh-cta-row {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.55s 0.5s ease, transform 0.55s 0.5s ease;
    }

    .gh-slide.is-active .gh-cta-row {
        opacity: 1;
        transform: translateY(0);
    }

    /* PRIMARY BUTTON */
    .gh-btn-primary {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 18px 40px;
        border-radius: 16px;
        font-size: 16px;
        font-weight: 800;
        text-decoration: none !important;
        overflow: hidden;
        border: none;
        cursor: pointer;
        color: #fff !important;
        transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.35s ease;
        z-index: 1;
    }

    .gh-btn-primary::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, transparent 60%);
        z-index: 2;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .gh-btn-primary::after {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: 18px;
        background: inherit;
        filter: blur(20px);
        opacity: 0;
        z-index: -1;
        transition: opacity 0.4s ease;
    }

    .gh-btn-primary:hover {
        transform: translateY(-4px);
    }

    .gh-btn-primary:hover::before {
        opacity: 1;
    }

    .gh-btn-primary:hover::after {
        opacity: 0.5;
    }

    .gh-btn-arrow {
        display: inline-flex;
        align-items: center;
        transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .gh-btn-primary:hover .gh-btn-arrow {
        transform: translateX(6px);
    }

    /* SECONDARY BUTTON */
    .gh-btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 17px 36px;
        border-radius: 16px;
        font-size: 16px;
        font-weight: 700;
        text-decoration: none !important;
        border: 1.5px solid currentColor;
        transition: transform 0.3s ease, background 0.3s ease;
    }

    .gh-btn-secondary:hover {
        transform: translateY(-3px);
    }

    /* ── IMAGE COLUMN ── */
    .gh-img-col {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .gh-img-stage {
        position: relative;
        width: 100%;
        max-width: 560px;
        aspect-ratio: 1 / 1;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    /* Animated glow ring */
    .gh-img-ring {
        position: absolute;
        width: 80%;
        height: 80%;
        border-radius: 50%;
        opacity: 0;
        transform: scale(0.8);
        transition: opacity 0.7s 0.3s ease, transform 0.7s 0.3s ease;
        animation: ringPulse 6s infinite ease-in-out;
    }

    .gh-slide.is-active .gh-img-ring {
        opacity: 1;
        transform: scale(1);
    }

    @keyframes ringPulse {

        0%,
        100% {
            transform: scale(1);
            opacity: 0.5;
        }

        50% {
            transform: scale(1.12);
            opacity: 0.9;
        }
    }

    /* The product image */
    .gh-img {
        width: 88%;
        height: 88%;
        object-fit: contain;
        position: relative;
        z-index: 3;
        opacity: 0;
        transform: translateX(60px) scale(0.9);
        transition: opacity 0.65s 0.25s cubic-bezier(0.23, 1, 0.32, 1), transform 0.65s 0.25s cubic-bezier(0.23, 1, 0.32, 1);
        animation: imgFloat 8s infinite ease-in-out;
    }

    .gh-slide.is-active .gh-img {
        opacity: 1;
        transform: translateX(0) scale(1);
    }

    @keyframes imgFloat {

        0%,
        100% {
            transform: translateY(0) rotate(0deg);
        }

        50% {
            transform: translateY(-24px) rotate(2deg);
        }
    }

    /* Badge floating card */
    .gh-badge {
        position: absolute;
        top: 10%;
        right: -5%;
        padding: 14px 22px;
        border-radius: 20px;
        backdrop-filter: blur(20px);
        font-weight: 800;
        font-size: 14px;
        white-space: nowrap;
        z-index: 10;
        opacity: 0;
        transform: translateX(20px);
        transition: opacity 0.5s 0.6s ease, transform 0.5s 0.6s ease;
        animation: badgeFloat 7s 1s infinite ease-in-out;
    }

    .gh-slide.is-active .gh-badge {
        opacity: 1;
        transform: translateX(0);
    }

    @keyframes badgeFloat {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    /* ── PROGRESS/DOTS ── */
    .gh-nav {
        position: absolute;
        bottom: 32px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 50;
    }

    .gh-dot {
        width: 10px;
        height: 10px;
        border-radius: 5px;
        background: rgba(255, 255, 255, 0.3);
        border: none;
        cursor: pointer;
        padding: 0;
        transition: width 0.4s ease, background 0.4s ease;
    }

    .gh-dot.is-active {
        width: 36px;
    }

    /* ── PREV / NEXT ARROWS ── */
    .gh-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 50;
        width: 52px;
        height: 52px;
        border-radius: 50%;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(16px);
        transition: transform 0.3s ease, background 0.3s ease;
    }

    .gh-arrow:hover {
        transform: translateY(-50%) scale(1.1);
    }

    .gh-arrow-prev {
        left: 24px;
    }

    .gh-arrow-next {
        right: 24px;
    }

    @media (max-width: 768px) {
        .gh-arrow {
            display: none;
        }
    }

    /* ── PROGRESS BAR ── */
    .gh-progress-bar {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        width: 0%;
        z-index: 50;
        transition: none;
    }

    .gh-progress-bar.animating {
        width: 100%;
        transition: width 4s linear;
    }

    /* SSR placeholder */
    .gh-ssr {
        min-height: 90vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #0f172a;
    }
</style>
@endpush

<v-gadget-hero :slides="{{ json_encode($heroSlides) }}" search-url="{{ route('shop.search.index') }}">
    <div class="gh-ssr">
        <p style="color:rgba(255,255,255,0.3);font-size:18px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;">Loading Experience...</p>
    </div>
</v-gadget-hero>

@pushOnce('scripts')
<script type="text/x-template" id="v-gadget-hero-template">
    <div class="gh-wrapper" :style="wrapperStyle" @mouseenter="pause" @mouseleave="resume">

        <!-- Noise texture -->
        <div class="gh-noise"></div>

        <!-- Animated orbs -->
        <div class="gh-orbs" :class="{ active: ready }">
            <div class="gh-orb gh-orb-1" :style="{ background: currentSlide.accent + '55' }"></div>
            <div class="gh-orb gh-orb-2" :style="{ background: currentSlide.accent + '33' }"></div>
        </div>

        <!-- SLIDES -->
        <div class="gh-slider">
            <div class="gh-slides-track" :style="{ minHeight: trackHeight + 'px' }">
                <div
                    v-for="(slide, i) in slides"
                    :key="i"
                    class="gh-slide"
                    :class="{ 'is-active': current === i }"
                    :ref="'slide' + i"
                >
                    <div class="gh-grid">

                        <!-- LEFT: TEXT -->
                        <div class="gh-text-col">
                            <div class="gh-tag-wrap">
                                <span class="gh-tag" :style="tagStyle(slide)">@{{ slide.tag }}</span>
                            </div>

                            <h1 class="gh-headline" :style="{ color: textColor(slide) }">
                                @{{ slide.headline }}
                                <span class="gh-highlight" :style="{ color: slide.accent }">@{{ slide.highlight }}</span>
                            </h1>

                            <p class="gh-sub" :style="{ color: subColor(slide) }">@{{ slide.sub }}</p>

                            <div class="gh-cta-row">
                                <a
                                    :href="slide.url || searchUrl"
                                    class="gh-btn-primary"
                                    :style="primaryBtnStyle(slide)"
                                >
                                    @{{ slide.cta_label }}
                                    <span class="gh-btn-arrow">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="5" y1="12" x2="19" y2="12"/>
                                            <polyline points="12 5 19 12 12 19"/>
                                        </svg>
                                    </span>
                                </a>
                                <a
                                    :href="slide.url || searchUrl"
                                    class="gh-btn-secondary"
                                    :style="secondaryBtnStyle(slide)"
                                >
                                    @{{ slide.cta2_label }}
                                </a>
                            </div>
                        </div>

                        <!-- RIGHT: IMAGE -->
                        <div class="gh-img-col">
                            <div class="gh-img-stage">
                                <div class="gh-img-ring" :style="ringStyle(slide)"></div>
                                <img
                                    :src="slide.image"
                                    :alt="slide.headline + ' ' + slide.highlight"
                                    class="gh-img"
                                    :style="imgBlend(slide)"
                                    loading="lazy"
                                />
                                <div class="gh-badge" :style="badgeStyle(slide)">@{{ slide.badge }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PROGRESS BAR -->
            <div class="gh-progress-bar" :class="{ animating: progressing }" :style="{ background: currentSlide.accent }"></div>
        </div>

        <!-- ARROWS -->
        <button class="gh-arrow gh-arrow-prev" :style="arrowStyle" @click="prev" aria-label="Previous">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>
        <button class="gh-arrow gh-arrow-next" :style="arrowStyle" @click="next" aria-label="Next">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 6 15 12 9 18"/>
            </svg>
        </button>

        <!-- DOTS -->
        <div class="gh-nav">
            <button
                v-for="(slide, i) in slides"
                :key="i"
                class="gh-dot"
                :class="{ 'is-active': current === i }"
                :style="dotStyle(i)"
                @click="goTo(i)"
                :aria-label="'Slide ' + (i + 1)"
            ></button>
        </div>
    </div>
</script>

<script type="module">
    app.component('v-gadget-hero', {
        template: '#v-gadget-hero-template',
        props: {
            slides: {
                type: Array,
                required: true
            },
            searchUrl: {
                type: String,
                default: '#'
            }
        },
        data() {
            return {
                current: 0,
                timer: null,
                progressing: false,
                ready: false,
                trackHeight: 600,
            };
        },
        computed: {
            currentSlide() {
                return this.slides[this.current];
            },
            wrapperStyle() {
                const s = this.currentSlide;
                return {
                    background: `linear-gradient(135deg, ${s.bg_from} 0%, ${s.bg_to} 100%)`,
                };
            },
            arrowStyle() {
                const s = this.currentSlide;
                const isDark = s.theme === 'dark';
                return {
                    background: isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)',
                    color: isDark ? '#fff' : '#0f172a',
                    boxShadow: `0 4px 24px ${s.accent}22`,
                };
            },
        },
        methods: {
            textColor(slide) {
                return slide.theme === 'dark' ? '#ffffff' : '#0f172a';
            },
            subColor(slide) {
                return slide.theme === 'dark' ? 'rgba(255,255,255,0.65)' : 'rgba(15,23,42,0.65)';
            },
            tagStyle(slide) {
                const isDark = slide.theme === 'dark';
                return {
                    color: slide.accent,
                    borderColor: slide.accent + '55',
                    background: slide.accent + '15',
                };
            },
            primaryBtnStyle(slide) {
                return {
                    background: `linear-gradient(135deg, ${slide.accent}, ${slide.accent}cc)`,
                    boxShadow: `0 12px 40px -8px ${slide.accent}66`,
                };
            },
            secondaryBtnStyle(slide) {
                const isDark = slide.theme === 'dark';
                return {
                    color: isDark ? 'rgba(255,255,255,0.85)' : 'rgba(15,23,42,0.85)',
                    borderColor: isDark ? 'rgba(255,255,255,0.2)' : 'rgba(15,23,42,0.15)',
                    background: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.04)',
                };
            },
            ringStyle(slide) {
                return {
                    background: `radial-gradient(circle, ${slide.accent}40 0%, ${slide.accent}00 70%)`,
                    filter: 'blur(40px)',
                };
            },
            imgBlend(slide) {
                return {
                    filter: `drop-shadow(0 40px 80px ${slide.accent}55)`,
                    mixBlendMode: slide.theme === 'light' ? 'multiply' : 'normal',
                };
            },
            badgeStyle(slide) {
                const isDark = slide.theme === 'dark';
                return {
                    background: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(255,255,255,0.85)',
                    color: isDark ? '#fff' : '#0f172a',
                    border: `1px solid ${slide.accent}33`,
                    boxShadow: `0 8px 32px ${slide.accent}22`,
                };
            },
            dotStyle(i) {
                const s = this.currentSlide;
                const isDark = s.theme === 'dark';
                return {
                    background: i === this.current ?
                        s.accent : isDark ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.2)',
                };
            },
            goTo(i) {
                this.current = i;
                this.resetProgress();
                this.scheduleNext();
            },
            next() {
                this.current = (this.current + 1) % this.slides.length;
                this.resetProgress();
                this.scheduleNext();
            },
            prev() {
                this.current = (this.current - 1 + this.slides.length) % this.slides.length;
                this.resetProgress();
                this.scheduleNext();
            },
            resetProgress() {
                this.progressing = false;
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.progressing = true;
                    }, 50);
                });
            },
            start() {
                this.resetProgress();
                this.scheduleNext();
            },
            scheduleNext() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => {
                    this.next();
                }, 4000);
            },
            pause() {
                clearTimeout(this.timer);
                this.progressing = false;
            },
            resume() {
                this.start();
            },
        },
        mounted() {
            this.ready = true;
            this.start();
        },
        beforeUnmount() {
            clearTimeout(this.timer);
        }
    });
</script>
@endpushOnce