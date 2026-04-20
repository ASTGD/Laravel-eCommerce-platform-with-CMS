@php
    $isEdit = (bool) $carrier->exists;
    $pageTitle = $isEdit ? 'Edit Courier Service' : 'Add Courier Service';
    $saveLabel = $isEdit ? 'Save Courier Service' : 'Add Courier Service';
    $selectedCourierService = old('courier_service', $selectedCourierService);
    $isSteadfast = $selectedCourierService === 'steadfast';
    $isPathao = $selectedCourierService === 'pathao';
    $isManualOther = $selectedCourierService === 'manual_other';
    $supportsCod = (int) old('supports_cod', $carrier->supports_cod ? 1 : 0) === 1;
    $trackingSyncEnabled = (int) old('tracking_sync_enabled', $carrier->tracking_sync_enabled ? 1 : 0) === 1;
@endphp

<x-admin::layouts>
    <x-slot:title>
        {{ $pageTitle }}
    </x-slot>

    <x-admin::form
        :action="$isEdit ? route('admin.sales.carriers.update', $carrier) : route('admin.sales.carriers.store')"
    >
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <div>
                <p class="text-xl font-bold text-gray-800 dark:text-white">
                    {{ $pageTitle }}
                </p>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Choose the courier first, then fill only the account details that courier requires.
                </p>
            </div>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.sales.carriers.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    Back
                </a>

                <button
                    type="submit"
                    class="primary-button"
                >
                    {{ $saveLabel }}
                </button>
            </div>
        </div>

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
                        Courier Service
                    </p>

                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                        Add the courier exactly as your business team will use it in shipment operations and COD settlement.
                    </p>

                    @if ($legacyDriverLabel)
                        <div class="mb-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm leading-6 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">
                            This saved courier uses the legacy internal connection <span class="font-semibold">{{ $legacyDriverLabel }}</span>.
                            It will stay preserved unless you switch this courier to Steadfast or Pathao.
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                Courier
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="courier_service"
                                label="Courier"
                                id="courier-service-select"
                                onchange="window.handleCourierServiceChange && window.handleCourierServiceChange(this)"
                            >
                                @foreach ($courierOptions as $courierValue => $courierLabel)
                                    <option
                                        value="{{ $courierValue }}"
                                        data-label="{{ $courierLabel }}"
                                        @selected($selectedCourierService === $courierValue)
                                    >
                                        {{ $courierLabel }}
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                Pick the courier your team actually uses. The form will only show the fields needed for that courier.
                            </p>

                            <x-admin::form.control-group.error control-name="courier_service" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                Display Name
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="name"
                                rules="required"
                                :value="old('name', $carrier->name)"
                                label="Display Name"
                                id="courier-display-name"
                                placeholder="Steadfast"
                            />

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                This is the name your staff will see when creating shipments, tracking deliveries, and reconciling COD payouts.
                            </p>

                            <x-admin::form.control-group.error control-name="name" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="col-span-2 max-md:col-span-1">
                            <x-admin::form.control-group.label>
                                Public Tracking Link
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="tracking_url_template"
                                :value="old('tracking_url_template', $carrier->tracking_url_template)"
                                label="Public Tracking Link"
                                placeholder="https://carrier.example/track/{tracking_number}"
                            />

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                If the courier gives customers a public tracking page, paste that link here and keep <span class="font-mono">{tracking_number}</span> where the system should insert the shipment tracking number.
                            </p>

                            <x-admin::form.control-group.error control-name="tracking_url_template" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                <div
                    class="box-shadow rounded bg-white p-4 dark:bg-gray-900 {{ $isManualOther ? 'hidden' : '' }}"
                    data-courier-section="steadfast,pathao"
                >
                    <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
                        Courier Account Connection
                    </p>

                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                        Paste the account details provided by the courier so the system can create bookings and fetch delivery updates.
                    </p>

                    <div
                        class="{{ $isSteadfast ? '' : 'hidden' }}"
                        data-courier-section="steadfast"
                    >
                        <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    Courier API URL
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="api_base_url"
                                    :value="old('api_base_url', $carrier->api_base_url)"
                                    label="Courier API URL"
                                    placeholder="https://portal.steadfast.com.bd/api/v1"
                                />

                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Use the Steadfast API URL from your merchant account or integration guide.
                                </p>

                                <x-admin::form.control-group.error control-name="api_base_url" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    Merchant Username
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="api_username"
                                    :value="old('api_username', $carrier->api_username)"
                                    label="Merchant Username"
                                    placeholder="Optional"
                                />

                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Only fill this if Steadfast also gave you a username for API access.
                                </p>

                                <x-admin::form.control-group.error control-name="api_username" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    Merchant Password
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    name="api_password"
                                    label="Merchant Password"
                                    placeholder="{{ $isEdit ? 'Leave blank to keep the current password' : 'Optional' }}"
                                />

                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Only fill this if Steadfast gave you a password for API access.
                                </p>

                                <x-admin::form.control-group.error control-name="api_password" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    API Key
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    name="api_key"
                                    label="API Key"
                                    placeholder="{{ $isEdit ? 'Leave blank to keep the current API key' : 'Paste the API key from Steadfast' }}"
                                />

                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Steadfast uses this key to verify your merchant account.
                                </p>

                                <x-admin::form.control-group.error control-name="api_key" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    API Secret
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    name="api_secret"
                                    label="API Secret"
                                    placeholder="{{ $isEdit ? 'Leave blank to keep the current API secret' : 'Paste the API secret from Steadfast' }}"
                                />

                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Steadfast uses this secret together with the API key.
                                </p>

                                <x-admin::form.control-group.error control-name="api_secret" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group class="col-span-2 max-md:col-span-1">
                                <x-admin::form.control-group.label>
                                    Status Update Secret
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    name="webhook_secret"
                                    label="Status Update Secret"
                                    placeholder="{{ $isEdit ? 'Leave blank to keep the current secret' : 'Optional but recommended' }}"
                                />

                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Optional. Use this when you want Steadfast to send secure automatic delivery updates into your store.
                                </p>

                                @if ($carrier->exists)
                                    <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                        Steadfast callback URL:
                                        <span class="break-all font-mono">
                                            {{ route('commerce-core.webhooks.shipment-carriers.steadfast', $carrier) }}
                                        </span>
                                    </p>
                                @endif

                                <x-admin::form.control-group.error control-name="webhook_secret" />
                            </x-admin::form.control-group>
                        </div>
                    </div>

                    <div
                        class="{{ $isPathao ? '' : 'hidden' }}"
                        data-courier-section="pathao"
                    >
                        <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    Courier API URL
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="api_base_url"
                                    :value="old('api_base_url', $carrier->api_base_url)"
                                    label="Courier API URL"
                                    placeholder="https://merchant.pathao.com/api/v1"
                                />

                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Use the Pathao merchant API URL from your Pathao onboarding or merchant panel.
                                </p>

                                <x-admin::form.control-group.error control-name="api_base_url" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    Store ID
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="number"
                                    name="api_store_id"
                                    min="1"
                                    :value="old('api_store_id', $carrier->api_store_id)"
                                    label="Store ID"
                                    placeholder="Pathao store ID"
                                />

                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    This is the merchant store number Pathao assigns to your pickup location.
                                </p>

                                <x-admin::form.control-group.error control-name="api_store_id" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    Username
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="api_username"
                                    :value="old('api_username', $carrier->api_username)"
                                    label="Username"
                                    placeholder="Pathao API username"
                                />

                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Use the username Pathao gave you for merchant API access.
                                </p>

                                <x-admin::form.control-group.error control-name="api_username" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    Password
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    name="api_password"
                                    label="Password"
                                    placeholder="{{ $isEdit ? 'Leave blank to keep the current password' : 'Pathao API password' }}"
                                />

                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Use the password Pathao gave you for merchant API access.
                                </p>

                                <x-admin::form.control-group.error control-name="api_password" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    API Key
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    name="api_key"
                                    label="API Key"
                                    placeholder="{{ $isEdit ? 'Leave blank to keep the current API key' : 'Pathao API key' }}"
                                />

                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Pathao may also call this your client ID. Paste it exactly as provided.
                                </p>

                                <x-admin::form.control-group.error control-name="api_key" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    API Secret
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    name="api_secret"
                                    label="API Secret"
                                    placeholder="{{ $isEdit ? 'Leave blank to keep the current API secret' : 'Pathao API secret' }}"
                                />

                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Pathao may also call this your client secret. Paste it exactly as provided.
                                </p>

                                <x-admin::form.control-group.error control-name="api_secret" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group class="col-span-2 max-md:col-span-1">
                                <x-admin::form.control-group.label>
                                    Status Update Secret
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    name="webhook_secret"
                                    label="Status Update Secret"
                                    placeholder="{{ $isEdit ? 'Leave blank to keep the current secret' : 'Optional but recommended' }}"
                                />

                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Optional. Use a shared secret if you want Pathao to send secure automatic shipment updates back into your store.
                                </p>

                                @if ($carrier->exists)
                                    <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                        Pathao callback URL:
                                        <span class="break-all font-mono">
                                            {{ route('commerce-core.webhooks.shipment-carriers.pathao', $carrier) }}
                                        </span>
                                    </p>
                                @endif

                                <x-admin::form.control-group.error control-name="webhook_secret" />
                            </x-admin::form.control-group>
                        </div>
                    </div>
                </div>

                <div
                    class="box-shadow rounded bg-white p-4 dark:bg-gray-900 {{ $isSteadfast ? 'hidden' : '' }}"
                    data-courier-section="pathao,manual_other"
                >
                    <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
                        Contact Details
                    </p>

                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                        Add the business contact details this courier uses for pickup, account support, or manual follow-up.
                    </p>

                    <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Contact Name
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="contact_name"
                                :value="old('contact_name', $carrier->contact_name)"
                                label="Contact Name"
                                placeholder="Operations contact"
                            />

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                For Pathao, this usually maps to the pickup contact name. For Manual / Other, use the person your staff calls when needed.
                            </p>

                            <x-admin::form.control-group.error control-name="contact_name" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="{{ $isPathao ? 'required' : '' }}">
                                Contact Phone
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="contact_phone"
                                :value="old('contact_phone', $carrier->contact_phone)"
                                label="Contact Phone"
                                placeholder="+8801XXXXXXXXX"
                            />

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                For Pathao, use the pickup phone number linked to your merchant account.
                            </p>

                            <x-admin::form.control-group.error control-name="contact_phone" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="col-span-2 max-md:col-span-1">
                            <x-admin::form.control-group.label>
                                Contact Email
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="email"
                                name="contact_email"
                                :value="old('contact_email', $carrier->contact_email)"
                                label="Contact Email"
                                placeholder="ops@example.com"
                            />

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                Optional. Use an email your operations team monitors for courier-related issues.
                            </p>

                            <x-admin::form.control-group.error control-name="contact_email" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
                        Cash Collection Defaults
                    </p>

                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                        These defaults help shipment operations and settlement reports understand how this courier handles COD collection and payout.
                    </p>

                    <input
                        type="hidden"
                        name="supports_cod"
                        value="0"
                    >

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Does this courier support cash on delivery?
                        </x-admin::form.control-group.label>

                        <label class="inline-flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="supports_cod"
                                value="1"
                                @checked($supportsCod)
                                id="supports-cod-toggle"
                                onchange="window.toggleCodSettings && window.toggleCodSettings(this.checked)"
                            >

                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                Yes, this courier can collect cash from the customer during delivery.
                            </span>
                        </label>

                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                            Turn this on if the courier collects order money and later pays that amount back to your business.
                        </p>

                        <x-admin::form.control-group.error control-name="supports_cod" />
                    </x-admin::form.control-group>

                    <div
                        class="mt-4 grid grid-cols-2 gap-4 max-md:grid-cols-1 {{ $supportsCod ? '' : 'hidden' }}"
                        data-cod-settings
                    >
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Default Payout Method
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="default_payout_method"
                                :label="'Default Payout Method'"
                            >
                                <option value="">
                                    Select payout method
                                </option>

                                @foreach ($payoutMethods as $payoutMethod)
                                    <option
                                        value="{{ $payoutMethod }}"
                                        @selected(old('default_payout_method', $carrier->default_payout_method) === $payoutMethod)
                                    >
                                        {{ str($payoutMethod)->replace('_', ' ')->title() }}
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                Use the most common way this courier hands your COD money back to your business.
                            </p>

                            <x-admin::form.control-group.error control-name="default_payout_method" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                COD Fee Type
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="default_cod_fee_type"
                                :label="'COD Fee Type'"
                            >
                                <option value="">
                                    Select fee type
                                </option>

                                @foreach ($codFeeTypes as $codFeeType)
                                    <option
                                        value="{{ $codFeeType }}"
                                        @selected(old('default_cod_fee_type', $carrier->default_cod_fee_type) === $codFeeType)
                                    >
                                        {{ str($codFeeType)->title() }}
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                Choose whether the courier usually charges a fixed amount or a percentage on COD orders.
                            </p>

                            <x-admin::form.control-group.error control-name="default_cod_fee_type" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Default COD Fee
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="number"
                                name="default_cod_fee_amount"
                                step="0.01"
                                min="0"
                                :value="old('default_cod_fee_amount', $carrier->default_cod_fee_amount ?? 0)"
                                label="Default COD Fee"
                                placeholder="0.00"
                            />

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                This default appears in shipment and settlement records when the courier charges COD handling fees.
                            </p>

                            <x-admin::form.control-group.error control-name="default_cod_fee_amount" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Default Return Fee
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="number"
                                name="default_return_fee_amount"
                                step="0.01"
                                min="0"
                                :value="old('default_return_fee_amount', $carrier->default_return_fee_amount ?? 0)"
                                label="Default Return Fee"
                                placeholder="0.00"
                            />

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                Use this if the courier charges your business to return failed or refused deliveries.
                            </p>

                            <x-admin::form.control-group.error control-name="default_return_fee_amount" />
                        </x-admin::form.control-group>
                    </div>
                </div>
            </div>

            <div class="flex w-[360px] max-w-full flex-col gap-2 max-xl:w-full">
                <div
                    class="box-shadow rounded bg-white p-4 dark:bg-gray-900 {{ $isManualOther ? 'hidden' : '' }}"
                    data-courier-section="steadfast,pathao"
                >
                    <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
                        Delivery Update Settings
                    </p>

                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                        Turn this on if you want this courier to send or fetch delivery status updates automatically.
                    </p>

                    <input
                        type="hidden"
                        name="tracking_sync_enabled"
                        value="0"
                    >

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Automatic Delivery Updates
                        </x-admin::form.control-group.label>

                        <label class="inline-flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="tracking_sync_enabled"
                                value="1"
                                @checked($trackingSyncEnabled || (! $isEdit && ! $isManualOther))
                            >

                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                Allow the system to sync shipment updates for this courier.
                            </span>
                        </label>

                        <x-admin::form.control-group.error control-name="tracking_sync_enabled" />
                    </x-admin::form.control-group>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        Availability
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Sort Order
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="number"
                            name="sort_order"
                            :value="old('sort_order', $carrier->sort_order ?? 0)"
                            label="Sort Order"
                            placeholder="0"
                        />

                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                            Lower numbers appear earlier when your team chooses a courier in admin flows.
                        </p>

                        <x-admin::form.control-group.error control-name="sort_order" />
                    </x-admin::form.control-group>

                    <input
                        type="hidden"
                        name="is_active"
                        value="0"
                    >

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Available Now
                        </x-admin::form.control-group.label>

                        <label class="inline-flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                @checked((int) old('is_active', $carrier->is_active ? 1 : 0) === 1)
                            >

                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                Make this courier available for shipment operations right away.
                            </span>
                        </label>

                        <x-admin::form.control-group.error control-name="is_active" />
                    </x-admin::form.control-group>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        Internal Notes
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Notes
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            name="notes"
                            :value="old('notes', $carrier->notes)"
                            label="Notes"
                            placeholder="Anything your operations team should remember about this courier."
                        />

                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                            Only your admin team sees this. Use it for pickup timing, account manager details, or any special operating rules.
                        </p>

                        <x-admin::form.control-group.error control-name="notes" />
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>

    @pushOnce('scripts')
        <script type="text/javascript">
            (() => {
                const courierSelect = document.getElementById('courier-service-select');
                const nameInput = document.getElementById('courier-display-name');
                const supportsCodToggle = document.getElementById('supports-cod-toggle');

                if (! courierSelect) {
                    return;
                }

                window.handleCourierServiceChange = (selectElement) => {
                    const selectedCourier = selectElement?.value || courierSelect.value;
                    const selectedOption = selectElement?.options?.[selectElement.selectedIndex]
                        ?? courierSelect.options[courierSelect.selectedIndex];

                    document.querySelectorAll('[data-courier-section]').forEach((section) => {
                        const sectionCouriers = (section.dataset.courierSection || '')
                            .split(',')
                            .map((value) => value.trim())
                            .filter(Boolean);

                        const isVisible = sectionCouriers.includes(selectedCourier);

                        section.classList.toggle('hidden', ! isVisible);

                        section.querySelectorAll('input, select, textarea').forEach((input) => {
                            input.disabled = ! isVisible;
                        });
                    });

                    if (nameInput && nameInput.value.trim() === '' && selectedOption && selectedOption.value !== 'manual_other') {
                        nameInput.value = selectedOption.dataset.label || selectedOption.text;
                    }

                    return selectedCourier;
                };

                window.toggleCodSettings = (isVisible) => {
                    const codSettings = document.querySelector('[data-cod-settings]');

                    if (! codSettings || ! supportsCodToggle) {
                        return isVisible;
                    }

                    codSettings.classList.toggle('hidden', ! isVisible);

                    codSettings.querySelectorAll('input, select, textarea').forEach((input) => {
                        input.disabled = ! isVisible;
                    });
                    return isVisible;
                };

                window.addEventListener('load', () => {
                    window.setTimeout(() => {
                        window.handleCourierServiceChange(courierSelect);
                        window.toggleCodSettings(Boolean(supportsCodToggle?.checked));
                    }, 0);
                });
            })();
        </script>
    @endPushOnce
</x-admin::layouts>
