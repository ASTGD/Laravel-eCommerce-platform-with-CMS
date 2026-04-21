<div class="grid gap-4">
    <div class="rounded-lg border border-violet-100 bg-violet-50 p-4 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
        Keep store pickup locations and courier handoff points in the same shipping setup area so the team can manage them without touching daily shipment operations.
    </div>

    <div class="rounded-lg border border-slate-200 p-4 dark:border-gray-800">
        <div class="grid gap-1">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                Open Pickup Points
            </p>

            <p class="text-sm text-gray-600 dark:text-gray-300">
                Add or edit pickup points, business hours, and location details for the couriers your store uses.
            </p>
        </div>

        <div class="mt-4">
            <a
                href="{{ route('admin.sales.pickup-points.index') }}"
                class="secondary-button"
            >
                Manage Pickup Points
            </a>
        </div>
    </div>
</div>
