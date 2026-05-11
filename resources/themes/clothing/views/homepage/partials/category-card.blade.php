@pushOnce('styles')
<style>
    .gadget-category-card {
        position: relative;
        min-height: 172px;
        border-radius: 32px;
        padding: 24px;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 18px;
        text-decoration: none;
        overflow: hidden;
        background: #fff;
        border: 1px solid rgba(23,17,20,.10);
        box-shadow: 0 16px 44px rgba(23,17,20,.06);
        transition: transform .25s ease, box-shadow .25s ease;
    }

    .gadget-category-card::before {
        content: '';
        position: absolute;
        inset: auto -35px -60px auto;
        width: 180px;
        height: 180px;
        background: radial-gradient(circle, rgba(255,79,112,.34), transparent 65%);
        border-radius: 50%;
        transition: transform .35s ease;
    }

    .gadget-category-card:nth-child(2n)::before { background: radial-gradient(circle, rgba(124,92,255,.33), transparent 65%); }
    .gadget-category-card:nth-child(3n)::before { background: radial-gradient(circle, rgba(200,255,77,.55), transparent 65%); }

    .gadget-category-card:hover { transform: translateY(-6px); box-shadow: 0 24px 65px rgba(23,17,20,.10); }
    .gadget-category-card:hover::before { transform: scale(1.15); }

    .gadget-category-card__image {
        width: 64px;
        height: 64px;
        border-radius: 22px;
        display: grid;
        place-items: center;
        background: #171114;
        color: #fff;
        font-size: 24px;
        font-weight: 950;
        position: relative;
        z-index: 1;
        flex: none;
        overflow: hidden;
    }

    .gadget-category-card__image img { width: 100%; height: 100%; object-fit: cover; }
    .gadget-category-card span:not(.gadget-category-card__image) { color: #171114; font-size: 23px; line-height: 1; font-weight: 900; letter-spacing: -0.045em; position: relative; z-index: 1; }
</style>
@endPushOnce

<a href="{{ $category['url'] ?? route('shop.search.index') }}" class="gadget-category-card">
    <span>{{ $category['name'] ?? 'Collection' }}</span>
    <span class="gadget-category-card__image">
        @if (! empty($category['image']))
            <img src="{{ $category['image'] }}" alt="{{ $category['name'] ?? 'Collection' }}" loading="lazy">
        @else
            {{ mb_substr($category['name'] ?? 'C', 0, 1) }}
        @endif
    </span>
</a>
