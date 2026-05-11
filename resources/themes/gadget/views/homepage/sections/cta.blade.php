@php($featureProduct = collect($products)->first())

@pushOnce('styles')
<style>
    .gadget-cta {
        padding: 100px 0;
        background: #0f172a;
        position: relative;
        overflow: hidden;
        color: #ffffff;
    }

    .cta-aura {
        position: absolute;
        inset: 0;
        z-index: 1;
        pointer-events: none;
    }

    .cta-blob {
        position: absolute;
        width: 700px;
        height: 700px;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.12) 0%, transparent 70%);
        border-radius: 50%;
        filter: blur(100px);
        animation: ctaAuraMove 20s infinite alternate ease-in-out;
    }

    .cta-blob-1 { top: -10%; left: -5%; background: radial-gradient(circle, rgba(139, 92, 246, 0.1) 0%, transparent 70%); }
    .cta-blob-2 { bottom: -10%; right: -5%; }

    @keyframes ctaAuraMove {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(-40px, 40px) scale(1.1); }
    }

    .gadget-experience-card {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(30px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 48px;
        padding: 80px;
        display: flex;
        align-items: center;
        gap: 80px;
        margin-bottom: 40px;
        position: relative;
        z-index: 5;
        transition: 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        box-shadow: 0 50px 100px rgba(0,0,0,0.4);
    }

    .gadget-experience-card:hover {
        transform: translateY(-10px);
        border-color: rgba(59, 130, 246, 0.3);
    }

    .gadget-experience-card h2 {
        font-size: 52px;
        font-weight: 950;
        color: #ffffff;
        margin-bottom: 25px;
        letter-spacing: -0.05em;
        line-height: 1.1;
    }

    .gadget-experience-card p {
        font-size: 20px;
        color: #94a3b8;
        margin-bottom: 45px;
        max-width: 500px;
        line-height: 1.7;
    }

    .gadget-cta-grid {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 40px;
        position: relative;
        z-index: 5;
    }

    .gadget-cta-grid article {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(25px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 40px;
        padding: 50px;
        transition: 0.4s cubic-bezier(0.23, 1, 0.32, 1);
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 380px;
    }

    .gadget-cta-grid article:hover {
        transform: translateY(-8px);
        border-color: rgba(59, 130, 246, 0.3);
        background: rgba(255, 255, 255, 0.05);
    }

    .gadget-cta-grid p {
        color: #3b82f6;
        font-weight: 900;
        text-transform: uppercase;
        font-size: 13px;
        margin-bottom: 16px;
        letter-spacing: 0.15em;
    }

    .gadget-cta-grid h3 {
        font-size: 32px;
        font-weight: 900;
        color: #ffffff;
        margin-bottom: 35px;
        letter-spacing: -0.03em;
    }

    .gadget-cta-grid__wide {
        display: flex !important;
        flex-direction: row !important;
        align-items: center;
        justify-content: space-between;
    }

    .btn-cta-aura {
        background: #3b82f6;
        color: #ffffff !important;
        padding: 18px 40px;
        border-radius: 16px;
        font-weight: 800;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        transition: 0.4s;
        box-shadow: 0 15px 30px rgba(59, 130, 246, 0.3);
    }

    .btn-cta-aura:hover {
        background: #2563eb;
        transform: scale(1.05);
        box-shadow: 0 20px 40px rgba(59, 130, 246, 0.5);
    }

    .cta-product-img {
        max-width: 100%;
        filter: drop-shadow(0 30px 60px rgba(0,0,0,0.4));
        animation: ctaFloat 8s infinite ease-in-out;
        -webkit-mask-image: radial-gradient(circle, black 50%, transparent 95%);
        mask-image: radial-gradient(circle, black 50%, transparent 95%);
    }

    @keyframes ctaFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-25px) rotate(3deg); }
    }

    @media (max-width: 1200px) {
        .gadget-experience-card { padding: 60px; gap: 40px; }
        .gadget-experience-card h2 { font-size: 42px; }
    }

    @media (max-width: 991px) {
        .gadget-experience-card { flex-direction: column; padding: 50px 30px; text-align: center; }
        .gadget-experience-card p { margin-inline: auto; }
        .gadget-cta-grid { grid-template-columns: 1fr; }
        .gadget-cta-grid__wide { flex-direction: column !important; text-align: center; gap: 40px; }
    }
</style>
@endpushOnce

<section class="gadget-section gadget-cta" aria-label="Featured gadget promotions">
    <div class="cta-aura">
        <div class="cta-blob cta-blob-1"></div>
        <div class="cta-blob cta-blob-2"></div>
    </div>

    <div class="gadget-container">
        <div class="gadget-experience-card">
            <div style="flex: 1.2;">
                <h2>Future Reality Experience</h2>
                <p>Unlock new dimensions of productivity and entertainment with the next generation of spatial tech.</p>
                <a href="{{ route('shop.search.index') }}" class="btn-cta-aura">
                    <span>Get Started</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            </div>

            <div class="gadget-experience-card__image" style="flex: 0.8; text-align: center;">
                <img 
                    src="https://images.unsplash.com/photo-1622979135225-d2ba269cf1ac?auto=format&fit=crop&q=80&w=1000" 
                    alt="VR Headset" 
                    class="cta-product-img"
                    onerror="this.src='https://via.placeholder.com/800x800/0f172a/3b82f6?text=VR+Experience'"
                >
            </div>
        </div>

        <div class="gadget-cta-grid">
            <article>
                <p>Trending Now</p>
                <h3>Next-Gen Smart Gear</h3>
                <a href="{{ route('shop.search.index') }}" class="btn-cta-aura">
                    Browse Gear
                </a>
            </article>

            <article class="gadget-cta-grid__wide">
                <div style="max-width: 350px;">
                    <p>Sonic Tech</p>
                    <h3>Pure Sound Fidelity</h3>
                    <a href="{{ route('shop.search.index', ['query' => 'headphones']) }}" class="btn-cta-aura">
                        View More
                    </a>
                </div>

                <div style="flex: 1; text-align: right;">
                    <img 
                        src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&q=80&w=600" 
                        alt="Headphones" 
                        class="cta-product-img"
                        style="max-width: 250px;"
                        onerror="this.src='https://via.placeholder.com/600x600/0f172a/3b82f6?text=Headphones'"
                    >
                </div>
            </article>
        </div>
    </div>
</section>
