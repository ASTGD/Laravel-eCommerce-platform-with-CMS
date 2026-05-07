<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.taxes.rates.index.title')
    </x-slot>

    <div class="space-y-8 bg-transparent pb-8" style="background-color: #f5f5f5;">
        <section class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                @lang('admin::app.settings.taxes.rates.index.title')
            </h1>

            <div class="flex flex-wrap items-center gap-2.5 max-sm:w-full">
                <!-- Tax Rate Export -->
                <x-admin::datagrid.export src="{{ route('admin.settings.taxes.rates.index') }}" />

                <!-- Create New Tax Rate Button -->
                @if (bouncer()->hasPermission('settings.taxes.tax_rates.create'))
                    <a
                        href="{{ route('admin.settings.taxes.rates.create') }}"
                        class="primary-button !rounded-xl !px-4 !py-2 !text-sm !shadow-sm !shadow-blue-200/60"
                    >
                        @lang('admin::app.settings.taxes.rates.index.button-title')
                    </a>
                @endif
            </div>
        </section>

        <x-admin::datagrid
            class="settings-modern-datagrid"
            :src="route('admin.settings.taxes.rates.index')"
            ref="datagrid"
        />
    </div>

    @include('admin::settings.partials.modern-index-styles')
</x-admin::layouts>
