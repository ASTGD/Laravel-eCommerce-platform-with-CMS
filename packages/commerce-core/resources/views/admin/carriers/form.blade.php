@php
    $isEdit = (bool) $carrier->exists;
    $selectedIntegrationDriver = old('integration_driver', $carrier->trackingDriver());
@endphp

<x-admin::layouts>
    <x-slot:title>
        {{ $isEdit ? 'Edit Carrier' : 'Create Carrier' }}
    </x-slot>

    <x-admin::form
        :action="$isEdit ? route('admin.sales.carriers.update', $carrier) : route('admin.sales.carriers.store')"
    >
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                {{ $isEdit ? 'Edit Carrier' : 'Create Carrier' }}
            </p>

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
                    Save Carrier
                </button>
            </div>
        </div>

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        General
                    </p>

                    <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                Code
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="code"
                                rules="required"
                                :value="old('code', $carrier->code)"
                                label="Code"
                                placeholder="steadfast"
                            />

                            <x-admin::form.control-group.error control-name="code" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                Name
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="name"
                                rules="required"
                                :value="old('name', $carrier->name)"
                                label="Name"
                                placeholder="Steadfast"
                            />

                            <x-admin::form.control-group.error control-name="name" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="col-span-2 max-md:col-span-1">
                            <x-admin::form.control-group.label>
                                Tracking URL Template
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="tracking_url_template"
                                :value="old('tracking_url_template', $carrier->tracking_url_template)"
                                label="Tracking URL Template"
                                placeholder="https://carrier.example/track/{tracking_number}"
                            />

                            <x-admin::form.control-group.error control-name="tracking_url_template" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        Contact
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
                                placeholder="Contact Name"
                            />

                            <x-admin::form.control-group.error control-name="contact_name" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Contact Phone
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="contact_phone"
                                :value="old('contact_phone', $carrier->contact_phone)"
                                label="Contact Phone"
                                placeholder="+8801XXXXXXXXX"
                            />

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

                            <x-admin::form.control-group.error control-name="contact_email" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        Tracking Integration
                    </p>

                    <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Integration Driver
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="integration_driver"
                                :label="'Integration Driver'"
                            >
                                @foreach ($integrationDrivers as $integrationDriver => $integrationLabel)
                                    <option
                                        value="{{ $integrationDriver }}"
                                        @selected(old('integration_driver', $carrier->trackingDriver()) === $integrationDriver)
                                    >
                                        {{ $integrationLabel }}
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>

                            <x-admin::form.control-group.error control-name="integration_driver" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                API Base URL
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="api_base_url"
                                :value="old('api_base_url', $carrier->api_base_url)"
                                label="API Base URL"
                                placeholder="https://api.carrier.example"
                            />

                            <x-admin::form.control-group.error control-name="api_base_url" />

                            @if ($selectedIntegrationDriver === 'pathao')
                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Pathao booking and tracking both use the merchant API base URL. Use the official sandbox or live endpoint provided by Pathao.
                                </p>
                            @endif
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                API Store ID
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="number"
                                name="api_store_id"
                                min="1"
                                :value="old('api_store_id', $carrier->api_store_id)"
                                label="API Store ID"
                                placeholder="Pathao merchant store ID"
                            />

                            <x-admin::form.control-group.error control-name="api_store_id" />

                            @if ($selectedIntegrationDriver === 'pathao')
                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Required for Pathao booking. Use the merchant store ID from the Pathao merchant panel.
                                </p>
                            @endif
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                API Username
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="api_username"
                                :value="old('api_username', $carrier->api_username)"
                                label="API Username"
                                placeholder="api-user"
                            />

                            <x-admin::form.control-group.error control-name="api_username" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                API Password
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="api_password"
                                label="API Password"
                                placeholder="{{ $isEdit ? 'Leave blank to keep current password' : 'API Password' }}"
                            />

                            <x-admin::form.control-group.error control-name="api_password" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                API Key
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="api_key"
                                label="API Key"
                                placeholder="{{ $isEdit ? 'Leave blank to keep current key' : 'API Key' }}"
                            />

                            <x-admin::form.control-group.error control-name="api_key" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                API Secret
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="api_secret"
                                label="API Secret"
                                placeholder="{{ $isEdit ? 'Leave blank to keep current secret' : 'API Secret' }}"
                            />

                            <x-admin::form.control-group.error control-name="api_secret" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="col-span-2 max-md:col-span-1">
                            <x-admin::form.control-group.label>
                                Webhook Secret
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="webhook_secret"
                                label="Webhook Secret"
                                placeholder="{{ $isEdit ? 'Leave blank to keep current webhook secret' : 'Webhook Secret' }}"
                            />

                            <x-admin::form.control-group.error control-name="webhook_secret" />

                            @if ($selectedIntegrationDriver === 'pathao')
                                <div class="mt-2 rounded border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-800 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-100">
                                    <p class="font-semibold">
                                        Pathao setup checklist
                                    </p>

                                    <ul class="mt-2 list-disc space-y-1 pl-4">
                                        <li>Register or confirm the merchant account in the Pathao merchant panel.</li>
                                        <li>Set the API Base URL, API Store ID, API Username, API Password, API Key, and API Secret.</li>
                                        <li>Save the carrier once before copying the callback URL.</li>
                                        <li>Send the webhook signature in <span class="font-mono">X-PATHAO-Signature</span> and echo the same secret in <span class="font-mono">X-Pathao-Merchant-Webhook-Integration-Secret</span>.</li>
                                    </ul>

                                    @if ($carrier->exists)
                                        <p class="mt-2">
                                            Callback URL:
                                            <span class="break-all font-mono">
                                                {{ route('commerce-core.webhooks.shipment-carriers.pathao', $carrier) }}
                                            </span>
                                        </p>
                                    @else
                                        <p class="mt-2">
                                            Save the carrier to reveal the exact callback URL for this install.
                                        </p>
                                    @endif
                                </div>
                            @elseif ($carrier->exists && $carrier->trackingDriver() === 'steadfast')
                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Callback URL:
                                    <span class="break-all font-mono">
                                        {{ route('commerce-core.webhooks.shipment-carriers.steadfast', $carrier) }}
                                    </span>
                                </p>

                                <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Configure Steadfast to send the webhook token as a bearer token matching this secret.
                                </p>
                            @endif
                        </x-admin::form.control-group>
                    </div>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        COD and Payout Defaults
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
                                    Select COD fee type
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

                            <x-admin::form.control-group.error control-name="default_cod_fee_type" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Default COD Fee Amount
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="number"
                                name="default_cod_fee_amount"
                                step="0.01"
                                min="0"
                                :value="old('default_cod_fee_amount', $carrier->default_cod_fee_amount ?? 0)"
                                label="Default COD Fee Amount"
                                placeholder="0.00"
                            />

                            <x-admin::form.control-group.error control-name="default_cod_fee_amount" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Default Return Fee Amount
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="number"
                                name="default_return_fee_amount"
                                step="0.01"
                                min="0"
                                :value="old('default_return_fee_amount', $carrier->default_return_fee_amount ?? 0)"
                                label="Default Return Fee Amount"
                                placeholder="0.00"
                            />

                            <x-admin::form.control-group.error control-name="default_return_fee_amount" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="col-span-2 max-md:col-span-1">
                            <x-admin::form.control-group.label>
                                Notes
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="textarea"
                                name="notes"
                                :value="old('notes', $carrier->notes)"
                                label="Notes"
                                placeholder="Carrier notes"
                            />

                            <x-admin::form.control-group.error control-name="notes" />
                        </x-admin::form.control-group>
                    </div>
                </div>
            </div>

            <div class="flex w-[360px] max-w-full flex-col gap-2 max-xl:w-full">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        Settings
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
                            placeholder="Sort Order"
                        />

                        <x-admin::form.control-group.error control-name="sort_order" />
                    </x-admin::form.control-group>

                    <input
                        type="hidden"
                        name="supports_cod"
                        value="0"
                    >

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Tracking Sync Enabled
                        </x-admin::form.control-group.label>

                        <input
                            type="hidden"
                            name="tracking_sync_enabled"
                            value="0"
                        >

                        <label class="inline-flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="tracking_sync_enabled"
                                value="1"
                                @checked((int) old('tracking_sync_enabled', $carrier->tracking_sync_enabled ? 1 : 0) === 1)
                            >

                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                Allow shipment tracking sync jobs and admin sync actions
                            </span>
                        </label>

                        <x-admin::form.control-group.error control-name="tracking_sync_enabled" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Supports COD
                        </x-admin::form.control-group.label>

                        <label class="inline-flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="supports_cod"
                                value="1"
                                @checked((int) old('supports_cod', $carrier->supports_cod ? 1 : 0) === 1)
                            >

                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                Courier collects cash on delivery
                            </span>
                        </label>

                        <x-admin::form.control-group.error control-name="supports_cod" />
                    </x-admin::form.control-group>

                    <input
                        type="hidden"
                        name="is_active"
                        value="0"
                    >

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Active
                        </x-admin::form.control-group.label>

                        <label class="inline-flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                @checked((int) old('is_active', $carrier->is_active ? 1 : 0) === 1)
                            >

                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                Available for shipment operations
                            </span>
                        </label>

                        <x-admin::form.control-group.error control-name="is_active" />
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
