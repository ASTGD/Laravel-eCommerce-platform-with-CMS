{!! view_render_event('bagisto.shop.checkout.onepage.address.guest.before') !!}

<!-- Guest Address Vue Component -->
<v-checkout-address-guest
    ref="checkoutAddress"
    :cart="cart"
    :checkout-state="checkoutState"
    @processing="stepForward"
    @processed="stepProcessed"
></v-checkout-address-guest>

{!! view_render_event('bagisto.shop.checkout.onepage.address.guest.after') !!}

@include('shop::checkout.onepage.address.form')

@php
    $checkoutRoutePrefix = request()->routeIs('shop.checkout.custom.*')
        ? 'shop.checkout.custom'
        : 'shop.checkout.onepage';
@endphp

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
                <form
                    id="checkout-address-form"
                    ref="checkoutAddressForm"
                    class="space-y-6"
                    @submit="handleSubmit($event, addAddress)"
                >
                {!! view_render_event('bagisto.shop.checkout.onepage.address.guest.billing.before') !!}

                <div class="space-y-5">
                    <v-checkout-address-form
                        control-name="billing"
                        :address="checkoutAddress"
                    ></v-checkout-address-form>
                </div>

                <template v-if="showCreateAccount">
                    <div class="flex items-center gap-2.5">
                        <x-shop::form.control-group.control
                            type="checkbox"
                            id="create_account"
                            for="create_account"
                            value="1"
                            v-model="createAccount"
                        />

                        <label
                            class="cursor-pointer select-none text-sm font-medium text-slate-700 ltr:pl-0 rtl:pr-0"
                            for="create_account"
                        >
                            @{{ createAccountLabel }}
                        </label>
                    </div>
                </template>

                <template v-if="createAccount">
                    <div class="grid gap-5 md:grid-cols-2">
                        <x-shop::form.control-group>
                            <x-shop::form.control-group.label class="required !mt-0 text-[13px] font-medium text-slate-700">
                                @{{ passwordLabel }}
                            </x-shop::form.control-group.label>

                            <x-shop::form.control-group.control
                                type="password"
                                name="billing.password"
                                rules="required|min:6"
                                :label="'Password'"
                                placeholder="Password"
                                class="rounded-full border border-slate-200 bg-white px-6 py-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-0"
                            />

                            <x-shop::form.control-group.error name="billing.password" />
                        </x-shop::form.control-group>

                        <x-shop::form.control-group>
                            <x-shop::form.control-group.label class="required !mt-0 text-[13px] font-medium text-slate-700">
                                @{{ passwordConfirmationLabel }}
                            </x-shop::form.control-group.label>

                            <x-shop::form.control-group.control
                                type="password"
                                name="billing.password_confirmation"
                                rules="required|min:6"
                                :label="'Confirm Password'"
                                placeholder="Confirm Password"
                                class="rounded-full border border-slate-200 bg-white px-6 py-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-0"
                            />

                            <x-shop::form.control-group.error name="billing.password_confirmation" />
                        </x-shop::form.control-group>
                    </div>
                </template>

                <button
                    type="submit"
                    class="hidden"
                    aria-hidden="true"
                    tabindex="-1"
                ></button>

                {!! view_render_event('bagisto.shop.checkout.onepage.address.guest.billing.after') !!}
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
                },

                passwordLabel() {
                    return this.checkoutState?.form?.guest?.password_field?.label || 'Password';
                },

                passwordConfirmationLabel() {
                    return this.checkoutState?.form?.guest?.password_confirmation_field?.label || 'Confirm Password';
                },
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
                    billing.create_account = this.createAccount;

                    return billing;
                },

                addAddress(params, { setErrors }) {
                    this.isStoring = true;

                    params.billing = this.normalizeAddress(params);

                    if (
                        this.createAccount
                        && params.billing.password !== params.billing.password_confirmation
                    ) {
                        this.isStoring = false;

                        setErrors({
                            'billing.password_confirmation': ['The password confirmation does not match.'],
                        });

                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: 'The password confirmation does not match.',
                        });

                        this.$emit('processing', 'address');

                        return;
                    }

                    this.$axios.post('{{ route($checkoutRoutePrefix.'.addresses.store') }}', params)
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
                }
            }
        });
    </script>
@endPushOnce
