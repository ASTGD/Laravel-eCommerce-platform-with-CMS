<x-admin::layouts>
    <x-slot:title>
        Carriers
    </x-slot>

    <div class="flex items-center justify-between gap-4">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Carriers
        </p>

        @if (bouncer()->hasPermission('sales.carriers.create'))
            <a
                href="{{ route('admin.sales.carriers.create') }}"
                class="primary-button"
            >
                Add Carrier
            </a>
        @endif
    </div>

    <x-admin::datagrid
        :src="route('admin.sales.carriers.index')"
        :isMultiRow="true"
    />
</x-admin::layouts>
