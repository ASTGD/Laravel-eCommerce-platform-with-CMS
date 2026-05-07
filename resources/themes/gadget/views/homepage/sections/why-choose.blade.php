<section class="gadget-section gadget-why" aria-labelledby="gadget-why-title">
    <div class="gadget-container">
        <div class="gadget-section-heading gadget-section-heading--center">
            <h2 id="gadget-why-title">What Makes Us Different</h2>
            <p>Clear benefits that make your shopping experience better</p>
        </div>

        <div class="gadget-why__grid">
            @foreach ([
                ['Premium Quality Products', 'We handpick every product to ensure top-notch quality.', 'PQ'],
                ['Fast & Secure Delivery', 'Get your items delivered quickly with full tracking.', 'FD'],
                ['Secure Payment', 'Get your items delivered quickly with secure payment options.', 'SP'],
                ['Easy Returns & Refunds', 'Changed your mind? No worries. Enjoy a smooth and simple return.', 'ER'],
            ] as $benefit)
                <article class="gadget-benefit-card">
                    <span>{{ $benefit[2] }}</span>
                    <h3>{{ $benefit[0] }}</h3>
                    <p>{{ $benefit[1] }}</p>
                </article>
            @endforeach

            <div class="gadget-why__media"></div>
        </div>
    </div>
</section>
