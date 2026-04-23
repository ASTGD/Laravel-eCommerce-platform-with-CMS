<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handover Sheet PDF</title>
    <style>
        body {
            margin: 0;
            padding: 24px;
            color: #111827;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        .sheet-section {
            margin-bottom: 24px;
        }

        .sheet-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .sheet-subtitle {
            margin-top: 8px;
            font-size: 15px;
            font-weight: bold;
        }

        .sheet-meta-line {
            margin-top: 4px;
            font-size: 13px;
            color: #4b5563;
        }

        .sheet-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .sheet-grid > div,
        .sheet-summary > div {
            display: inline-block;
            width: 32%;
            vertical-align: top;
            margin-right: 1%;
            margin-bottom: 16px;
        }

        .sheet-label {
            margin-bottom: 4px;
            color: #6b7280;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .sheet-value {
            font-size: 13px;
            line-height: 1.5;
        }

        .sheet-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sheet-table th,
        .sheet-table td {
            padding: 9px;
            border: 1px solid #cbd5e1;
            font-size: 12px;
            text-align: left;
            vertical-align: top;
        }

        .sheet-table th {
            background: #f8fafc;
        }

        .sheet-summary {
            margin-top: 12px;
        }

        .sheet-summary-card {
            padding: 12px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .sheet-signatures {
            margin-top: 36px;
        }

        .sheet-signature-box {
            display: inline-block;
            width: 47%;
            margin-right: 2%;
            margin-bottom: 28px;
            padding-top: 36px;
            border-top: 1px solid #111827;
            font-size: 12px;
        }
    </style>
</head>
<body>
    @include('commerce-core::admin.manual-to-ship.partials.handover-sheet-content', [
        'merchant' => $merchant,
        'handoverSheet' => $handoverSheet,
    ])
</body>
</html>
