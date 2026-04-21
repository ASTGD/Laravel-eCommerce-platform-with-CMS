<div class="grid gap-4">
    <div class="rounded-lg border border-amber-100 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
        Choose how your team handles shipping work. <strong>Manual Basic</strong> is for simple courier tracking and manual delivery confirmation. <strong>Advanced Pro</strong> enables shipment ops, carrier booking, tracking sync, webhooks, and COD settlement workflows.
    </div>

    @include('commerce-core::admin.configuration.partials.system-group-fields', ['groupKey' => 'sales.shipping_workflow'])
</div>
