@pushOnce('styles')
<style>
    .gadget-why {
        padding: 120px 0;
        background: #f8fafc;
        position: relative;
    }

    .gadget-why__grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 32px;
    }

    .gadget-benefit-card {
        background: #ffffff;
        padding: 60px 40px;
        border-radius: 40px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.02);
        display: flex;
        flex-direction: column;
    }

    .gadget-benefit-card:hover {
        transform: translateY(-12px);
        border-color: #3b82f6;
        box-shadow: 0 40px 80px rgba(59, 130, 246, 0.1);
    }

    .benefit-icon-wrap {
        width: 70px;
        height: 70px;
        background: #eff6ff;
        border-radius: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 35px;
        position: relative;
        transition: 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        color: #3b82f6;
    }

    .gadget-benefit-card:hover .benefit-icon-wrap {
        background: #3b82f6;
        color: #ffffff;
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 15px 30px rgba(59, 130, 246, 0.3);
    }

    .benefit-icon-wrap svg {
        width: 32px;
        height: 32px;
        transition: 0.5s;
    }

    .gadget-benefit-card:hover svg {
        transform: scale(1.1);
    }

    .benefit-icon-glow {
        position: absolute;
        width: 100%;
        height: 100%;
        background: rgba(59, 130, 246, 0.3);
        filter: blur(25px);
        border-radius: 50%;
        opacity: 0;
        transition: 0.5s;
    }

    .gadget-benefit-card:hover .benefit-icon-glow {
        opacity: 1;
    }

    .gadget-benefit-card h3 {
        font-size: 22px;
        font-weight: 950;
        color: #0f172a;
        margin-bottom: 18px;
        letter-spacing: -0.04em;
    }

    .gadget-benefit-card p {
        font-size: 16px;
        color: #64748b;
        line-height: 1.7;
        font-weight: 500;
    }

    @media (max-width: 1200px) {
        .gadget-why__grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 600px) {
        .gadget-why__grid { grid-template-columns: 1fr; }
        .gadget-benefit-card { padding: 45px 30px; }
    }
</style>
@endpushOnce

<section class="gadget-section gadget-why" aria-labelledby="gadget-why-title">
    <div class="gadget-container">
        <div class="gadget-section-heading" style="text-align: center; justify-content: center; flex-direction: column; align-items: center; margin-bottom: 120px; position: relative; z-index: 10;">
            <!-- Title Aura -->
            <div style="position: absolute; width: 400px; height: 120px; background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%); top: 50%; left: 50%; transform: translate(-50%, -50%); filter: blur(50px); z-index: -1;"></div>
            
            <p style="color: #3b82f6; font-weight: 900; text-transform: uppercase; letter-spacing: 0.35em; font-size: 13px; margin-bottom: 20px;">Advanced Ecosystem</p>
            <h2 id="gadget-why-title" style="font-size: 56px; font-weight: 950; letter-spacing: -0.06em; line-height: 1; background: linear-gradient(135deg, #0f172a 0%, #3b82f6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 10px 20px rgba(59, 130, 246, 0.1)); padding-bottom: 10px;">Engineered for Excellence</h2>
        </div>

        <div class="gadget-why__grid">
            @php
                $benefits = [
                    [
                        'title' => 'Elite Build Quality', 
                        'text' => 'Every product is handpicked for durability and performance.', 
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12l4 6-10 12L2 9z"></path><path d="M11 3v18"></path><path d="M5 3l7 18 7-18"></path><path d="M2 9h20"></path></svg>'
                    ],
                    [
                        'title' => 'Global Logistics', 
                        'text' => 'Fast, secure, and trackable shipping to your doorstep.', 
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>'
                    ],
                    [
                        'title' => 'Secure Payments', 
                        'text' => 'Multi-layered encryption for a safe shopping experience.', 
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>'
                    ],
                    [
                        'title' => 'Priority Support', 
                        'text' => 'Dedicated tech experts ready to assist you 24/7.', 
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path></svg>'
                    ],
                ];
            @endphp

            @foreach ($benefits as $benefit)
                <article class="gadget-benefit-card">
                    <div class="benefit-icon-wrap">
                        <div class="benefit-icon-glow"></div>
                        {!! $benefit['icon'] !!}
                    </div>
                    <h3>{{ $benefit['title'] }}</h3>
                    <p>{{ $benefit['text'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
