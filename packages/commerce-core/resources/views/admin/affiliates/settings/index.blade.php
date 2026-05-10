<x-admin::layouts>
    <x-slot:title>
        Affiliate Settings
    </x-slot>

    @php
        $cardClass = 'overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900';
        $cardHeaderClass = 'mb-5 flex items-start justify-between gap-4';
        $cardTitleClass = 'font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white';
        $cardSubtitleClass = 'mt-1 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400';
        $labelClass = 'text-sm font-medium text-slate-700 dark:text-slate-200';
        $helperClass = 'mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400';
        $inputClass = 'w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-950 dark:text-slate-200 dark:placeholder:text-gray-500 dark:focus:border-blue-400';
        $selectClass = 'custom-select w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 transition-all hover:border-slate-300 focus:border-blue-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-950 dark:text-slate-200 dark:hover:border-gray-700 dark:focus:border-blue-400';
        $textareaClass = 'w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm text-slate-700 transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-950 dark:text-slate-200 dark:placeholder:text-gray-500 dark:focus:border-blue-400';
        $checkboxWrapClass = 'rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-800 dark:bg-gray-950';
        $checkboxClass = 'mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900';
    @endphp

    <x-admin::form
        :action="route('admin.affiliates.settings.update')"
        v-slot="{ errors }"
    >
        <section class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-950 dark:text-white">
                    Affiliate Settings
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                    Configure affiliate approval, commission, payout, and terms from a single place.
                </p>
            </div>

            <button
                type="submit"
                class="primary-button shrink-0"
            >
                Save Settings
            </button>
        </section>

        @if (session('success'))
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 shadow-sm dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <div>
                        <h2 class="{{ $cardTitleClass }}">
                            Approval and Commission
                        </h2>

                        <p class="{{ $cardSubtitleClass }}">
                            Set who can join and how affiliate earnings are approved.
                        </p>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">
                            Approval Required
                        </label>

                        <input
                            type="hidden"
                            name="approval_required"
                            value="0"
                        >

                        <label class="{{ $checkboxWrapClass }} mt-2 flex items-start gap-3">
                            <input
                                type="checkbox"
                                name="approval_required"
                                value="1"
                                class="{{ $checkboxClass }}"
                                @checked(old('approval_required', (int) $settings['approval_required']))
                            >

                            <span class="text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Customers require admin approval before becoming active affiliates.
                            </span>
                        </label>

                        @error('approval_required')
                            <p class="mt-2 text-xs text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Cookie Window Days
                        </label>

                        <input
                            type="number"
                            min="1"
                            max="365"
                            name="cookie_window_days"
                            value="{{ old('cookie_window_days', $settings['cookie_window_days']) }}"
                            class="{{ $inputClass }}"
                        >

                        <p class="{{ $helperClass }}">
                            Referral links remain valid while the affiliate is active. This window controls how long a visitor click can still attribute an order.
                        </p>

                        @error('cookie_window_days')
                            <p class="mt-2 text-xs text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Default Commission Type
                        </label>

                        <select
                            name="default_commission_type"
                            class="{{ $selectClass }}"
                        >
                            <option value="percentage" @selected(old('default_commission_type', $settings['default_commission']['type']) === 'percentage')>
                                Percentage
                            </option>

                            <option value="fixed" @selected(old('default_commission_type', $settings['default_commission']['type']) === 'fixed')>
                                Fixed Amount
                            </option>
                        </select>

                        @error('default_commission_type')
                            <p class="mt-2 text-xs text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Affiliate Commission Approval
                        </label>

                        <select
                            name="commission_approval_mode"
                            class="{{ $selectClass }}"
                        >
                            <option value="manual" @selected(old('commission_approval_mode', $settings['commission_approval_mode']) === 'manual')>
                                Manual
                            </option>

                            <option value="automatic" @selected(old('commission_approval_mode', $settings['commission_approval_mode']) === 'automatic')>
                                Automatic
                            </option>
                        </select>

                        <p class="{{ $helperClass }}">
                            Manual keeps new commissions pending until admin approval. Automatic approves commissions when orders become eligible.
                        </p>

                        @error('commission_approval_mode')
                            <p class="mt-2 text-xs text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Default Commission Value
                        </label>

                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            name="default_commission_value"
                            value="{{ old('default_commission_value', $settings['default_commission']['value']) }}"
                            class="{{ $inputClass }}"
                        >

                        <p class="{{ $helperClass }}">
                            Percentage uses this as a percent. Fixed amount uses this as the commission value.
                        </p>

                        @error('default_commission_value')
                            <p class="mt-2 text-xs text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <div>
                        <h2 class="{{ $cardTitleClass }}">
                            Payout Rules
                        </h2>

                        <p class="{{ $cardSubtitleClass }}">
                            Control minimum payout thresholds and available payout methods.
                        </p>
                    </div>
                </div>

                <div class="grid gap-5">
                    <div>
                        <label class="{{ $labelClass }}">
                            Minimum Payout Amount
                        </label>

                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            name="minimum_payout_amount"
                            value="{{ old('minimum_payout_amount', $settings['minimum_payout_amount']) }}"
                            class="{{ $inputClass }}"
                        >

                        @error('minimum_payout_amount')
                            <p class="mt-2 text-xs text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Payout Methods
                        </label>

                        <textarea
                            name="payout_methods_text"
                            rows="8"
                            class="{{ $textareaClass }}"
                        >{{ old('payout_methods_text', $payoutMethodsText) }}</textarea>

                        <p class="{{ $helperClass }}">
                            Add one method per line using code=Label, for example bank_transfer=Bank Transfer.
                        </p>

                        @error('payout_methods_text')
                            <p class="mt-2 text-xs text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="{{ $cardClass }} xl:col-span-2">
                <div class="{{ $cardHeaderClass }}">
                    <div>
                        <h2 class="{{ $cardTitleClass }}">
                            Affiliate Terms
                        </h2>

                        <p class="{{ $cardSubtitleClass }}">
                            Shown to customers when they apply for the affiliate program.
                        </p>
                    </div>
                </div>

                <div class="grid gap-5">
                    <div>
                        <label class="{{ $labelClass }}">
                            Terms Text
                        </label>

                        <textarea
                            name="terms_text"
                            rows="9"
                            class="{{ $textareaClass }}"
                        >{{ old('terms_text', $settings['terms_text']) }}</textarea>

                        @error('terms_text')
                            <p class="mt-2 text-xs text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </section>
        </div>
    </x-admin::form>
</x-admin::layouts>
