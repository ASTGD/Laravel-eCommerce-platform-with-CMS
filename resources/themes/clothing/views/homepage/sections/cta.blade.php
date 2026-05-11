@php($featureProduct = collect($products)->first())

@pushOnce('styles')
<style>
    .gadget-cta { background: #fffdfa; }

    .gadget-experience-card {
        position: relative;
        overflow: hidden;
        border-radius: 52px;
        min-height: 480px;
        padding: clamp(34px, 7vw, 72px);
        display: grid;
        grid-template-columns: 1fr .9fr;
        align-items: center;
        gap: 40px;
        background:
            radial-gradient(circle at 18% 18%, rgba(200,255,77,.48), transparent 22%),
            radial-gradient(circle at 82% 74%, rgba(255,79,112,.26), transparent 32%),
            #fff1f3;
        border: 1px solid rgba(23,17,20,.10);
        box-shadow: var(--fashion-shadow);
    }

    .gadget-experience-card h2 {
        font-family: 'Fraunces', serif;
        font-size: clamp(44px, 7vw, 92px);
        line-height: .86;
        letter-spacing: -0.07em;
        margin: 0 0 18px;
        color: #171114;
    }

    .gadget-experience-card p {
        color: #5f5559;
        font-size: 19px;
        line-height: 1.6;
        margin: 0 0 28px;
        max-width: 520px;
    }

    .gadget-experience-card__image {
        display: grid;
        place-items: center;
    }

    .gadget-experience-card__image img {
        max-width: min(420px, 100%);
        width: 100%;
        aspect-ratio: 4 / 5;
        object-fit: contain;
        border-radius: 38px;
        background: rgba(255,255,255,.54);
        filter: drop-shadow(0 28px 44px rgba(23,17,20,.16));
    }

    .gadget-cta-grid {
        margin-top: 22px;
        display: grid;
        grid-template-columns: .82fr 1.18fr;
        gap: 22px;
    }

    .gadget-cta-grid article {
        position: relative;
        overflow: hidden;
        min-height: 230px;
        border-radius: 34px;
        padding: 34px;
        border: 1px solid rgba(23,17,20,.10);
        background: #fff;
    }

    .gadget-cta-grid article:first-child { background: #171114; color: #fffdfa; }
    .gadget-cta-grid article:nth-child(2) { background: #fff8e8; }
    .gadget-cta-grid p { display: inline-flex; margin: 0 0 12px; background: #c8ff4d; color: #171114; border-radius: 999px; padding: 7px 12px; font-size: 11px; letter-spacing: .13em; text-transform: uppercase; font-weight: 950; }
    .gadget-cta-grid h3 { font-family: 'Fraunces', serif; font-size: clamp(30px, 4vw, 52px); line-height: .93; letter-spacing: -0.06em; margin: 0 0 22px; }
    .gadget-cta-grid__wide { display: flex; align-items: center; justify-content: space-between; gap: 26px; }
    .btn-cta-light { background: #fff; color: #171114 !important; border: 1px solid rgba(23,17,20,.12); padding: 14px 22px; }
    .gadget-cta-grid article:first-child .btn-cta-light { background: #ff4f70; color: #fff !important; border-color: transparent; }
    .cta-emoji { font-size: clamp(70px, 12vw, 126px); line-height: 1; filter: drop-shadow(0 16px 20px rgba(23,17,20,.10)); }

    @media (max-width: 900px) { .gadget-experience-card, .gadget-cta-grid { grid-template-columns: 1fr; } .gadget-cta-grid__wide { align-items: flex-start; } }
</style>
@endPushOnce

<section class="gadget-section gadget-cta" aria-label="Featured fashion promotions">
    <div class="gadget-container">
        <div class="gadget-experience-card">
            <div>
                <h2>Dress louder. Feel lighter.</h2>
                <p>Discover expressive wardrobe pieces that keep the base clean, the colors vibrant, and the styling effortless.</p>
                <a href="{{ route('shop.search.index') }}" class="fashion-button fashion-button--dark">Build My Outfit</a>
            </div>

            <div class="gadget-experience-card__image">
                <img src="{{ $featureProduct['image'] ?? bagisto_asset('images/medium-product-placeholder.webp', 'shop') }}" alt="">
            </div>
        </div>

        <div class="gadget-cta-grid">
            <article>
                <p>Trending now</p>
                <h3>Weekend-ready layers</h3>
                <a href="{{ route('shop.search.index') }}" class="btn-cta-light">Browse Looks</a>
            </article>

            <article class="gadget-cta-grid__wide">
                <div style="max-width: 390px;">
                    <p>Style tip</p>
                    <h3>One bright piece changes everything.</h3>
                    <a href="{{ route('shop.search.index', ['query' => 'accessories']) }}" class="btn-cta-light">Find Accent Pieces</a>
                </div>
                <div class="cta-emoji" aria-hidden="true">👜</div>
            </article>
        </div>
    </div>
</section>
