@push('meta')
    <meta name="description" content="@lang('shop::app.checkout.onepage.index.checkout')">
    <meta name="keywords" content="@lang('shop::app.checkout.onepage.index.checkout')">
@endpush

@push('styles')
    @bagistoVite(['src/Resources/assets/css/app.css'])
@endpush

@push('scripts')
    @bagistoVite(['src/Resources/assets/js/app.js'])
@endpush

@php
    $initialCheckoutStep = match (request('step')) {
        'payment', 'review' => request('step'),
        'shipping' => 'payment',
        default => 'address',
    };
@endphp

<main class="min-h-screen bg-white">
    <div class="border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center px-6 py-4 max-lg:px-8 max-sm:px-4">
            <a
                href="{{ route('shop.home.index') }}"
                class="flex min-h-[30px] items-center"
                aria-label="@lang('shop::checkout.onepage.index.bagisto')"
            >
                <img
                    src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                    alt="{{ config('app.name') }}"
                    width="131"
                    height="29"
                >
            </a>

        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-10 max-lg:px-8 max-sm:px-4 lg:py-12">
        <v-checkout>
            <x-shop::shimmer.checkout.onepage />
        </v-checkout>
    </div>
</main>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-checkout-template"
    >
        <template v-if="! cart">
            <x-shop::shimmer.checkout.onepage />
        </template>

        <template v-else>
            <div
                id="steps-container"
                class="grid gap-8 xl:grid-cols-[minmax(0,1.05fr)_minmax(24rem,0.95fr)]"
            >
                <div class="space-y-6">
                    <div class="space-y-3">
                        @guest('customer')
                            @include('shop::checkout.login')
                        @endguest

                        @include('shop::checkout.coupon')
                    </div>

                    @include('shop::checkout.onepage.address')
                </div>

                <aside class="space-y-6 rounded-[2rem] bg-slate-100 p-5 ring-1 ring-slate-200 lg:sticky lg:top-8 lg:p-6">
                    @include('theme-default::storefront.checkout-summary')

                    @include('shop::checkout.onepage.payment')

                    <div
                        class="flex justify-end"
                        v-if="canPlaceOrder"
                    >
                        <template v-if="cart.payment_method == 'paypal_smart_button'">
                            {!! view_render_event('bagisto.shop.checkout.onepage.summary.paypal_smart_button.before') !!}

                            <v-paypal-smart-button></v-paypal-smart-button>

                            {!! view_render_event('bagisto.shop.checkout.onepage.summary.paypal_smart_button.after') !!}
                        </template>

                        <template v-else>
                            <x-shop::button
                                type="button"
                                class="primary-button w-full rounded-2xl bg-navyBlue px-11 py-3 max-md:mb-4 max-md:max-w-full max-md:rounded-lg max-sm:py-1.5"
                                :title="trans('shop::app.checkout.onepage.summary.place-order')"
                                ::disabled="isPlacingOrder"
                                ::loading="isPlacingOrder"
                                @click="placeOrder"
                            />
                        </template>
                    </div>
                </aside>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-checkout', {
            template: '#v-checkout-template',

            data() {
                return {
                    cart: null,

                    checkoutState: null,

                    displayTax: {
                        prices: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_prices') }}",

                        subtotal: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_subtotal') }}",

                        shipping: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_shipping_amount') }}",
                    },

                    isPlacingOrder: false,

                    currentStep: @json($initialCheckoutStep),

                    paymentMethods: null,

                    canPlaceOrder: false,
                }
            },

            mounted() {
                this.getCheckoutState();
            },

            methods: {
                getCheckoutState() {
                    this.$axios.get("{{ route('shop.checkout.onepage.state') }}")
                        .then(response => {
                            this.checkoutState = response.data.data;
                            this.cart = response.data.data.cart;
                            this.paymentMethods = response.data.data.payment_methods;

                            this.scrollToCurrentStep();
                        })
                        .catch(error => {});
                },

                stepForward(step) {
                    this.currentStep = step;

                    if (step == 'review') {
                        this.canPlaceOrder = true;

                        return;
                    }

                    this.canPlaceOrder = false;

                    if (this.currentStep == 'payment') {
                        this.paymentMethods = null;
                    }
                },

                stepProcessed(data) {
                    if (this.currentStep == 'payment') {
                        this.paymentMethods = data;
                    }

                    this.getCheckoutState();
                },

                scrollToCurrentStep() {
                    let container = document.getElementById('steps-container');

                    if (! container) {
                        return;
                    }

                    container.scrollIntoView({
                        behavior: 'smooth',
                        block: 'end'
                    });
                },

                placeOrder() {
                    this.isPlacingOrder = true;

                    this.$axios.post('{{ route('shop.checkout.onepage.orders.store') }}')
                        .then(response => {
                            if (response.data.data.redirect) {
                                window.location.href = response.data.data.redirect_url;
                            } else {
                                window.location.href = '{{ route('shop.checkout.onepage.success') }}';
                            }

                            this.isPlacingOrder = false;
                        })
                        .catch(error => {
                            this.isPlacingOrder = false

                            this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                        });
                }
            },
        });
    </script>
@endPushOnce
