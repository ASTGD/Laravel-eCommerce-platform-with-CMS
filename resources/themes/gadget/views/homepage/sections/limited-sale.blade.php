@push('styles')
<style>
    .gadget-sale {
        padding: 140px 0;
        background: #0f172a;
        position: relative;
        overflow: hidden;
        color: #ffffff;
    }

    .sale-aura {
        position: absolute;
        inset: 0;
        z-index: 1;
        pointer-events: none;
    }

    .sale-blob {
        position: absolute;
        width: 800px;
        height: 800px;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, transparent 70%);
        border-radius: 50%;
        filter: blur(100px);
        animation: saleAuraMove 25s infinite alternate ease-in-out;
    }

    .sale-blob-1 { top: -20%; right: -10%; background: radial-gradient(circle, rgba(139, 92, 246, 0.15) 0%, transparent 70%); }
    .sale-blob-2 { bottom: -20%; left: -10%; }

    @keyframes saleAuraMove {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(60px, 40px) scale(1.1); }
    }

    .gadget-sale__panel {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(30px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        padding: 100px 80px;
        border-radius: 60px;
        text-align: center;
        max-width: 1000px;
        margin: 0 auto;
        position: relative;
        z-index: 5;
        box-shadow: 0 50px 120px rgba(0, 0, 0, 0.4);
    }

    .gadget-sale__panel h2 {
        font-size: 48px;
        font-weight: 950;
        color: #ffffff;
        margin-bottom: 60px;
        letter-spacing: -0.05em;
        line-height: 1.1;
    }

    .gadget-sale__timer-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 24px;
        margin-bottom: 70px;
    }

    .gadget-sale__digit-box {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(59, 130, 246, 0.2);
        width: 140px;
        height: 160px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        border-radius: 32px;
        position: relative;
        transition: 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .gadget-sale__digit-box:hover {
        transform: translateY(-10px);
        border-color: #3b82f6;
        background: rgba(59, 130, 246, 0.1);
        box-shadow: 0 20px 50px rgba(59, 130, 246, 0.3);
    }

    .gadget-sale__number {
        font-size: 64px;
        font-weight: 950;
        line-height: 1;
        color: #ffffff;
        text-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        margin: 0;
    }

    .gadget-sale__label {
        font-size: 13px;
        color: #94a3b8;
        text-transform: uppercase;
        font-weight: 900;
        margin-top: 15px;
        letter-spacing: 0.2em;
    }

    .gadget-sale__colon {
        font-size: 48px;
        font-weight: 950;
        color: #3b82f6;
        padding-bottom: 40px;
        animation: neonPulse 2s infinite;
    }

    @keyframes neonPulse {
        0%, 100% { opacity: 1; text-shadow: 0 0 20px rgba(59, 130, 246, 0.8); }
        50% { opacity: 0.4; text-shadow: none; }
    }

    .btn-sale-aura {
        background: #3b82f6;
        color: #ffffff !important;
        padding: 24px 70px;
        border-radius: 20px;
        font-weight: 900;
        text-decoration: none !important;
        font-size: 20px;
        transition: 0.4s;
        display: inline-flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 15px 40px rgba(59, 130, 246, 0.4);
    }

    .btn-sale-aura:hover {
        background: #2563eb;
        transform: scale(1.05);
        box-shadow: 0 25px 50px rgba(59, 130, 246, 0.6);
    }

    @media (max-width: 991px) {
        .gadget-sale__panel { padding: 60px 30px; }
        .gadget-sale__timer-wrap { gap: 15px; flex-wrap: wrap; }
        .gadget-sale__digit-box { width: 110px; height: 130px; }
        .gadget-sale__number { font-size: 44px; }
        .gadget-sale__colon { display: none; }
        .gadget-sale__panel h2 { font-size: 32px; }
    }
</style>
@endpush

<section class="gadget-sale">
    <!-- Animated Background Aura -->
    <div class="sale-aura">
        <div class="sale-blob sale-blob-1"></div>
        <div class="sale-blob sale-blob-2"></div>
    </div>

    <div class="gadget-container">
        <div class="gadget-sale__panel">
            <h2>Flash Sale Ending Soon — Don't Miss Out!</h2>

            <v-gadget-timer end-date="{{ now()->addDays(7)->toIso8601String() }}"></v-gadget-timer>

            <a href="{{ route('shop.search.index') }}" class="btn-sale-aura">
                <span>Explore the Sale</span>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>
    </div>
</section>

@pushOnce('scripts')
<script type="text/x-template" id="v-gadget-timer-template">
    <div class="gadget-sale__timer-wrap">
        <div class="gadget-sale__digit-box">
            <span class="gadget-sale__number">@{{ timeLeft.days }}</span>
            <span class="gadget-sale__label">Days</span>
        </div>
        <div class="gadget-sale__colon">:</div>
        <div class="gadget-sale__digit-box">
            <span class="gadget-sale__number">@{{ timeLeft.hours }}</span>
            <span class="gadget-sale__label">Hours</span>
        </div>
        <div class="gadget-sale__colon">:</div>
        <div class="gadget-sale__digit-box">
            <span class="gadget-sale__number">@{{ timeLeft.minutes }}</span>
            <span class="gadget-sale__label">Mins</span>
        </div>
        <div class="gadget-sale__colon">:</div>
        <div class="gadget-sale__digit-box">
            <span class="gadget-sale__number">@{{ timeLeft.seconds }}</span>
            <span class="gadget-sale__label">Secs</span>
        </div>
    </div>
</script>

<script type="module">
    app.component('v-gadget-timer', {
        template: '#v-gadget-timer-template',
        props: ['endDate'],
        data() {
            return {
                timeLeft: { days: '00', hours: '00', minutes: '00', seconds: '00' },
                interval: null
            }
        },
        mounted() {
            this.updateTimer();
            this.interval = setInterval(() => {
                this.updateTimer();
            }, 1000);
        },
        beforeUnmount() {
            if (this.interval) {
                clearInterval(this.interval);
            }
        },
        methods: {
            updateTimer() {
                const end = new Date(this.endDate).getTime();
                const now = new Date().getTime();
                const diff = end - now;

                if (diff <= 0) {
                    this.timeLeft = { days: '00', hours: '00', minutes: '00', seconds: '00' };
                    if (this.interval) clearInterval(this.interval);
                    return;
                }

                const d = Math.floor(diff / (1000 * 60 * 60 * 24));
                const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((diff % (1000 * 60)) / 1000);

                this.timeLeft = {
                    days: d.toString().padStart(2, '0'),
                    hours: h.toString().padStart(2, '0'),
                    minutes: m.toString().padStart(2, '0'),
                    seconds: s.toString().padStart(2, '0')
                };
            }
        }
    });
</script>
@endpushOnce
