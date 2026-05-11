@pushOnce('styles')
<style>
    .gadget-testimonials {
        padding: 100px 0;
        background: #ffffff;
    }

    .gadget-testimonial-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }

    .gadget-testimonial-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 40px;
        border-radius: 32px;
        position: relative;
        transition: 0.4s;
    }

    .gadget-testimonial-card:hover {
        background: #ffffff;
        border-color: #3b82f6;
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.04);
    }

    .gadget-quote {
        font-size: 60px;
        color: #cbd5e1;
        position: absolute;
        top: 20px;
        right: 40px;
        font-family: serif;
        opacity: 0.5;
    }

    .gadget-testimonial-card p {
        font-size: 16px;
        color: #475569;
        line-height: 1.7;
        margin-bottom: 30px;
        font-style: italic;
    }

    .gadget-testimonial-card__buyer {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .buyer-avatar {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-weight: 800;
        font-size: 14px;
    }

    .gadget-testimonial-card__buyer strong {
        display: block;
        color: #1e293b;
        font-size: 16px;
    }

    .gadget-testimonial-card__buyer small {
        color: #64748b;
        font-size: 13px;
    }

    @media (max-width: 991px) {
        .gadget-testimonial-grid { grid-template-columns: 1fr; }
    }
</style>
@endpushOnce

<section class="gadget-section gadget-testimonials" aria-labelledby="gadget-testimonials-title">
    <div class="gadget-container">
        <div class="gadget-section-heading" style="text-align: center; justify-content: center; flex-direction: column; align-items: center;">
            <h2 id="gadget-testimonials-title">Trusted by Tech Enthusiasts</h2>
            <p style="color: #64748b; font-size: 18px; margin-top: 10px;">Hear from our community about their experience</p>
        </div>

        <div class="gadget-testimonial-grid">
            @php($names = ['Sarah Jenkins', 'Michael Chen', 'Elena Rodriguez'])
            @php($initials = ['SJ', 'MC', 'ER'])
            @foreach (range(0, 2) as $index)
                <article class="gadget-testimonial-card">
                    <span class="gadget-quote">“</span>
                    <p>The build quality is incredible. Fast delivery and the attention to detail in the design is exactly what I was looking for.</p>
                    <div class="gadget-testimonial-card__buyer">
                        <div class="buyer-avatar">{{ $initials[$index] }}</div>
                        <div>
                            <strong>{{ $names[$index] }}</strong>
                            <small>Verified Explorer</small>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
