<x-admin::layouts>
    <x-slot:title>
        Shipment Operations
    </x-slot>

    <div class="flex items-center justify-between gap-4">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Shipment Operations
        </p>
    </div>

    <x-admin::datagrid
        :src="route('admin.sales.shipment-operations.index')"
        :isMultiRow="true"
    />
</x-admin::layouts>
