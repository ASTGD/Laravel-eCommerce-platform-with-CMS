@php
    $admin = auth()->guard('admin')->user();
    $adminName = $admin?->name ?? 'Admin';
    $adminEmail = $admin?->email ?? '';
@endphp

<header class="sticky top-0 z-[10001] flex h-[62px] items-center border-b border-slate-200/80 bg-white/95 font-inter text-slate-700 shadow-[0_1px_0_rgba(15,23,42,0.03)] backdrop-blur dark:border-gray-800 dark:bg-gray-900/95 dark:text-gray-300">
    <div class="flex h-full w-full min-w-0 items-center">
        <div class="flex h-full w-auto shrink-0 items-center gap-2 border-r border-slate-200/70 px-3 dark:border-gray-800 sm:px-4 lg:w-[270px]">
            <!-- Mobile Menu -->
            <button
                type="button"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-600 transition-all hover:bg-slate-100 hover:text-blue-600 dark:text-slate-300 dark:hover:bg-gray-800 dark:hover:text-blue-300 lg:hidden"
                aria-label="Open admin menu"
                @click="$refs.sidebarMenuDrawer.open()"
            >
                <span class="icon-menu text-[21px] leading-none"></span>
            </button>

            <!-- Logo -->
            @include('admin.partials.brand-lockup', [
                'link' => route('admin.dashboard.index'),
                'containerClass' => 'flex min-w-0 flex-shrink-0 items-center gap-2.5',
                'imageClass' => 'h-8 w-auto max-w-[44px] sm:h-9',
                'markClass' => 'flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm',
                'eyebrowClass' => 'text-[11px] font-bold uppercase tracking-[0.16em] text-blue-600 dark:text-blue-400',
                'nameClass' => 'text-sm font-bold text-gray-900 dark:text-white sm:text-base',
            ])
        </div>

        <div class="flex h-full min-w-0 flex-1 items-center justify-between gap-3 px-3 sm:px-4">
            <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                <!-- Desktop Sidebar Toggle -->
                <button
                    type="button"
                    class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-600 transition-all hover:bg-slate-100 hover:text-blue-600 dark:text-slate-300 dark:hover:bg-gray-800 dark:hover:text-blue-300 lg:inline-flex"
                    aria-label="Toggle desktop sidebar"
                    data-admin-topbar-sidebar-toggle
                >
                    <span class="icon-menu text-[21px] leading-none"></span>
                </button>

                <!-- Mega Search Bar Vue Component -->
                <v-mega-search class="hidden min-w-0 md:block">
                    <div class="relative flex w-[260px] max-w-full items-center lg:w-[360px] xl:w-[420px]">
                        <i class="icon-search absolute top-1/2 flex -translate-y-1/2 items-center text-xl text-slate-400 ltr:left-3 rtl:right-3"></i>

                        <input
                            type="text"
                            class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-10 py-2 text-sm leading-6 text-slate-700 transition-all placeholder:text-slate-400 hover:border-slate-300 hover:bg-white focus:border-blue-300 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200 dark:placeholder-gray-500 dark:hover:border-gray-700 dark:focus:border-blue-700 ltr:pr-16 rtl:pl-16"
                            placeholder="@lang('admin::app.components.layouts.header.mega-search.title')"
                        >

                        <span class="pointer-events-none absolute top-1/2 hidden -translate-y-1/2 rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[11px] font-medium text-slate-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 ltr:right-2 rtl:left-2 lg:inline-flex">
                            Ctrl K
                        </span>
                    </div>
                </v-mega-search>
            </div>

            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <!-- Visit Shop Link -->
                <a
                    href="{{ route('shop.home.index') }}"
                    target="_blank"
                    class="hidden h-10 w-10 items-center justify-center rounded-xl text-slate-600 transition-all hover:bg-slate-100 hover:text-blue-600 dark:text-slate-300 dark:hover:bg-gray-800 dark:hover:text-blue-300 sm:inline-flex"
                    title="@lang('admin::app.components.layouts.header.visit-shop')"
                >
                    <span class="icon-store text-[21px] leading-none"></span>
                </a>

                <!-- Dark mode Switcher -->
                <v-dark>
                    <div class="flex">
                        <span
                            class="{{ request()->cookie('dark_mode') ? 'icon-light' : 'icon-dark' }} flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl text-[21px] leading-none text-slate-600 transition-all hover:bg-slate-100 hover:text-blue-600 dark:text-slate-300 dark:hover:bg-gray-800 dark:hover:text-blue-300"
                        ></span>
                    </div>
                </v-dark>

                <!-- Notification Component -->
                <v-notifications {{ $attributes }}>
                    <span class="relative flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 transition-all hover:bg-slate-100 hover:text-blue-600 dark:text-slate-300 dark:hover:bg-gray-800 dark:hover:text-blue-300">
                        <span
                            class="icon-notification text-[21px] leading-none"
                            title="@lang('admin::app.components.layouts.header.notifications')"
                        >
                        </span>
                    </span>
                </v-notifications>

                <!-- Admin profile -->
                <x-admin::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
                    <x-slot:toggle>
                        <button class="flex min-w-0 cursor-pointer items-center gap-2 rounded-2xl border border-transparent p-1 transition-all hover:border-slate-200 hover:bg-slate-50 focus:border-slate-200 focus:bg-slate-50 dark:hover:border-gray-800 dark:hover:bg-gray-800 dark:focus:border-gray-800 dark:focus:bg-gray-800 sm:pr-2.5">
                            @if ($admin?->image)
                                <span class="flex h-9 w-9 shrink-0 overflow-hidden rounded-full">
                                    <img
                                        src="{{ $admin->image_url }}"
                                        class="h-full w-full object-cover"
                                    />
                                </span>
                            @else
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold leading-6 text-white shadow-sm">
                                    {{ substr($adminName, 0, 1) }}
                                </span>
                            @endif

                            <span class="hidden min-w-0 text-left leading-tight xl:block">
                                <span class="block max-w-[150px] truncate text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ $adminName }}
                                </span>

                                @if ($adminEmail)
                                    <span class="block max-w-[150px] truncate text-xs text-slate-500 dark:text-slate-400">
                                        {{ $adminEmail }}
                                    </span>
                                @endif
                            </span>
                        </button>
                    </x-slot>

                    <!-- Admin Dropdown -->
                    <x-slot:content class="!p-0">
                        <div class="flex items-center gap-2 border-b border-slate-200 px-4 py-2 dark:border-gray-800 dark:bg-gray-900 sm:px-5 sm:py-2.5">
                            @include('admin.partials.brand-mark', [
                                'class' => 'flex h-5 w-5 items-center justify-center rounded-lg bg-blue-600 text-white shadow-sm sm:h-6 sm:w-6',
                            ])

                            <div class="grid gap-0.5 leading-none">
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-200 sm:text-sm">
                                    ASTGD ECommerce
                                </p>

                                <p class="text-[11px] text-gray-400 sm:text-xs">
                                    @lang('admin::app.components.layouts.header.app-version', ['version' => 'v' . core()->version()])
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-1 pb-2.5">
                            <a
                                class="cursor-pointer px-4 py-2 text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white sm:px-5 sm:text-base"
                                href="{{ route('admin.account.edit') }}"
                            >
                                @lang('admin::app.components.layouts.header.my-account')
                            </a>

                            <!--Admin logout-->
                            <x-admin::form
                                method="DELETE"
                                action="{{ route('admin.session.destroy') }}"
                                id="adminLogout"
                            >
                            </x-admin::form>

                            <a
                                class="cursor-pointer px-4 py-2 text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white sm:px-5 sm:text-base"
                                href="{{ route('admin.session.destroy') }}"
                                onclick="event.preventDefault(); document.getElementById('adminLogout').submit();"
                            >
                                @lang('admin::app.components.layouts.header.logout')
                            </a>
                        </div>
                    </x-slot>
                </x-admin::dropdown>
            </div>
        </div>
    </div>
