@php
    use Webkul\Core\SystemConfig\ItemField;

    $channels = core()->getAllChannels();
    $currentChannel = core()->getRequestedChannel();
    $currentLocale = core()->getRequestedLocale();
    $systemConfigGroup = collect(config('core'))->firstWhere('key', $groupKey);
@endphp

@if ($systemConfigGroup)
    <div class="rounded-lg border border-slate-200 p-4 dark:border-gray-800">
        <div class="mb-4 grid gap-1">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                {{ trans($systemConfigGroup['name']) }}
            </p>

            @if (! empty($systemConfigGroup['info']))
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ trans($systemConfigGroup['info']) }}
                </p>
            @endif
        </div>

        @foreach (($systemConfigGroup['fields'] ?? []) as $fieldConfig)
            @php
                $field = new ItemField(
                    item_key: $systemConfigGroup['key'],
                    name: $fieldConfig['name'],
                    title: $fieldConfig['title'],
                    info: $fieldConfig['info'] ?? null,
                    type: $fieldConfig['type'],
                    path: $fieldConfig['path'] ?? null,
                    validation: $fieldConfig['validation'] ?? null,
                    depends: $fieldConfig['depends'] ?? null,
                    default: isset($fieldConfig['default']) ? (string) $fieldConfig['default'] : null,
                    channel_based: $fieldConfig['channel_based'] ?? null,
                    locale_based: $fieldConfig['locale_based'] ?? null,
                    placeholder: $fieldConfig['placeholder'] ?? null,
                    options: $fieldConfig['options'] ?? [],
                    is_visible: $fieldConfig['is_visible'] ?? true,
                );

                $child = $systemConfigGroup;
            @endphp

            @include('admin::configuration.field-type')
        @endforeach
    </div>
@endif
