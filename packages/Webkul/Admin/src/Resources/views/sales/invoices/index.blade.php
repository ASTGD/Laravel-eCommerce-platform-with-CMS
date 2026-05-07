<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.sales.invoices.index.title')
    </x-slot>

    <div class="space-y-8 bg-transparent pb-8" style="background-color: #f5f5f5;">
        <section class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                @lang('admin::app.sales.invoices.index.title')
            </h1>

            <div class="flex flex-wrap items-center gap-2.5 max-sm:w-full">
                <!-- Export Modal -->
                <x-admin::datagrid.export :src="route('admin.sales.invoices.index')" />
            </div>
        </section>

        <x-admin::datagrid
            class="sales-invoices-modern-datagrid"
            :src="route('admin.sales.invoices.index')"
        />
    </div>

    @pushOnce('styles')
        <style>
            .sales-invoices-modern-datagrid > .mt-7 {
                margin-top: 0;
            }

            .sales-invoices-modern-datagrid > .mt-4 {
                margin-top: 1rem;
            }

            .sales-invoices-modern-datagrid .table-responsive.box-shadow {
                border: 0;
                border-radius: 1.25rem;
                box-shadow: 0 1px 2px 0 rgb(148 163 184 / 0.18);
                background: #ffffff;
                overflow: hidden;
            }

            .dark .sales-invoices-modern-datagrid .table-responsive.box-shadow {
                background: rgb(15 23 42);
            }

            .sales-invoices-modern-datagrid .table-responsive > .row {
                border-color: rgb(226 232 240);
            }

            .sales-invoices-modern-datagrid .table-responsive > .row:first-child {
                background: rgb(248 250 252 / 0.8);
                color: rgb(100 116 139);
                font-size: 0.75rem;
                letter-spacing: 0.025em;
                text-transform: uppercase;
            }

            .dark .sales-invoices-modern-datagrid .table-responsive > .row {
                border-color: rgb(30 41 59);
            }

            .dark .sales-invoices-modern-datagrid .table-responsive > .row:first-child {
                background: rgb(2 6 23 / 0.4);
                color: rgb(148 163 184);
            }
        </style>
    @endPushOnce

</x-admin::layouts>
