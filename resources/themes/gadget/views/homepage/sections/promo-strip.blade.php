@push('styles')
<style>
    .gadget-promo-strip {
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #3b82f6);
        background-size: 200% auto;
        color: #ffffff;
        padding: 14px 0;
        animation: gradientFlow 8s linear infinite;
        position: relative;
        z-index: 20;
        box-shadow: 0 4px 20px rgba(59, 130, 246, 0.2);
        overflow: hidden;
    }

    @keyframes gradientFlow {
        0% { background-position: 0% 50%; }
        100% { background-position: 200% 50%; }
    }

    .promo-marquee-container {
        display: flex;
        overflow: hidden;
        user-select: none;
        mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent);
        -webkit-mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent);
    }

    .promo-marquee-content {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        gap: 60px;
        min-width: 100%;
        animation: scrollMarquee 30s linear infinite;
    }

    @keyframes scrollMarquee {
        from { transform: translateX(0); }
        to { transform: translateX(-50%); }
    }

    .promo-item {
        display: flex;
        align-items: center;
        gap: 15px;
        white-space: nowrap;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: 0.03em;
    }

    .promo-badge-mini {
        background: rgba(255, 255, 255, 0.2);
        padding: 3px 12px;
        border-radius: 100px;
        font-size: 10px;
        text-transform: uppercase;
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .gadget-promo-strip:hover .promo-marquee-content {
        animation-play-state: paused;
    }
</style>
@endpush

<section class="gadget-promo-strip" aria-label="Ticker promotion">
    <div class="promo-marquee-container">
        <!-- The content is repeated to ensure a seamless loop -->
        <div class="promo-marquee-content">
            @for ($i = 0; $i < 10; $i++)
                <div class="promo-item">
                    <span class="promo-badge-mini">Limited Offer</span>
                    <p>50 Taka Only! Get It Fast — Express Home Delivery Inside Dhaka</p>
                    <span style="opacity: 0.5;">•</span>
                </div>
            @endfor
        </div>
        <!-- Duplicated for seamless transition -->
        <div class="promo-marquee-content" aria-hidden="true">
            @for ($i = 0; $i < 10; $i++)
                <div class="promo-item">
                    <span class="promo-badge-mini">Limited Offer</span>
                    <p>50 Taka Only! Get It Fast — Express Home Delivery Inside Dhaka</p>
                    <span style="opacity: 0.5;">•</span>
                </div>
            @endfor
        </div>
    </div>
</section>
