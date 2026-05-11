@php
    $saleEndDate = now()->addDays(5)->endOfDay()->toIso8601String();
@endphp

@pushOnce('styles')
<style>
    .gadget-sale {
        position: relative;
        overflow: hidden;
        background: #fffdfa;
        padding: clamp(70px, 8vw, 112px) 0;
    }

    .gadget-sale__panel {
        position: relative;
        overflow: hidden;
        min-height: 520px;
        border-radius: 52px;
        background:
            linear-gradient(100deg, rgba(23,17,20,.92), rgba(23,17,20,.64) 48%, rgba(23,17,20,.18)),
            url('https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&q=80&w=1800') center/cover;
        color: #fffdfa;
        display: grid;
        align-items: end;
        padding: clamp(34px, 7vw, 76px);
        box-shadow: var(--fashion-shadow);
    }

    .gadget-sale__content { max-width: 660px; position: relative; z-index: 1; }
    .gadget-sale__eyebrow { display: inline-flex; background: #c8ff4d; color: #171114; padding: 8px 14px; border-radius: 999px; font-weight: 950; letter-spacing: .14em; text-transform: uppercase; font-size: 11px; margin-bottom: 18px; }
    .gadget-sale__title { font-family: 'Fraunces', serif; font-size: clamp(46px, 7vw, 96px); line-height: .85; letter-spacing: -0.07em; margin: 0 0 18px; }
    .gadget-sale__text { color: rgba(255,253,250,.78); font-size: 19px; line-height: 1.62; margin: 0 0 28px; max-width: 560px; }

    .gadget-sale__timer { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin: 0 0 30px; }
    .gadget-sale__digit-box { width: 86px; height: 82px; border-radius: 24px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22); display: grid; place-items: center; backdrop-filter: blur(16px); }
    .gadget-sale__number { font-size: 28px; font-weight: 950; line-height: 1; }
    .gadget-sale__label { font-size: 11px; text-transform: uppercase; letter-spacing: .12em; color: rgba(255,253,250,.66); }
    .gadget-sale__colon { font-size: 28px; font-weight: 950; color: #c8ff4d; }

    @media (max-width: 620px) { .gadget-sale__panel { border-radius: 34px; min-height: 580px; } .gadget-sale__digit-box { width: 70px; height: 72px; } }
</style>
@endPushOnce

<section class="gadget-sale" aria-labelledby="gadget-sale-title">
    <div class="gadget-container">
        <div class="gadget-sale__panel">
            <div class="gadget-sale__content">
                <span class="gadget-sale__eyebrow">Limited style edit</span>
                <h2 class="gadget-sale__title" id="gadget-sale-title">Bright fits before they’re gone.</h2>
                <p class="gadget-sale__text">Refresh your wardrobe with expressive seasonal picks, sharp prices, and pieces made for repeat styling.</p>

                <v-gadget-timer end-date="{{ $saleEndDate }}"></v-gadget-timer>

                <a href="{{ route('shop.search.index') }}" class="fashion-button fashion-button--color">Shop the Sale</a>
            </div>
        </div>
    </div>
</section>

@pushOnce('scripts')
<script type="text/x-template" id="v-gadget-timer-template">
    <div class="gadget-sale__timer" aria-label="Sale countdown">
        <div class="gadget-sale__digit-box"><span class="gadget-sale__number">@{{ timeLeft.days }}</span><span class="gadget-sale__label">Days</span></div>
        <div class="gadget-sale__colon">:</div>
        <div class="gadget-sale__digit-box"><span class="gadget-sale__number">@{{ timeLeft.hours }}</span><span class="gadget-sale__label">Hours</span></div>
        <div class="gadget-sale__colon">:</div>
        <div class="gadget-sale__digit-box"><span class="gadget-sale__number">@{{ timeLeft.minutes }}</span><span class="gadget-sale__label">Mins</span></div>
        <div class="gadget-sale__colon">:</div>
        <div class="gadget-sale__digit-box"><span class="gadget-sale__number">@{{ timeLeft.seconds }}</span><span class="gadget-sale__label">Secs</span></div>
    </div>
</script>

<script type="module">
    app.component('v-gadget-timer', {
        template: '#v-gadget-timer-template',
        props: ['endDate'],
        data() { return { timeLeft: { days: '00', hours: '00', minutes: '00', seconds: '00' }, interval: null } },
        mounted() { this.updateTimer(); this.interval = setInterval(this.updateTimer, 1000); },
        beforeUnmount() { clearInterval(this.interval); },
        methods: {
            updateTimer() {
                const end = new Date(this.endDate).getTime();
                const now = new Date().getTime();
                const diff = end - now;
                if (diff <= 0) { this.timeLeft = { days: '00', hours: '00', minutes: '00', seconds: '00' }; clearInterval(this.interval); return; }
                const d = Math.floor(diff / (1000 * 60 * 60 * 24));
                const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((diff % (1000 * 60)) / 1000);
                this.timeLeft = { days: d.toString().padStart(2, '0'), hours: h.toString().padStart(2, '0'), minutes: m.toString().padStart(2, '0'), seconds: s.toString().padStart(2, '0') };
            }
        }
    });
</script>
@endPushOnce
