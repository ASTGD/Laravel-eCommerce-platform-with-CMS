<x-admin::layouts>
    <x-slot:title>
        Payments
    </x-slot>

    <div class="flex items-center justify-between gap-4">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Payments
        </p>
    </div>

    <x-admin::datagrid
        :src="route('admin.sales.payments.index')"
        :isMultiRow="true"
    />
</x-admin::layouts>
