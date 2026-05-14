@php($featureProduct = collect($products)->first())

@pushOnce('styles')
<style>
    .gadget-affiliate-banner {
        width: 100%;
        min-height: 550px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        background: #f8fafc;
    }

    .affiliate-bg-image {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
        opacity: 0.9;
        filter: brightness(1.05) contrast(1.02);
    }

    .affiliate-overlay {
        position: absolute;
        inset: 0;
        z-index: 2;
        background: linear-gradient(90deg, rgba(255,255,255,1) 0%, rgba(255,255,255,0.6) 50%, rgba(255,255,255,0.2) 100%);
    }

    .affiliate-mesh {
        position: absolute;
        width: 120%;
        height: 120%;
        z-index: 3;
        background: 
            radial-gradient(circle at 10% 10%, rgba(59, 130, 246, 0.2) 0%, transparent 40%),
            radial-gradient(circle at 90% 90%, rgba(236, 72, 153, 0.2) 0%, transparent 40%),
            radial-gradient(circle at 50% 50%, rgba(34, 211, 238, 0.15) 0%, transparent 60%);
        filter: blur(80px);
        animation: affiliateMesh 20s infinite alternate ease-in-out;
    }

    @keyframes affiliateMesh {
        0% { transform: translate(0, 0) rotate(0deg); }
        100% { transform: translate(-5%, 5%) rotate(10deg); }
    }

    .affiliate-container {
        position: relative;
        z-index: 10;
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        padding: 60px 40px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 60px;
    }

    .affiliate-left {
        flex: 1.2;
        text-align: left;
    }

    .affiliate-right {
        flex: 0.8;
        text-align: left;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        background: rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(20px);
        padding: 40px;
        border-radius: 32px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 30px 60px rgba(0,0,0,0.05);
    }

    .affiliate-eyebrow {
        display: inline-block;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.3em;
        color: #3b82f6;
        margin-bottom: 25px;
        font-size: 14px;
        background: rgba(59, 130, 246, 0.1);
        padding: 8px 24px;
        border-radius: 100px;
        backdrop-filter: blur(10px);
    }

    .affiliate-left h2 {
        font-size: clamp(40px, 6vw, 75px);
        font-weight: 950;
        color: #0f172a;
        line-height: 1.1;
        margin-bottom: 30px;
        letter-spacing: -0.05em;
        padding-bottom: 10px;
    }

    .affiliate-left h2 span {
        display: block;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .affiliate-left p {
        font-size: clamp(18px, 1.8vw, 22px);
        color: #475569;
        max-width: 650px;
        line-height: 1.6;
        font-weight: 500;
    }

    .affiliate-benefits {
        list-style: none;
        padding: 0;
        margin: 0 0 35px 0;
    }

    .affiliate-benefits li {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #0f172a;
        font-weight: 700;
        font-size: 18px;
        margin-bottom: 15px;
    }

    .affiliate-benefits li svg {
        color: #3b82f6;
    }

    .btn-affiliate-join {
        display: inline-flex;
        align-items: center;
        gap: 15px;
        background: #0f172a;
        color: #ffffff !important;
        padding: 20px 40px;
        border-radius: 20px;
        font-weight: 900;
        text-decoration: none !important;
        font-size: 18px;
        transition: 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.2);
        border: 1px solid #0f172a;
        width: 100%;
        justify-content: center;
    }

    .btn-affiliate-join:hover {
        background: #3b82f6;
        border-color: #3b82f6;
        transform: translateY(-5px);
        box-shadow: 0 30px 60px rgba(59, 130, 246, 0.4);
    }

    @media (max-width: 991px) {
        .affiliate-container { flex-direction: column; text-align: center; gap: 40px; padding: 60px 20px; }
        .affiliate-left, .affiliate-right { text-align: center; align-items: center; }
        .affiliate-left p { margin: 0 auto; }
        .affiliate-benefits { margin-bottom: 25px; }
    }
</style>
@endpushOnce

<section class="gadget-affiliate-banner" aria-label="Join our affiliate program">
    <!-- Background Layer -->
    <img 
        src="https://images.unsplash.com/photo-1551434678-e076c223a692?auto=format&fit=crop&q=80&w=2000" 
        alt="Modern Affiliate Tech" 
        class="affiliate-bg-image"
    >

    <!-- Visual Polish -->
    <div class="affiliate-overlay"></div>
    <div class="affiliate-mesh"></div>
    
    <div class="affiliate-container">
        <!-- Left Column: Core Message -->
        <div class="affiliate-left">
            <span class="affiliate-eyebrow">Affiliate Opportunity</span>
            
            <h2>
                EARN MONEY
                <span>JOIN Affiliate Program</span>
            </h2>
            
            <p>Partner with the industry leader in futuristic gadget tech and transform your network into a recurring revenue stream.</p>
        </div>

        <!-- Right Column: Call to Action + Benefits -->
        <div class="affiliate-right">
            <ul class="affiliate-benefits">
                <li>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Up to 50% Commission</span>
                </li>
                <li>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Instant Payout System</span>
                </li>
                <li>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Premium Marketing Kit</span>
                </li>
            </ul>

            <a href="{{ route('shop.search.index') }}" class="btn-affiliate-join">
                <span>Register Now</span>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>
    </div>
</section>







