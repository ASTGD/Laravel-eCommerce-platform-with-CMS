<x-admin::layouts>
    <x-slot:title>
        Affiliate Settings
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Affiliate Settings
            </p>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                Configure the shared affiliate rules used by both admin and customer portal workflows.
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="mt-5 rounded border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('admin.affiliates.settings.update') }}"
        class="mt-5 grid gap-4"
    >
        @csrf

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                Approval and Commission
            </p>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <label class="text-sm font-medium text-gray-800 dark:text-white">
                        Approval Required
                    </label>

                    <input type="hidden" name="approval_required" value="0">

                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <input
                            type="checkbox"
                            name="approval_required"
                            value="1"
                            class="rounded border-gray-300"
                            @checked(old('approval_required', (int) $settings['approval_required']))
                        >
                        Customers require admin approval before becoming active affiliates.
                    </label>

                    @error('approval_required') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-2">
                    <label class="text-sm font-medium text-gray-800 dark:text-white">
                        Cookie Window Days
                    </label>

                    <input
                        type="number"
                        min="1"
                        max="365"
                        name="cookie_window_days"
                        value="{{ old('cookie_window_days', $settings['cookie_window_days']) }}"
                        class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                    >

                    <p class="text-xs text-gray-500 dark:text-gray-300">
                        Orders can be attributed while the referral cookie is still inside this window.
                    </p>

                    @error('cookie_window_days') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-2">
                    <label class="text-sm font-medium text-gray-800 dark:text-white">
                        Default Commission Type
                    </label>

                    <select
                        name="default_commission_type"
                        class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                    >
                        <option value="percentage" @selected(old('default_commission_type', $settings['default_commission']['type']) === 'percentage')>
                            Percentage
                        </option>

                        <option value="fixed" @selected(old('default_commission_type', $settings['default_commission']['type']) === 'fixed')>
                            Fixed Amount
                        </option>
                    </select>

                    @error('default_commission_type') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-2">
                    <label class="text-sm font-medium text-gray-800 dark:text-white">
                        Default Commission Value
                    </label>

                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        name="default_commission_value"
                        value="{{ old('default_commission_value', $settings['default_commission']['value']) }}"
                        class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                    >

                    <p class="text-xs text-gray-500 dark:text-gray-300">
                        Percentage uses this as a percent. Fixed amount uses this as the commission value.
                    </p>

                    @error('default_commission_value') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                Payout Rules
            </p>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <label class="text-sm font-medium text-gray-800 dark:text-white">
                        Minimum Payout Amount
                    </label>

                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        name="minimum_payout_amount"
                        value="{{ old('minimum_payout_amount', $settings['minimum_payout_amount']) }}"
                        class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                    >

                    @error('minimum_payout_amount') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-2 md:row-span-2">
                    <label class="text-sm font-medium text-gray-800 dark:text-white">
                        Payout Methods
                    </label>

                    <textarea
                        name="payout_methods_text"
                        rows="6"
                        class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                    >{{ old('payout_methods_text', $payoutMethodsText) }}</textarea>

                    <p class="text-xs text-gray-500 dark:text-gray-300">
                        Add one method per line using code=Label, for example bank_transfer=Bank Transfer.
                    </p>

                    @error('payout_methods_text') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                Affiliate Terms
            </p>

            <div class="mt-4 grid gap-2">
                <label class="text-sm font-medium text-gray-800 dark:text-white">
                    Terms Text
                </label>

                <textarea
                    name="terms_text"
                    rows="7"
                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                >{{ old('terms_text', $settings['terms_text']) }}</textarea>

                <p class="text-xs text-gray-500 dark:text-gray-300">
                    Shown to customers when they apply for the affiliate program.
                </p>

                @error('terms_text') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <button type="submit" class="primary-button">
                Save Settings
            </button>
        </div>
    </form>
</x-admin::layouts>
