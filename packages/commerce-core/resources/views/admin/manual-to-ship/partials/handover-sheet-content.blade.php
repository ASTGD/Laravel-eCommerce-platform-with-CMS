<div class="sheet-section sheet-header">
    <h1>{{ $merchant['name'] ?: 'Store' }}</h1>

    <div class="sheet-subtitle">Handover Sheet</div>

    @if (! empty($merchant['address']))
        <div class="sheet-meta-line">{{ $merchant['address'] }}</div>
    @endif

    @if (! empty($merchant['contact']))
        <div class="sheet-meta-line">Contact: {{ $merchant['contact'] }}</div>
    @endif
</div>

<div class="sheet-section sheet-grid">
    <div>
        <div class="sheet-label">Courier</div>
        <div class="sheet-value">{{ $handoverSheet['carrier']?->name ?: 'Manual Courier' }}</div>
    </div>

    <div>
        <div class="sheet-label">Handover Date &amp; Time</div>
        <div class="sheet-value">{{ \Illuminate\Support\Carbon::parse($handoverSheet['handover_at'])->format('d M Y, h:i A') }}</div>
    </div>

    <div>
        <div class="sheet-label">Handover Type</div>
        <div class="sheet-value">{{ $handoverSheet['handover_type_label'] }}</div>
    </div>

    <div>
        <div class="sheet-label">Receiver / Driver Name</div>
        <div class="sheet-value">{{ $handoverSheet['receiver_name'] ?: 'Not added' }}</div>
    </div>

    <div>
        <div class="sheet-label">Batch Reference</div>
        <div class="sheet-value">{{ $handoverSheet['reference'] }}</div>
    </div>

    <div>
        <div class="sheet-label">Total COD Amount</div>
        <div class="sheet-value">{{ $handoverSheet['total_cod_amount_formatted'] }}</div>
    </div>
</div>

<div class="sheet-section">
    <table class="sheet-table">
        <thead>
            <tr>
                <th>Order No</th>
                <th>Tracking Number</th>
                <th>Customer Name</th>
                <th>Phone</th>
                <th>Area / Address</th>
                <th>Parcel Count</th>
                <th>COD Amount</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($handoverSheet['shipments'] as $shipmentRecord)
                <tr>
                    <td>#{{ $shipmentRecord->order?->increment_id ?: $shipmentRecord->order_id }}</td>
                    <td>{{ $shipmentRecord->tracking_number ?: 'Not added' }}</td>
                    <td>{{ $shipmentRecord->recipient_name ?: 'N/A' }}</td>
                    <td>{{ $shipmentRecord->recipient_phone ?: 'N/A' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($shipmentRecord->recipient_address ?: 'No address added', 90) }}</td>
                    <td>{{ max(1, (int) $shipmentRecord->package_count) }}</td>
                    <td>
                        @if ((float) $shipmentRecord->cod_amount_expected > 0)
                            {{ core()->formatBasePrice((float) $shipmentRecord->cod_amount_expected) }}
                        @else
                            No COD
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="sheet-section sheet-summary">
    <div class="sheet-summary-card">
        <div class="sheet-label">Total Parcel Count</div>
        <div class="sheet-value">{{ $handoverSheet['parcel_count'] }}</div>
    </div>

    <div class="sheet-summary-card">
        <div class="sheet-label">Total COD Amount</div>
        <div class="sheet-value">{{ $handoverSheet['total_cod_amount_formatted'] }}</div>
    </div>

    <div class="sheet-summary-card">
        <div class="sheet-label">Remarks</div>
        <div class="sheet-value">{{ $handoverSheet['notes'] ?: 'None' }}</div>
    </div>
</div>

<div class="sheet-signatures">
    <div class="sheet-signature-box">
        Merchant signature
    </div>

    <div class="sheet-signature-box">
        Courier signature
    </div>

    <div class="sheet-signature-box">
        Received date/time
    </div>

    <div class="sheet-signature-box">
        Remarks
    </div>
</div>
