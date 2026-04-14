@php
    $pickupPoint = data_get($order->shipping_address?->additional, 'pickup_point');
@endphp

@if ($order->shipping_method === \Platform\CommerceCore\Services\PickupPointService::SHIPPING_METHOD_CODE && $pickupPoint)
    <p class="pt-4 font-semibold text-gray-800 dark:text-white">
        Pick-up Point
    </p>

    <div class="pt-1 text-gray-600 dark:text-gray-300">
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
