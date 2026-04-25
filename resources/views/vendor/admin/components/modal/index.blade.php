@props([
    'isActive' => false,
    'boxClass' => '',
    'boxStyle' => '',
])

@include('admin::components.modal.styles')

<v-modal
    is-active="{{ $isActive }}"
    {{ $attributes }}
>
    @isset($toggle)
        <template v-slot:toggle>
            {{ $toggle }}
        </template>
    @endisset

    @isset($header)
        <template v-slot:header="{ toggle, isOpen }">
            <div {{ $header->attributes->merge(['class' => 'admin-modal-header flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-5 dark:border-gray-800 dark:bg-gray-900']) }}>
                <div class="min-w-0 flex-1">
                    {{ $header }}
                </div>

                <span
                    class="admin-modal-close icon-cancel-1 inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white text-xl text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                    role="button"
                    tabindex="0"
                    aria-label="Close modal"
                    @click="toggle"
                    @keydown.enter.prevent="toggle"
                    @keydown.space.prevent="toggle"
                >
                </span>
            </div>
        </template>
    @endisset

    @isset($content)
        <template v-slot:content>
            <div {{ $content->attributes->merge(['class' => 'admin-modal-body min-h-0 overflow-y-auto px-6 py-5 text-gray-700 dark:text-gray-200']) }}>
                {{ $content }}
            </div>
        </template>
    @endisset

    @isset($footer)
        <template v-slot:footer>
            <div {{ $footer->attributes->merge(['class' => 'admin-modal-footer flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/80 px-6 py-4 dark:border-gray-800 dark:bg-gray-950/50']) }}>
                {{ $footer }}
            </div>
        </template>
    @endisset
</v-modal>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-modal-template"
    >
        <div>
            <div @click="toggle">
                <slot name="toggle">
                </slot>
            </div>

            <transition
                tag="div"
                name="modal-overlay"
                enter-class="duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-class="duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    class="admin-modal-backdrop fixed inset-0 transition-opacity"
                    style="z-index: 10001; background: rgba(15, 23, 42, 0.72); backdrop-filter: blur(2px);"
                    v-show="isOpen"
                ></div>
            </transition>

            <transition
                tag="div"
                name="modal-content"
                enter-class="duration-300 ease-out"
                enter-from-class="translate-y-4 opacity-0 md:translate-y-0 md:scale-95"
                enter-to-class="translate-y-0 opacity-100 md:scale-100"
                leave-class="duration-200 ease-in"
                leave-from-class="translate-y-0 opacity-100 md:scale-100"
                leave-to-class="translate-y-4 opacity-0 md:translate-y-0 md:scale-95"
            >
                <div
                    class="admin-modal-viewport fixed inset-0 transform overflow-y-auto transition"
                    style="z-index: 10002;"
                    v-if="isOpen"
                >
                    <div class="admin-modal-positioner flex min-h-full items-center justify-center p-4 sm:p-6">
                        <div
                            class="admin-modal-panel relative z-[999] flex w-full max-w-[568px] flex-col overflow-hidden rounded-2xl border border-white/70 bg-white shadow-2xl ring-1 ring-slate-950/10 dark:border-gray-700 dark:bg-gray-900 dark:ring-white/10 max-md:w-[90%] {{ $boxClass }}"
                            style="max-height: calc(100vh - 2rem); {{ $boxStyle }}"
                        >
                            <!-- Header Slot -->
                            <slot
                                name="header"
                                :toggle="toggle"
                                :isOpen="isOpen"
                            >
                            </slot>

                            <!-- Content Slot -->
                            <slot name="content"></slot>

                            <!-- Footer Slot -->
                            <slot name="footer"></slot>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </script>

    <script type="module">
        app.component('v-modal', {
            template: '#v-modal-template',

            props: ['isActive'],

            data() {
                return {
                    isOpen: this.isActive,
                };
            },

            methods: {
                toggle() {
                    this.isOpen = ! this.isOpen;

                    if (this.isOpen) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = 'auto';
                    }

                    this.$emit('toggle', { isActive: this.isOpen });
                },

                open() {
                    this.isOpen = true;

                    document.body.style.overflow = 'hidden';

                    this.$emit('open', { isActive: this.isOpen });
                },

                close() {
                    this.isOpen = false;

                    document.body.style.overflow = 'auto';

                    this.$emit('close', { isActive: this.isOpen });
                }
            }
        });
    </script>
@endPushOnce
