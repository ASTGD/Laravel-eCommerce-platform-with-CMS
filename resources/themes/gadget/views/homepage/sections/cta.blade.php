@php($featureProduct = collect($products)->first())

@pushOnce('styles')
<style>
    .gadget-cta {
        padding: 100px 0;
        background: #ffffff;
    }

    .gadget-experience-card {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
        border-radius: 40px;
        padding: 60px;
        display: flex;
        align-items: center;
        gap: 60px;
        margin-bottom: 30px;
        transition: 0.4s;
    }

    .gadget-experience-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.03);
    }

    .gadget-experience-card h2 {
        font-size: 42px;
        font-weight: 900;
        color: #0f172a;
        margin-bottom: 20px;
        letter-spacing: -0.04em;
    }

    .gadget-experience-card p {
        font-size: 18px;
        color: #64748b;
        margin-bottom: 40px;
        max-width: 450px;
    }

    .gadget-cta-grid {
        display: grid;
        grid-template-columns: 0.8fr 1.2fr;
        gap: 30px;
    }

    .gadget-cta-grid article {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 32px;
        padding: 40px;
        transition: 0.4s;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .gadget-cta-grid article:hover {
        background: #ffffff;
        border-color: #3b82f6;
    }

    .gadget-cta-grid p {
        color: #3b82f6;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 12px;
        margin-bottom: 12px;
    }

    .gadget-cta-grid h3 {
        font-size: 28px;
        font-weight: 850;
        color: #1e293b;
        margin-bottom: 24px;
    }

    .gadget-cta-grid__wide {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .btn-cta-light {
        background: #0f172a;
        color: #ffffff !important;
        padding: 14px 32px;
        border-radius: 12px;
        font-weight: 700;
        text-decoration: none !important;
        display: inline-block;
        transition: 0.3s;
    }

    .btn-cta-light:hover {
        background: #3b82f6;
        transform: translateY(-2px);
    }

    @media (max-width: 991px) {
        .gadget-experience-card { flex-direction: column; padding: 40px; text-align: center; }
        .gadget-cta-grid { grid-template-columns: 1fr; }
        .gadget-cta-grid__wide { flex-direction: column; text-align: center; }
    }
</style>
@endpushOnce

<section class="gadget-section gadget-cta" aria-label="Featured gadget promotions">
    <div class="gadget-container">
        <div class="gadget-experience-card">
            <div style="flex: 1;">
                <h2>Future Reality Experience</h2>
                <p>Unlock new dimensions of productivity and entertainment with the next generation of spatial tech.</p>
                <a href="{{ route('shop.search.index') }}" class="btn-aura">
                    Get Started
                </a>
            </div>

            <div class="gadget-experience-card__image" style="flex: 1; text-align: center;">
                <img src="{{ $featureProduct['image'] ?? bagisto_asset('images/medium-product-placeholder.webp', 'shop') }}" alt="" style="max-width: 100%; filter: drop-shadow(0 20px 40px rgba(0,0,0,0.1));">
            </div>
        </div>

        <div class="gadget-cta-grid">
            <article>
                <p>Trending Now</p>
                <h3>Smart Gear</h3>
                <a href="{{ route('shop.search.index') }}" class="btn-cta-light">
                    Browse Gear
                </a>
            </article>

            <article class="gadget-cta-grid__wide">
                <div style="max-width: 300px;">
                    <p>Wellness Tech</p>
                    <h3>Personal Care</h3>
                    <a href="{{ route('shop.search.index', ['query' => 'personal care']) }}" class="btn-cta-light">
                        View More
                    </a>
                </div>

                <div style="font-size: 80px;">🧴</div>
            </article>
        </div>
    </div>
</section>
