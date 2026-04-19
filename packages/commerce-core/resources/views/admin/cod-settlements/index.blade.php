<x-admin::layouts>
    <x-slot:title>
        COD Settlements
    </x-slot>

    <div class="flex items-center justify-between gap-4">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            COD Settlements
        </p>
    </div>

    <x-admin::datagrid
        :src="route('admin.sales.cod-settlements.index')"
        :isMultiRow="true"
    />
</x-admin::layouts>
