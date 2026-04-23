<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handover Sheet Preview</title>
    <style>
        body {
            margin: 0;
            background: #f8fafc;
            color: #111827;
            font-family: Arial, sans-serif;
        }

        .preview-bar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 24px;
            border-bottom: 1px solid #e2e8f0;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
        }

        .preview-title {
            display: grid;
            gap: 4px;
        }

        .preview-title h1 {
            margin: 0;
            font-size: 20px;
        }

        .preview-title p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        .preview-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .preview-actions a,
        .preview-actions button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 16px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .preview-actions .primary {
            border-color: #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .preview-shell {
            padding: 32px 24px 48px;
        }

        .preview-sheet {
            max-width: 1040px;
            margin: 0 auto;
            padding: 32px;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
        }

        .sheet-section {
            margin-bottom: 28px;
        }

        .sheet-header h1 {
            margin: 0;
            font-size: 28px;
        }

        .sheet-subtitle {
            margin-top: 8px;
            font-size: 16px;
            font-weight: 600;
        }

        .sheet-meta-line {
            margin-top: 6px;
            font-size: 14px;
            color: #475569;
        }

        .sheet-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .sheet-label {
            margin-bottom: 6px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .sheet-value {
            font-size: 14px;
            line-height: 1.5;
        }

        .sheet-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sheet-table th,
        .sheet-table td {
            padding: 12px;
            border: 1px solid #cbd5e1;
            font-size: 13px;
            text-align: left;
            vertical-align: top;
        }

        .sheet-table th {
            background: #f8fafc;
        }

        .sheet-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .sheet-summary-card {
            padding: 16px 18px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #f8fafc;
        }

        .sheet-signatures {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 40px 28px;
            margin-top: 40px;
        }

        .sheet-signature-box {
            padding-top: 44px;
            border-top: 1px solid #0f172a;
            font-size: 13px;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .preview-bar {
                display: none;
            }

            .preview-shell {
                padding: 0;
            }

            .preview-sheet {
                max-width: none;
                padding: 0;
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="preview-bar">
        <div class="preview-title">
            <h1>Handover Sheet Preview</h1>
            <p>Print this sheet or download a PDF copy before confirming physical courier handover.</p>
        </div>

        <div class="preview-actions">
            <button type="button" onclick="window.print()">Print</button>

            @if ($downloadUrl)
                <a href="{{ $downloadUrl }}">Download PDF</a>
            @endif

            <button type="button" class="primary" onclick="window.close()">Close</button>
        </div>
    </div>

    <div class="preview-shell">
        <div class="preview-sheet">
            @include('commerce-core::admin.manual-to-ship.partials.handover-sheet-content', [
                'merchant' => $merchant,
                'handoverSheet' => $handoverSheet,
            ])
        </div>
    </div>
</body>
</html>
