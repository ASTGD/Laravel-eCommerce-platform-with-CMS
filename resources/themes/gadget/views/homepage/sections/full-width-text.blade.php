@pushOnce('styles')
<style>
    .gadget-about-text {
        padding: 80px 0;
        background: #ffffff;
        font-family: 'Inter', sans-serif;
    }
    
    .gadget-about-text .text-center {
        text-align: center;
    }

    .about-header {
        max-width: 700px;
        margin: 0 auto 60px;
    }

    .about-header h2 {
        font-size: 32px;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.4;
        margin-bottom: 24px;
    }

    .about-header h2 .text-blue {
        color: #3b82f6;
        font-weight: 700;
        display: block;
        margin-top: 4px;
    }

    .about-header .subtitle {
        font-size: 15px;
        color: #64748b;
        line-height: 1.7;
    }

    .read-more-wrapper {
        position: relative;
        max-width: 900px;
        margin: 0 auto;
    }

    .read-more-content {
        max-height: 360px;
        overflow: hidden;
        transition: max-height 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .read-more-wrapper.is-expanded .read-more-content {
        max-height: 1500px;
    }

    .text-block {
        margin-bottom: 40px;
        text-align: left;
    }

    .text-block h3 {
        font-size: 16px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 16px;
        padding-left: 14px;
        border-left: 3px solid #3b82f6;
        letter-spacing: 0.02em;
    }

    .text-block p {
        font-size: 14px;
        color: #64748b;
        line-height: 1.8;
        margin-bottom: 16px;
    }

    .text-block p strong {
        color: #475569;
        font-weight: 700;
    }

    .read-more-fade {
        position: absolute;
        bottom: 20px;
        left: 0;
        width: 100%;
        height: 150px;
        background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(255,255,255,1) 85%);
        pointer-events: none;
        z-index: 5;
    }

    .read-more-action {
        text-align: center;
        margin-top: -20px;
        position: relative;
        z-index: 10;
        padding-bottom: 20px;
    }

    .read-more-wrapper.is-expanded .read-more-action {
        margin-top: 10px;
    }

    .btn-read-more {
        background: #ffffff;
        color: #3b82f6;
        border: 1px solid #e2e8f0;
        padding: 10px 24px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.05em;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
    }

    .btn-read-more:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
        border-color: #cbd5e1;
        transform: translateY(-2px);
    }

    .about-footer {
        margin-top: 30px;
        padding-top: 40px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .about-footer h4 {
        font-size: 18px;
        font-weight: 800;
        color: #334155;
        margin-bottom: 6px;
    }

    .about-footer p {
        font-size: 11px;
        color: #94a3b8;
        letter-spacing: 0.1em;
        margin-bottom: 24px;
        font-weight: 600;
    }

    .btn-explore {
        background: #3b82f6;
        color: #ffffff;
        padding: 12px 30px;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 600;
        display: inline-block;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .btn-explore:hover {
        background: #2563eb;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4);
    }
</style>
@endpushOnce

<section class="gadget-section gadget-about-text">
    <div class="gadget-container">
        <div class="about-header text-center">
            <h2>
                The Ultimate Destination For<br>
                Next-Generation Smart Devices<br>
                <span class="text-blue">{{ core()->getCurrentChannel()->name ?? 'Premium Gadgets' }}</span>
            </h2>
            <p class="subtitle">
                Premium International Gadgets Made Affordable. Trusted by tech enthusiasts worldwide. We deliver high-performance, 100% authentic gadgets designed for a smarter, more connected everyday life.
            </p>
        </div>

        <v-read-more>
            <template v-slot:default="{ isExpanded, toggle }">
                <div class="read-more-wrapper" :class="{ 'is-expanded': isExpanded }">
                    <div class="read-more-content">
                        
                        <div class="text-block">
                            <h3>Curated for Tech Enthusiasts</h3>
                            <p>Welcome to a seamless e-commerce experience designed for those who demand innovation. Our curated collection focuses strictly on practical, high-performance gadgets that simplify your daily routines and boost your efficiency.</p>
                            <p>We are a dedicated tech ecosystem, proudly serving customers with cutting-edge authentic products. We bypass the clutter of ordinary retail to focus solely on sourcing premium smart devices, audio equipment, and tech accessories through trusted global manufacturers. By specializing purely in smart gadgets, we can guarantee expert curation, rigorous quality control, and the best technical recommendations tailored precisely to your digital lifestyle.</p>
                        </div>

                        <div class="text-block">
                            <h3>OUR COMMITMENT TO THE DIGITAL LIFESTYLE</h3>
                            <p>We have played a pivotal role in redefining the modern smart home and personal audio market by introducing accessible, premium tech solutions.</p>
                            <p>From high-fidelity wireless audio systems to intelligent home automation sensors, we prioritize bringing you the best value without compromising on quality. Products in our catalog are rigorously tested to ensure they meet the highest standards of reliability. Through a community-driven approach and lightning-fast customer support, we have built a reputation of absolute trust—making tomorrow's technology easily accessible to everyday users today.</p>
                        </div>

                    </div>

                    <div class="read-more-fade" v-if="!isExpanded"></div>
                    <div class="read-more-action">
                        <button type="button" @click="toggle" class="btn-read-more">
                            @{{ isExpanded ? 'SHOW LESS' : 'READ MORE' }}
                            <svg v-if="!isExpanded" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                        </button>
                    </div>
                </div>
            </template>
        </v-read-more>

        <div class="about-footer text-center">
            <h4>Your Favourite Smart Gadget Store</h4>
            <p>PREMIUM GADGETS. AUTHENTIC QUALITY.</p>
            <a href="{{ route('shop.home.index') }}" class="btn-explore">Explore All Products &rarr;</a>
        </div>
    </div>
</section>

@pushOnce('scripts')
<script type="module">
    app.component('v-read-more', {
        data() {
            return {
                isExpanded: false
            }
        },
        methods: {
            toggle() {
                this.isExpanded = !this.isExpanded;
            }
        },
        render() {
            return this.$slots.default({
                isExpanded: this.isExpanded,
                toggle: this.toggle
            });
        }
    });
</script>
@endpushOnce
