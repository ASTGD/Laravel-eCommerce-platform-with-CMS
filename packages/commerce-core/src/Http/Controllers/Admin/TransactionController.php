<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Platform\CommerceCore\DataGrids\Sales\UnifiedTransactionLedgerDataGrid;
use Platform\CommerceCore\Models\CodRemittance;
use Platform\CommerceCore\Models\SettlementBatch;
use Webkul\Payment\Facades\Payment;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;

class TransactionController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
        protected OrderTransactionRepository $orderTransactionRepository
    ) {}

    public function index()
    {
        if (request()->ajax()) {
            return datagrid(UnifiedTransactionLedgerDataGrid::class)->process();
        }

        $paymentMethods = Payment::getSupportedPaymentMethods();

        return view('commerce-core::admin.transactions.index', compact('paymentMethods'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'invoice_id' => 'required',
            'payment_method' => 'required',
            'amount' => 'required|numeric',
        ]);

        $invoice = $this->invoiceRepository->where('id', $request->invoice_id)->first();

        if (! $invoice) {
            return new JsonResponse([
                'message' => trans('admin::app.sales.transactions.index.create.invoice-missing'),
            ], 400);
        }

        $transactionAmtBefore = $this->orderTransactionRepository->where('invoice_id', $invoice->id)->sum('amount');

        $transactionAmtFinal = $request->amount + $transactionAmtBefore;

        if ($invoice->state == 'paid') {
            return new JsonResponse([
                'message' => trans('admin::app.sales.transactions.index.create.already-paid'),
            ], 400);
        }

        if ($transactionAmtFinal > $invoice->base_grand_total) {
            return new JsonResponse([
                'message' => trans('admin::app.sales.transactions.index.create.transaction-amount-exceeds'),
            ], 400);
        }

        if ($request->amount <= 0) {
            return new JsonResponse([
                'message' => trans('admin::app.sales.transactions.index.create.transaction-amount-zero'),
            ], 400);
        }

        $order = $this->orderRepository->find($invoice->order_id);

        $this->orderTransactionRepository->create([
            'transaction_id' => bin2hex(random_bytes(20)),
            'type' => $request->payment_method,
            'payment_method' => $request->payment_method,
            'invoice_id' => $invoice->id,
            'order_id' => $invoice->order_id,
            'amount' => $request->amount,
            'status' => 'paid',
            'data' => json_encode([
                'paidAmount' => $request->amount,
            ]),
        ]);

        $transactionTotal = $this->orderTransactionRepository->where('invoice_id', $invoice->id)->sum('amount');

        if ($transactionTotal >= $invoice->base_grand_total) {
            $this->orderRepository->updateOrderStatus($order);

            $this->invoiceRepository->updateState($invoice, Invoice::STATUS_PAID);
        }

        return new JsonResponse([
            'message' => trans('admin::app.sales.transactions.index.create.transaction-saved'),
        ]);
    }

    public function view(int|string $id): JsonResponse
    {
        if (str_starts_with((string) $id, 'cod-remittance:')) {
            return new JsonResponse([
                'data' => $this->codRemittancePayload((int) str((string) $id)->after(':')->value()),
            ]);
        }

        if (str_starts_with((string) $id, 'settlement-batch:')) {
            return new JsonResponse([
                'data' => $this->settlementBatchPayload((int) str((string) $id)->after(':')->value()),
            ]);
        }

        $nativeId = str_starts_with((string) $id, 'invoice-payment:')
            ? (int) str((string) $id)->after(':')->value()
            : (int) $id;

        $transaction = $this->orderTransactionRepository->findOrFail($nativeId);
        $order = $this->orderRepository->find($transaction->order_id);

        return new JsonResponse([
            'data' => [
                'id' => $transaction->id,
                'transaction_id' => $transaction->transaction_id,
                'ledger_type' => 'invoice_payment',
                'type_label' => 'Invoice Payment',
                'transaction_ref' => $transaction->transaction_id,
                'source' => 'Invoice #'.$transaction->invoice_id.' / Order #'.$order?->increment_id,
                'counterparty' => trim($order?->customer_full_name ?: '') ?: $order?->customer_email,
                'amount' => core()->formatBasePrice((float) $transaction->amount),
                'status' => str($transaction->status)->replace('_', ' ')->title()->value(),
                'payment_method' => data_get(config('payment_methods'), $transaction->payment_method.'.title')
                    ?: $transaction->payment_method,
                'payment_title' => data_get(config('payment_methods'), $transaction->payment_method.'.title')
                    ?: $transaction->payment_method,
                'invoice_id' => $transaction->invoice_id,
                'order_id' => $transaction->order_id,
                'order_increment_id' => $order?->increment_id,
                'created_at' => $transaction->created_at?->format('d M Y H:i') ?: 'N/A',
                'allocations' => [],
            ],
        ]);
    }

    protected function codRemittancePayload(int $id): array
    {
        $remittance = CodRemittance::query()
            ->with([
                'carrier',
                'allocations.order',
                'allocations.codSettlement',
                'allocations.shipmentRecord',
            ])
            ->findOrFail($id);

        return [
            'ledger_type' => 'cod_remittance',
            'type_label' => 'COD Remittance',
            'transaction_ref' => $remittance->reference,
            'source' => 'COD Receivable Receipt',
            'counterparty' => $remittance->carrier?->name ?: 'Unknown Courier',
            'amount' => core()->formatBasePrice((float) $remittance->amount_received),
            'allocated_amount' => core()->formatBasePrice((float) $remittance->allocated_amount),
            'unallocated_amount' => core()->formatBasePrice((float) $remittance->unallocated_amount),
            'status' => $remittance->status_label,
            'payment_method' => 'COD Received',
            'created_at' => $remittance->received_at?->format('d M Y H:i') ?: $remittance->created_at?->format('d M Y H:i'),
            'note' => $remittance->note,
            'allocations' => $remittance->allocations->map(fn ($allocation) => [
                'order' => $allocation->order?->increment_id ? '#'.$allocation->order->increment_id : 'N/A',
                'settlement' => '#'.$allocation->cod_settlement_id,
                'shipment' => $allocation->shipment_record_id ? '#'.$allocation->shipment_record_id : 'N/A',
                'amount' => core()->formatBasePrice((float) $allocation->allocated_amount),
                'status' => $allocation->status_label,
            ])->values()->all(),
        ];
    }

    protected function settlementBatchPayload(int $id): array
    {
        $batch = SettlementBatch::query()
            ->with([
                'carrier',
                'items.codSettlement.order',
                'items.codSettlement.shipmentRecord',
            ])
            ->findOrFail($id);

        return [
            'ledger_type' => 'settlement_batch',
            'type_label' => 'COD Remittance',
            'transaction_ref' => $batch->reference,
            'source' => 'Settlement Batch',
            'counterparty' => $batch->carrier?->name ?: 'Unknown Courier',
            'amount' => core()->formatBasePrice((float) $batch->gross_remitted_amount),
            'allocated_amount' => core()->formatBasePrice((float) $batch->gross_remitted_amount),
            'unallocated_amount' => core()->formatBasePrice((float) $batch->reconciliation_gap_amount),
            'status' => $batch->status_label,
            'payment_method' => $batch->payout_method ? str($batch->payout_method)->replace('_', ' ')->title()->value() : 'N/A',
            'created_at' => ($batch->received_at ?: $batch->remitted_at ?: $batch->created_at)?->format('d M Y H:i') ?: 'N/A',
            'note' => $batch->notes,
            'allocations' => $batch->items->map(fn ($item) => [
                'order' => $item->codSettlement?->order?->increment_id ? '#'.$item->codSettlement->order->increment_id : 'N/A',
                'settlement' => '#'.$item->cod_settlement_id,
                'shipment' => $item->codSettlement?->shipment_record_id ? '#'.$item->codSettlement->shipment_record_id : 'N/A',
                'amount' => core()->formatBasePrice((float) $item->remitted_amount),
                'status' => $item->codSettlement?->status_label ?: 'N/A',
            ])->values()->all(),
        ];
    }
}
