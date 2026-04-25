@once('admin-modal-design-system-styles')
    <style id="admin-modal-design-system-styles">
        .admin-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(2px);
            transition: opacity 200ms ease;
        }

        .admin-modal-overlay {
            position: fixed;
            inset: 0;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(2px);
        }

        .admin-modal-viewport {
            position: fixed;
            inset: 0;
            overflow-y: auto;
            transform: translateZ(0);
            transition: opacity 200ms ease, transform 200ms ease;
        }

        .admin-modal-positioner {
            display: flex;
            min-height: 100%;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .admin-modal-panel {
            position: relative;
            z-index: 999;
            display: flex;
            width: min(100%, 568px);
            max-width: 568px;
            max-height: calc(100vh - 2rem);
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.72);
            border-radius: 1rem;
            background: #ffffff;
            box-shadow:
                0 24px 80px -28px rgba(15, 23, 42, 0.65),
                0 12px 32px -18px rgba(15, 23, 42, 0.35);
            outline: 1px solid rgba(15, 23, 42, 0.10);
        }

        .admin-modal-panel--confirm {
            max-width: 420px;
        }

        .admin-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 1.25rem 1.5rem;
        }

        .admin-modal-body {
            min-height: 0;
            overflow-y: auto;
            padding: 1.25rem 1.5rem;
            color: #374151;
        }

        .admin-modal-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            border-top: 1px solid #e2e8f0;
            background: rgba(248, 250, 252, 0.86);
            padding: 1rem 1.5rem;
        }

        .admin-modal-close {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex-shrink: 0;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: #ffffff;
            color: #64748b;
            font-size: 1.25rem;
            line-height: 1;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
            transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease;
        }

        .admin-modal-close:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #1e293b;
        }

        .admin-modal-close[class^="icon-"],
        .admin-modal-close[class*=" icon-"] {
            color: #64748b;
        }

        .admin-modal-close[class^="icon-"]:hover,
        .admin-modal-close[class*=" icon-"]:hover {
            color: #1e293b;
        }

        html.dark .admin-modal-panel {
            border-color: #374151;
            background: #111827;
            outline-color: rgba(255, 255, 255, 0.10);
        }

        html.dark .admin-modal-header {
            border-color: #1f2937;
            background: #111827;
        }

        html.dark .admin-modal-body {
            color: #e5e7eb;
        }

        html.dark .admin-modal-footer {
            border-color: #1f2937;
            background: rgba(3, 7, 18, 0.50);
        }

        html.dark .admin-modal-close {
            border-color: #374151;
            background: #111827;
            color: #d1d5db;
        }

        html.dark .admin-modal-close:hover {
            background: #1f2937;
            color: #ffffff;
        }

        html.dark .admin-modal-close[class^="icon-"],
        html.dark .admin-modal-close[class*=" icon-"] {
            color: #d1d5db;
        }

        html.dark .admin-modal-close[class^="icon-"]:hover,
        html.dark .admin-modal-close[class*=" icon-"]:hover {
            color: #ffffff;
        }

        @media (max-width: 767.98px) {
            .admin-modal-positioner {
                padding: 1rem;
            }

            .admin-modal-panel {
                width: 90%;
            }
        }
    </style>
@endonce
