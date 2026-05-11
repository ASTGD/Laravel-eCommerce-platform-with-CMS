@pushOnce('styles')
<style>
    .gadget-promo-strip {
        background: #171114;
        color: #fffdfa;
        padding: 13px 0;
        overflow: hidden;
        border-block: 1px solid rgba(255,255,255,.08);
    }

    .promo-marquee-container {
        display: flex;
        overflow: hidden;
        user-select: none;
        mask-image: linear-gradient(to right, transparent, black 12%, black 88%, transparent);
        -webkit-mask-image: linear-gradient(to right, transparent, black 12%, black 88%, transparent);
    }

    .promo-marquee-content {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        gap: 42px;
        min-width: 100%;
        animation: fashionMarquee 32s linear infinite;
    }

    @keyframes fashionMarquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }

    .promo-item { display: flex; align-items: center; gap: 16px; white-space: nowrap; font-size: 15px; font-weight: 900; letter-spacing: .03em; }
    .promo-badge-mini { background: #c8ff4d; color: #171114; padding: 5px 12px; border-radius: 999px; font-size: 10px; text-transform: uppercase; letter-spacing: .12em; }
    .gadget-promo-strip:hover .promo-marquee-content { animation-play-state: paused; }
</style>
@endPushOnce

<section class="gadget-promo-strip" aria-label="Ticker promotion">
    <div class="promo-marquee-container">
        <div class="promo-marquee-content">
            @for ($i = 0; $i < 10; $i++)
                <div class="promo-item"><span class="promo-badge-mini">Style Drop</span><p>New fits, bold colors, easy delivery across Bangladesh</p><span style="opacity: .5;">✦</span></div>
            @endfor
        </div>
        <div class="promo-marquee-content" aria-hidden="true">
            @for ($i = 0; $i < 10; $i++)
                <div class="promo-item"><span class="promo-badge-mini">Style Drop</span><p>New fits, bold colors, easy delivery across Bangladesh</p><span style="opacity: .5;">✦</span></div>
            @endfor
        </div>
    </div>
</section>
