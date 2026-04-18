@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-checkout-address-form-template"
    >
        <div class="mt-2 space-y-5 max-md:mt-4">
            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.id'"
                    ::value="address.id"
                />
            </x-shop::form.control-group>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0 text-[13px] font-medium text-slate-700">
                    Name
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.name'"
                    ::value="displayName"
                    rules="required"
                    :label="'Name'"
                    placeholder="Name"
                    class="rounded-full border border-slate-200 bg-white px-6 py-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-0"
                />

                <x-shop::form.control-group.error ::name="controlName + '.name'" />
            </x-shop::form.control-group>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0 text-[13px] font-medium text-slate-700">
                    Mobile Number
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.phone'"
                    ::value="address.phone"
                    rules="required|phone"
                    :label="'Mobile Number'"
                    placeholder="Mobile Number"
                    class="rounded-full border border-slate-200 bg-white px-6 py-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-0"
                />

                <x-shop::form.control-group.error ::name="controlName + '.phone'" />
            </x-shop::form.control-group>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0 text-[13px] font-medium text-slate-700">
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
                    class="rounded-full border border-slate-200 bg-white px-6 py-3 text-sm shadow-sm focus:border-blue-500 focus:ring-0"
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
                <x-shop::form.control-group.label class="required !mt-0 text-[13px] font-medium text-slate-700">
                    District / Region
                </x-shop::form.control-group.label>

                <template v-if="usesBangladeshDistricts">
                    <x-shop::form.control-group.control
                        type="select"
                        ::name="controlName + '.state'"
                        ::value="selectedDistrict"
                        v-model="selectedDistrict"
                        rules="required"
                        :label="'District / Region'"
                        class="rounded-full border border-slate-200 bg-white px-6 py-3 text-sm shadow-sm focus:border-blue-500 focus:ring-0"
                    >
                        <option value="">
                            Select District
                        </option>

                        <option
                            v-for="district in districtOptions"
                            :key="district.code"
                            :value="district.name"
                        >
                            @{{ district.name }}
                        </option>
                    </x-shop::form.control-group.control>
                </template>

                <template v-else>
                    <x-shop::form.control-group.control
                        type="text"
                        ::name="controlName + '.state'"
                        ::value="address.state"
                        rules="required"
                        :label="'District / Region'"
                        placeholder="District / Region"
                        class="rounded-full border border-slate-200 bg-white px-6 py-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-0"
                    />
                </template>

                <x-shop::form.control-group.error ::name="controlName + '.state'" />
            </x-shop::form.control-group>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0 text-[13px] font-medium text-slate-700">
                    Full Address
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="textarea"
                    ::name="controlName + '.address.[0]'"
                    ::value="address.address[0]"
                    rules="required|address"
                    :label="'Full Address'"
                    placeholder="Full Address"
                    class="min-h-28 rounded-[1.5rem] border border-slate-200 bg-white px-6 py-4 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-0"
                />

                <x-shop::form.control-group.error
                    class="mb-2"
                    ::name="controlName + '.address.[0]'"
                />
            </x-shop::form.control-group>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0 text-[13px] font-medium text-slate-700">
                    Email
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="email"
                    ::name="controlName + '.email'"
                    ::value="address.email"
                    rules="required|email"
                    :label="'Email'"
                    placeholder="email@example.com"
                    class="rounded-full border border-slate-200 bg-white px-6 py-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-0"
                />

                <x-shop::form.control-group.error ::name="controlName + '.email'" />
            </x-shop::form.control-group>

            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.city'"
                    ::value="address.city || selectedDistrict || address.state || ''"
                />
            </x-shop::form.control-group>

            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.postcode'"
                    ::value="address.postcode || selectedDistrict || address.state || ''"
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

                    selectedDistrict: this.address.state,

                    countries: [],

                    districtOptions: @json(app(\Platform\CommerceCore\Services\BangladeshDistrictService::class)->districtOptions()),

                    configuredDefaultCountry: @json(app(\Platform\CommerceCore\Services\BangladeshDistrictService::class)->defaultCountryCode()),
                }
            },

            computed: {
                displayName() {
                    return this.address.name
                        || this.address.full_name
                        || [this.address.first_name, this.address.last_name].filter(Boolean).join(' ');
                },

                usesBangladeshDistricts() {
                    return String(this.selectedCountry || '').toUpperCase() === 'BD';
                },
            },

            watch: {
                'address.country': {
                    handler(country) {
                        this.selectedCountry = country;
                    },

                    immediate: true,
                },

                'address.state': {
                    handler(state) {
                        this.selectedDistrict = state;
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

                            if (! this.selectedCountry) {
                                this.selectedCountry = this.resolvePreferredCountry(response.data.data);
                            }
                        })
                        .catch(() => {});
                },

                resolvePreferredCountry(countries) {
                    const browserCountry = this.resolveBrowserCountry(countries);

                    if (browserCountry) {
                        return browserCountry;
                    }

                    return this.configuredDefaultCountry;
                },

                resolveBrowserCountry(countries) {
                    const locale = navigator.language || Intl.DateTimeFormat().resolvedOptions().locale || '';
                    const countryCode = locale.split('-')[1]?.toUpperCase();

                    if (! countryCode) {
                        return null;
                    }

                    return countries.some(country => country.code === countryCode)
                        ? countryCode
                        : null;
                },
            }
        });
    </script>
@endPushOnce
