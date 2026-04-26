@php
    $socialProfilesText = data_get($profile?->social_profiles, 'text');
@endphp

<form
    method="POST"
    action="{{ route('shop.customers.account.affiliate.apply') }}"
    class="rounded-2xl border border-zinc-200 bg-white p-6"
>
    @csrf

    <div>
        <p class="text-lg font-medium text-black">
            Affiliate Application
        </p>

        <p class="mt-2 text-sm leading-6 text-zinc-500">
            Share a few details so the team can review whether the affiliate program is a good fit.
        </p>
    </div>

    <div class="mt-6 grid gap-5">
        <div class="grid gap-2">
            <label
                for="application_note"
                class="text-sm font-medium text-zinc-800"
            >
                How will you promote this store? <span class="text-red-600">*</span>
            </label>

            <textarea
                id="application_note"
                name="application_note"
                rows="4"
                class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-black outline-none transition hover:border-zinc-300 focus:border-navyBlue"
                placeholder="Example: I will share products through my blog, Facebook page, and customer community."
            >{{ old('application_note', $profile?->application_note) }}</textarea>

            @error('application_note')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div class="grid gap-2">
                <label
                    for="website_url"
                    class="text-sm font-medium text-zinc-800"
                >
                    Website URL
                </label>

                <input
                    id="website_url"
                    name="website_url"
                    type="url"
                    value="{{ old('website_url', $profile?->website_url) }}"
                    class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-black outline-none transition hover:border-zinc-300 focus:border-navyBlue"
                    placeholder="https://example.com"
                >

                @error('website_url')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-2">
                <label
                    for="payout_method"
                    class="text-sm font-medium text-zinc-800"
                >
                    Preferred payout method
                </label>

                <select
                    id="payout_method"
                    name="payout_method"
                    class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-black outline-none transition hover:border-zinc-300 focus:border-navyBlue"
                >
                    <option value="">Choose later</option>

                    @foreach ($payoutMethods as $methodCode => $methodLabel)
                        <option
                            value="{{ $methodCode }}"
                            @selected(old('payout_method', $profile?->payout_method) === $methodCode)
                        >
                            {{ $methodLabel }}
                        </option>
                    @endforeach
                </select>

                @error('payout_method')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-2">
            <label
                for="social_profiles_text"
                class="text-sm font-medium text-zinc-800"
            >
                Social profiles or audience links
            </label>

            <textarea
                id="social_profiles_text"
                name="social_profiles_text"
                rows="3"
                class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-black outline-none transition hover:border-zinc-300 focus:border-navyBlue"
                placeholder="Facebook page, YouTube channel, Instagram, community, or other audience links."
            >{{ old('social_profiles_text', $socialProfilesText) }}</textarea>

            @error('social_profiles_text')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label
                for="payout_reference"
                class="text-sm font-medium text-zinc-800"
            >
                Payout account details
            </label>

            <input
                id="payout_reference"
                name="payout_reference"
                type="text"
                value="{{ old('payout_reference', $profile?->payout_reference) }}"
                class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-black outline-none transition hover:border-zinc-300 focus:border-navyBlue"
                placeholder="Optional account, bank, wallet, or payment reference"
            >

            @error('payout_reference')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm leading-6 text-zinc-700">
            <input
                type="checkbox"
                name="terms_accepted"
                value="1"
                class="mt-1 h-4 w-4 rounded border-zinc-300"
                @checked(old('terms_accepted'))
            >

            <span>
                {{ $termsText }}
            </span>
        </label>

        @error('terms_accepted')
            <p class="text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-6 flex justify-end">
        <button
            type="submit"
            class="primary-button rounded-xl px-6 py-3 text-sm font-medium"
        >
            {{ $submitLabel }}
        </button>
    </div>
</form>
