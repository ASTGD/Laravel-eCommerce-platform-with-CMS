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

<main class="flex min-h-screen flex-col bg-[#f6f3f2] text-slate-900">
    @include('theme-default::storefront.checkout-header')

    <div class="mx-auto w-full max-w-[1440px] flex-1 px-6 py-10 lg:px-10 xl:px-12 lg:py-12">
        <v-checkout>
            <x-shop::shimmer.checkout.onepage />
        </v-checkout>
    </div>

    @include('theme-default::storefront.checkout-footer')
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
                class="grid gap-10 xl:grid-cols-[minmax(0,1.03fr)_minmax(22rem,0.97fr)]"
            >
                <div class="space-y-3">
                    @guest('customer')
                        @include('shop::checkout.login')
                    @endguest

                    @include('shop::checkout.coupon')
                </div>

                <div class="hidden xl:block"></div>

                <div class="flex h-full flex-col">
                    @include('shop::checkout.onepage.address')
                </div>

                <aside class="space-y-6 xl:sticky xl:top-8">
                    @include('theme-default::storefront.checkout-summary')

                    @include('shop::checkout.onepage.payment')

                    <template v-if="cart && cart.payment_method == 'paypal_smart_button'">
                        {!! view_render_event('bagisto.shop.checkout.onepage.summary.paypal_smart_button.before') !!}

                        <v-paypal-smart-button></v-paypal-smart-button>

                        {!! view_render_event('bagisto.shop.checkout.onepage.summary.paypal_smart_button.after') !!}
                    </template>

                    <template v-else>
                        <button
                            type="button"
                            class="primary-button flex w-full items-center justify-center rounded-2xl bg-[#2f5ec5] px-11 py-4 text-center text-sm font-semibold uppercase tracking-[0.2em] text-white shadow-sm transition hover:bg-[#244cad] max-md:rounded-xl max-md:px-8 max-md:py-3 max-md:text-xs max-md:tracking-[0.16em]"
                            :disabled="isPlacingOrder"
                            @click.prevent="submitOrder"
                        >
                            @lang('shop::app.checkout.onepage.summary.place-order')
                        </button>
                    </template>
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

                    pendingPlaceOrder: false,

                    currentStep: @json($initialCheckoutStep),

                    paymentMethods: null,

                }
            },

            mounted() {
                this.getCheckoutState();
            },

            methods: {
                getCheckoutState() {
                    return this.$axios.get("{{ route('shop.checkout.custom.state') }}")
                        .then(response => {
                            this.checkoutState = response.data.data;
                            this.cart = response.data.data.cart;
                            this.paymentMethods = response.data.data.payment_methods;

                            this.scrollToCurrentStep();
                        })
                        .catch(error => {});
                },

                submitOrder() {
                    if (this.isPlacingOrder) {
                        return;
                    }

                    const addressForm = document.getElementById('checkout-address-form');

                    if (! addressForm) {
                        this.placeOrder();

                        return;
                    }

                    this.pendingPlaceOrder = true;

                    addressForm.requestSubmit();
                },

                stepForward(step) {
                    this.currentStep = step;

                    if (this.currentStep == 'payment') {
                        this.paymentMethods = null;
                    }
                },

                stepProcessed(data) {
                    if (this.currentStep == 'payment') {
                        this.paymentMethods = data;
                    }

                    if (this.pendingPlaceOrder) {
                        return this.getCheckoutState().then(() => {
                            this.pendingPlaceOrder = false;

                            return this.$nextTick(() => this.placeOrder());
                        });
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

                    this.$axios.post('{{ route('shop.checkout.custom.orders.store') }}')
                        .then(response => {
                            if (response.data.data.redirect) {
                                window.location.href = response.data.data.redirect_url;
                            } else {
                                window.location.href = '{{ route('shop.checkout.success') }}';
                            }

                            this.isPlacingOrder = false;
                        })
                        .catch(error => {
                            this.isPlacingOrder = false

                            this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                        });
                },

                updateItemQuantity(itemId, quantity) {
                    this.$axios.put('{{ route('shop.api.checkout.cart.update') }}', {
                            qty: {
                                [itemId]: quantity,
                            },
                        })
                        .then((response) => {
                            this.cart = response.data.data;

                            return this.getCheckoutState();
                        })
                        .catch((error) => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message || 'Unable to update the cart quantity.',
                            });
                        });
                }
            },
        });
    </script>
@endPushOnce
