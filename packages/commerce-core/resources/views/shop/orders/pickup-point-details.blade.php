@php
    $pickupPoint = data_get($order->shipping_address?->additional, 'pickup_point');
@endphp

@if ($order->shipping_method === \Platform\CommerceCore\Services\PickupPointService::SHIPPING_METHOD_CODE && $pickupPoint)
    <div class="rounded-lg bg-gray-50 p-3 text-xs text-zinc-600">
        <p class="font-medium text-navyBlue">
            Pick-up Point
        </p>

        <p>{{ $pickupPoint['name'] ?? '' }}</p>

        @if (! empty($pickupPoint['courier_name']))
            <p>{{ $pickupPoint['courier_name'] }}</p>
        @endif

        <p>
            {{ $pickupPoint['address_line_1'] ?? '' }}
            @if (! empty($pickupPoint['address_line_2']))
                , {{ $pickupPoint['address_line_2'] }}
            @endif
        </p>

        <p>
            {{ $pickupPoint['city'] ?? '' }}
            @if (! empty($pickupPoint['state']))
                , {{ $pickupPoint['state'] }}
            @endif
            @if (! empty($pickupPoint['postcode']))
                - {{ $pickupPoint['postcode'] }}
            @endif
        </p>

        @if (! empty($pickupPoint['phone']))
            <p>{{ $pickupPoint['phone'] }}</p>
        @endif
    </div>
@endif
