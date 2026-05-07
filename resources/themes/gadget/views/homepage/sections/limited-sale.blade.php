<section class="gadget-sale">
    <div class="gadget-sale__panel">
        <h2>Don't Miss Out! Sales Ends In</h2>

        <div class="gadget-sale__timer" aria-label="Sale countdown">
            @foreach ([['6', 'Days'], ['23', 'Hours'], ['59', 'Minutes'], ['53', 'Seconds']] as $item)
                <div class="gadget-sale__unit">
                    <strong>{{ $item[0] }}</strong>
                    <span>{{ $item[1] }}</span>
                </div>
            @endforeach
        </div>

        <a href="{{ route('shop.search.index') }}" class="gadget-button gadget-button--primary">
            <span></span>
            Shop Now
        </a>
    </div>
</section>
