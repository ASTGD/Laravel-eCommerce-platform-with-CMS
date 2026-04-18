{!! view_render_event('bagisto.shop.checkout.onepage.address.customer.before') !!}

<!-- Customer Address Vue Component -->
<v-checkout-address-customer
    ref="checkoutAddress"
    :cart="cart"
    :checkout-state="checkoutState"
    @processing="stepForward"
    @processed="stepProcessed"
>
    <!-- Billing Address Shimmer -->
    <x-shop::shimmer.checkout.onepage.address />
</v-checkout-address-customer>

{!! view_render_event('bagisto.shop.checkout.onepage.address.customer.after') !!}

@php
    $checkoutRoutePrefix = request()->routeIs('shop.checkout.custom.*')
        ? 'shop.checkout.custom'
        : 'shop.checkout.onepage';
@endphp

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-checkout-address-customer-template"
    >
        <template v-if="isLoading">
            <x-shop::shimmer.checkout.onepage.address />
        </template>

        <template v-else>
            <x-shop::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
            >
                <form
                    id="checkout-address-form"
                    ref="checkoutAddressForm"
                    class="space-y-6"
                    @submit="handleSubmit($event, addAddressToCart)"
                >
                    <v-checkout-address-form
                        control-name="billing"
                        :address="checkoutAddress"
                    ></v-checkout-address-form>

                    <button
                        type="submit"
                        class="hidden"
                        aria-hidden="true"
                        tabindex="-1"
                    ></button>
                </form>
            </x-shop::form>
        </template>
    </script>

    <script type="module">
        app.component('v-checkout-address-customer', {
            template: '#v-checkout-address-customer-template',

            props: {
                cart: {
                    type: Object,
                    required: true,
                },

                checkoutState: {
                    type: Object,
                    default: null,
                },
            },

            emits: ['processing', 'processed'],

            data() {
                return {
                    isLoading: true,

                    isStoring: false,
                }
            },

            computed: {
                checkoutAddress() {
                    return this.checkoutState?.customer?.draft || this.cart.billing_address || undefined;
                },
            },

            mounted() {
                this.isLoading = false;
            },

            methods: {
                submitAddress() {
                    this.$refs.checkoutAddressForm?.requestSubmit();
                },

                normalizeAddress(params) {
                    const billing = params.billing ?? {};

                    const nameParts = (billing.name ?? '')
                        .trim()
                        .split(/\s+/)
                        .filter(Boolean);

                    billing.first_name = nameParts.shift() || '';
                    billing.last_name = nameParts.join(' ') || billing.first_name;
                    billing.city = billing.city || billing.state || '';
                    billing.postcode = billing.postcode || billing.state || '';
                    billing.use_for_shipping = true;

                    return billing;
                },

                addAddressToCart(params, { setErrors }) {
                    const payload = {
                        billing: this.normalizeAddress(params),
                    };

                    this.isStoring = true;

                    this.$axios.post('{{ route($checkoutRoutePrefix.'.addresses.store') }}', payload)
                        .then((response) => {
                            this.isStoring = false;

                            if (response.data.data.redirect_url) {
                                window.location.href = response.data.data.redirect_url;
                            } else {
                                this.moveToNextStep();
                                this.$emit('processed', response.data.data.payment_methods);
                            }
                        })
                        .catch(error => {
                            this.isStoring = false;

                            this.$emit('processing', 'address');

                            if (error.response.status == 422) {
                                setErrors(this.mapValidationErrors(error.response.data.errors));

                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response?.data?.message || 'Please review the highlighted checkout fields.',
                                });
                            }
                        });
                },

                moveToNextStep() {
                    this.$emit('processing', 'payment');
                },

                mapValidationErrors(errors) {
                    const mappedErrors = {};

                    Object.entries(errors || {}).forEach(([key, messages]) => {
                        if (['billing.first_name', 'billing.last_name'].includes(key)) {
                            mappedErrors['billing.name'] = messages;
                        } else if (['billing.city', 'billing.postcode'].includes(key)) {
                            mappedErrors['billing.state'] = messages;
                        } else {
                            mappedErrors[key] = messages;
                        }
                    });

                    return mappedErrors;
                },
            },
        });
    </script>
@endPushOnce
