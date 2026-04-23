<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Shipment Documents</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 0;
            padding: 24px;
        }

        .document {
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .page-break {
            page-break-after: always;
        }

        .header,
        .section {
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            margin: 0 0 8px;
        }

        .header-subtitle {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #4b5563;
        }

        .label-header {
            text-align: center;
        }

        .label-header h1 {
            font-size: 30px;
            margin-bottom: 6px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
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
            margin-top: 12px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 10px;
            font-size: 14px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
        }

        .badge {
            display: inline-block;
            border: 1px solid #d1d5db;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
        }

        @media print {
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    @php
        $showLabel = in_array($printData['document'], ['label', 'both'], true);
        $showInvoice = in_array($printData['document'], ['invoice', 'both'], true);
    @endphp

    @if ($showLabel)
        <section class="document {{ $showInvoice ? 'page-break' : '' }}">
            <div class="header label-header">
                <h1>{{ $printData['merchant']['name'] }}</h1>
                <div class="header-subtitle">Parcel Label</div>
                <div class="value">{{ $printData['merchant']['address'] }}</div>
            </div>

            <div class="grid section">
                <div>
                    <div class="label">Order No</div>
                    <div class="value">#{{ $printData['order']->increment_id }}</div>
                </div>
                <div>
                    <div class="label">Courier</div>
                    <div class="value">{{ $printData['carrier']?->name ?: 'Manual Courier' }}</div>
                </div>
                <div>
                    <div class="label">Tracking Number</div>
                    <div class="value">{{ $printData['tracking_number'] ?: 'Not added' }}</div>
                </div>
                <div>
                    <div class="label">Payment</div>
                    <div class="value">
                        @if ($printData['cod_amount'] > 0)
                            <span class="badge">COD {{ $printData['cod_amount_formatted'] }}</span>
                        @else
                            <span class="badge">Prepaid</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid section">
                <div>
                    <div class="label">Customer Name</div>
                    <div class="value">{{ $printData['customer_name'] }}</div>
                </div>
                <div>
                    <div class="label">Customer Phone</div>
                    <div class="value">{{ $printData['customer_phone'] ?: 'No phone added' }}</div>
                </div>
            </div>

            <div class="section">
                <div class="label">Delivery Address</div>
                <div class="value">{{ $printData['delivery_address'] }}</div>
            </div>

            <div class="grid section">
                <div>
                    <div class="label">Parcel Count</div>
                    <div class="value">{{ $printData['package_count'] }}</div>
                </div>
                <div>
                    <div class="label">Handover Mode</div>
                    <div class="value">{{ $printData['handover_mode_label'] }}</div>
                </div>
                <div>
                    <div class="label">Weight</div>
                    <div class="value">{{ $printData['package_weight_kg'] ? $printData['package_weight_kg'].' kg' : 'Not added' }}</div>
                </div>
                <div>
                    <div class="label">Dimensions</div>
                    <div class="value">{{ $printData['package_dimensions'] ?: 'Not added' }}</div>
                </div>
            </div>

            @if ($printData['is_fragile'] || $printData['special_handling'])
                <div class="section">
                    <div class="label">Fragile / Special Handling</div>
                    <div class="value">
                        {{ $printData['special_handling'] ?: 'Fragile parcel' }}
                    </div>
                </div>
            @endif
        </section>
    @endif

    @if ($showInvoice)
        <section class="document">
            <div class="header">
                <h1>Invoice</h1>
                <div class="value">{{ $printData['merchant']['name'] }}</div>
                <div class="value">{{ $printData['merchant']['address'] }}</div>
            </div>

            <div class="grid section">
                <div>
                    <div class="label">Order No</div>
                    <div class="value">#{{ $printData['order']->increment_id }}</div>
                </div>
                <div>
                    <div class="label">Customer</div>
                    <div class="value">{{ $printData['customer_name'] }}</div>
                </div>
                <div>
                    <div class="label">Phone</div>
                    <div class="value">{{ $printData['customer_phone'] ?: 'No phone added' }}</div>
                </div>
                <div>
                    <div class="label">Payment Type</div>
                    <div class="value">{{ $printData['payment_label'] }}</div>
                </div>
            </div>

            <div class="section">
                <div class="label">Delivery Address</div>
                <div class="value">{{ $printData['delivery_address'] }}</div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($printData['items'] as $item)
                        <tr>
                            <td>
                                <strong>{{ $item['name'] }}</strong><br>
                                <span>{{ $item['sku'] }}</span>
                            </td>
                            <td>{{ $item['qty'] }}</td>
                            <td>{{ $item['price_formatted'] }}</td>
                            <td>{{ $item['subtotal_formatted'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="grid section" style="margin-top: 20px;">
                <div>
                    <div class="label">Total Qty</div>
                    <div class="value">{{ $printData['total_qty'] }}</div>
                </div>
                <div>
                    <div class="label">Order Total</div>
                    <div class="value">{{ $printData['order_total_formatted'] }}</div>
                </div>
                @if ($printData['cod_amount'] > 0)
                    <div>
                        <div class="label">COD Amount</div>
                        <div class="value">{{ $printData['cod_amount_formatted'] }}</div>
                    </div>
                @endif
                <div>
                    <div class="label">Courier</div>
                    <div class="value">{{ $printData['carrier']?->name ?: 'Manual Courier' }}</div>
                </div>
            </div>
        </section>
    @endif
</body>
</html>
