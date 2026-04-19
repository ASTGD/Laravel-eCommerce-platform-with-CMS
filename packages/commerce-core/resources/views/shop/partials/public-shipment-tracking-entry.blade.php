@php
    $trackingEntryUrl = isset($order)
        ? route('shop.shipment-tracking.index', ['reference' => $order->increment_id])
        : route('shop.shipment-tracking.index');
@endphp

<div class="mt-4">
    <a
        href="{{ $trackingEntryUrl }}"
        class="inline-flex items-center justify-center rounded-xl border border-navyBlue px-5 py-3 text-sm font-medium text-navyBlue transition hover:bg-navyBlue hover:text-white"
    >
        {{ isset($order) ? 'Track your shipment' : 'Track shipment' }}
    </a>
</div>
