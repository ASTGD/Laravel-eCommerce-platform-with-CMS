<div class="fixed top-14 z-[1000] h-full w-[270px] border-r border-slate-200/70 bg-white pt-5 font-inter antialiased shadow-none transition-all duration-300 ease-in-out group-[.sidebar-collapsed]/container:w-[70px] dark:border-gray-800 dark:bg-gray-900 max-lg:hidden">
    <div class="journal-scroll h-[calc(100vh-100px)] overflow-auto px-3 group-[.sidebar-collapsed]/container:overflow-visible group-[.sidebar-collapsed]/container:px-2">
        <nav class="grid w-full gap-1.5">
            <!-- Navigation Menu -->
            @foreach (menu()->getItems('admin') as $menuItem)
                @if ($menuItem->haveChildren())
                    <details
                        class="group/item relative ast-sidebar-menu-item {{ $menuItem->isActive() ? 'active open' : 'inactive' }}"
                        data-sidebar-menu-item
                        @if ($menuItem->isActive()) open @endif
                    >
                        <summary
                            class="ast-sidebar-menu-toggle peer flex h-11 w-full cursor-pointer list-none items-center gap-3 rounded-xl px-3 text-[16px] leading-6 tracking-normal {{ $menuItem->isActive() ? 'bg-blue-50 font-medium text-blue-600 shadow-[inset_3px_0_0_#2563eb] dark:bg-blue-950/40 dark:text-blue-300 dark:shadow-[inset_3px_0_0_#1d4ed8]' : 'font-normal text-slate-700 dark:text-slate-300' }} transition-all duration-200 ease-out hover:translate-x-0.5 hover:bg-slate-100 dark:hover:bg-gray-800 dark:hover:text-blue-300 group-[.sidebar-collapsed]/container:justify-center group-[.sidebar-collapsed]/container:px-0"
                        >
                            <span class="{{ $menuItem->getIcon() }} text-[20px] leading-none transition-all duration-200 {{ $menuItem->isActive() ? 'text-blue-600 dark:text-blue-300' : 'text-slate-700 dark:text-slate-300' }}"></span>
                            
                            <p class="whitespace-nowrap text-[16px] leading-6 tracking-normal {{ $menuItem->isActive() ? 'font-medium text-blue-600 dark:text-blue-300' : 'font-normal text-slate-700 dark:text-slate-300' }} transition-colors duration-200 group-[.sidebar-collapsed]/container:hidden">
                                {{ $menuItem->getName() }}
                            </p>

                            <span
                                class="ast-sidebar-menu-chevron icon-sort-down text-[16px] leading-none transition-all duration-200 ltr:ml-auto rtl:mr-auto {{ $menuItem->isActive() ? 'text-blue-600 dark:text-blue-300' : 'text-slate-700 dark:text-slate-300' }}"
                                aria-hidden="true"
                            ></span>
                        </summary>

                        <div class="ast-sidebar-submenu-rail group-[.sidebar-collapsed]/container:hidden">
                            @foreach ($menuItem->getChildren() as $subMenuItem)
                                <a
                                    href="{{ $subMenuItem->getUrl() }}"
                                    class="ast-sidebar-submenu-rail-link {{ $subMenuItem->isActive() ? 'is-active' : '' }}"
                                >
                                    <span class="ast-sidebar-submenu-rail-label">
                                        {{ $subMenuItem->getName() }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </details>
                @else
                    <div
                        class="group/item relative {{ $menuItem->isActive() ? 'active open' : 'inactive' }}"
                        data-sidebar-menu-item
                    >
                        <a
                            href="{{ $menuItem->getUrl() }}"
                            class="peer flex h-11 cursor-pointer items-center gap-3 rounded-xl px-3 text-[16px] leading-6 tracking-normal {{ $menuItem->isActive() ? 'bg-blue-50 font-medium text-blue-600 shadow-[inset_3px_0_0_#2563eb] dark:bg-blue-950/40 dark:text-blue-300 dark:shadow-[inset_3px_0_0_#1d4ed8]' : 'font-normal text-slate-700 dark:text-slate-300' }} transition-all duration-200 ease-out hover:translate-x-0.5 hover:bg-slate-100 dark:hover:bg-gray-800 dark:hover:text-blue-300 group-[.sidebar-collapsed]/container:justify-center group-[.sidebar-collapsed]/container:px-0"
                        >
                            <span class="{{ $menuItem->getIcon() }} text-[20px] leading-none transition-all duration-200 {{ $menuItem->isActive() ? 'text-blue-600 dark:text-blue-300' : 'text-slate-700 dark:text-slate-300' }}"></span>
                            
                            <p class="whitespace-nowrap text-[16px] leading-6 tracking-normal {{ $menuItem->isActive() ? 'font-medium text-blue-600 dark:text-blue-300' : 'font-normal text-slate-700 dark:text-slate-300' }} transition-colors duration-200 group-[.sidebar-collapsed]/container:hidden">
                                {{ $menuItem->getName() }}
                            </p>
                        </a>
                    </div>
                @endif
            @endforeach
        </nav>
    </div>

    <!-- Collapse menu -->
    <v-sidebar-collapse></v-sidebar-collapse>
</div>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-sidebar-collapse-template"
    >
        <div
            class="fixed bottom-0 w-full max-w-[270px] cursor-pointer border-t border-slate-200/70 bg-white px-3 py-2 transition-all duration-300 ease-in-out hover:bg-slate-100 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800"
            :class="{'max-w-[70px]': isCollapsed}"
            @click="toggle"
        >
            <div class="flex h-10 items-center justify-center rounded-xl text-slate-500 transition-all duration-200 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400">
                <span
                    class="icon-collapse text-[20px] leading-none transition-all duration-300"
                    :class="[isCollapsed ? 'ltr:rotate-[180deg] rtl:rotate-[0]' : 'ltr:rotate-[0] rtl:rotate-[180deg]']"
                ></span>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-sidebar-collapse', {
            template: '#v-sidebar-collapse-template',

            data() {
                return {
                    isCollapsed: {{ request()->cookie('sidebar_collapsed') ?? 0 }},
                }
            },

            methods: {
                toggle() {
                    this.isCollapsed = parseInt(this.isCollapsedCookie()) ? 0 : 1;

                    var expiryDate = new Date();

                    expiryDate.setMonth(expiryDate.getMonth() + 1);

                    document.cookie = 'sidebar_collapsed=' + this.isCollapsed + '; path=/; expires=' + expiryDate.toGMTString();

                    this.$root.$refs.appLayout.classList.toggle('sidebar-collapsed');
                },

                isCollapsedCookie() {
                    const cookies = document.cookie.split(';');

                    for (const cookie of cookies) {
                        const [name, value] = cookie.trim().split('=');

                        if (name === 'sidebar_collapsed') {
                            return value;
                        }
                    }
                    
                    return 0;
                },
            },
        });
    </script>

@endPushOnce
