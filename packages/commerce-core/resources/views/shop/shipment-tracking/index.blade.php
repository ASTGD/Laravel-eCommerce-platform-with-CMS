<x-shop::layouts>
    <x-slot:title>
        Shipment Tracking
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-10 max-md:py-7">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 max-md:p-4">
            <div class="max-w-2xl">
                <h1 class="text-2xl font-medium text-black max-md:text-xl">
                    Track Your Shipment
                </h1>

                <p class="mt-2 text-sm text-zinc-500">
                    Enter your order number or tracking number and the phone number used during checkout.
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('shop.shipment-tracking.lookup') }}"
                class="mt-6 grid gap-4 md:grid-cols-2"
            >
                @csrf

                <div class="grid gap-1.5">
                    <label
                        for="reference"
                        class="text-sm font-medium text-zinc-700"
                    >
                        Order / Tracking Number
                    </label>

                    <input
                        id="reference"
                        name="reference"
                        type="text"
                        value="{{ old('reference', $lookupInput['reference'] ?? data_get($trackingResult, 'reference')) }}"
                        class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-black outline-none transition hover:border-zinc-300 focus:border-navyBlue"
                        placeholder="e.g. 000000123 or TRACK-12345"
                    >

                    @error('reference')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-1.5">
                    <label
                        for="phone"
                        class="text-sm font-medium text-zinc-700"
                    >
                        Phone Number
                    </label>

                    <input
                        id="phone"
                        name="phone"
                        type="text"
                        value="{{ old('phone', $lookupInput['phone'] ?? null) }}"
                        class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-black outline-none transition hover:border-zinc-300 focus:border-navyBlue"
                        placeholder="e.g. 017XXXXXXXX"
                    >

                    @error('phone')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-navyBlue px-5 py-3 text-sm font-medium text-white transition hover:opacity-90"
                    >
                        Track Shipment
                    </button>
                </div>
            </form>
        </div>

        @if ($lookupError)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                {{ $lookupError }}
            </div>
        @endif

        @if ($trackingResult)
            <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 max-md:p-4">
                <div class="flex items-center justify-between gap-3 max-sm:flex-wrap">
                    <div>
                        <p class="text-base font-medium text-black">
                            Shipment Tracking Result
                        </p>

                        @if (! empty($trackingResult['order_increment_id']))
                            <p class="mt-1 text-sm text-zinc-500">
                                Order #{{ $trackingResult['order_increment_id'] }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="mt-5">
                    @include('commerce-core::shop.shipment-tracking.cards', ['shipmentTimelines' => $trackingResult['shipments']])
                </div>
            </div>
        @endif
    </div>
</x-shop::layouts>
