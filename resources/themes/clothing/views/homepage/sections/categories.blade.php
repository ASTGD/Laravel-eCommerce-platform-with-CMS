@php($fallbackCategories = collect([
    ['name' => 'Dresses', 'url' => route('shop.search.index', ['query' => 'dress']), 'image' => null],
    ['name' => 'Tops', 'url' => route('shop.search.index', ['query' => 'top']), 'image' => null],
    ['name' => 'Denim', 'url' => route('shop.search.index', ['query' => 'denim']), 'image' => null],
    ['name' => 'Accessories', 'url' => route('shop.search.index', ['query' => 'accessories']), 'image' => null],
]))
@php($categoryItems = collect($categories)->isNotEmpty() ? collect($categories) : $fallbackCategories)

@pushOnce('styles')
<style>
    .gadget-categories { background: linear-gradient(180deg, #fffdfa, #fff8e8); }
    .gadget-category-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; margin-bottom: 46px; }

    .gadget-order-banner {
        position: relative;
        overflow: hidden;
        min-height: 360px;
        border-radius: 46px;
        padding: clamp(34px, 6vw, 70px);
        display: grid;
        grid-template-columns: 1fr .8fr;
        align-items: center;
        gap: 28px;
        color: #171114;
        background:
            radial-gradient(circle at 82% 24%, rgba(255,79,112,.34), transparent 22%),
            radial-gradient(circle at 70% 78%, rgba(124,92,255,.24), transparent 30%),
            #fffdfa;
        border: 1px solid rgba(23,17,20,.10);
        box-shadow: var(--fashion-shadow);
    }

    .gadget-order-banner::after {
        content: '';
        position: absolute;
        inset: 18px;
        border: 1px solid rgba(23,17,20,.08);
        border-radius: 32px;
        pointer-events: none;
    }

    .gadget-order-card { position: relative; z-index: 1; max-width: 640px; }
    .gadget-order-card p { display: inline-flex; margin: 0 0 14px; background: #c8ff4d; border-radius: 999px; padding: 8px 13px; color: #171114; font-weight: 950; text-transform: uppercase; letter-spacing: .13em; font-size: 11px; }
    .gadget-order-card h3 { font-family: 'Fraunces', serif; font-size: clamp(36px, 5.5vw, 78px); line-height: .9; letter-spacing: -0.065em; margin: 0 0 26px; }
    .btn-order-now { background: #171114; color: #fff !important; padding: 16px 26px; }
    .gadget-order-image { position: relative; z-index: 1; display: grid; place-items: center; }
    .fashion-orbit { width: min(280px, 62vw); aspect-ratio: 1; border-radius: 50%; background: conic-gradient(from 140deg, #ff4f70, #ffd166, #c8ff4d, #55d7ff, #7c5cff, #ff4f70); padding: 18px; animation: orbitSpin 12s linear infinite; }
    .fashion-orbit > div { width: 100%; height: 100%; border-radius: 50%; background: #fffdfa; display: grid; place-items: center; font-size: 76px; animation: orbitSpin 12s linear infinite reverse; }
    @keyframes orbitSpin { to { transform: rotate(360deg); } }

    @media (max-width: 980px) { .gadget-category-grid { grid-template-columns: repeat(2, 1fr); } .gadget-order-banner { grid-template-columns: 1fr; text-align: center; } .gadget-order-card { margin-inline: auto; } }
    @media (max-width: 560px) { .gadget-category-grid { grid-template-columns: 1fr; } }
</style>
@endPushOnce

<section class="gadget-section gadget-categories" aria-labelledby="gadget-categories-title">
    <div class="gadget-container">
        <div class="gadget-section-heading">
            <div>
                <h2 id="gadget-categories-title">Shop by mood</h2>
                <p>Choose a direction first: polished, playful, relaxed, or bold.</p>
            </div>
        </div>

        <div class="gadget-category-grid">
            @foreach ($categoryItems->take(4) as $category)
                @include('shop::homepage.partials.category-card', ['category' => $category])
            @endforeach
        </div>

        <div class="gadget-order-banner">
            <div class="gadget-order-card">
                <p>Easy outfit building</p>
                <h3>Find pieces that mix, match, and stand out.</h3>
                <a href="{{ route('shop.search.index') }}" class="btn-order-now">Start Styling</a>
            </div>

            <div class="gadget-order-image" aria-hidden="true">
                <div class="fashion-orbit"><div>🧥</div></div>
            </div>
        </div>
    </div>
</section>
