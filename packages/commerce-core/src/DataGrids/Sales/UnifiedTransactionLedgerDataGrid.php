<?php

namespace Platform\CommerceCore\DataGrids\Sales;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\DataGrid\DataGrid;

class UnifiedTransactionLedgerDataGrid extends DataGrid
{
    protected $primaryColumn = 'transaction_date';

    protected $sortColumn = 'transaction_date';

    protected $sortOrder = 'desc';

    public function prepareQueryBuilder(): Builder
    {
        $invoicePayments = DB::table('order_transactions')
            ->leftJoin('orders', 'order_transactions.order_id', '=', 'orders.id')
            ->leftJoin('invoices', 'order_transactions.invoice_id', '=', 'invoices.id')
            ->select([
                DB::raw("CONCAT('invoice-payment:', ".DB::getTablePrefix().'order_transactions.id) as id'),
                'order_transactions.id as native_id',
                DB::raw("'invoice_payment' as ledger_type"),
                DB::raw("'Invoice Payment' as type_label"),
                'order_transactions.transaction_id as transaction_ref',
                'order_transactions.created_at as transaction_date',
                DB::raw("CONCAT('Invoice #', COALESCE(".DB::getTablePrefix().'invoices.increment_id, '.DB::getTablePrefix()."order_transactions.invoice_id), ' / Order #', ".DB::getTablePrefix().'orders.increment_id) as source'),
                DB::raw('TRIM(CONCAT(COALESCE('.DB::getTablePrefix()."orders.customer_first_name, ''), ' ', COALESCE(".DB::getTablePrefix()."orders.customer_last_name, ''))) as counterparty"),
                'orders.customer_email as counterparty_secondary',
                'order_transactions.amount as amount',
                'order_transactions.status as status',
                'order_transactions.payment_method as payment_method',
                'order_transactions.invoice_id as invoice_id',
                'order_transactions.order_id as order_id',
                DB::raw('NULL as carrier_id'),
            ]);

        $union = $invoicePayments;

        if (Schema::hasTable('cod_remittances')) {
            $union->unionAll($this->basicCodRemittancesQuery());
        }

        if (Schema::hasTable('settlement_batches')) {
            $union->unionAll($this->advancedSettlementBatchesQuery());
        }

        $queryBuilder = DB::query()
            ->fromSub($union, 'transaction_ledger')
            ->select('transaction_ledger.*');

        $this->addFilter('id', 'transaction_ledger.id');
        $this->addFilter('transaction_ref', 'transaction_ledger.transaction_ref');
        $this->addFilter('ledger_type', 'transaction_ledger.ledger_type');
        $this->addFilter('type_label', 'transaction_ledger.type_label');
        $this->addFilter('source', 'transaction_ledger.source');
        $this->addFilter('counterparty', 'transaction_ledger.counterparty');
        $this->addFilter('amount', 'transaction_ledger.amount');
        $this->addFilter('status', 'transaction_ledger.status');
        $this->addFilter('transaction_date', 'transaction_ledger.transaction_date');
        $this->addFilter('payment_method', 'transaction_ledger.payment_method');
        $this->addFilter('carrier_id', 'transaction_ledger.carrier_id');

        return $queryBuilder;
    }

    protected function basicCodRemittancesQuery(): Builder
    {
        return DB::table('cod_remittances')
            ->leftJoin('shipment_carriers', 'cod_remittances.shipment_carrier_id', '=', 'shipment_carriers.id')
            ->select([
                DB::raw("CONCAT('cod-remittance:', ".DB::getTablePrefix().'cod_remittances.id) as id'),
                'cod_remittances.id as native_id',
                DB::raw("'cod_remittance' as ledger_type"),
                DB::raw("'COD Remittance' as type_label"),
                'cod_remittances.reference as transaction_ref',
                DB::raw('COALESCE('.DB::getTablePrefix().'cod_remittances.received_at, '.DB::getTablePrefix().'cod_remittances.created_at) as transaction_date'),
                DB::raw("CONCAT('COD Receipt / ', ".DB::getTablePrefix().'cod_remittances.reference) as source'),
                DB::raw('COALESCE('.DB::getTablePrefix()."shipment_carriers.name, 'Unknown Courier') as counterparty"),
                DB::raw('NULL as counterparty_secondary'),
                'cod_remittances.amount_received as amount',
                'cod_remittances.status as status',
                DB::raw("'COD Received' as payment_method"),
                DB::raw('NULL as invoice_id'),
                DB::raw('NULL as order_id'),
                'cod_remittances.shipment_carrier_id as carrier_id',
            ]);
    }

