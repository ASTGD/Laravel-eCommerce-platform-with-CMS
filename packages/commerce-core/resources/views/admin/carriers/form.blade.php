@php
    $isEdit = (bool) $carrier->exists;
    $pageTitle = $isEdit ? 'Edit Courier Service' : 'Add Courier Service';
    $saveLabel = $isEdit ? 'Save Courier Service' : 'Add Courier Service';
    $isProMode = (bool) $showsAdvancedCarrierConfiguration;
    $isBasicMode = ! $isProMode;
    $selectedAutomationType = old('courier_service', $selectedCourierService);
    $isSteadfast = $selectedAutomationType === 'steadfast';
    $isPathao = $selectedAutomationType === 'pathao';
    $usesIntegratedAutomation = in_array($selectedAutomationType, ['steadfast', 'pathao'], true);
    $supportsCod = (int) old('supports_cod', $carrier->supports_cod ? 1 : 0) === 1;
    $trackingSyncEnabled = (int) old('tracking_sync_enabled', $carrier->tracking_sync_enabled ? 1 : 0) === 1;
    $isActive = (int) old('is_active', $carrier->is_active ? 1 : 0) === 1;
    $phoneHelper = $isProMode && $isPathao
        ? 'When you use Pathao automation, enter the pickup phone number linked to your Pathao account.'
        : 'Optional. Use the phone number your team calls when handing parcels to this courier.';
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
                    {{ $isBasicMode
                        ? 'Add a courier your team can choose during shipment booking. This stays a manual courier registry in Basic mode.'
                        : 'Start with the business details your team needs, then add automation only if this courier connects to your store.' }}
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

        <div class="mt-3.5 grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="grid gap-4">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
                        Basic Information
                    </p>

                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                        Add the courier exactly as your business team knows it.
                    </p>

                    @if ($isBasicMode)
                        <input
                            type="hidden"
                            name="courier_service"
                            value="{{ $selectedAutomationType }}"
                        >
                    @endif

                    @if ($preservedConnectionLabel)
                        <div class="mb-4 rounded border border-blue-200 bg-blue-50 p-3 text-sm leading-6 text-blue-900 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-100">
                            This courier already has <span class="font-semibold">{{ $preservedConnectionLabel }}</span> automation details saved. They are hidden in Basic mode and will be preserved when you save.
                        </div>
                    @elseif ($legacyDriverLabel)
                        <div class="mb-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm leading-6 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">
                            This courier uses the legacy internal connection <span class="font-semibold">{{ $legacyDriverLabel }}</span>. It will stay preserved unless you switch the automation type in Pro mode.
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                Courier Name
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="name"
                                rules="required"
                                :value="old('name', $carrier->name)"
                                label="Courier Name"
                                id="courier-name"
                                placeholder="Sundarban"
                            />

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                This is the name your staff will see during shipment booking and delivery follow-up.
                            </p>

                            <x-admin::form.control-group.error control-name="name" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Courier Code
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="code"
                                :value="old('code', $carrier->code)"
                                label="Courier Code"
                                id="courier-code"
                                placeholder="sundarban"
                            />

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                Optional. If you leave this blank, the system will create a code from the courier name.
                            </p>

                            <x-admin::form.control-group.error control-name="code" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Contact Person
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="contact_name"
                                :value="old('contact_name', $carrier->contact_name)"
                                label="Contact Person"
                                placeholder="Operations contact"
                            />

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                Optional. Add the person your team contacts for pickup or courier support.
                            </p>

                            <x-admin::form.control-group.error control-name="contact_name" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="{{ $isProMode && $isPathao ? 'required' : '' }}">
                                Phone
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="contact_phone"
                                :value="old('contact_phone', $carrier->contact_phone)"
                                label="Phone"
                                placeholder="+8801XXXXXXXXX"
                            />

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                {{ $phoneHelper }}
                            </p>

                            <x-admin::form.control-group.error control-name="contact_phone" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="col-span-2 max-md:col-span-1">
                            <x-admin::form.control-group.label>
                                Address
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="textarea"
                                name="address"
                                :value="old('address', $carrier->address)"
                                label="Address"
                                placeholder="Courier office or pickup address"
                            />

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                Optional. Use this for the office, pickup point, or branch address your team needs to remember.
                            </p>

                            <x-admin::form.control-group.error control-name="address" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
                        Tracking
                    </p>

                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                        Add the public tracking link pattern only if this courier offers customer tracking.
                    </p>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>
                            Tracking URL Template
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="tracking_url_template"
                            :value="old('tracking_url_template', $carrier->tracking_url_template)"
                            label="Tracking URL Template"
                            placeholder="https://courier.example/track/{tracking_number}"
                        />

                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                            Optional. If the courier has a public tracking page, keep <span class="font-mono">{tracking_number}</span> where the system should insert the shipment tracking number.
                        </p>

                        <x-admin::form.control-group.error control-name="tracking_url_template" />
                    </x-admin::form.control-group>
                </div>

                @if ($isProMode)
                    <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                        <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
                            Automation &amp; API Connection (Pro)
                        </p>

                        <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                            Choose Manual if this courier is only used for manual booking. Select Steadfast or Pathao only when this courier connects directly to your store.
                        </p>

                        <div class="grid gap-4">
                            <x-admin::form.control-group class="!mb-0">
                                <x-admin::form.control-group.label class="required">
                                    Automation Type
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="courier_service"
                                    label="Automation Type"
                                    id="courier-automation-select"
                                    onchange="window.handleAutomationTypeChange && window.handleAutomationTypeChange(this)"
                                >
                                    @foreach ($integrationOptions as $integrationValue => $integrationLabel)
                                        <option
                                            value="{{ $integrationValue }}"
                                            @selected($selectedAutomationType === $integrationValue)
                                        >
                                            {{ $integrationLabel }}
                                        </option>
                                    @endforeach
                                </x-admin::form.control-group.control>

                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Manual keeps this courier business-only. Steadfast and Pathao unlock booking and delivery update automation.
                                </p>

                                <x-admin::form.control-group.error control-name="courier_service" />
                            </x-admin::form.control-group>

                            <div
                                class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200 {{ $usesIntegratedAutomation ? 'hidden' : '' }}"
                                data-automation-section="manual_other"
                            >
                                This courier will stay manual. Your team can still select it during shipment booking, but no API or automatic update fields are required.
                            </div>

                            <div
                                class="{{ $isSteadfast ? '' : 'hidden' }}"
                                data-automation-section="steadfast"
                            >
                                <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.label class="required">
                                            API URL
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="api_base_url"
                                            :value="old('api_base_url', $carrier->api_base_url)"
                                            label="API URL"
                                            placeholder="https://portal.steadfast.com.bd/api/v1"
                                        />

                                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                            Use the Steadfast API URL from your merchant account.
                                        </p>

                                        <x-admin::form.control-group.error control-name="api_base_url" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.label>
                                            Username
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="api_username"
                                            :value="old('api_username', $carrier->api_username)"
                                            label="Username"
                                            placeholder="Optional"
                                        />

                                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                            Only fill this if Steadfast also gave you a username for API access.
                                        </p>

                                        <x-admin::form.control-group.error control-name="api_username" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.label>
                                            Password
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="password"
                                            name="api_password"
                                            label="Password"
                                            placeholder="{{ $isEdit ? 'Leave blank to keep the current password' : 'Optional' }}"
                                        />

                                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                            Only fill this if Steadfast gave you a password for API access.
                                        </p>

                                        <x-admin::form.control-group.error control-name="api_password" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group class="!mb-0">
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

                                    <x-admin::form.control-group class="!mb-0">
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

                                    <x-admin::form.control-group class="col-span-2 !mb-0 max-md:col-span-1">
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
                                data-automation-section="pathao"
                            >
                                <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.label class="required">
                                            API URL
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="api_base_url"
                                            :value="old('api_base_url', $carrier->api_base_url)"
                                            label="API URL"
                                            placeholder="https://merchant.pathao.com/api/v1"
                                        />

                                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                            Use the Pathao merchant API URL from your onboarding details.
                                        </p>

                                        <x-admin::form.control-group.error control-name="api_base_url" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group class="!mb-0">
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
                                            This is the store number Pathao assigns to your pickup location.
                                        </p>

                                        <x-admin::form.control-group.error control-name="api_store_id" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group class="!mb-0">
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
                                            Use the username Pathao gave you for API access.
                                        </p>

                                        <x-admin::form.control-group.error control-name="api_username" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group class="!mb-0">
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
                                            Use the password Pathao gave you for API access.
                                        </p>

                                        <x-admin::form.control-group.error control-name="api_password" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group class="!mb-0">
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
                                            Pathao may also call this your client ID.
                                        </p>

                                        <x-admin::form.control-group.error control-name="api_key" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group class="!mb-0">
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
                                            Pathao may also call this your client secret.
                                        </p>

                                        <x-admin::form.control-group.error control-name="api_secret" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group class="col-span-2 !mb-0 max-md:col-span-1">
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
                                            Optional. Use a shared secret if you want secure Pathao delivery callbacks.
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

                            <input
                                type="hidden"
                                name="tracking_sync_enabled"
                                value="0"
                            >

                            <div
                                class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-gray-800 dark:bg-gray-800 {{ $usesIntegratedAutomation ? '' : 'hidden' }}"
                                data-automation-section="steadfast,pathao"
                            >
                                <x-admin::form.control-group class="!mb-0">
                                    <x-admin::form.control-group.label>
                                        Automatic Delivery Updates
                                    </x-admin::form.control-group.label>

                                    <label class="inline-flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            name="tracking_sync_enabled"
                                            value="1"
                                            @checked($trackingSyncEnabled || (! $isEdit && $usesIntegratedAutomation))
                                        >

                                        <span class="text-sm text-gray-600 dark:text-gray-300">
                                            Allow the system to sync shipment updates for this courier.
                                        </span>
                                    </label>

                                    <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                        Leave this on when you want delivery status updates to flow in automatically.
                                    </p>

                                    <x-admin::form.control-group.error control-name="tracking_sync_enabled" />
                                </x-admin::form.control-group>
                            </div>
                        </div>
                    </div>

                    <div
                        class="box-shadow rounded bg-white p-4 dark:bg-gray-900 {{ $supportsCod ? '' : 'hidden' }}"
                        data-cod-settings
                    >
                        <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
                            Courier Payment Defaults
                        </p>

                        <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                            These defaults help advanced shipment and settlement workflows understand how this courier normally handles COD remittance.
                        </p>

                        <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
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
                                    Use the most common way this courier pays your COD money back to your business.
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
                                    Choose whether this courier usually charges a flat fee or a percentage on COD orders.
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
                                    Use this when the courier usually charges a COD handling fee.
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
                                    Use this if the courier usually charges for returned or failed deliveries.
                                </p>

                                <x-admin::form.control-group.error control-name="default_return_fee_amount" />
                            </x-admin::form.control-group>
                        </div>
                    </div>
                @endif
            </div>

            <div class="grid gap-4">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        Shipping Options
                    </p>

                    <input
                        type="hidden"
                        name="supports_cod"
                        value="0"
                    >

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Supports COD
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
                                This courier can collect cash from the customer during delivery.
                            </span>
                        </label>

                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                            Turn this on if the courier collects COD money before remitting it to your business.
                        </p>

                        <x-admin::form.control-group.error control-name="supports_cod" />
                    </x-admin::form.control-group>

                    <input
                        type="hidden"
                        name="is_active"
                        value="0"
                    >

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>
                            Active
                        </x-admin::form.control-group.label>

                        <label class="inline-flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                @checked($isActive)
                            >

                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                Make this courier available during shipment booking.
                            </span>
                        </label>

                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                            Turn this off when your team should stop selecting this courier for new shipments.
                        </p>

                        <x-admin::form.control-group.error control-name="is_active" />
                    </x-admin::form.control-group>
                </div>

                @if ($isProMode)
                    <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                            Admin Details
                        </p>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Support Email
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="email"
                                name="contact_email"
                                :value="old('contact_email', $carrier->contact_email)"
                                label="Support Email"
                                placeholder="ops@example.com"
                            />

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                Optional. Use an email your operations team monitors for courier issues.
                            </p>

                            <x-admin::form.control-group.error control-name="contact_email" />
                        </x-admin::form.control-group>

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
                                Lower numbers appear earlier when your team chooses a courier.
                            </p>

                            <x-admin::form.control-group.error control-name="sort_order" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                Notes
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="textarea"
                                name="notes"
                                :value="old('notes', $carrier->notes)"
                                label="Notes"
                                placeholder="Internal notes for your operations team."
                            />

                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                Only your admin team sees this. Use it for reminders or special operating rules.
                            </p>

                            <x-admin::form.control-group.error control-name="notes" />
                        </x-admin::form.control-group>
                    </div>
                @endif
            </div>
        </div>
    </x-admin::form>

    @pushOnce('scripts')
        <script type="text/javascript">
            (() => {
                const automationSelect = document.getElementById('courier-automation-select');
                const courierNameInput = document.getElementById('courier-name');
                const courierCodeInput = document.getElementById('courier-code');
                const supportsCodToggle = document.getElementById('supports-cod-toggle');
                let codeManuallyEdited = {{ $isEdit || filled(old('code', $carrier->code)) ? 'true' : 'false' }};

                const slugify = (value) => value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '')
                    .slice(0, 100);

                window.handleAutomationTypeChange = (selectElement) => {
                    const selectedAutomation = selectElement?.value || automationSelect?.value || '{{ $selectedAutomationType }}';

                    document.querySelectorAll('[data-automation-section]').forEach((section) => {
                        const supportedTypes = (section.dataset.automationSection || '')
                            .split(',')
                            .map((value) => value.trim())
                            .filter(Boolean);

                        const isVisible = supportedTypes.includes(selectedAutomation);

                        section.classList.toggle('hidden', ! isVisible);

                        section.querySelectorAll('input, select, textarea').forEach((input) => {
                            if (input.name === 'tracking_sync_enabled' && input.type === 'hidden') {
                                return;
                            }

                            input.disabled = ! isVisible;
                        });
                    });

                    return selectedAutomation;
                };

                window.toggleCodSettings = (isVisible) => {
                    document.querySelectorAll('[data-cod-settings]').forEach((section) => {
                        section.classList.toggle('hidden', ! isVisible);

                        section.querySelectorAll('input, select, textarea').forEach((input) => {
                            input.disabled = ! isVisible;
                        });
                    });

                    return isVisible;
                };

                if (courierNameInput && courierCodeInput) {
                    courierNameInput.addEventListener('input', () => {
                        if (codeManuallyEdited) {
                            return;
                        }

                        courierCodeInput.value = slugify(courierNameInput.value);
                    });

                    courierCodeInput.addEventListener('input', () => {
                        const generatedValue = slugify(courierNameInput.value);
                        const enteredValue = courierCodeInput.value.trim();

                        codeManuallyEdited = enteredValue !== '' && enteredValue !== generatedValue;
                    });
                }

                if (automationSelect) {
                    window.handleAutomationTypeChange(automationSelect);
                }

                if (supportsCodToggle) {
                    window.toggleCodSettings(supportsCodToggle.checked);
                }
            })();
        </script>
    @endPushOnce
</x-admin::layouts>
