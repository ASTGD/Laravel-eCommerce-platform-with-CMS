@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-checkout-address-form-template"
    >
        <div class="mt-2 max-md:mt-3">
            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.id'"
                    ::value="address.id"
                />
            </x-shop::form.control-group>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    Name
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.name'"
                    ::value="displayName"
                    rules="required"
                    :label="'Name'"
                    placeholder="Name"
                />

                <x-shop::form.control-group.error ::name="controlName + '.name'" />
            </x-shop::form.control-group>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    Mobile Number
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.phone'"
                    ::value="address.phone"
                    rules="required|phone"
                    :label="'Mobile Number'"
                    placeholder="Mobile Number"
                />

                <x-shop::form.control-group.error ::name="controlName + '.phone'" />
            </x-shop::form.control-group>

            <x-shop::form.control-group class="!mb-4">
                <x-shop::form.control-group.label class="required !mt-0">
                    Country / Region
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="select"
                    ::name="controlName + '.country'"
                    ::value="address.country"
                    v-model="selectedCountry"
                    rules="required"
                    :label="'Country / Region'"
                    placeholder="Country / Region"
                >
                    <option value="">
                        Select Country
                    </option>

                    <option
                        v-for="country in countries"
                        :value="country.code"
                    >
                        @{{ country.name }}
                    </option>
                </x-shop::form.control-group.control>

                <x-shop::form.control-group.error ::name="controlName + '.country'" />
            </x-shop::form.control-group>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    District / Region
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.state'"
                    ::value="address.state"
                    rules="required"
                    :label="'District / Region'"
                    placeholder="District / Region"
                />

                <x-shop::form.control-group.error ::name="controlName + '.state'" />
            </x-shop::form.control-group>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    Full Address
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="textarea"
                    ::name="controlName + '.address.[0]'"
                    ::value="address.address[0]"
                    rules="required|address"
                    :label="'Full Address'"
                    placeholder="Full Address"
                />

                <x-shop::form.control-group.error
                    class="mb-2"
                    ::name="controlName + '.address.[0]'"
                />
            </x-shop::form.control-group>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    Email
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="email"
                    ::name="controlName + '.email'"
                    ::value="address.email"
                    rules="required|email"
                    :label="'Email'"
                    placeholder="email@example.com"
                />

                <x-shop::form.control-group.error ::name="controlName + '.email'" />
            </x-shop::form.control-group>

            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.city'"
                    ::value="address.city || address.state || ''"
                />
            </x-shop::form.control-group>

            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.postcode'"
                    ::value="address.postcode || address.state || ''"
                />
            </x-shop::form.control-group>
        </div>
    </script>

    <script type="module">
        app.component('v-checkout-address-form', {
            template: '#v-checkout-address-form-template',

            props: {
                controlName: {
                    type: String,
                    required: true,
                },

                address: {
                    type: Object,

                    default: () => ({
                        id: 0,
                        name: '',
                        company_name: '',
                        first_name: '',
                        last_name: '',
                        email: '',
                        address: [],
                        country: '',
                        state: '',
                        city: '',
                        postcode: '',
                        phone: '',
                    }),
                },
            },

            data() {
                return {
                    selectedCountry: this.address.country,

                    countries: [],
                }
            },

            computed: {
                displayName() {
                    return this.address.name
                        || this.address.full_name
                        || [this.address.first_name, this.address.last_name].filter(Boolean).join(' ');
                },
            },

            watch: {
                'address.country': {
                    handler(country) {
                        this.selectedCountry = country;
                    },

                    immediate: true,
                },
            },

            mounted() {
                this.getCountries();
            },

            methods: {
                getCountries() {
                    this.$axios.get("{{ route('shop.api.core.countries') }}")
                        .then(response => {
                            this.countries = response.data.data;
                        })
                        .catch(() => {});
                },
            }
        });
    </script>
@endPushOnce
