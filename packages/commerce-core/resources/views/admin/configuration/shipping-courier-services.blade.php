<div class="grid gap-4">
    <div class="rounded-lg border border-sky-100 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-100">
        Manage the courier partners your business uses for delivery. This is setup data, not a daily operations screen, so it now lives inside the shipping settings area.
    </div>

    <div class="rounded-lg border border-slate-200 p-4 dark:border-gray-800">
        <div class="grid gap-1">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                Open Courier Services
            </p>

            <p class="text-sm text-gray-600 dark:text-gray-300">
                Add or edit your courier services, business defaults, and advanced carrier connections without mixing them into daily shipment operations.
            </p>
        </div>

        <div class="mt-4">
            <a
                href="{{ route('admin.sales.carriers.index') }}"
                class="secondary-button"
            >
                Manage Courier Services
            </a>
        </div>
    </div>
</div>
