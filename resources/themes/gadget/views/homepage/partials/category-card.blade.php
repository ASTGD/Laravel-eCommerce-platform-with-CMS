@pushOnce('styles')
<style>
    .gadget-category-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        transition: 0.4s;
    }

    .gadget-category-card:hover {
        background: #ffffff;
        border-color: #3b82f6;
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(59, 130, 246, 0.08);
    }

    .gadget-category-card__image {
        width: 60px;
        height: 60px;
        background: #ffffff;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        font-size: 24px;
        font-weight: 800;
        color: #3b82f6;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
    }

    .gadget-category-card span:not(.gadget-category-card__image) {
        font-weight: 700;
        color: #1e293b;
        font-size: 14px;
        text-align: center;
    }
</style>
@endpushOnce

<a href="{{ $category['url'] }}" class="gadget-category-card">
    <span class="gadget-category-card__image">
        @if ($category['image'])
            <img src="{{ $category['image'] }}" alt="{{ $category['name'] }}" style="width: 70%; height: 70%; object-fit: contain;">
        @else
            <span>{{ \Illuminate\Support\Str::of($category['name'])->substr(0, 1)->upper() }}</span>
        @endif
    </span>

    <span>{{ $category['name'] }}</span>
</a>
