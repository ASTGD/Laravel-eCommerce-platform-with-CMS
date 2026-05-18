@pushOnce('styles')
<style>
    .gadget-feature-cards {
        padding: 40px 0 80px 0;
    }

    .gadget-feature-cards .gadget-container {
        max-width: 1600px !important;
        width: 100%;
        margin-left: auto;
        margin-right: auto;
    }

    .feature-cards-wrapper {
        display: flex;
        gap: 30px;
        align-items: stretch;
    }

    .feature-banner {
        flex: 1;
        border-radius: 20px;
        overflow: hidden;
        display: block;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.08);
    }

    .feature-banner:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.12);
    }

    .feature-banner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    @media (max-width: 992px) {
        .feature-cards-wrapper {
            flex-direction: column;
            gap: 20px;
        }
    }
</style>
@endpushOnce

<section class="gadget-section gadget-feature-cards">
    <div class="gadget-container">
        <div class="feature-cards-wrapper">

            <a href="{{ route('shop.home.index') }}" class="feature-banner" aria-label="EMI Facilities">
                <img src="{{ asset('images/12.png') }}" alt="#" loading="lazy">
            </a>

            <a href="{{ route('shop.home.index') }}" class="feature-banner" aria-label="Fast Delivery">
                <img src="{{ asset('images/13.png') }}" alt="#" loading="lazy">
            </a>

        </div>
    </div>
</section>