<div class="grid gap-4">
    <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-100">
        Keep all customer-facing delivery choices in one place. These options control what buyers see at checkout, while courier setup now lives in the <strong>Courier Services</strong> section of this shipping settings hub.
    </div>

    @include('commerce-core::admin.configuration.partials.system-group-fields', ['groupKey' => 'sales.carriers.courier'])

    <div class="grid gap-4 md:grid-cols-2">
        @include('commerce-core::admin.configuration.partials.system-group-fields', ['groupKey' => 'sales.carriers.free'])

        @include('commerce-core::admin.configuration.partials.system-group-fields', ['groupKey' => 'sales.carriers.flatrate'])
    </div>
</div>
