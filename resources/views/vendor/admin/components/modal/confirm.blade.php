@include('admin::components.modal.styles')

<v-modal-confirm ref="confirmModal"></v-modal-confirm>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-modal-confirm-template"
    >
        <div>
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
                    style="z-index: 10002; background: rgba(15, 23, 42, 0.72); backdrop-filter: blur(2px);"
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
                    style="z-index: 10003;"
                    v-if="isOpen"
                >
                    <div class="admin-modal-positioner flex min-h-full items-center justify-center p-4 sm:p-6">
                        <div class="admin-modal-panel admin-modal-panel--confirm relative z-[999] flex w-full max-w-[420px] flex-col overflow-hidden rounded-2xl border border-white/70 bg-white shadow-2xl ring-1 ring-slate-950/10 dark:border-gray-700 dark:bg-gray-900 dark:ring-white/10 max-md:w-[90%]">
                            <div class="admin-modal-header flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-5 dark:border-gray-800 dark:bg-gray-900">
                                <div class="min-w-0 flex-1 text-lg font-bold text-gray-800 dark:text-white">
                                    @{{ title }}
                                </div>
                            </div>

                            <div class="admin-modal-body min-h-0 overflow-y-auto px-6 py-5 text-left text-gray-600 dark:text-gray-300">
                                @{{ message }}
                            </div>

                            <div class="admin-modal-footer flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/80 px-6 py-4 dark:border-gray-800 dark:bg-gray-950/50">
                                <button type="button" class="transparent-button" @click="disagree">
                                    @{{ options.btnDisagree }}
                                </button>

                                <button type="button" class="primary-button" @click="agree">
                                    @{{ options.btnAgree }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </script>

    <script type="module">
        app.component('v-modal-confirm', {
            template: '#v-modal-confirm-template',

            data() {
                return {
                    isOpen: false,

                    title: '',

                    message: '',

                    options: {
                        btnDisagree: '',
                        btnAgree: '',
                    },

                    agreeCallback: null,

                    disagreeCallback: null,
                };
            },

            created() {
                this.registerGlobalEvents();
            },

            methods: {
                open({
                    title = "@lang('admin::app.components.modal.confirm.title')",
                    message = "@lang('admin::app.components.modal.confirm.message')",
                    options = {
                        btnDisagree: "@lang('admin::app.components.modal.confirm.disagree-btn')",
                        btnAgree: "@lang('admin::app.components.modal.confirm.agree-btn')",
                    },
                    agree = () => {},
                    disagree = () => {},
                }) {
                    this.isOpen = true;

                    document.body.style.overflow = 'hidden';

                    this.title = title;

                    this.message = message;

                    this.options = options;

                    this.agreeCallback = agree;

                    this.disagreeCallback = disagree;
                },

                disagree() {
                    this.isOpen = false;

                    document.body.style.overflow = 'auto';

                    this.disagreeCallback();
                },

                agree() {
                    this.isOpen = false;

                    document.body.style.overflow = 'auto';

                    this.agreeCallback();
                },

                registerGlobalEvents() {
                    this.$emitter.on('open-confirm-modal', this.open);
                },
            }
        });
    </script>
@endPushOnce
