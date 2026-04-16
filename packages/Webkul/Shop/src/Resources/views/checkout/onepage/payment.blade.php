{!! view_render_event('bagisto.shop.checkout.onepage.payment_methods.before') !!}

<v-payment-methods
    :methods="paymentMethods"
    :cart="cart"
    @processing="stepForward"
    @processed="stepProcessed"
>
    <x-shop::shimmer.checkout.onepage.payment-method />
</v-payment-methods>

{!! view_render_event('bagisto.shop.checkout.onepage.payment_methods.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-payment-methods-template"
    >
        <section class="overflow-hidden rounded-[1.75rem] bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)] ring-1 ring-slate-200">
            <template v-if="! methods">
                <div class="p-6">
                    <x-shop::shimmer.checkout.onepage.payment-method />
                </div>
            </template>

            <template v-else>
                {!! view_render_event('bagisto.shop.checkout.onepage.payment_method.accordion.before') !!}

                <div class="px-6 py-6">
                    <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.32em] text-slate-400">
                                Payment
                            </p>

                            <h2 class="mt-2 text-xl font-semibold uppercase tracking-[0.18em] text-slate-900">
                                @lang('shop::app.checkout.onepage.payment.payment-method')
                            </h2>
                        </div>
                    </div>

                    <div class="space-y-3">
                        {!! view_render_event('bagisto.shop.checkout.onepage.payment_method.before') !!}

                        <div
                            class="group"
                            v-for="(payment, index) in methods"
                            :key="payment.method"
                        >
                            <input
                                type="radio"
                                name="payment[method]"
                                :value="payment.method"
                                :id="payment.method"
                                class="peer sr-only"
                                :checked="cart && cart.payment_method == payment.method"
                                @change="store(payment)"
                            >

                            <label
                                :for="payment.method"
                            class="flex min-h-[6.75rem] w-full cursor-pointer items-center gap-4 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-slate-300 peer-checked:border-[#2f5ec5] peer-checked:bg-blue-50/40"
                            >
                                <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white peer-checked:border-[#2f5ec5]">
                                    <span
                                        class="h-2 w-2 rounded-full bg-[#2f5ec5] transition"
                                        :class="cart && cart.payment_method == payment.method ? 'opacity-100' : 'opacity-0'"
                                    ></span>
                                </span>

                                <div class="min-w-0 flex-1">
                                    {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.title.before') !!}

                                    <div class="flex items-center justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-slate-900">
                                                @{{ payment.method_title }}
                                            </p>

                                            {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.title.after') !!}

                                            {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.description.before') !!}

                                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                                @{{ paymentDescription(payment) }}
                                            </p>

                                            {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.description.after') !!}
                                        </div>

                                        <div
                                            v-if="payment.image"
                                            class="ml-auto shrink-0"
                                            :class="paymentLogoFrameClass(payment.method)"
                                        >
                                            <img
                                                class="block h-full w-full"
                                                :class="paymentLogoClass(payment.method)"
                                                :style="paymentLogoStyle(payment.method)"
                                                :src="paymentLogoSrc(payment)"
                                                :alt="payment.method_title"
                                                :title="payment.method_title"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {!! view_render_event('bagisto.shop.checkout.payment-method.after') !!}

                                {{-- \Webkul\Payment\Payment::getAdditionalDetails($payment['method'] --}}
                            </label>
                        </div>

                        {!! view_render_event('bagisto.shop.checkout.onepage.payment_method.after') !!}
                    </div>
                </div>

                {!! view_render_event('bagisto.shop.checkout.onepage.payment_method.accordion.after') !!}
            </template>
        </section>
    </script>

    <script type="module">
        app.component('v-payment-methods', {
            template: '#v-payment-methods-template',

            props: {
                methods: {
                    type: Array,
                    required: true,
                    default: () => null,
                },

                cart: {
                    type: Object,
                    default: null,
                },
            },

            emits: ['processing', 'processed'],

            methods: {
                paymentLogoFrameClass(method) {
                    if (method === 'bkash') {
                        return 'h-12 w-[8.75rem] overflow-hidden rounded-xl';
                    }

                    if (method === 'sslcommerz') {
                        return 'h-[42px] w-[130px] overflow-hidden rounded-xl';
                    }

                    if (method === 'cashondelivery') {
                        return 'h-8 w-[5.75rem] overflow-hidden rounded-lg bg-transparent';
                    }

                    return 'h-9 w-[6.5rem] overflow-hidden rounded-lg bg-transparent';
                },

                paymentLogoClass(method) {
                    if (method === 'bkash') {
                        return 'scale-[1.12]';
                    }

                    if (method === 'sslcommerz') {
                        return 'scale-100';
                    }

                    if (method === 'cashondelivery') {
                        return 'scale-[1.02]';
                    }

                    return '';
                },

                paymentLogoStyle(method) {
                    if (method === 'bkash') {
                        return {
                            objectFit: 'cover',
                            objectPosition: '86% 78%',
                        };
                    }

                    if (method === 'sslcommerz') {
                        return {
                            objectFit: 'contain',
                            objectPosition: 'center',
                        };
                    }

                    if (method === 'cashondelivery') {
                        return {
                            objectFit: 'contain',
                            objectPosition: 'center',
                        };
                    }

                    return {
                        objectFit: 'contain',
                        objectPosition: 'right center',
                    };
                },

                paymentDescription(payment) {
                    if (payment.method === 'sslcommerz') {
                        return 'Cards, mobile banking, and internet banking.';
                    }

                    if (payment.method === 'bkash') {
                        return payment.description || 'Pay directly with bKash.';
                    }

                    if (payment.method === 'cashondelivery') {
                        return payment.description || 'Cash On Delivery';
                    }

                    return payment.description;
                },

                paymentLogoSrc(payment) {
                    if (payment.method === 'sslcommerz') {
                        return 'https://extremegadgets.com.bd/wp-content/plugins/wc-sslcommerz-easycheckout/images/sslcz-verified.png';
                    }

                    return payment.image;
                },

                store(selectedMethod) {
                    this.$emit('processing', 'review');

                    this.$axios.post("{{ route('shop.checkout.onepage.payment_methods.store') }}", {
                            payment: selectedMethod
                        })
                        .then(response => {
                            this.$emit('processed', response.data.cart);

                            if (window.innerWidth <= 768) {
                                window.scrollTo({
                                    top: document.body.scrollHeight,
                                    behavior: 'smooth'
                                });
                            }
                        })
                        .catch(error => {
                            this.$emit('processing', 'payment');

                            if (error.response.data.redirect_url) {
                                window.location.href = error.response.data.redirect_url;
                            }
                        });
                },
            },
        });
    </script>
@endPushOnce
