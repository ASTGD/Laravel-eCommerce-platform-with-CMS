<div class="grid gap-4">
    <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
        Control which shipment update emails are sent automatically. Keep customer and team notifications in one place so shipping communication stays easy to manage.
    </div>

    @include('commerce-core::admin.configuration.partials.system-group-fields', ['groupKey' => 'sales.shipment_notifications'])
</div>
