{!! view_render_event('bagisto.shop.checkout.onepage.address.customer.before') !!}

<!-- Customer Address Vue Component -->
<v-checkout-address-customer
    :cart="cart"
    :checkout-state="checkoutState"
    @processing="stepForward"
    @processed="stepProcessed"
>
    <!-- Billing Address Shimmer -->
    <x-shop::shimmer.checkout.onepage.address />
</v-checkout-address-customer>

{!! view_render_event('bagisto.shop.checkout.onepage.address.customer.after') !!}

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
                <form @submit="handleSubmit($event, addAddressToCart)">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xl font-medium max-md:text-base max-sm:font-normal">
                            @lang('shop::app.checkout.onepage.address.billing-address')
                        </h2>
                    </div>

                    <v-checkout-address-form
                        control-name="billing"
                        :address="checkoutAddress"
                    ></v-checkout-address-form>

                    <div class="mt-4 flex justify-end">
                        <x-shop::button
                            class="primary-button rounded-2xl px-11 py-3 max-md:rounded-lg max-sm:w-full max-sm:max-w-full max-sm:py-1.5"
                            :title="trans('shop::app.checkout.onepage.address.proceed')"
                            ::loading="isStoring"
                            ::disabled="isStoring"
                        />
                    </div>
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

                    this.moveToNextStep();

                    this.$axios.post('{{ route('shop.checkout.onepage.addresses.store') }}', payload)
                        .then((response) => {
                            this.isStoring = false;

                            if (response.data.data.redirect_url) {
                                window.location.href = response.data.data.redirect_url;
                            } else {
                                this.$emit('processed', response.data.data.payment_methods);
                            }
                        })
                        .catch(error => {
                            this.isStoring = false;

                            this.$emit('processing', 'address');

                            if (error.response.status == 422) {
                                setErrors(this.mapValidationErrors(error.response.data.errors));
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
