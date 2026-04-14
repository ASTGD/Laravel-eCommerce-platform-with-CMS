{!! view_render_event('bagisto.shop.checkout.onepage.shipping_methods.before') !!}

<v-shipping-methods
    :methods="shippingMethods"
    :pickup-points='@json($pickupPoints)'
    :selected-method="cart.shipping_method"
    :selected-pickup-point-id="cart.shipping_address && cart.shipping_address.additional ? cart.shipping_address.additional.pickup_point_id : null"
    @processing="stepForward"
    @processed="stepProcessed"
>
    <x-shop::shimmer.checkout.onepage.shipping-method />
</v-shipping-methods>

{!! view_render_event('bagisto.shop.checkout.onepage.shipping_methods.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-shipping-methods-template"
    >
        <div class="mb-7 max-md:mb-0">
            <template v-if="! methods">
                <x-shop::shimmer.checkout.onepage.shipping-method />
            </template>

            <template v-else>
                <x-shop::accordion class="overflow-hidden !border-b-0 max-md:rounded-lg max-md:!border-none max-md:!bg-gray-100">
                    <x-slot:header class="px-0 py-4 max-md:p-3 max-md:text-sm max-md:font-medium max-sm:p-2">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-medium max-md:text-base">
                                @lang('shop::app.checkout.onepage.shipping.shipping-method')
                            </h2>
                        </div>
                    </x-slot>

                    <x-slot:content class="mt-8 !p-0 max-md:mt-0 max-md:rounded-t-none max-md:border max-md:border-t-0 max-md:!p-4">
                        <div class="flex flex-wrap gap-8 max-md:gap-4 max-sm:gap-2.5">
                            <template v-for="method in methods">
                                {!! view_render_event('bagisto.shop.checkout.onepage.shipping_method.before') !!}

                                <div
                                    class="relative max-w-[218px] select-none max-md:max-w-full max-md:flex-auto"
                                    v-for="rate in method.rates"
                                >
                                    <input
                                        type="radio"
                                        name="shipping_method"
                                        :id="rate.method"
                                        :value="rate.method"
                                        class="peer hidden"
                                        :checked="localSelectedMethod === rate.method"
                                        @change="handleMethodChange(rate.method)"
                                    >

                                    <label
                                        class="icon-radio-unselect peer-checked:icon-radio-select absolute top-5 cursor-pointer text-2xl text-navyBlue ltr:right-5 rtl:left-5"
                                        :for="rate.method"
                                    >
                                    </label>

                                    <label
                                        class="block cursor-pointer rounded-xl border border-zinc-200 p-5 max-sm:flex max-sm:gap-4 max-sm:rounded-lg max-sm:px-4 max-sm:py-2.5"
                                        :for="rate.method"
                                    >
                                        <span class="icon-flate-rate text-6xl text-navyBlue max-sm:text-5xl"></span>

                                        <div>
                                            <p class="mt-1.5 text-2xl font-semibold max-md:text-base">
                                                @{{ rate.base_formatted_price }}
                                            </p>

                                            <p class="mt-2.5 text-xs font-medium max-md:mt-1 max-sm:mt-0 max-sm:font-normal max-sm:text-zinc-500">
                                                <span class="font-medium">@{{ rate.method_title }}</span> - @{{ rate.method_description }}
                                            </p>
                                        </div>
                                    </label>
                                </div>

                                {!! view_render_event('bagisto.shop.checkout.onepage.shipping_method.after') !!}
                            </template>
                        </div>

                        <div
                            class="mt-6 rounded-xl border border-zinc-200 p-4"
                            v-if="localSelectedMethod === pickupMethodCode"
                        >
                            <label class="mb-2 block text-sm font-medium text-navyBlue">
                                Select courier pick-up point
                            </label>

                            <select
                                class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400"
                                v-model="selectedPickupPointIdValue"
                                @change="storePickupSelection"
                            >
                                <option value="">
                                    Choose a pick-up point
                                </option>

                                <option
                                    v-for="pickupPoint in pickupPoints"
                                    :key="pickupPoint.id"
                                    :value="String(pickupPoint.id)"
                                >
                                    @{{ pickupPoint.name }} - @{{ pickupPoint.city }}
                                </option>
                            </select>

                            <p
                                class="mt-2 text-xs text-red-600"
                                v-if="pickupPointError"
                            >
                                @{{ pickupPointError }}
                            </p>

                            <div
                                class="mt-3 rounded-lg bg-gray-50 p-3 text-xs text-zinc-600"
                                v-if="selectedPickupPoint"
                            >
                                <p class="font-medium text-navyBlue">
                                    @{{ selectedPickupPoint.name }}
                                </p>

                                <p v-if="selectedPickupPoint.courier_name">
                                    @{{ selectedPickupPoint.courier_name }}
                                </p>

                                <p>
                                    @{{ selectedPickupPoint.address_line_1 }}
                                    <template v-if="selectedPickupPoint.address_line_2">, @{{ selectedPickupPoint.address_line_2 }}</template>
                                </p>

                                <p>
                                    @{{ selectedPickupPoint.city }}
                                    <template v-if="selectedPickupPoint.state">, @{{ selectedPickupPoint.state }}</template>
                                    <template v-if="selectedPickupPoint.postcode"> - @{{ selectedPickupPoint.postcode }}</template>
                                </p>

                                <p v-if="selectedPickupPoint.phone">
                                    Phone: @{{ selectedPickupPoint.phone }}
                                </p>

                                <p v-if="selectedPickupPoint.opening_hours">
                                    Hours: @{{ selectedPickupPoint.opening_hours }}
                                </p>
                            </div>
                        </div>
                    </x-slot>
                </x-shop::accordion>
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-shipping-methods', {
            template: '#v-shipping-methods-template',

            props: {
                methods: {
                    type: Object,
                    required: true,
                    default: () => null,
                },

                pickupPoints: {
                    type: Array,
                    required: true,
                    default: () => [],
                },

                selectedMethod: {
                    type: String,
                    required: false,
                    default: null,
                },

                selectedPickupPointId: {
                    type: [String, Number],
                    required: false,
                    default: null,
                },
            },

            emits: ['processing', 'processed'],

            data() {
                return {
                    pickupMethodCode: 'courier_pickup',
                    localSelectedMethod: this.selectedMethod,
                    selectedPickupPointIdValue: this.selectedPickupPointId ? String(this.selectedPickupPointId) : '',
                    pickupPointError: null,
                };
            },

            computed: {
                selectedPickupPoint() {
                    return this.pickupPoints.find((pickupPoint) => String(pickupPoint.id) === String(this.selectedPickupPointIdValue)) || null;
                },
            },

            watch: {
                selectedMethod(value) {
                    this.localSelectedMethod = value;
                },

                selectedPickupPointId(value) {
                    this.selectedPickupPointIdValue = value ? String(value) : '';
                },
            },

            methods: {
                handleMethodChange(selectedMethod) {
                    this.localSelectedMethod = selectedMethod;
                    this.pickupPointError = null;

                    if (selectedMethod === this.pickupMethodCode) {
                        if (this.selectedPickupPointIdValue) {
                            this.store(selectedMethod, this.selectedPickupPointIdValue);
                        }

                        return;
                    }

                    this.selectedPickupPointIdValue = '';
                    this.store(selectedMethod, null);
                },

                storePickupSelection() {
                    this.pickupPointError = null;

                    if (! this.selectedPickupPointIdValue) {
                        return;
                    }

                    this.store(this.pickupMethodCode, this.selectedPickupPointIdValue);
                },

                store(selectedMethod, pickupPointId) {
                    this.$emit('processing', 'payment');

                    this.$axios.post("{{ route('shop.checkout.onepage.shipping_methods.store') }}", {
                            shipping_method: selectedMethod,
                            pickup_point_id: pickupPointId,
                        })
                        .then(response => {
                            if (response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else {
                                this.$emit('processed', response.data.payment_methods);
                            }
                        })
                        .catch(error => {
                            this.$emit('processing', 'shipping');

                            if (error.response?.status === 422) {
                                this.pickupPointError = error.response.data.errors?.pickup_point_id?.[0] || 'Select a courier pick-up point before continuing.';

                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: this.pickupPointError,
                                });

                                return;
                            }

                            if (error.response?.data?.redirect_url) {
                                window.location.href = error.response.data.redirect_url;
                            }
                        });
                },
            },
        });
    </script>
@endPushOnce
