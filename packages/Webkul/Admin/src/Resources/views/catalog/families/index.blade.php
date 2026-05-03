<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.families.index.title')
    </x-slot>

    <div class="space-y-8 bg-transparent pb-8" style="background-color: #eff3f8;">
        <section class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                @lang('admin::app.catalog.families.index.title')
            </h1>

            <div class="flex flex-wrap items-center gap-2.5 max-sm:w-full">
                @if (bouncer()->hasPermission('catalog.families.create'))
                    <a href="{{ route('admin.catalog.families.create') }}">
                        <div class="primary-button !rounded-xl !px-4 !py-2 !text-sm !shadow-sm !shadow-blue-200/60">
                            @lang('admin::app.catalog.families.index.add')
                        </div>
                    </a>
                @endif
            </div>
        </section>

        {!! view_render_event('bagisto.admin.catalog.families.list.before') !!}

        <x-admin::datagrid
            class="catalog-families-modern-datagrid"
            :src="route('admin.catalog.families.index')"
        />

        {!! view_render_event('bagisto.admin.catalog.families.list.after') !!}
    </div>

    @pushOnce('styles')
        <style>
            .catalog-families-modern-datagrid > .mt-7 {
                margin-top: 0;
            }

            .catalog-families-modern-datagrid > .mt-4 {
                margin-top: 1rem;
            }

            .catalog-families-modern-datagrid .table-responsive.box-shadow {
                border: 0;
                border-radius: 1.25rem;
                box-shadow: 0 1px 2px 0 rgb(148 163 184 / 0.18);
                background: #ffffff;
                overflow: hidden;
            }

            .dark .catalog-families-modern-datagrid .table-responsive.box-shadow {
                background: rgb(15 23 42);
            }

            .catalog-families-modern-datagrid .table-responsive > .row {
                border-color: rgb(226 232 240);
            }

            .catalog-families-modern-datagrid .table-responsive > .row:first-child {
                background: rgb(248 250 252 / 0.8);
                color: rgb(100 116 139);
                font-size: 0.75rem;
                letter-spacing: 0.025em;
                text-transform: uppercase;
            }

            .dark .catalog-families-modern-datagrid .table-responsive > .row {
                border-color: rgb(30 41 59);
            }

            .dark .catalog-families-modern-datagrid .table-responsive > .row:first-child {
                background: rgb(2 6 23 / 0.4);
                color: rgb(148 163 184);
            }
        </style>
    @endPushOnce

</x-admin::layouts>
