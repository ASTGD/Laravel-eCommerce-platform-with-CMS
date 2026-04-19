@component('admin::emails.layout')
    <div style="margin-bottom: 28px;">
        <span style="font-size: 22px;font-weight: 600;color: #121A26;">
            {{ $notificationLabel }}
        </span>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            Dear {{ core()->getAdminEmailDetails()['name'] }},<br>
            Shipment Ops #{{ $shipmentRecord->id }} for order #{{ $shipmentRecord->order->increment_id }} has a logistics update.
        </p>
    </div>

    <div style="padding: 20px;border: 1px solid #CBD5E1;border-radius: 12px;margin-bottom: 24px;">
        <p style="margin: 0 0 10px;font-size: 16px;font-weight: 600;color: #121A26;">Shipment Summary</p>
        <p style="margin: 0 0 8px;font-size: 15px;color: #384860;">Status: {{ $shipmentRecord->status_label }}</p>
        <p style="margin: 0 0 8px;font-size: 15px;color: #384860;">Carrier: {{ $shipmentRecord->carrier?->name ?? $shipmentRecord->carrier_name_snapshot ?: 'N/A' }}</p>
        <p style="margin: 0 0 8px;font-size: 15px;color: #384860;">Tracking: {{ $shipmentRecord->tracking_number ?: 'Not assigned yet' }}</p>
        <p style="margin: 0 0 8px;font-size: 15px;color: #384860;">Customer: {{ $shipmentRecord->recipient_name ?: $shipmentRecord->order->customer_full_name }}</p>

        @if ($shipmentEvent->note)
            <p style="margin: 0;font-size: 15px;color: #384860;">Note: {{ $shipmentEvent->note }}</p>
        @endif
    </div>

    <div style="margin-bottom: 24px;">
        <a
            href="{{ route('admin.sales.shipment-operations.view', $shipmentRecord) }}"
            style="display: inline-block;padding: 12px 18px;border-radius: 10px;background: #1D4ED8;color: #ffffff;text-decoration: none;font-size: 15px;font-weight: 600;"
        >
            View Shipment Ops
        </a>
    </div>
@endcomponent
