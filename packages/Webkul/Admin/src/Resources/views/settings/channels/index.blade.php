<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.settings.channels.index.title')
    </x-slot>

    <div class="space-y-8 bg-transparent pb-8" style="background-color: #eff3f8;">
        <section class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                @lang('admin::app.settings.channels.index.title')
            </h1>

            <div class="flex flex-wrap items-center gap-2.5 max-sm:w-full">
                <!-- Create New Channel Button -->
                @if (bouncer()->hasPermission('settings.channels.create'))
                    <a
                        href="{{ route('admin.settings.channels.create') }}"
                        class="primary-button !rounded-xl !px-4 !py-2 !text-sm !shadow-sm !shadow-blue-200/60"
                    >
                        @lang('admin::app.settings.channels.index.create-btn')
                    </a>
                @endif
            </div>
        </section>

        {!! view_render_event('bagisto.settings.channels.list.before') !!}

        <x-admin::datagrid
            class="settings-modern-datagrid"
            :src="route('admin.settings.channels.index')"
        />

        {!! view_render_event('bagisto.settings.channels.list.after') !!}
    </div>

    @include('admin::settings.partials.modern-index-styles')

</x-admin::layouts>
