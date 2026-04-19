<div class="grid gap-4">
    @foreach ($shipmentTimelines as $shipmentTimeline)
        @php
            $statusClasses = match ($shipmentTimeline['status']) {
                \Platform\CommerceCore\Models\ShipmentRecord::STATUS_DELIVERED => 'bg-emerald-100 text-emerald-700',
                \Platform\CommerceCore\Models\ShipmentRecord::STATUS_OUT_FOR_DELIVERY => 'bg-blue-100 text-blue-700',
                \Platform\CommerceCore\Models\ShipmentRecord::STATUS_DELIVERY_FAILED => 'bg-amber-100 text-amber-700',
                \Platform\CommerceCore\Models\ShipmentRecord::STATUS_RETURNED => 'bg-rose-100 text-rose-700',
                default => 'bg-zinc-100 text-zinc-700',
            };
        @endphp

        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4">
            <div class="flex items-start justify-between gap-4 max-md:flex-col">
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-black">
                        Shipment #{{ $shipmentTimeline['id'] }}
                    </p>

                    <div class="text-sm text-zinc-600">
                        @if ($shipmentTimeline['carrier_name'])
                            <p>{{ $shipmentTimeline['carrier_name'] }}</p>
                        @endif

                        @if ($shipmentTimeline['tracking_number'])
                            <p>Tracking: {{ $shipmentTimeline['tracking_number'] }}</p>
                        @endif
                    </div>
                </div>

                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses }}">
                    {{ $shipmentTimeline['status_label'] }}
                </span>
            </div>

            @if (! empty($shipmentTimeline['carrier_tracking_url']))
                <div class="mt-3">
                    <a
                        href="{{ $shipmentTimeline['carrier_tracking_url'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 text-sm font-medium text-navyBlue hover:underline"
                    >
                        Track on carrier site
                    </a>
                </div>
            @endif

            <div class="mt-4 grid gap-3">
                @foreach ($shipmentTimeline['events'] as $event)
                    <div class="flex gap-3">
                        <span class="mt-1.5 h-2.5 w-2.5 rounded-full bg-navyBlue"></span>

                        <div class="grid gap-0.5">
                            <p class="text-sm font-medium text-black">
                                {{ $event['label'] }}
                            </p>

                            <p class="text-xs text-zinc-500">
                                {{ $event['occurred_at']?->format('d M Y, h:i a') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
