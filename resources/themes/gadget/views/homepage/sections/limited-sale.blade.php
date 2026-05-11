@push('styles')
<style>
    .gadget-sale {
        padding: 120px 0;
        background: #ffffff;
        position: relative;
    }

    .gadget-sale__panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 80px 60px;
        border-radius: 60px;
        text-align: center;
        max-width: 950px;
        margin: 0 auto;
        box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }

    .gadget-sale__panel::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 8px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #3b82f6);
        background-size: 200% auto;
        animation: gradientFlow 3s linear infinite;
    }

    @keyframes gradientFlow {
        0% { background-position: 0% 50%; }
        100% { background-position: 200% 50%; }
    }

    .gadget-sale__panel h2 {
        font-size: 40px;
        font-weight: 950;
        color: #0f172a;
        margin-bottom: 50px;
        letter-spacing: -0.04em;
    }

    .gadget-sale__timer-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        margin-bottom: 60px;
    }

    .gadget-sale__digit-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        width: 120px;
        height: 140px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        border-radius: 28px;
        position: relative;
        transition: 0.5s cubic-bezier(0.23, 1, 0.32, 1);
    }

    .gadget-sale__digit-box:hover {
        transform: translateY(-10px) scale(1.02);
        border-color: #3b82f6;
        background: #ffffff;
        box-shadow: 0 20px 40px rgba(59, 130, 246, 0.12);
    }

    .gadget-sale__number {
        font-size: 52px;
        font-weight: 950;
        line-height: 1;
        background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        text-shadow: 0 10px 20px rgba(37, 99, 235, 0.15);
        margin: 0;
    }

    .gadget-sale__label {
        font-size: 11px;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 850;
        margin-top: 15px;
        letter-spacing: 0.15em;
    }

    .gadget-sale__colon {
        font-size: 40px;
        font-weight: 900;
        color: #cbd5e1;
        padding-bottom: 30px;
        animation: pulseDots 1.5s infinite;
    }

    @keyframes pulseDots {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }

    .btn-sale-aura {
        background: #0f172a;
        color: #ffffff !important;
        padding: 22px 60px;
        border-radius: 20px;
        font-weight: 850;
        text-decoration: none !important;
        font-size: 18px;
        transition: 0.4s;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }
    .btn-sale-aura:hover {
        background: #3b82f6;
        transform: translateY(-5px);
        box-shadow: 0 25px 45px rgba(59, 130, 246, 0.4);
    }

    @media (max-width: 768px) {
        .gadget-sale__timer-wrap { gap: 10px; flex-wrap: wrap; }
        .gadget-sale__digit-box { width: 85px; height: 100px; border-radius: 18px; }
        .gadget-sale__number { font-size: 32px; }
        .gadget-sale__colon { display: none; }
        .gadget-sale__panel { padding: 50px 20px; border-radius: 40px; }
        .gadget-sale__panel h2 { font-size: 28px; }
    }
</style>
@endpush

<section class="gadget-sale">
    <div class="gadget-container">
        <div class="gadget-sale__panel">
            <h2>Flash Sale Ending Soon — Don't Miss Out!</h2>

            <v-gadget-timer end-date="{{ now()->addDays(7)->toIso8601String() }}">
                <div class="gadget-sale__timer-wrap">
                    <div class="gadget-sale__digit-box">
                        <span class="gadget-sale__number">00</span>
                        <span class="gadget-sale__label">Days</span>
                    </div>
                    <div class="gadget-sale__colon">:</div>
                    <div class="gadget-sale__digit-box">
                        <span class="gadget-sale__number">00</span>
                        <span class="gadget-sale__label">Hours</span>
                    </div>
                    <div class="gadget-sale__colon">:</div>
                    <div class="gadget-sale__digit-box">
                        <span class="gadget-sale__number">00</span>
                        <span class="gadget-sale__label">Mins</span>
                    </div>
                    <div class="gadget-sale__colon">:</div>
                    <div class="gadget-sale__digit-box">
                        <span class="gadget-sale__number">00</span>
                        <span class="gadget-sale__label">Secs</span>
                    </div>
                </div>
            </v-gadget-timer>

            <a href="{{ route('shop.search.index') }}" class="btn-sale-aura">
                Explore the Sale
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
            this.interval = setInterval(this.updateTimer, 1000);
        },
        beforeUnmount() {
            clearInterval(this.interval);
        },
        methods: {
            updateTimer() {
                const end = new Date(this.endDate).getTime();
                const now = new Date().getTime();
                const diff = end - now;

                if (diff <= 0) {
                    this.timeLeft = { days: '00', hours: '00', minutes: '00', seconds: '00' };
                    clearInterval(this.interval);
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
