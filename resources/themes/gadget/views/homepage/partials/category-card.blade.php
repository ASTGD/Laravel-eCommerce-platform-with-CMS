<a href="{{ $category['url'] }}" class="gadget-category-card">
    <span class="gadget-category-card__image">
        @if ($category['image'])
            <img src="{{ $category['image'] }}" alt="{{ $category['name'] }}" loading="lazy">
        @else
            <span>{{ \Illuminate\Support\Str::of($category['name'])->substr(0, 1)->upper() }}</span>
        @endif
    </span>

    <span>{{ $category['name'] }}</span>
</a>
