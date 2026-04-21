@php
    $channels = core()->getAllChannels();

    $currentChannel = core()->getRequestedChannel();

    $currentLocale = core()->getRequestedLocale();

    $activeConfiguration = system_config()->getActiveConfigurationItem();

    $configurationDisplayNames = [
        'sales.shipping' => 'Shipping',
        'sales.shipping.origin' => 'Origin & Fulfillment',
    ];

    $configurationDisplayInfo = [
        'sales.shipping' => 'Manage shipping origin, pickup points, checkout delivery methods, courier workflow, and shipment notifications in one place.',
        'sales.shipping.origin' => 'Set the business address and fulfillment details used for shipping calculations and order paperwork.',
    ];

    $isPaymentMethodConfiguration = request()->route('slug') === 'sales'
        && request()->route('slug2') === 'payment_methods';

    $name = $configurationDisplayNames[$activeConfiguration->getKey()] ?? $activeConfiguration->getName();
@endphp

<x-admin::layouts>
    <x-slot:title>
        {{ $name }}
    </x-slot>

    <x-admin::form
        action=""
        enctype="multipart/form-data"
    >
        <div class="mt-3.5 flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p
                class="text-xl font-bold text-gray-800 dark:text-white"
                v-pre
            >
                {{ $name }}
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.configuration.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('admin::app.configuration.index.back-btn')
                </a>

                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('admin::app.configuration.index.save-btn')
                </button>
            </div>
        </div>

        <div class="mt-7 flex items-center justify-between gap-4 max-md:flex-wrap">
            <div class="flex items-center gap-x-1">
                <x-admin::dropdown :class="$channels->count() <= 1 ? 'hidden' : ''">
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="transparent-button px-1 py-1.5 hover:bg-gray-200 focus:bg-gray-200 dark:text-white dark:hover:bg-gray-800 dark:focus:bg-gray-800"
                        >
                            <span class="icon-store text-2xl"></span>

                            <span v-pre>{{ $currentChannel->name }}</span>

                            <input
                                type="hidden"
                                name="channel"
                                value="{{ $currentChannel->code }}"
                            />

                            <span class="icon-sort-down text-2xl"></span>
                        </button>
                    </x-slot>

                    <x-slot:content class="!p-0">
                        @foreach ($channels as $channel)
                            <a
                                href="?{{ Arr::query(['channel' => $channel->code, 'locale' => $channel->default_locale?->code ?? $currentLocale->code]) }}"
                                class="flex cursor-pointer gap-2.5 px-5 py-2 text-base hover:bg-gray-100 dark:text-white dark:hover:bg-gray-950"
                                v-pre
                            >
                                {{ $channel->name }}
                            </a>
                        @endforeach
                    </x-slot>
                </x-admin::dropdown>

                <x-admin::dropdown :class="$currentChannel->locales->count() <= 1 ? 'hidden' : ''">
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="transparent-button px-1 py-1.5 hover:bg-gray-200 focus:bg-gray-200 dark:text-white dark:hover:bg-gray-800 dark:focus:bg-gray-800"
                        >
                            <span class="icon-language text-2xl"></span>

                            <span v-pre>{{ $currentLocale->name }}</span>

                            <input
                                type="hidden"
                                name="locale"
                                value="{{ $currentLocale->code }}"
                            />

                            <span class="icon-sort-down text-2xl"></span>
                        </button>
                    </x-slot>

                    <x-slot:content class="!p-0">
                        @foreach ($currentChannel->locales->sortBy('name') as $locale)
                            <a
                                href="?{{ Arr::query(['channel' => $currentChannel->code, 'locale' => $locale->code]) }}"
                                class="flex cursor-pointer gap-2.5 px-5 py-2 text-base hover:bg-gray-100 dark:text-white dark:hover:bg-gray-950 {{ $locale->code == $currentLocale->code ? 'bg-gray-100 dark:bg-gray-950' : '' }}"
                                v-pre
                            >
                                {{ $locale->name }}
                            </a>
                        @endforeach
                    </x-slot>
                </x-admin::dropdown>
            </div>
        </div>

        @if ($isPaymentMethodConfiguration)
            @include('vendor.admin.configuration.partials.payment-method-tabs')
        @else
            <div class="mt-6 grid grid-cols-[1fr_2fr] gap-10 max-xl:flex-wrap">
                @foreach ($activeConfiguration->getChildren() as $child)
                    @php
                        $childName = $configurationDisplayNames[$child->getKey()] ?? $child->getName();
                        $childInfo = $configurationDisplayInfo[$child->getKey()] ?? $child->getInfo();
                    @endphp

                    <div class="grid content-start gap-2.5">
                        <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                            {{ $childName }}
                        </p>

                        <p class="leading-[140%] text-gray-600 dark:text-gray-300">
                            {!! $childInfo !!}
                        </p>
                    </div>

                    <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                        @foreach ($child->getFields() as $field)
                            @if (
                                $field->getType() == 'blade'
                                && view()->exists($path = $field->getPath())
                            )
                                {!! view($path, compact('field', 'child'))->render() !!}
                            @else
                                @include('admin::configuration.field-type')
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </x-admin::form>
</x-admin::layouts>
