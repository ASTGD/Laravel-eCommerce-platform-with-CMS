@php
    $menuItems = menu()->getItems('admin');

    $menuButtonBase = 'ast-sidebar-menu-button peer flex h-11 w-full cursor-pointer items-center gap-3 rounded-xl px-3 text-[15px] leading-6 tracking-normal transition-all duration-200 ease-out hover:bg-slate-100 hover:text-blue-600 dark:hover:bg-gray-800 dark:hover:text-blue-300 group-[.sidebar-collapsed]/container:mx-auto group-[.sidebar-collapsed]/container:w-12 group-[.sidebar-collapsed]/container:justify-center group-[.sidebar-collapsed]/container:px-0';
    $menuButtonActive = 'bg-blue-50 font-semibold text-blue-600 shadow-[inset_3px_0_0_#2563eb] dark:bg-blue-950/40 dark:text-blue-300 dark:shadow-[inset_3px_0_0_#1d4ed8]';
    $menuButtonInactive = 'font-medium text-slate-700 dark:text-slate-300';
    $menuIconBase = 'ast-sidebar-menu-icon shrink-0 text-[20px] leading-none transition-colors duration-200';
    $menuLabelBase = 'min-w-0 flex-1 truncate whitespace-nowrap text-[15px] leading-6 tracking-normal transition-colors duration-200 group-[.sidebar-collapsed]/container:hidden';
@endphp

<aside
    class="ast-admin-sidebar fixed bottom-0 top-[62px] z-[1000] flex w-[270px] flex-col overflow-visible border-r border-slate-200/70 bg-white font-inter antialiased shadow-none transition-all duration-300 ease-in-out group-[.sidebar-collapsed]/container:w-[74px] dark:border-gray-800 dark:bg-gray-900 max-lg:hidden"
    data-admin-sidebar