</header>

<!-- Menu Sidebar Drawer -->
<x-admin::drawer
    position="left"
    width="270px"
    ref="sidebarMenuDrawer"
>
    <!-- Drawer Header -->
    <x-slot:header>
        @include('admin.partials.brand-lockup', [
            'containerClass' => 'flex items-center gap-2',
            'imageClass' => 'h-8 w-auto sm:h-10',
            'markClass' => 'flex h-8 w-8 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm sm:h-10 sm:w-10',
            'eyebrowClass' => 'text-sm font-bold uppercase tracking-[0.14em] text-blue-600 dark:text-blue-400',
            'nameClass' => 'text-base font-bold text-gray-900 dark:text-white',
        ])
    </x-slot>

    <!-- Drawer Content -->
    <x-slot:content class="p-3 sm:p-4">
        <div class="journal-scroll h-[calc(100vh-100px)] overflow-auto">
            <nav class="grid w-full gap-1.5 sm:gap-2">
                <!-- Navigation Menu -->
                @foreach (menu()->getItems('admin') as $menuItem)
                    <div class="group/item relative">
                        <a
                            href="{{ $menuItem->getUrl() }}"
                            class="flex items-center gap-2 p-1.5 cursor-pointer hover:rounded-lg {{ $menuItem->isActive() == 'active' ? 'bg-blue-600 rounded-lg' : ' hover:bg-gray-100 hover:dark:bg-gray-950' }} peer sm:gap-2.5"
                        >
                            <span class="{{ $menuItem->getIcon() }} text-xl {{ $menuItem->isActive() ? 'text-white' : ''}} sm:text-2xl"></span>
                            
                            <p class="font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap text-sm group-[.sidebar-collapsed]/container:hidden {{ $menuItem->isActive() ? 'text-white' : ''}} sm:text-base">
                                {{ $menuItem->getName() }}
                            </p>
                        </a>

                        @if ($menuItem->haveChildren())
                            <div class="{{ $menuItem->isActive() ? ' !grid bg-gray-100 dark:bg-gray-950' : '' }} hidden min-w-[180px] ltr:pl-8 rtl:pr-8 pb-2 rounded-b-lg z-[100] sm:ltr:pl-10 sm:rtl:pr-10">
                                @foreach ($menuItem->getChildren() as $subMenuItem)
                                    <a
                                        href="{{ $subMenuItem->getUrl() }}"
                                        class="text-xs text-{{ $subMenuItem->isActive() ? 'blue':'gray' }}-600 dark:text-{{ $subMenuItem->isActive() ? 'blue':'gray' }}-300 whitespace-nowrap py-1 group-[.sidebar-collapsed]/container:px-4 group-[.sidebar-collapsed]/container:py-2 group-[.inactive]/item:px-4 group-[.inactive]/item:py-2 hover:text-blue-600 dark:hover:bg-gray-800 sm:text-sm sm:group-[.sidebar-collapsed]/container:px-5 sm:group-[.sidebar-collapsed]/container:py-2.5 sm:group-[.inactive]/item:px-5 sm:group-[.inactive]/item:py-2.5"
                                    >
                                        {{ $subMenuItem->getName() }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </nav>
        </div>
    </x-slot>
</x-admin::drawer>

@pushOnce('scripts')
    <script type="module">
        const initAdminTopbarSidebarToggle = () => {
            const toggle = document.querySelector('[data-admin-topbar-sidebar-toggle]');
            const layout = document.querySelector('[data-admin-layout]');

            if (! toggle || ! layout || toggle.__adminTopbarSidebarToggleReady) {
                return;
            }

            toggle.__adminTopbarSidebarToggleReady = true;

            const isCollapsed = () => layout.classList.contains('sidebar-collapsed');

            const setCollapsed = (collapsed) => {
                const expiryDate = new Date();

                expiryDate.setMonth(expiryDate.getMonth() + 1);

                document.cookie = 'sidebar_collapsed=' + Number(collapsed) + '; path=/; expires=' + expiryDate.toGMTString();

                layout.classList.toggle('sidebar-collapsed', collapsed);
                layout.classList.toggle('sidebar-not-collapsed', ! collapsed);
                toggle.setAttribute('aria-pressed', collapsed ? 'true' : 'false');

                window.dispatchEvent(new CustomEvent('admin-sidebar:collapse-change', {
                    detail: {
                        collapsed,
                        source: 'topbar',
                    },
                }));
            };

            toggle.setAttribute('aria-pressed', isCollapsed() ? 'true' : 'false');
            toggle.addEventListener('click', () => setCollapsed(! isCollapsed()));
            window.addEventListener('admin-sidebar:collapse-change', (event) => {
                if (! event.detail || typeof event.detail.collapsed === 'undefined') {
                    return;
                }

                toggle.setAttribute('aria-pressed', event.detail.collapsed ? 'true' : 'false');
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAdminTopbarSidebarToggle, { once: true });
        } else {
            initAdminTopbarSidebarToggle();
        }

        window.addEventListener('load', () => window.setTimeout(initAdminTopbarSidebarToggle, 0), { once: true });
    </script>

    <script
        type="text/x-template"
        id="v-mega-search-template"
    >
        <div class="relative flex w-[260px] max-w-full items-center lg:w-[360px] xl:w-[420px]">
            <i class="icon-search absolute top-1/2 flex -translate-y-1/2 items-center text-xl text-slate-400 ltr:left-3 rtl:right-3"></i>

            <input 
                type="text"
                class="peer block h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-10 py-2 text-sm leading-6 text-slate-700 transition-all placeholder:text-slate-400 hover:border-slate-300 hover:bg-white focus:border-blue-300 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200 dark:placeholder-gray-500 dark:hover:border-gray-700 dark:focus:border-blue-700 ltr:pr-16 rtl:pl-16"
                :class="{'border-blue-300 bg-white dark:border-blue-700': isDropdownOpen}"
                ref="searchInput"
                placeholder="@lang('admin::app.components.layouts.header.mega-search.title')"
                v-model.lazy="searchTerm"
                @click="searchTerm.length >= 2 ? isDropdownOpen = true : {}"
                v-debounce="500"
            >

            <span class="pointer-events-none absolute top-1/2 hidden -translate-y-1/2 rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[11px] font-medium text-slate-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 ltr:right-2 rtl:left-2 lg:inline-flex">
                Ctrl K
            </span>

            <div
                class="absolute top-12 z-10 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-[0_18px_45px_rgba(15,23,42,0.16)] dark:border-gray-800 dark:bg-gray-900"
                v-if="isDropdownOpen"
            >
                <!-- Search Tabs -->
                <div class="flex border-b text-xs text-gray-600 dark:border-gray-800 dark:text-gray-300 sm:text-sm">
                    <div
                        class="cursor-pointer p-2 hover:bg-gray-100 dark:hover:bg-gray-800 sm:p-4"
                        :class="{ 'border-b-2 border-blue-600': activeTab == tab.key }"
                        v-for="tab in tabs"
                        @click="activeTab = tab.key; search();"
                    >
                        @{{ tab.title }}
                    </div>
                </div>

                <!-- Searched Results -->
                <template v-if="activeTab == 'products'">
                    <template v-if="isLoading">
                        <x-admin::shimmer.header.mega-search.products />
                    </template>

                    <template v-else>
                        <div class="grid max-h-[300px] overflow-y-auto sm:max-h-[400px]">
                            <a
                                :href="'{{ route('admin.catalog.products.edit', ':id') }}'.replace(':id', product.id)"
                                class="flex cursor-pointer justify-between gap-2 border-b border-slate-300 p-3 last:border-b-0 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 sm:gap-2.5 sm:p-4"
                                v-for="product in searchedResults.products.data"
                            >
                                <!-- Left Information -->
                                <div class="flex gap-2 sm:gap-2.5">
                                    <!-- Image -->
                                    <div
                                        class="relative h-10 max-h-10 w-full max-w-10 overflow-hidden rounded sm:h-[60px] sm:max-h-[60px] sm:max-w-[60px]"
                                        :class="{'overflow-hidden rounded border border-dashed border-gray-300 dark:border-gray-800 dark:mix-blend-exclusion dark:invert': ! product.images.length}"
                                    >
                                        <template v-if="! product.images.length">
                                            <img src="{{ bagisto_asset('images/product-placeholders/front.svg') }}" class="h-full w-full object-cover">
                                        
                                            <p class="absolute bottom-0.5 w-full text-center text-[4px] font-semibold text-gray-400 sm:bottom-1.5 sm:text-[6px]">
                                                @lang('admin::app.catalog.products.edit.types.grouped.image-placeholder')
                                            </p>
                                        </template>

                                        <template v-else>
                                            <img :src="product.images[0].url" class="h-full w-full object-cover">
                                        </template>
                                    </div>

                                    <!-- Details -->
                                    <div class="grid place-content-start gap-1 sm:gap-1.5">
                                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-300 sm:text-base">
                                            @{{ product.name }}
                                        </p>

                                        <p class="text-xs text-gray-500 sm:text-sm">
                                            @{{ "@lang('admin::app.components.layouts.header.mega-search.sku')".replace(':sku', product.sku) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Right Information -->
                                <div class="grid place-content-center gap-1 text-right">
                                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-300 sm:text-base">
                                        @{{ product.formatted_price }}
                                    </p>
                                </div>
                            </a>
                        </div>

                        <div class="flex border-t p-2 dark:border-gray-800 sm:p-3">
                            <a
                                :href="'{{ route('admin.catalog.products.index') }}?search=:query'.replace(':query', searchTerm)"
                                class="cursor-pointer text-xs font-semibold text-blue-600 transition-all hover:underline"
                                v-if="searchedResults.products.data.length"
                            >
                                @{{ "@lang('admin::app.components.layouts.header.mega-search.explore-all-matching-products')".replace(':query', searchTerm).replace(':count', searchedResults.products.meta.total) }}
                            </a>

                            <a
                                href="{{ route('admin.catalog.products.index') }}"
                                class="cursor-pointer text-xs font-semibold text-blue-600 transition-all hover:underline"
                                v-else
                            >
                                @lang('admin::app.components.layouts.header.mega-search.explore-all-products')
                            </a>
                        </div>
                    </template>
                </template>

                <template v-if="activeTab == 'orders'">
                    <template v-if="isLoading">
                        <x-admin::shimmer.header.mega-search.orders />
                    </template>

                    <template v-else>
                        <div class="grid max-h-[300px] overflow-y-auto sm:max-h-[400px]">
                            <a
                                :href="'{{ route('admin.sales.orders.view', ':id') }}'.replace(':id', order.id)"
                                class="grid cursor-pointer place-content-start gap-1 border-b border-slate-300 p-3 last:border-b-0 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 sm:gap-1.5 sm:p-4"
                                v-for="order in searchedResults.orders.data"
                            >
                                <p class="text-sm font-semibold text-gray-600 dark:text-gray-300 sm:text-base">
                                    #@{{ order.increment_id }}
                                </p>

                                <p class="text-xs text-gray-500 dark:text-gray-300 sm:text-sm">
                                    @{{ order.formatted_created_at + ', ' + order.status_label + ', ' + order.customer_full_name }}
                                </p>
                            </a>
                        </div>

                        <div class="flex border-t p-2 dark:border-gray-800 sm:p-3">
                            <a
                                :href="'{{ route('admin.sales.orders.index') }}?search=:query'.replace(':query', searchTerm)"
                                class="cursor-pointer text-xs font-semibold text-blue-600 transition-all hover:underline"
                                v-if="searchedResults.orders.data.length"
                            >
                                @{{ "@lang('admin::app.components.layouts.header.mega-search.explore-all-matching-orders')".replace(':query', searchTerm).replace(':count', searchedResults.orders.total) }}
                            </a>

                            <a
                                href="{{ route('admin.sales.orders.index') }}"
                                class="cursor-pointer text-xs font-semibold text-blue-600 transition-all hover:underline"
                                v-else
                            >
                                @lang('admin::app.components.layouts.header.mega-search.explore-all-orders')
                            </a>
                        </div>
                    </template>
                </template>

                <template v-if="activeTab == 'categories'">
                    <template v-if="isLoading">
                        <x-admin::shimmer.header.mega-search.categories />
                    </template>

                    <template v-else>
                        <div class="grid max-h-[300px] overflow-y-auto sm:max-h-[400px]">
                            <a
                                :href="'{{ route('admin.catalog.categories.edit', ':id') }}'.replace(':id', category.id)"
                                class="cursor-pointer border-b p-3 text-xs font-semibold text-gray-600 last:border-b-0 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800 sm:p-4 sm:text-sm"
                                v-for="category in searchedResults.categories.data"
                            >
                                @{{ category.name }}
                            </a>
                        </div>

                        <div class="flex border-t p-2 dark:border-gray-800 sm:p-3">
                            <a
                                :href="'{{ route('admin.catalog.categories.index') }}?search=:query'.replace(':query', searchTerm)"
                                class="cursor-pointer text-xs font-semibold text-blue-600 transition-all hover:underline"
                                v-if="searchedResults.categories.data.length"
                            >
                                @{{ "@lang('admin::app.components.layouts.header.mega-search.explore-all-matching-categories')".replace(':query', searchTerm).replace(':count', searchedResults.categories.total) }}
                            </a>

                            <a
                                href="{{ route('admin.catalog.categories.index') }}"
                                class="cursor-pointer text-xs font-semibold text-blue-600 transition-all hover:underline"
                                v-else
                            >
                                @lang('admin::app.components.layouts.header.mega-search.explore-all-categories')
                            </a>
                        </div>
                    </template>
                </template>

                <template v-if="activeTab == 'customers'">
                    <template v-if="isLoading">
                        <x-admin::shimmer.header.mega-search.customers />
                    </template>

                    <template v-else>
                        <div class="grid max-h-[300px] overflow-y-auto sm:max-h-[400px]">
                            <a
                                :href="'{{ route('admin.customers.customers.view', ':id') }}'.replace(':id', customer.id)"
                                class="grid cursor-pointer place-content-start gap-1 border-b border-slate-300 p-3 last:border-b-0 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 sm:gap-1.5 sm:p-4"
                                v-for="customer in searchedResults.customers.data"
                            >
                                <p class="text-sm font-semibold text-gray-600 dark:text-gray-300 sm:text-base">
                                    @{{ customer.first_name + ' ' + customer.last_name }}
                                </p>

                                <p class="text-xs text-gray-500 sm:text-sm">
                                    @{{ customer.email }}
                                </p>
                            </a>
                        </div>

                        <div class="flex border-t p-2 dark:border-gray-800 sm:p-3">
                            <a
                                :href="'{{ route('admin.customers.customers.index') }}?search=:query'.replace(':query', searchTerm)"
                                class="cursor-pointer text-xs font-semibold text-blue-600 transition-all hover:underline"
                                v-if="searchedResults.customers.data.length"
                            >
                                @{{ "@lang('admin::app.components.layouts.header.mega-search.explore-all-matching-customers')".replace(':query', searchTerm).replace(':count', searchedResults.customers.total) }}
                            </a>

                            <a
                                href="{{ route('admin.customers.customers.index') }}"
                                class="cursor-pointer text-xs font-semibold text-blue-600 transition-all hover:underline"
                                v-else
                            >
                                @lang('admin::app.components.layouts.header.mega-search.explore-all-customers')
                            </a>
                        </div>
                    </template>
                </template>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-mega-search', {
            template: '#v-mega-search-template',

            data() {
                return {
                    activeTab: 'products',

                    isDropdownOpen: false,

                    tabs: {
                        products: {
                            key: 'products',
                            title: "@lang('admin::app.components.layouts.header.mega-search.products')",
                            is_active: true,
                            endpoint: "{{ route('admin.catalog.products.search') }}"
                        },
                        
                        orders: {
                            key: 'orders',
                            title: "@lang('admin::app.components.layouts.header.mega-search.orders')",
                            endpoint: "{{ route('admin.sales.orders.search') }}"
                        },
                        
                        categories: {
                            key: 'categories',
                            title: "@lang('admin::app.components.layouts.header.mega-search.categories')",
                            endpoint: "{{ route('admin.catalog.categories.search') }}"
                        },
                        
                        customers: {
                            key: 'customers',
                            title: "@lang('admin::app.components.layouts.header.mega-search.customers')",
                            endpoint: "{{ route('admin.customers.customers.search') }}"
                        }
                    },

                    isLoading: false,

                    searchTerm: '',

                    searchedResults: {
                        products: [],
                        orders: [],
                        categories: [],
                        customers: []
                    },
                }
            },

            watch: {
                searchTerm: function(newVal, oldVal) {
                    this.search()
                }
            },

            created() {
                window.addEventListener('click', this.handleFocusOut);
                window.addEventListener('keydown', this.handleSearchShortcut);
            },

            beforeDestroy() {
                window.removeEventListener('click', this.handleFocusOut);
                window.removeEventListener('keydown', this.handleSearchShortcut);
            },

            beforeUnmount() {
                window.removeEventListener('click', this.handleFocusOut);
                window.removeEventListener('keydown', this.handleSearchShortcut);
            },

            methods: {
                handleSearchShortcut(event) {
                    if (! (event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 'k') {
                        return;
                    }

                    event.preventDefault();
                    this.$refs.searchInput?.focus();

                    if (this.searchTerm.length >= 2) {
                        this.isDropdownOpen = true;
                    }
                },

                search() {
                    if (this.searchTerm.length <= 1) {
                        this.searchedResults[this.activeTab] = [];

                        this.isDropdownOpen = false;

                        return;
                    }

                    this.isDropdownOpen = true;

                    let self = this;

                    this.isLoading = true;
                    
                    this.$axios.get(this.tabs[this.activeTab].endpoint, {
                            params: {query: this.searchTerm}
                        })
                        .then(function(response) {
                            self.searchedResults[self.activeTab] = response.data;

                            self.isLoading = false;
                        })
                        .catch(function (error) {
                        })
                },

                handleFocusOut(e) {
                    if (! this.$el.contains(e.target)) {
                        this.isDropdownOpen = false;
                    }
                },
            }
        });
    </script>

    <script
        type="text/x-template"
        id="v-notifications-template"
    >
        <x-admin::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
            <!-- Notification Toggle -->
            <x-slot:toggle>
                <span class="relative flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 transition-all hover:bg-slate-100 hover:text-blue-600 dark:text-slate-300 dark:hover:bg-gray-800 dark:hover:text-blue-300">
                    <span
                        class="icon-notification cursor-pointer text-[21px] leading-none"
                        title="@lang('admin::app.components.layouts.header.notifications')"
                    >
                    </span>
                
                    <span
                        class="absolute -top-1.5 flex h-5 min-w-5 cursor-pointer items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-semibold leading-[9px] text-white ring-2 ring-white dark:ring-gray-900 ltr:right-0 rtl:left-0"
                        v-if="totalUnRead"
                    >
                        @{{ totalUnRead }}
                    </span>
                </span>
            </x-slot>

            <!-- Notification Content -->
            <x-slot:content class="min-w-[250px] max-w-[250px] !p-0">
                <!-- Header -->
                <div class="border-b p-3 text-base font-semibold text-gray-600 dark:border-gray-800 dark:text-gray-300">
                    @lang('admin::app.notifications.title', ['read' => 0])
                </div>

                <!-- Content -->
                <div class="grid">
                    <a
                        class="flex items-start gap-1.5 border-b p-3 last:border-b-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                        v-for="notification in notifications"
                        :href="'{{ route('admin.notification.viewed_notification', ':orderId') }}'.replace(':orderId', notification.order_id)"
                    >
                        <!-- Notification Icon -->
                        <span
                            v-if="notification.order.status in notificationStatusIcon"
                            class="h-fit"
                            :class="notificationStatusIcon[notification.order.status]"
                        >
                        </span>

                        <div class="grid">
                            <!-- Order Id & Status -->
                            <p class="text-gray-800 dark:text-white">
                                #@{{ notification.order.id }}
                                @{{ orderTypeMessages[notification.order.status] }}
                            </p>

                            <!-- Created Date In humand Readable Format -->
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                @{{ notification.order.datetime }}
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Footer -->
                <div class="flex h-[47px] justify-between gap-1.5 border-t px-6 py-4 dark:border-gray-800">
                    <a
                        href="{{ route('admin.notification.index') }}"
                        class="cursor-pointer text-xs font-semibold text-blue-600 transition-all hover:underline"
                    >
                        @lang('admin::app.notifications.view-all')
                    </a>

                    <a
                        class="cursor-pointer text-xs font-semibold text-blue-600 transition-all hover:underline"
                        v-if="notifications?.length"
                        @click="readAll()"
                    >
                        @lang('admin::app.notifications.read-all')
                    </a>
                </div>
            </x-slot>
        </x-admin::dropdown>
    </script>

    <script type="module">
        app.component('v-notifications', {
            template: '#v-notifications-template',

                props: [
                    'getReadAllUrl',
                    'readAllTitle',
                ],

                data() {
                    return {
                        notifications: [],

                        ordertype: {
                            pending: {
                                icon: 'icon-information',
                                message: "@lang('admin::app.notifications.order-status-messages.pending-payment')"
                            },

                            processing: {
                                icon: 'icon-processing',
                                message: "@lang('admin::app.notifications.order-status-messages.processing')",
                            },

                            shipped: {
                                icon: 'icon-ship',
                                message: "@lang('admin::app.notifications.order-status-messages.shipped')",
                            },

                            canceled: {
                                icon: 'icon-cancel-1',
                                message: "@lang('admin::app.notifications.order-status-messages.canceled')"
                            },

                            completed: {
                                icon: 'icon-done',
                                message: "@lang('admin::app.notifications.order-status-messages.completed')"
                            },

                            closed: {
                                icon: 'icon-cancel-1',
                                message: "@lang('admin::app.notifications.order-status-messages.closed')"
                            },

                            pending_payment: {
                                icon: "icon-information",
                                message: "@lang('admin::app.notifications.order-status-messages.pending-payment')"
                            },
                        },

                        totalUnRead: 0,

                        orderTypeMessages: {
                            {{ \Webkul\Sales\Models\Order::STATUS_PENDING }}: "@lang('admin::app.notifications.order-status-messages.pending')",
                            {{ \Webkul\Sales\Models\Order::STATUS_CANCELED }}: "@lang('admin::app.notifications.order-status-messages.canceled')",
                            {{ \Webkul\Sales\Models\Order::STATUS_CLOSED }}: "@lang('admin::app.notifications.order-status-messages.closed')",
                            {{ \Webkul\Sales\Models\Order::STATUS_COMPLETED }}: "@lang('admin::app.notifications.order-status-messages.completed')",
                            {{ \Webkul\Sales\Models\Order::STATUS_PROCESSING }}: "@lang('admin::app.notifications.order-status-messages.processing')",
                            {{ \Webkul\Sales\Models\Order::STATUS_SHIPPED }}: "@lang('admin::app.notifications.order-status-messages.shipped')",
                            {{ \Webkul\Sales\Models\Order::STATUS_PENDING_PAYMENT }}: "@lang('admin::app.notifications.order-status-messages.pending-payment')",
                        }
                    }
                },

                computed: {
                    notificationStatusIcon() {
                        return {
                            pending: 'icon-information rounded-full bg-amber-100 text-2xl text-amber-600 dark:!text-amber-600',
                            closed: 'icon-repeat rounded-full bg-red-100 text-2xl text-red-600 dark:!text-red-600',
                            completed: 'icon-done rounded-full bg-blue-100 text-2xl text-blue-600 dark:!text-blue-600',
                            canceled: 'icon-cancel-1 rounded-full bg-red-100 text-2xl text-red-600 dark:!text-red-600',
                            processing: 'icon-sort-right rounded-full bg-green-100 text-2xl text-green-600 dark:!text-green-600',
                            shipped: 'icon-ship rounded-full bg-sky-100 text-2xl text-sky-600 dark:!text-sky-600',
                        };
                    },
                },

                mounted() {
                    this.getNotification();
                },

                methods: {
                    getNotification() {
                        this.$axios.get('{{ route('admin.notification.get_notification') }}', {
                                params: {
                                    limit: 5,
                                    read: 0
                                }
                            })
                            .then((response) => {
                                this.notifications = response.data.search_results.data;

                                this.totalUnRead =   response.data.total_unread;
                            })
                            .catch(error => console.log(error))
                    },

                    readAll() {
                        this.$axios.post('{{ route('admin.notification.read_all') }}')
                            .then((response) => {
                                this.notifications = response.data.search_results.data;

                                this.totalUnRead = response.data.total_unread;

                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.success_message });
                        })
                        .catch((error) => {});
                },
            },
        });
    </script>

    <script
        type="text/x-template"
        id="v-dark-template"
    >
        <div class="flex">
            <span
                class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl text-[21px] leading-none text-slate-600 transition-all hover:bg-slate-100 hover:text-blue-600 dark:text-slate-300 dark:hover:bg-gray-800 dark:hover:text-blue-300"
                :class="[isDarkMode ? 'icon-light' : 'icon-dark']"
                @click="toggle"
            ></span>
        </div>
    </script>

    <script type="module">
        app.component('v-dark', {
            template: '#v-dark-template',

            data() {
                return {
                    isDarkMode: {{ request()->cookie('dark_mode') ?? 0 }},
                };
            },

            methods: {
                toggle() {
                    this.isDarkMode = parseInt(this.isDarkModeCookie()) ? 0 : 1;

                    var expiryDate = new Date();

                    expiryDate.setMonth(expiryDate.getMonth() + 1);

                    document.cookie = 'dark_mode=' + this.isDarkMode + '; path=/; expires=' + expiryDate.toGMTString();

                    document.documentElement.classList.toggle('dark', this.isDarkMode === 1);

                    if (this.isDarkMode) {
                        this.$emitter.emit('change-theme', 'dark');
                    } else {
                        this.$emitter.emit('change-theme', 'light');
                    }
                },

                isDarkModeCookie() {
                    const cookies = document.cookie.split(';');

                    for (const cookie of cookies) {
                        const [name, value] = cookie.trim().split('=');

                        if (name === 'dark_mode') {
                            return value;
                        }
                    }

                    return 0;
                },
            },
        });
    </script>
@endpushOnce
