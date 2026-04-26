<x-admin::layouts>
    <x-slot:title>
        Add Affiliate
    </x-slot>

    <div class="flex items-start justify-between gap-4 max-sm:flex-wrap">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Add Affiliate
            </p>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                Create one affiliate profile for an existing customer account.
            </p>
        </div>

        <a
            href="{{ route('admin.affiliates.profiles.index') }}"
            class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
        >
            Back
        </a>
    </div>

    <form
        method="POST"
        action="{{ route('admin.affiliates.profiles.store') }}"
        class="mt-5 grid gap-4 lg:grid-cols-[2fr_1fr]"
    >
        @csrf

        <div class="grid gap-4">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    Customer and Status
                </p>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2 md:col-span-2">
                        <label
                            for="customer_id"
                            class="text-sm font-medium text-gray-800 dark:text-white"
                        >
                            Customer Account <span class="text-red-600">*</span>
                        </label>

                        <select
                            id="customer_id"
                            name="customer_id"
                            class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        >
                            <option value="">Select customer</option>

                            @foreach ($customers as $customer)
                                @php
                                    $customerName = trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''));
                                @endphp

                                <option
                                    value="{{ $customer->id }}"
                                    @selected((string) old('customer_id') === (string) $customer->id)
                                >
                                    {{ $customerName ?: 'Customer #'.$customer->id }} — {{ $customer->email }}
                                </option>
                            @endforeach
                        </select>

                        @if ($customers->isEmpty())
                            <p class="text-xs text-amber-600">
                                No customer accounts without an affiliate profile are available.
                            </p>
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-300">
                                The affiliate will use this customer login. No separate affiliate account is created.
                            </p>
                        @endif

                        @error('customer_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-2">
                        <label
                            for="status"
                            class="text-sm font-medium text-gray-800 dark:text-white"
                        >
                            Status <span class="text-red-600">*</span>
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        >
                            @foreach ($statusOptions as $statusCode => $statusLabel)
                                <option
                                    value="{{ $statusCode }}"
                                    @selected(old('status', \Platform\CommerceCore\Models\AffiliateProfile::STATUS_ACTIVE) === $statusCode)
                                >
                                    {{ $statusLabel }}
                                </option>
                            @endforeach
                        </select>

                        <p class="text-xs text-gray-500 dark:text-gray-300">
                            Active records are approved immediately. Pending records enter the normal review list.
                        </p>

                        @error('status') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-2">
                        <label
                            for="referral_code"
                            class="text-sm font-medium text-gray-800 dark:text-white"
                        >
                            Referral Code
                        </label>

                        <input
                            id="referral_code"
                            name="referral_code"
                            type="text"
                            value="{{ old('referral_code') }}"
                            class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            placeholder="Leave blank to auto-generate"
                        >

                        <p class="text-xs text-gray-500 dark:text-gray-300">
                            Leave this blank unless support needs a specific code.
                        </p>

                        @error('referral_code') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    Affiliate Details
                </p>

                <div class="mt-4 grid gap-4">
                    <div class="grid gap-2">
                        <label
                            for="application_note"
                            class="text-sm font-medium text-gray-800 dark:text-white"
                        >
                            Profile / Application Note
                        </label>

                        <textarea
                            id="application_note"
                            name="application_note"
                            rows="4"
                            class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            placeholder="Internal context about this affiliate."
                        >{{ old('application_note') }}</textarea>

                        @error('application_note') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <label
                                for="website_url"
                                class="text-sm font-medium text-gray-800 dark:text-white"
                            >
                                Website URL
                            </label>

                            <input
                                id="website_url"
                                name="website_url"
                                type="url"
                                value="{{ old('website_url') }}"
                                class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                placeholder="https://example.com"
                            >

                            @error('website_url') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-2">
                            <label
                                for="payout_method"
                                class="text-sm font-medium text-gray-800 dark:text-white"
                            >
                                Payout Method
                            </label>

                            <select
                                id="payout_method"
                                name="payout_method"
                                class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option value="">Choose later</option>

                                @foreach ($payoutMethods as $methodCode => $methodLabel)
                                    <option
                                        value="{{ $methodCode }}"
                                        @selected(old('payout_method') === $methodCode)
                                    >
                                        {{ $methodLabel }}
                                    </option>
                                @endforeach
                            </select>

                            @error('payout_method') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <label
                            for="social_profiles_text"
                            class="text-sm font-medium text-gray-800 dark:text-white"
                        >
                            Social Profiles / Audience Links
                        </label>

                        <textarea
                            id="social_profiles_text"
                            name="social_profiles_text"
                            rows="3"
                            class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            placeholder="Facebook page, YouTube channel, community, or other audience links."
                        >{{ old('social_profiles_text') }}</textarea>

                        @error('social_profiles_text') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-2">
                        <label
                            for="payout_reference"
                            class="text-sm font-medium text-gray-800 dark:text-white"
                        >
                            Payout Account Details
                        </label>

                        <input
                            id="payout_reference"
                            name="payout_reference"
                            type="text"
                            value="{{ old('payout_reference') }}"
                            class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            placeholder="Optional bank, wallet, or payment reference"
                        >

                        @error('payout_reference') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="grid content-start gap-4">
            <div class="box-shadow rounded bg-white p-4 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-300">
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    Admin-created affiliate
                </p>

                <p class="mt-3 leading-6">
                    This creates an affiliate profile on the selected customer account and tags the application source as admin-created.
                </p>

                <p class="mt-3 leading-6">
                    Use Active when the customer is already approved. Use Pending when the customer still needs review.
                </p>
            </div>

            <div class="flex justify-end gap-2">
                <a
                    href="{{ route('admin.affiliates.profiles.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="primary-button"
                    @disabled($customers->isEmpty())
                >
                    Create Affiliate
                </button>
            </div>
        </div>
    </form>
</x-admin::layouts>
