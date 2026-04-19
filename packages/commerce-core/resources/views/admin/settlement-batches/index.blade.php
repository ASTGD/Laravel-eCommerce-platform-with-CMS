<x-admin::layouts>
    <x-slot:title>
        Settlement Batches
    </x-slot>

    <div class="flex items-center justify-between gap-4">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Settlement Batches
        </p>

        @if (bouncer()->hasPermission('sales.settlement_batches.create'))
            <a
                href="{{ route('admin.sales.settlement-batches.create') }}"
                class="primary-button"
            >
                Create Batch
            </a>
        @endif
    </div>

    <x-admin::datagrid
        :src="route('admin.sales.settlement-batches.index')"
        :isMultiRow="true"
    />
</x-admin::layouts>
