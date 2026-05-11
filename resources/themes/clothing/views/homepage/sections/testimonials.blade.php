@pushOnce('styles')
<style>
    .gadget-testimonials { background: #fffdfa; }
    .gadget-testimonial-grid { display: grid; grid-template-columns: 1.1fr .9fr; gap: 22px; align-items: stretch; }
    .gadget-testimonial-card { position: relative; overflow: hidden; border-radius: 38px; padding: clamp(28px, 5vw, 52px); background: #171114; color: #fffdfa; min-height: 310px; display: flex; flex-direction: column; justify-content: space-between; }
    .gadget-testimonial-card:nth-child(2) { background: #fff8e8; color: #171114; }
    .gadget-testimonial-card:nth-child(3) { grid-column: 1 / -1; min-height: 230px; background: linear-gradient(135deg, #ff4f70, #7c5cff); color: #fff; }
    .gadget-quote { position: absolute; right: 28px; top: 8px; font-family: 'Fraunces', serif; font-size: 120px; line-height: 1; opacity: .16; }
    .gadget-testimonial-card p { position: relative; z-index: 1; font-family: 'Fraunces', serif; font-size: clamp(25px, 3vw, 42px); line-height: 1.05; letter-spacing: -0.045em; margin: 0 0 30px; max-width: 880px; }
    .gadget-testimonial-card__buyer { display: flex; align-items: center; gap: 14px; position: relative; z-index: 1; }
    .buyer-avatar { width: 48px; height: 48px; border-radius: 50%; display: grid; place-items: center; background: #c8ff4d; color: #171114; font-weight: 950; }
    .gadget-testimonial-card:nth-child(2) .buyer-avatar { background: #ff4f70; color: #fff; }
    .gadget-testimonial-card:nth-child(3) .buyer-avatar { background: #fff; color: #7c5cff; }
    .gadget-testimonial-card__buyer strong { display: block; font-weight: 950; }
    .gadget-testimonial-card__buyer small { color: currentColor; opacity: .68; }
    @media (max-width: 860px) { .gadget-testimonial-grid { grid-template-columns: 1fr; } .gadget-testimonial-card:nth-child(3) { grid-column: auto; } }
</style>
@endPushOnce

<section class="gadget-section gadget-testimonials" aria-labelledby="gadget-testimonials-title">
    <div class="gadget-container">
        <div class="gadget-section-heading" style="justify-content: center; text-align: center;">
            <div>
                <h2 id="gadget-testimonials-title">Loved in motion</h2>
                <p style="margin-inline: auto;">Real words from shoppers who want style without the extra effort.</p>
            </div>
        </div>

        <div class="gadget-testimonial-grid">
            @php($names = ['Nadia Rahman', 'Arif Hossain', 'Samira Khan'])
            @php($initials = ['NR', 'AH', 'SK'])
            @foreach (range(0, 2) as $index)
                <article class="gadget-testimonial-card">
                    <span class="gadget-quote">“</span>
                    <p>{{ [
                        'The colors look fresh, the fit feels comfortable, and delivery was quick.',
                        'I like that the pieces are easy to style without looking boring.',
                        'Finally, a store that feels vibrant but still clean and simple to shop.'
                    ][$index] }}</p>
                    <div class="gadget-testimonial-card__buyer">
                        <div class="buyer-avatar">{{ $initials[$index] }}</div>
                        <div>
                            <strong>{{ $names[$index] }}</strong>
                            <small>Verified customer</small>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
