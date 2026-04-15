@php
    $customConfigurationCodes = collect([
        'bkash_gateway',
        'bkash',
        'sslcommerz_gateway',
        'sslcommerz',
    ]);

    $paymentMethodChildren = $activeConfiguration->getChildren();
    $paymentMethodChildrenByCode = $paymentMethodChildren->keyBy(function ($child) {
        return \Illuminate\Support\Str::afterLast($child->getKey(), '.');
    });

    $defaultChildren = $paymentMethodChildren->filter(function ($child) use ($customConfigurationCodes) {
        $code = \Illuminate\Support\Str::afterLast($child->getKey(), '.');

        return ! $customConfigurationCodes->contains($code);
    })->values();
@endphp

<div
    class="mt-6"
    data-payment-method-tabs
    data-initial-tab="default"
>
    <div class="flex justify-center gap-4 bg-white pt-2 dark:bg-gray-900 dark:text-gray-300 max-sm:hidden">
        <button
            type="button"
            class="cursor-pointer px-2.5 pb-3.5 text-base font-medium text-gray-300"
            data-tab-trigger="default"
            data-payment-method-tab="default"
            aria-controls="payment-method-panel-default"
            aria-selected="true"
        >
            Default
        </button>

        <button
            type="button"
            class="cursor-pointer px-2.5 pb-3.5 text-base font-medium text-gray-300"
            data-tab-trigger="custom"
            data-payment-method-tab="custom"
            aria-controls="payment-method-panel-custom"
            aria-selected="false"
        >
            Custom
        </button>
    </div>

    <div
        id="payment-method-panel-default"
        class="payment-method-tab-panel"
        data-tab-panel="default"
    >
        <div class="rounded-lg border border-gray-200 bg-blue-50/40 p-4 dark:border-gray-800 dark:bg-gray-900/60">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                These are Default Payment Methods
            </p>

            <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">
                These are Default Payment Methods.
            </p>
        </div>

        <div class="mt-6 grid grid-cols-[1fr_2fr] gap-10 max-xl:flex-wrap">
            @include('vendor.admin.configuration.partials.children-grid', ['children' => $defaultChildren])
        </div>
    </div>

    <div
        id="payment-method-panel-custom"
        class="payment-method-tab-panel hidden"
        data-tab-panel="custom"
    >
        <div class="rounded-lg border border-gray-200 bg-pink-50/40 p-4 dark:border-gray-800 dark:bg-gray-900/60">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                These are Custom Payment Methods
            </p>

            <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">
                These are Custom Payment Methods.
            </p>
        </div>

        <div class="mt-6 grid grid-cols-[1fr_2fr] gap-10 max-xl:flex-wrap">
            @include('vendor.admin.configuration.partials.payment-method-group', [
                'title' => 'bKash',
                'info' => 'Configure bKash payment and gateway settings.',
                'methodChild' => $paymentMethodChildrenByCode->get('bkash'),
                'gatewayChild' => $paymentMethodChildrenByCode->get('bkash_gateway'),
            ])

            @include('vendor.admin.configuration.partials.payment-method-group', [
                'title' => 'SSLCommerz',
                'info' => 'Configure SSLCommerz payment and gateway settings.',
                'methodChild' => $paymentMethodChildrenByCode->get('sslcommerz'),
                'gatewayChild' => $paymentMethodChildrenByCode->get('sslcommerz_gateway'),
            ])
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script type="module">
        const initializePaymentMethodTabs = () => {
            const storageKey = 'admin-payment-method-config-tab';
            const inactiveTabClasses = ['text-gray-300'];

            document.querySelectorAll('[data-payment-method-tabs]').forEach((container) => {
                if (container.dataset.tabsInitialized === 'true') {
                    return;
                }

                const triggers = Array.from(container.querySelectorAll('[data-tab-trigger]'));
                const panels = Array.from(container.querySelectorAll('[data-tab-panel]'));

                if (! triggers.length || ! panels.length) {
                    return;
                }

                const setActiveTab = (tabName) => {
                    triggers.forEach((trigger) => {
                        const isActive = trigger.dataset.tabTrigger === tabName;

                        trigger.setAttribute('aria-selected', isActive ? 'true' : 'false');
                        trigger.classList.toggle('border-blue-600', isActive);
                        trigger.classList.toggle('border-b-2', isActive);
                        trigger.classList.toggle('text-blue-600', isActive);
                        trigger.classList.toggle('transition', isActive);

                        if (isActive) {
                            trigger.classList.remove(...inactiveTabClasses);
                        } else {
                            trigger.classList.add(...inactiveTabClasses);
                        }
                    });

                    panels.forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.tabPanel !== tabName);
                    });

                    window.sessionStorage.setItem(storageKey, tabName);
                };

                const preferredTab = window.sessionStorage.getItem(storageKey);
                const initialTab = triggers.some((trigger) => trigger.dataset.tabTrigger === preferredTab)
                    ? preferredTab
                    : (container.dataset.initialTab || triggers[0].dataset.tabTrigger);

                setActiveTab(initialTab);

                container.addEventListener('click', (event) => {
                    const trigger = event.target.closest('[data-tab-trigger]');

                    if (! trigger || ! container.contains(trigger)) {
                        return;
                    }

                    setActiveTab(trigger.dataset.tabTrigger);
                });

                container.dataset.tabsInitialized = 'true';
            });
        };

        window.addEventListener('load', () => {
            window.setTimeout(initializePaymentMethodTabs, 0);
        });
    </script>
@endPushOnce