    protected function advancedSettlementBatchesQuery(): Builder
    {
        return DB::table('settlement_batches')
            ->leftJoin('shipment_carriers', 'settlement_batches.shipment_carrier_id', '=', 'shipment_carriers.id')
            ->where('settlement_batches.status', '<>', 'draft')
            ->select([
                DB::raw("CONCAT('settlement-batch:', ".DB::getTablePrefix().'settlement_batches.id) as id'),
                'settlement_batches.id as native_id',
                DB::raw("'settlement_batch' as ledger_type"),
                DB::raw("'COD Remittance' as type_label"),
                'settlement_batches.reference as transaction_ref',
                DB::raw('COALESCE('.DB::getTablePrefix().'settlement_batches.received_at, '.DB::getTablePrefix().'settlement_batches.remitted_at, '.DB::getTablePrefix().'settlement_batches.created_at) as transaction_date'),
                DB::raw("CONCAT('Settlement Batch / ', ".DB::getTablePrefix().'settlement_batches.reference) as source'),
                DB::raw('COALESCE('.DB::getTablePrefix()."shipment_carriers.name, 'Unknown Courier') as counterparty"),
                DB::raw('NULL as counterparty_secondary'),
                'settlement_batches.gross_remitted_amount as amount',
                'settlement_batches.status as status',
                'settlement_batches.payout_method as payment_method',
                DB::raw('NULL as invoice_id'),
                DB::raw('NULL as order_id'),
                'settlement_batches.shipment_carrier_id as carrier_id',
            ]);
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'transaction_ref',
            'label' => 'Transaction Ref',
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'transaction_date',
            'label' => 'Date',
            'type' => 'date',
            'filterable' => true,
            'filterable_type' => 'date_range',
            'sortable' => true,
            'closure' => fn ($row) => $row->transaction_date
                ? core()->formatDate($row->transaction_date, 'd M Y H:i')
                : 'N/A',
        ]);

        $this->addColumn([
            'index' => 'type_label',
            'label' => 'Type',
            'type' => 'string',
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => [
                ['label' => 'Invoice Payments', 'value' => 'Invoice Payment'],
                ['label' => 'COD Remittances', 'value' => 'COD Remittance'],
            ],
            'sortable' => true,
            'closure' => function ($row) {
                $classes = $row->ledger_type === 'invoice_payment'
                    ? 'border-blue-200 bg-blue-50 text-blue-700'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-700';

                return '<span class="inline-flex rounded-full border px-2 py-1 text-xs font-semibold '.$classes.'">'.$row->type_label.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'source',
            'label' => 'Source',
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'counterparty',
            'label' => 'Counterparty',
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => fn ($row) => trim($row->counterparty ?: '')
                ?: ($row->counterparty_secondary ?: 'N/A'),
        ]);

        $this->addColumn([
            'index' => 'amount',
            'label' => 'Amount',
            'type' => 'decimal',
            'filterable' => true,
            'sortable' => true,
            'closure' => fn ($row) => core()->formatBasePrice((float) $row->amount),
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => 'Status',
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => fn ($row) => str($row->status)->replace('_', ' ')->title()->value(),
        ]);
    }

    public function prepareActions(): void
    {
        if (! bouncer()->hasPermission('sales.transactions.view')) {
            return;
        }

        $this->addAction([
            'icon' => 'icon-view',
            'title' => trans('admin::app.sales.transactions.index.datagrid.view'),
            'method' => 'GET',
            'url' => fn ($row) => route('admin.sales.transactions.view', $row->id),
        ]);
    }
}
