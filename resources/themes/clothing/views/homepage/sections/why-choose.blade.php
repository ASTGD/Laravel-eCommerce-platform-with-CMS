@pushOnce('styles')
<style>
    .gadget-why { background: linear-gradient(180deg, #fffdfa, #fff1f3); }
    .gadget-why__grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: rgba(23,17,20,.10); border: 1px solid rgba(23,17,20,.10); border-radius: 38px; overflow: hidden; box-shadow: var(--fashion-shadow); }
    .gadget-benefit-card { background: rgba(255,253,250,.92); padding: clamp(24px, 4vw, 42px); min-height: 260px; display: flex; flex-direction: column; justify-content: space-between; }
    .benefit-icon { width: 58px; height: 58px; border-radius: 50%; background: #171114; color: #fff; display: grid; place-items: center; font-size: 23px; margin-bottom: 26px; }
    .gadget-benefit-card:nth-child(2) .benefit-icon { background: #ff4f70; }
    .gadget-benefit-card:nth-child(3) .benefit-icon { background: #7c5cff; }
    .gadget-benefit-card:nth-child(4) .benefit-icon { background: #c8ff4d; color: #171114; }
    .gadget-benefit-card h3 { font-size: 23px; line-height: 1; letter-spacing: -0.045em; margin: 0 0 12px; color: #171114; }
    .gadget-benefit-card p { color: #6f6468; line-height: 1.6; margin: 0; }
    @media (max-width: 980px) { .gadget-why__grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 560px) { .gadget-why__grid { grid-template-columns: 1fr; } }
</style>
@endPushOnce

<section class="gadget-section gadget-why" aria-labelledby="gadget-why-title">
    <div class="gadget-container">
        <div class="gadget-section-heading" style="justify-content: center; text-align: center;">
            <div>
                <h2 id="gadget-why-title">Made for real closets</h2>
                <p style="margin-inline: auto;">Good style should be easy to wear, easy to repeat, and easy to receive.</p>
            </div>
        </div>

        <div class="gadget-why__grid">
            @foreach ([
                ['Quality Feel', 'Soft fabrics and everyday fits selected for comfort and movement.', '✂️'],
                ['Fast Delivery', 'Reliable shipping so your new look arrives without the long wait.', '🚚'],
                ['Secure Checkout', 'Smooth ordering with safe payment and clear cart flow.', '🔒'],
                ['Style Support', 'Helpful assistance for sizing, orders, and outfit questions.', '💬'],
            ] as $benefit)
                <article class="gadget-benefit-card">
                    <div>
                        <div class="benefit-icon">{{ $benefit[2] }}</div>
                        <h3>{{ $benefit[0] }}</h3>
                    </div>
                    <p>{{ $benefit[1] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
