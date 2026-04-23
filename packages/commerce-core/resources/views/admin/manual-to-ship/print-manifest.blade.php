<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Handover Manifest</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 0;
            padding: 24px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 24px;
        }

        .section {
            margin-bottom: 24px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }

        .value {
            font-size: 14px;
            line-height: 1.5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 10px;
            font-size: 13px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 40px;
            margin-top: 40px;
        }

        .signature-box {
            padding-top: 40px;
            border-top: 1px solid #111827;
            font-size: 13px;
        }

        @media print {
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="section">
        <h1>Handover Sheet / Manifest</h1>
        <div class="value">{{ $merchant['name'] }}</div>
        <div class="value">{{ $merchant['address'] }}</div>
    </div>

    <div class="grid section">
        <div>
            <div class="label">Courier</div>
            <div class="value">{{ $manifest['carrier']?->name ?: 'Manual Courier' }}</div>
        </div>
        <div>
            <div class="label">Handover Date & Time</div>
            <div class="value">{{ \Illuminate\Support\Carbon::parse($manifest['handover_at'])->format('d M Y, h:i A') }}</div>
        </div>
        <div>
            <div class="label">Batch Reference</div>
            <div class="value">{{ $manifest['reference'] }}</div>
        </div>
        <div>
            <div class="label">Handover Type</div>
            <div class="value">{{ $manifest['handover_type_label'] }}</div>
        </div>
        <div>
            <div class="label">Total Parcel Count</div>
            <div class="value">{{ $manifest['parcel_count'] }}</div>
        </div>
        <div>
            <div class="label">Total COD Amount</div>
            <div class="value">{{ $manifest['total_cod_amount_formatted'] }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>SL</th>
                <th>Order No</th>
                <th>Tracking No</th>
                <th>Customer Name</th>
                <th>Phone</th>
                <th>Area / Address</th>
                <th>COD Amount</th>
                <th>Parcel Count</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($manifest['shipments'] as $index => $shipmentRecord)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>#{{ $shipmentRecord->order?->increment_id ?: $shipmentRecord->order_id }}</td>
                    <td>{{ $shipmentRecord->tracking_number ?: 'Not added' }}</td>
                    <td>{{ $shipmentRecord->recipient_name ?: 'N/A' }}</td>
                    <td>{{ $shipmentRecord->recipient_phone ?: 'N/A' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($shipmentRecord->recipient_address ?: 'No address added', 90) }}</td>
                    <td>
                        @if ((float) $shipmentRecord->cod_amount_expected > 0)
                            {{ core()->formatBasePrice((float) $shipmentRecord->cod_amount_expected) }}
                        @else
                            No COD
                        @endif
                    </td>
                    <td>{{ max(1, (int) $shipmentRecord->package_count) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="grid section" style="margin-top: 24px;">
        <div>
            <div class="label">Receiver / Driver Name</div>
            <div class="value">{{ $manifest['receiver_name'] ?: 'Not added' }}</div>
        </div>
        <div>
            <div class="label">Remarks</div>
            <div class="value">{{ $manifest['notes'] ?: 'None' }}</div>
        </div>
        <div>
            <div class="label">Merchant Contact</div>
            <div class="value">{{ $merchant['contact'] ?: 'Not added' }}</div>
        </div>
    </div>

    <div class="signatures">
        <div class="signature-box">
            Merchant handover signature
        </div>

        <div class="signature-box">
            Courier received-by signature
            <div style="margin-top: 12px;">Received date/time: __________________</div>
            <div style="margin-top: 8px;">Remarks: _____________________________</div>
        </div>
    </div>
</body>
</html>
