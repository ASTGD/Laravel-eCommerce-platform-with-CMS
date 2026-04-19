@php
    $shipmentTimelines = app(\Platform\CommerceCore\Services\CustomerShipmentTrackingService::class)->forOrder($order);
@endphp

@if ($shipmentTimelines->isNotEmpty())
    <div class="mt-8 rounded-xl border border-zinc-200 bg-white p-5 max-md:mt-5">
        <div class="flex items-center justify-between gap-3 max-sm:flex-wrap">
            <div>
                <p class="text-base font-medium text-black">
                    Shipment Tracking
                </p>

                <p class="mt-1 text-sm text-zinc-500">
                    Follow the delivery progress of your parcel.
                </p>
            </div>
        </div>

        <div class="mt-5">
            @include('commerce-core::shop.shipment-tracking.cards', ['shipmentTimelines' => $shipmentTimelines])
        </div>
    </div>
@endif
