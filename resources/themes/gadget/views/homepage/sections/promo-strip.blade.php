@push('styles')
<style>
    /* ─── PROMO STRIP — VIBRANT NEON TICKER ─── */
    .gadget-promo-strip {
        position: relative;
        z-index: 20;
        overflow: hidden;
        isolation: isolate;
        background: linear-gradient(135deg, #0f0c29 0%, #1a1040 25%, #24243e 50%, #1a1040 75%, #0f0c29 100%);
        background-size: 300% 300%;
        animation: bgPan 15s ease infinite;
    }

    @keyframes bgPan {
        0%   { background-position: 0% 50%; }
        50%  { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* Vivid aurora glow layers */
    .promo-glow {
        position: absolute;
        inset: 0;
        z-index: 0;
        pointer-events: none;
    }

    .promo-glow-blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
        animation: blobFloat 8s ease-in-out infinite alternate;
    }

    .promo-glow-blob:nth-child(1) {
        width: 300px;
        height: 120px;
        background: rgba(0, 210, 255, 0.25);
        top: -30px;
        left: 5%;
        animation-delay: 0s;
    }

    .promo-glow-blob:nth-child(2) {
        width: 250px;
        height: 100px;
        background: rgba(168, 85, 247, 0.3);
        top: -20px;
        left: 35%;
        animation-delay: -3s;
    }

    .promo-glow-blob:nth-child(3) {
        width: 280px;
        height: 110px;
        background: rgba(236, 72, 153, 0.2);
        bottom: -30px;
        right: 10%;
        animation-delay: -5s;
    }

    .promo-glow-blob:nth-child(4) {
        width: 200px;
        height: 90px;
        background: rgba(56, 189, 248, 0.2);
        bottom: -20px;
        right: 45%;
        animation-delay: -2s;
    }

    @keyframes blobFloat {
        0%   { transform: translateX(0) scale(1); }
        100% { transform: translateX(40px) scale(1.2); }
    }

    /* Neon border lines */
    .promo-neon-top,
    .promo-neon-bottom {
        position: absolute;
        left: 0;
        width: 100%;
        height: 2px;
        z-index: 3;
    }

    .promo-neon-top {
        top: 0;
        background: linear-gradient(90deg,
            transparent,
            rgba(0, 210, 255, 0.4) 20%,
            rgba(168, 85, 247, 0.6) 50%,
            rgba(236, 72, 153, 0.4) 80%,
            transparent
        );
        box-shadow: 0 0 8px rgba(168, 85, 247, 0.3);
    }

    .promo-neon-bottom {
        bottom: 0;
        background: linear-gradient(90deg,
            transparent,
            rgba(236, 72, 153, 0.4) 20%,
            rgba(0, 210, 255, 0.6) 50%,
            rgba(168, 85, 247, 0.4) 80%,
            transparent
        );
        box-shadow: 0 0 12px rgba(0, 210, 255, 0.3);
    }

    /* Scanning horizontal beam */
    .promo-scan-beam {
        position: absolute;
        top: 0;
        left: -30%;
        width: 30%;
        height: 100%;
        z-index: 2;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.03), transparent);
        animation: scanBeam 6s linear infinite;
        pointer-events: none;
    }

    @keyframes scanBeam {
        0%   { left: -30%; }
        100% { left: 130%; }
    }

    /* ─── MARQUEE ─── */
    .promo-marquee-container {
        position: relative;
        z-index: 2;
        display: flex;
        overflow: hidden;
        user-select: none;
        padding: 13px 0;
        mask-image: linear-gradient(to right, transparent, black 6%, black 94%, transparent);
        -webkit-mask-image: linear-gradient(to right, transparent, black 6%, black 94%, transparent);
    }

    .promo-marquee-track {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        gap: 20px;
        min-width: 100%;
        animation: marqueeScroll 30s linear infinite;
    }

    @keyframes marqueeScroll {
        from { transform: translateX(0); }
        to   { transform: translateX(-50%); }
    }

    .gadget-promo-strip:hover .promo-marquee-track {
        animation-play-state: paused;
    }

    /* ─── EACH PROMO UNIT ─── */
    .promo-unit {
        display: flex;
        align-items: center;
        gap: 14px;
        white-space: nowrap;
        font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
    }

    /* Badge — glowing pill */
    .promo-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: #fff;
        border-radius: 100px;
        background: linear-gradient(135deg, rgba(0, 210, 255, 0.25), rgba(168, 85, 247, 0.3));
        border: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow:
            0 0 10px rgba(0, 210, 255, 0.2),
            0 0 20px rgba(168, 85, 247, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        position: relative;
        overflow: hidden;
    }

    /* Live pulse dot inside badge */
    .promo-live-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #00ff88;
        box-shadow: 0 0 6px #00ff88, 0 0 12px rgba(0, 255, 136, 0.4);
        animation: dotPulse 1.5s ease-in-out infinite;
    }

    @keyframes dotPulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50%      { transform: scale(0.6); opacity: 0.4; }
    }

    /* Pill shimmer */
    .promo-pill::after {
        content: '';
        position: absolute;
        top: 0;
        left: -120%;
        width: 80%;
        height: 100%;
        background: linear-gradient(105deg, transparent 30%, rgba(255,255,255,0.2) 50%, transparent 70%);
        animation: pillShimmer 4s ease-in-out infinite;
    }

    @keyframes pillShimmer {
        0%   { left: -120%; }
        30%  { left: 150%; }
        100% { left: 150%; }
    }

    /* Main text */
    .promo-copy {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.8);
        letter-spacing: 0.02em;
    }

    .promo-price {
        font-weight: 900;
        font-size: 15px;
        background: linear-gradient(to right, #00d2ff, #a855f7, #ec4899);
        background-size: 200% auto;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: priceShift 4s linear infinite;
    }

    @keyframes priceShift {
        0%   { background-position: 0% center; }
        100% { background-position: 200% center; }
    }

    .promo-dash {
        color: rgba(255, 255, 255, 0.2);
        font-weight: 300;
    }

    .promo-delivery {
        color: rgba(255, 255, 255, 0.55);
        font-weight: 500;
    }

    /* Separator — glowing star */
    .promo-star-sep {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        flex-shrink: 0;
    }

    .promo-star-sep svg {
        width: 10px;
        height: 10px;
        fill: none;
        stroke: url(#promoStarGrad);
        stroke-width: 1.5;
        filter: drop-shadow(0 0 4px rgba(0, 210, 255, 0.5));
        animation: starSpin 12s linear infinite;
    }

    @keyframes starSpin {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }

    /* ─── RESPONSIVE ─── */
    @media (max-width: 768px) {
        .promo-copy { font-size: 12px; }
        .promo-price { font-size: 13px; }
        .promo-pill { padding: 4px 10px; font-size: 9px; }
        .promo-marquee-container { padding: 10px 0; }
    }
</style>
@endpush

{{-- SVG gradient definition (invisible, used by stroke references) --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <defs>
        <linearGradient id="promoStarGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#00d2ff"/>
            <stop offset="100%" stop-color="#a855f7"/>
        </linearGradient>
    </defs>
</svg>

<section class="gadget-promo-strip" aria-label="Promotional ticker">
    {{-- Background effects --}}
    <div class="promo-glow">
        <div class="promo-glow-blob"></div>
        <div class="promo-glow-blob"></div>
        <div class="promo-glow-blob"></div>
        <div class="promo-glow-blob"></div>
    </div>
    <div class="promo-neon-top"></div>
    <div class="promo-neon-bottom"></div>
    <div class="promo-scan-beam"></div>

    {{-- Scrolling ticker --}}
    <div class="promo-marquee-container">
        <div class="promo-marquee-track">
            @for ($i = 0; $i < 8; $i++)
                <div class="promo-unit">
                    <span class="promo-pill">
                        <span class="promo-live-dot"></span>
                        LIVE OFFER
                    </span>
                    <p class="promo-copy">
                        <span class="promo-price">৳50 Only!</span>
                        <span class="promo-dash">—</span>
                        <span class="promo-delivery">Express Delivery Inside Dhaka</span>
                    </p>
                    <span class="promo-star-sep">
                        <svg viewBox="0 0 24 24"><polygon points="12,2 15,9 22,9 16,14 18,22 12,17 6,22 8,14 2,9 9,9"/></svg>
                    </span>
                </div>
            @endfor
        </div>
        <div class="promo-marquee-track" aria-hidden="true">
            @for ($i = 0; $i < 8; $i++)
                <div class="promo-unit">
                    <span class="promo-pill">
                        <span class="promo-live-dot"></span>
                        LIVE OFFER
                    </span>
                    <p class="promo-copy">
                        <span class="promo-price">৳50 Only!</span>
                        <span class="promo-dash">—</span>
                        <span class="promo-delivery">Express Delivery Inside Dhaka</span>
                    </p>
                    <span class="promo-star-sep">
                        <svg viewBox="0 0 24 24"><polygon points="12,2 15,9 22,9 16,14 18,22 12,17 6,22 8,14 2,9 9,9"/></svg>
                    </span>
                </div>
            @endfor
        </div>
    </div>
</section>
