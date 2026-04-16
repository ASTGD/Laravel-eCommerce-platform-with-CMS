{!! view_render_event('bagisto.shop.checkout.onepage.address.guest.before') !!}

<!-- Guest Address Vue Component -->
<v-checkout-address-guest
    :cart="cart"
    :checkout-state="checkoutState"
    @processing="stepForward"
    @processed="stepProcessed"
></v-checkout-address-guest>

{!! view_render_event('bagisto.shop.checkout.onepage.address.guest.after') !!}

@include('shop::checkout.onepage.address.form')

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-checkout-address-guest-template"
    >
        <!-- Address Form -->
        <x-shop::form
            v-slot="{ meta, errors, handleSubmit }"
            as="div"
        >
            <form @submit="handleSubmit($event, addAddress)">
                <!-- Guest Billing Address -->
                <div class="mb-4">
                    {!! view_render_event('bagisto.shop.checkout.onepage.address.guest.billing.before') !!}

                    <!-- Billing Address Header -->
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-medium max-md:text-lg max-sm:text-base">
                            @lang('shop::app.checkout.onepage.address.billing-address')
                        </h2>
                    </div>

                    <!-- Billing Address Form -->
                    <v-checkout-address-form
                        control-name="billing"
                        :address="checkoutAddress"
                    ></v-checkout-address-form>

                    {!! view_render_event('bagisto.shop.checkout.onepage.address.guest.billing.after') !!}
                </div>

                <template v-if="showCreateAccount">
                    <x-shop::form.control-group class="!mb-0 flex items-center gap-2.5">
                        <x-shop::form.control-group.control
                            type="checkbox"
                            id="create_account"
                            for="create_account"
                            value="1"
                            v-model="createAccount"
                        />

                        <label
                            class="cursor-pointer select-none text-base text-zinc-500 max-md:text-sm max-sm:text-xs ltr:pl-0 rtl:pr-0"
                            for="create_account"
                        >
                            @{{ createAccountLabel }}
                        </label>
                    </x-shop::form.control-group>
                </template>

                <!-- Proceed Button -->
                <div class="mt-4 flex justify-end">
                    <x-shop::button
                        class="primary-button rounded-2xl px-11 py-3 max-md:w-full max-md:max-w-full max-md:rounded-lg"
                        :title="trans('shop::app.checkout.onepage.address.proceed')"
                        ::loading="isStoring"
                        ::disabled="isStoring"
                    />
                </div>
            </form>
        </x-shop::form>
    </script>

    <script type="module">
        app.component('v-checkout-address-guest', {
            template: '#v-checkout-address-guest-template',

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
                    isStoring: false,
                    createAccount: false,
                }
            },

            computed: {
                checkoutAddress() {
                    return this.checkoutState?.customer?.draft || this.cart.billing_address || undefined;
                },

                createAccountLabel() {
                    return this.checkoutState?.form?.guest?.create_account_field?.label || 'Create an account?';
                },

                showCreateAccount() {
                    return this.checkoutState?.form?.guest?.show_create_account ?? true;
                }
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
                    billing.create_account = this.createAccount;

                    return billing;
                },

                addAddress(params, { setErrors }) {
                    this.isStoring = true;

                    params.billing = this.normalizeAddress(params);

                    this.moveToNextStep();

                    this.$axios.post('{{ route('shop.checkout.onepage.addresses.store') }}', params)
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
                }
            }
        });
    </script>
@endPushOnce
