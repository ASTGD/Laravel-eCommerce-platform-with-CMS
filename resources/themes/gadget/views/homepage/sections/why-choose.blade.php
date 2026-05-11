@pushOnce('styles')
<style>
    .gadget-why {
        padding: 100px 0;
        background: #f8fafc;
    }

    .gadget-why__grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
    }

    .gadget-benefit-card {
        background: #ffffff;
        padding: 40px 30px;
        border-radius: 28px;
        border: 1px solid #e2e8f0;
        transition: 0.4s;
    }

    .gadget-benefit-card:hover {
        border-color: #3b82f6;
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.03);
    }

    .benefit-icon {
        width: 50px;
        height: 50px;
        background: #eff6ff;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #3b82f6;
        font-weight: 800;
        font-size: 14px;
        margin-bottom: 24px;
    }

    .gadget-benefit-card h3 {
        font-size: 18px;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 16px;
    }

    .gadget-benefit-card p {
        font-size: 14px;
        color: #64748b;
        line-height: 1.6;
    }

    @media (max-width: 991px) {
        .gadget-why__grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 580px) {
        .gadget-why__grid { grid-template-columns: 1fr; }
    }
</style>
@endpushOnce

<section class="gadget-section gadget-why" aria-labelledby="gadget-why-title">
    <div class="gadget-container">
        <div class="gadget-section-heading" style="text-align: center; justify-content: center; flex-direction: column; align-items: center; margin-bottom: 60px;">
            <h2 id="gadget-why-title">Engineered for Excellence</h2>
            <p style="color: #64748b; font-size: 18px; margin-top: 10px;">Why thousands of digital natives choose us</p>
        </div>

        <div class="gadget-why__grid">
            @foreach ([
                ['Elite Build Quality', 'Every product is handpicked for durability and performance.', '💎'],
                ['Global Logistics', 'Fast, secure, and trackable shipping to your doorstep.', '🌐'],
                ['Secure Transactions', 'Multi-layered encryption for a safe shopping experience.', '🔒'],
                ['Support Ecosystem', 'Dedicated tech experts ready to assist you 24/7.', '🎧'],
            ] as $benefit)
                <article class="gadget-benefit-card">
                    <div class="benefit-icon">{{ $benefit[2] }}</div>
                    <h3>{{ $benefit[0] }}</h3>
                    <p>{{ $benefit[1] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
