<x-admin::layouts>
    <x-slot:title>
        Pickup Points
    </x-slot>

    <div class="flex items-center justify-between gap-4">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Pickup Points
        </p>

        @if (bouncer()->hasPermission('sales.pickup_points.create'))
            <a
                href="{{ route('admin.sales.pickup-points.create') }}"
                class="primary-button"
            >
                Add Pickup Point
            </a>
        @endif
    </div>

    <x-admin::datagrid
        :src="route('admin.sales.pickup-points.index')"
        :isMultiRow="true"
    />
</x-admin::layouts>
