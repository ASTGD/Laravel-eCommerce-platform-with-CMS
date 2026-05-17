@pushOnce('styles')
<style>
    .gadget-faq {
        padding: 80px 0;
        background-color: #ffffff;
        position: relative;
        overflow: hidden;
    }

    .faq-wrapper {
        position: relative;
        z-index: 1;
        max-width: 800px;
        margin: 0 auto;
    }

    .faq-title {
        text-align: center;
        margin-bottom: 50px;
    }

    .faq-title h2 {
        font-size: 36px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 15px;
    }

    .faq-title p {
        color: #64748b;
        font-size: 16px;
    }

    .faq-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .faq-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .faq-item:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .faq-item.active {
        background: #ffffff;
        border-color: #38bdf8;
        box-shadow: 0 4px 20px rgba(56, 189, 248, 0.1);
    }

    .faq-header {
        width: 100%;
        padding: 20px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: none;
        border: none;
        outline: none;
        cursor: pointer;
        text-align: left;
    }

    .faq-header h3 {
        font-size: 18px;
        font-weight: 600;
        color: #0f172a;
        transition: color 0.3s ease;
    }

    .faq-item:hover .faq-header h3 {
        color: #0284c7;
    }

    .faq-item.active .faq-header h3 {
        color: #0284c7;
    }

    .faq-icon {
        width: 24px;
        height: 24px;
        position: relative;
        transition: transform 0.3s ease;
    }

    .faq-icon::before, .faq-icon::after {
        content: '';
        position: absolute;
        background-color: #64748b;
        border-radius: 2px;
    }

    .faq-icon::before {
        top: 11px;
        left: 4px;
        width: 16px;
        height: 2px;
    }

    .faq-icon::after {
        top: 4px;
        left: 11px;
        width: 2px;
        height: 16px;
        transition: transform 0.3s ease;
    }

    .faq-item.active .faq-icon {
        transform: rotate(180deg);
    }

    .faq-item.active .faq-icon::after {
        transform: rotate(90deg); /* Makes it a minus sign */
    }

    /* CSS-only transition support with fixed max-height */
    .faq-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #f8fafc;
    }

    .faq-item.active .faq-content {
        max-height: 300px; /* Fixed max-height for CSS transition */
    }

    .faq-content-inner {
        padding: 20px 25px;
        color: #4b5563;
        font-size: 15px;
        line-height: 1.6;
        border-top: 1px solid #e2e8f0;
    }
</style>
@endpushOnce

<section class="gadget-section gadget-faq" aria-labelledby="faq-title">
    <div class="gadget-container">
        <div class="faq-wrapper">
            <div class="faq-title" id="faq-title">
                <h2>Frequently Asked Questions</h2>
                <p>Find answers to common questions about our services and products.</p>
            </div>

            <div class="faq-list">
                <!-- FAQ Item 1 -->
                <div class="faq-item">
                    <button class="faq-header">
                        <h3>What are your delivery charges?</h3>
                        <div class="faq-icon"></div>
                    </button>
                    <div class="faq-content">
                        <div class="faq-content-inner">
                            <p>We offer express home delivery inside Dhaka for only <strong>50 Taka</strong>. For locations outside Dhaka, standard delivery charges apply and will be calculated at checkout.</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="faq-item">
                    <button class="faq-header">
                        <h3>How long does delivery take?</h3>
                        <div class="faq-icon"></div>
                    </button>
                    <div class="faq-content">
                        <div class="faq-content-inner">
                            <p>For deliveries inside Dhaka, we aim for same-day or next-day delivery. Outside Dhaka, it typically takes 2-4 business days depending on the courier service.</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 3 -->
                <div class="faq-item">
                    <button class="faq-header">
                        <h3>Do you offer warranty on gadgets?</h3>
                        <div class="faq-icon"></div>
                    </button>
                    <div class="faq-content">
                        <div class="faq-content-inner">
                            <p>Yes, most of our electronic products and gadgets come with a brand warranty. The specific warranty period is mentioned on the product page. Please keep your invoice for warranty claims.</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 4 -->
                <div class="faq-item">
                    <button class="faq-header">
                        <h3>What is your return policy?</h3>
                        <div class="faq-icon"></div>
                    </button>
                    <div class="faq-content">
                        <div class="faq-content-inner">
                            <p>We accept returns within 7 days of delivery for products that are defective or not as described. The product must be unused and in its original packaging. Please contact our support team to initiate a return.</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 5 -->
                <div class="faq-item">
                    <button class="faq-header">
                        <h3>How can I track my order?</h3>
                        <div class="faq-icon"></div>
                    </button>
                    <div class="faq-content">
                        <div class="faq-content-inner">
                            <p>Once your order is shipped, you will receive a tracking number via SMS and email. You can use this number on our website or the courier's website to track your shipment.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Using Event Delegation on Document - Foolproof method --}}
<script>
    (function() {
        if (window.faqListenerAttached) return;
        window.faqListenerAttached = true;
        
        function handleFaqClick(e) {
            const header = e.target.closest('.faq-header');
            if (!header) return;

            const item = header.closest('.faq-item');
            if (!item) return;

            e.preventDefault();

            const isActive = item.classList.contains('active');

            // Close all items
            const allItems = document.querySelectorAll('.faq-item');
            allItems.forEach(i => i.classList.remove('active'));

            // Toggle current item
            if (!isActive) {
                item.classList.add('active');
            }
        }

        document.addEventListener('click', handleFaqClick);
    })();
</script>
