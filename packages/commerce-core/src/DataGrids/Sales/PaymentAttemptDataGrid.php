<?php

namespace Platform\CommerceCore\DataGrids\Sales;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class PaymentAttemptDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('payment_attempts')
            ->leftJoin('orders', 'payment_attempts.order_id', '=', 'orders.id')
            ->addSelect(
                'payment_attempts.id',
                'payment_attempts.provider',
                'payment_attempts.method_code',
                'payment_attempts.order_id',
                'payment_attempts.cart_id',
                'payment_attempts.merchant_tran_id',
                'payment_attempts.gateway_tran_id',
                'payment_attempts.amount',
                'payment_attempts.currency',
                'payment_attempts.status',
                'payment_attempts.validation_status',
                'payment_attempts.finalized_via',
                'payment_attempts.finalized_at',
                'payment_attempts.callback_count',
                'payment_attempts.ipn_count',
                'payment_attempts.last_reconciled_at',
                'payment_attempts.last_reconciled_status',
                'payment_attempts.updated_at',
                'orders.increment_id as order_increment_id',
            );

        $this->addFilter('id', 'payment_attempts.id');
        $this->addFilter('provider', 'payment_attempts.provider');
        $this->addFilter('method_code', 'payment_attempts.method_code');
        $this->addFilter('order_id', 'payment_attempts.order_id');
        $this->addFilter('merchant_tran_id', 'payment_attempts.merchant_tran_id');
        $this->addFilter('gateway_tran_id', 'payment_attempts.gateway_tran_id');
        $this->addFilter('status', 'payment_attempts.status');
        $this->addFilter('validation_status', 'payment_attempts.validation_status');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'id',
            'label' => 'ID',
            'type' => 'integer',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'provider',
            'label' => 'Provider',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'method_code',
            'label' => 'Method',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'order_increment_id',
            'label' => 'Order',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'closure' => fn ($row) => $row->order_increment_id ? '#'.$row->order_increment_id : 'Not finalized',
        ]);

        $this->addColumn([
            'index' => 'merchant_tran_id',
            'label' => 'Merchant Transaction',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'gateway_tran_id',
            'label' => 'Gateway Transaction',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'amount',
            'label' => 'Amount',
            'type' => 'decimal',
            'sortable' => true,
            'closure' => fn ($row) => core()->formatBasePrice((float) $row->amount),
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => 'Status',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'validation_status',
            'label' => 'Validation',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'last_reconciled_at',
            'label' => 'Last Reconciled',
            'type' => 'datetime',
            'sortable' => true,
            'closure' => fn ($row) => $row->last_reconciled_at ? core()->formatDate($row->last_reconciled_at, 'd M Y H:i') : 'Never',
        ]);

        $this->addColumn([
            'index' => 'updated_at',
            'label' => 'Updated',
            'type' => 'datetime',
            'sortable' => true,
            'closure' => fn ($row) => core()->formatDate($row->updated_at, 'd M Y H:i'),
        ]);
    }

    public function prepareActions(): void
    {
        $this->addAction([
            'icon' => 'icon-view',
            'title' => 'View',
            'method' => 'GET',
            'url' => fn ($row) => route('admin.sales.payments.view', $row->id),
        ]);

        $this->addAction([
            'icon' => 'icon-refresh',
            'title' => 'Reconcile',
            'method' => 'POST',
            'url' => fn ($row) => route('admin.sales.payments.reconcile', $row->id),
        ]);
    }
}
