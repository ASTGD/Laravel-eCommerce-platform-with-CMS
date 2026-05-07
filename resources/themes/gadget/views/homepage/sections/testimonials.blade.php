<section class="gadget-section gadget-testimonials" aria-labelledby="gadget-testimonials-title">
    <div class="gadget-container">
        <div class="gadget-section-heading gadget-section-heading--center">
            <h2 id="gadget-testimonials-title">What Our Clients Say</h2>
            <p>Clear benefits that make your shopping experience better</p>
        </div>

        <div class="gadget-testimonial-grid">
            @foreach (range(1, 3) as $index)
                <article class="gadget-testimonial-card">
                    <span class="gadget-quote">"</span>
                    <p>Excellent quality and super fast delivery. The product looked even better in person!</p>
                    <div class="gadget-testimonial-card__buyer">
                        <span>JD</span>
                        <div>
                            <strong>John Davidson</strong>
                            <small>Verified Buyer</small>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