>
    <div class="ast-admin-sidebar-menu journal-scroll min-h-0 flex-1 overflow-y-auto overflow-x-hidden px-3 py-4 group-[.sidebar-collapsed]/container:px-2">
        <nav
            class="grid w-full gap-1.5"
            aria-label="Admin navigation"
        >
            @foreach ($menuItems as $menuItem)
                @php($isActive = $menuItem->isActive())

                @if ($menuItem->haveChildren())
                    <details
                        class="group/item relative ast-sidebar-menu-item {{ $isActive ? 'active open' : 'inactive' }}"
                        data-sidebar-menu-item
                        @if ($isActive) open @endif
                    >
                        <summary
                            class="ast-sidebar-menu-toggle {{ $menuButtonBase }} {{ $isActive ? $menuButtonActive : $menuButtonInactive }}"
                            data-sidebar-flyout-trigger
                            aria-label="{{ $menuItem->getName() }}"
                        >
                            <span class="{{ $menuItem->getIcon() }} {{ $menuIconBase }} {{ $isActive ? 'text-blue-600 dark:text-blue-300' : 'text-slate-600 dark:text-slate-300' }}"></span>

                            <span class="{{ $menuLabelBase }} {{ $isActive ? 'text-blue-600 dark:text-blue-300' : 'text-slate-700 dark:text-slate-300' }}">
                                {{ $menuItem->getName() }}
                            </span>

                            <span
                                class="ast-sidebar-menu-chevron icon-sort-down shrink-0 text-[14px] leading-none transition-all duration-200 ltr:ml-auto rtl:mr-auto {{ $isActive ? 'text-blue-600 dark:text-blue-300' : 'text-slate-500 dark:text-slate-400' }}"
                                aria-hidden="true"
                            ></span>
                        </summary>

                        <div class="ast-sidebar-submenu-rail group-[.sidebar-collapsed]/container:hidden">
                            @foreach ($menuItem->getChildren() as $subMenuItem)
                                <a
                                    href="{{ $subMenuItem->getUrl() }}"
                                    class="ast-sidebar-submenu-rail-link {{ $subMenuItem->isActive() ? 'is-active' : '' }}"
                                    @if ($subMenuItem->isActive()) aria-current="page" @endif
                                >
                                    <span class="ast-sidebar-submenu-rail-label">
                                        {{ $subMenuItem->getName() }}
                                    </span>
                                </a>
                            @endforeach
                        </div>

                        <div
                            class="ast-sidebar-flyout journal-scroll"
                            data-sidebar-flyout
                        >
                            <div class="ast-sidebar-flyout-heading">
                                <span class="{{ $menuItem->getIcon() }} ast-sidebar-flyout-heading-icon"></span>

                                <span class="truncate">
                                    {{ $menuItem->getName() }}
                                </span>
                            </div>

                            <div class="ast-sidebar-flyout-links">
                                @foreach ($menuItem->getChildren() as $subMenuItem)
                                    <a
                                        href="{{ $subMenuItem->getUrl() }}"
                                        class="ast-sidebar-flyout-link {{ $subMenuItem->isActive() ? 'is-active' : '' }}"
                                        @if ($subMenuItem->isActive()) aria-current="page" @endif
                                    >
                                        {{ $subMenuItem->getName() }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </details>
                @else
                    <div
                        class="group/item relative {{ $isActive ? 'active open' : 'inactive' }}"
                        data-sidebar-menu-item
                    >
                        <a
                            href="{{ $menuItem->getUrl() }}"
                            class="{{ $menuButtonBase }} {{ $isActive ? $menuButtonActive : $menuButtonInactive }}"
                            aria-label="{{ $menuItem->getName() }}"
                            @if ($isActive) aria-current="page" @endif
                        >
                            <span class="{{ $menuItem->getIcon() }} {{ $menuIconBase }} {{ $isActive ? 'text-blue-600 dark:text-blue-300' : 'text-slate-600 dark:text-slate-300' }}"></span>

                            <span class="{{ $menuLabelBase }} {{ $isActive ? 'text-blue-600 dark:text-blue-300' : 'text-slate-700 dark:text-slate-300' }}">
                                {{ $menuItem->getName() }}
                            </span>
                        </a>
                    </div>
                @endif
            @endforeach
        </nav>
    </div>

    <v-sidebar-collapse></v-sidebar-collapse>
</aside>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-sidebar-collapse-template"
    >
        <button
            type="button"
            class="w-full cursor-pointer border-t border-slate-200/70 bg-white px-3 py-2 text-slate-500 transition-all duration-300 ease-in-out hover:bg-slate-100 hover:text-blue-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-blue-400"
            @click="toggle"
            :aria-pressed="isCollapsed ? 'true' : 'false'"
            aria-label="Toggle admin sidebar"
        >
            <span class="flex h-10 items-center justify-center rounded-xl transition-all duration-200">
                <span
                    class="icon-collapse text-[20px] leading-none transition-all duration-300"
                    :class="[isCollapsed ? 'ltr:rotate-[180deg] rtl:rotate-[0]' : 'ltr:rotate-[0] rtl:rotate-[180deg]']"
                ></span>
            </span>
        </button>
    </script>

    <script type="module">
        app.component('v-sidebar-collapse', {
            template: '#v-sidebar-collapse-template',

            data() {
                return {
                    isCollapsed: Number({{ request()->cookie('sidebar_collapsed') ?? 0 }}),
                }
            },

            mounted() {
                this.syncLayoutClass();
            },

            methods: {
                toggle() {
                    this.isCollapsed = parseInt(this.isCollapsedCookie()) ? 0 : 1;

                    var expiryDate = new Date();

                    expiryDate.setMonth(expiryDate.getMonth() + 1);

                    document.cookie = 'sidebar_collapsed=' + this.isCollapsed + '; path=/; expires=' + expiryDate.toGMTString();

                    this.syncLayoutClass();

                    window.dispatchEvent(new CustomEvent('admin-sidebar:collapse-change', {
                        detail: {
                            collapsed: Boolean(this.isCollapsed),
                        },
                    }));
                },

                syncLayoutClass() {
                    const layout = this.$root.$refs.appLayout;

                    if (! layout) {
                        return;
                    }

                    layout.classList.toggle('sidebar-collapsed', Boolean(this.isCollapsed));
                    layout.classList.toggle('sidebar-not-collapsed', ! Boolean(this.isCollapsed));
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

        const initAdminSidebarFlyouts = () => {
            const sidebar = document.querySelector('[data-admin-sidebar]');
            const layout = document.querySelector('[data-admin-layout]');

            if (! sidebar || ! layout || sidebar.dataset.flyoutReady === 'true') {
                return;
            }

            sidebar.dataset.flyoutReady = 'true';

            const isCollapsed = () => layout.classList.contains('sidebar-collapsed');
            const closeTimers = new WeakMap();

            const cancelClose = (item) => {
                const timer = closeTimers.get(item);

                if (timer) {
                    window.clearTimeout(timer);
                    closeTimers.delete(item);
                }
            };

            const restoreDetailsState = (item) => {
                if (item.tagName !== 'DETAILS' || item.classList.contains('active')) {
                    return;
                }

                item.removeAttribute('open');
            };

            const closeFlyouts = (except = null) => {
                sidebar.querySelectorAll('[data-sidebar-menu-item][data-flyout-open="true"]').forEach((item) => {
                    if (item !== except) {
                        item.removeAttribute('data-flyout-open');
                        restoreDetailsState(item);
                        cancelClose(item);
                    }
                });
            };

            const positionFlyout = (item) => {
                const flyout = item.querySelector('[data-sidebar-flyout]');

                if (! flyout) {
                    return;
                }

                if (isCollapsed() && item.tagName === 'DETAILS') {
                    item.setAttribute('open', '');
                }

                const itemRect = item.getBoundingClientRect();
                const flyoutHeight = flyout.offsetHeight || 260;
                const minTop = 72;
                const maxTop = Math.max(minTop, window.innerHeight - flyoutHeight - 12);
                const top = Math.min(Math.max(itemRect.top, minTop), maxTop);

                flyout.style.setProperty('--ast-sidebar-flyout-top', `${top}px`);
            };

            sidebar.querySelectorAll('[data-sidebar-menu-item]').forEach((item) => {
                const trigger = item.querySelector('[data-sidebar-flyout-trigger]');
                const flyout = item.querySelector('[data-sidebar-flyout]');

                if (! trigger || ! flyout) {
                    return;
                }

                item.addEventListener('mouseenter', () => {
                    if (! isCollapsed()) {
                        return;
                    }

                    cancelClose(item);
                    closeFlyouts(item);
                    item.setAttribute('data-flyout-open', 'true');
                    positionFlyout(item);
                });

                item.addEventListener('focusin', () => {
                    if (! isCollapsed()) {
                        return;
                    }

                    cancelClose(item);
                    closeFlyouts(item);
                    item.setAttribute('data-flyout-open', 'true');
                    positionFlyout(item);
                });

                item.addEventListener('mouseleave', () => {
                    if (! isCollapsed()) {
                        return;
                    }

                    const timer = window.setTimeout(() => {
                        if (item.matches(':hover') || flyout.matches(':hover')) {
                            return;
                        }

                        item.removeAttribute('data-flyout-open');
                        restoreDetailsState(item);
                        closeTimers.delete(item);
                    }, 320);

                    closeTimers.set(item, timer);
                });

                flyout.addEventListener('mouseenter', () => {
                    cancelClose(item);
                });

                flyout.addEventListener('mouseleave', () => {
                    if (! isCollapsed()) {
                        return;
                    }

                    const timer = window.setTimeout(() => {
                        if (item.matches(':hover') || flyout.matches(':hover')) {
                            return;
                        }

                        item.removeAttribute('data-flyout-open');
                        restoreDetailsState(item);
                        closeTimers.delete(item);
                    }, 200);

                    closeTimers.set(item, timer);
                });

                trigger.addEventListener('click', (event) => {
                    if (! isCollapsed()) {
                        return;
                    }

                    event.preventDefault();
                    cancelClose(item);

                    const shouldOpen = item.getAttribute('data-flyout-open') !== 'true';

                    closeFlyouts(item);

                    if (shouldOpen) {
                        item.setAttribute('open', '');
                        positionFlyout(item);
                        item.setAttribute('data-flyout-open', 'true');
                    } else {
                        item.removeAttribute('data-flyout-open');
                        restoreDetailsState(item);
                    }
                });
            });

            document.addEventListener('click', (event) => {
                if (! sidebar.contains(event.target)) {
                    closeFlyouts();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeFlyouts();
                }
            });

            window.addEventListener('resize', () => closeFlyouts());
            window.addEventListener('admin-sidebar:collapse-change', () => closeFlyouts());
        };

        if (document.readyState === 'complete') {
            window.setTimeout(initAdminSidebarFlyouts, 0);
        } else {
            window.addEventListener('load', () => window.setTimeout(initAdminSidebarFlyouts, 0), { once: true });
        }
    </script>

@endPushOnce
