<?php

namespace Platform\CommerceCore\DataGrids\Sales;

use Illuminate\Support\Facades\DB;
use Platform\CommerceCore\Models\SettlementBatch;
use Webkul\DataGrid\DataGrid;

class SettlementBatchDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('settlement_batches')
            ->leftJoin('shipment_carriers', 'settlement_batches.shipment_carrier_id', '=', 'shipment_carriers.id')
            ->leftJoin('settlement_batch_items', 'settlement_batches.id', '=', 'settlement_batch_items.settlement_batch_id')
            ->leftJoin('cod_settlements', 'settlement_batch_items.cod_settlement_id', '=', 'cod_settlements.id')
            ->groupBy(
                'settlement_batches.id',
                'settlement_batches.reference',
                'settlement_batches.status',
                'settlement_batches.payout_method',
                'settlement_batches.gross_expected_amount',
                'settlement_batches.gross_remitted_amount',
                'settlement_batches.total_adjustment_amount',
                'settlement_batches.total_short_amount',
                'settlement_batches.net_amount',
                'settlement_batches.remitted_at',
                'settlement_batches.received_at',
                'shipment_carriers.name'
            )
            ->addSelect(
                'settlement_batches.id',
                'settlement_batches.reference',
                'settlement_batches.status',
                'settlement_batches.payout_method',
                'settlement_batches.gross_expected_amount',
                'settlement_batches.gross_remitted_amount',
                'settlement_batches.total_adjustment_amount',
                'settlement_batches.total_short_amount',
                'settlement_batches.net_amount',
                'settlement_batches.remitted_at',
                'settlement_batches.received_at',
                'shipment_carriers.name as carrier_name',
                DB::raw('COUNT(settlement_batch_items.id) as settlements_count'),
                DB::raw("SUM(CASE
                    WHEN cod_settlements.status IN ('short_settled', 'disputed', 'written_off')
                        OR settlement_batch_items.short_amount > 0
                    THEN 1 ELSE 0 END) as attention_items_count"),
                DB::raw('GREATEST(0, settlement_batches.gross_expected_amount - settlement_batches.gross_remitted_amount - settlement_batches.total_adjustment_amount) as reconciliation_gap_amount'),
            );

        $this->addFilter('id', 'settlement_batches.id');
        $this->addFilter('reference', 'settlement_batches.reference');
        $this->addFilter('status', 'settlement_batches.status');
        $this->addFilter('payout_method', 'settlement_batches.payout_method');
        $this->addFilter('carrier_name', 'shipment_carriers.name');

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
            'index' => 'reference',
            'label' => 'Reference',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'carrier_name',
            'label' => 'Carrier',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->carrier_name ?: 'N/A',
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => 'Status',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => SettlementBatch::statusLabels()[$row->status] ?? str($row->status)->replace('_', ' ')->title()->value(),
        ]);

        $this->addColumn([
            'index' => 'payout_method',
            'label' => 'Payout',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->payout_method ? str($row->payout_method)->replace('_', ' ')->title()->value() : 'N/A',
        ]);

        $this->addColumn([
            'index' => 'settlements_count',
            'label' => 'Settlements',
            'type' => 'integer',
            'sortable' => true,
            'filterable' => false,
        ]);

        $this->addColumn([
            'index' => 'attention_items_count',
            'label' => 'Attention Items',
            'type' => 'integer',
            'sortable' => true,
            'filterable' => false,
        ]);

        $this->addColumn([
            'index' => 'gross_expected_amount',
            'label' => 'Gross Expected',
            'type' => 'decimal',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'gross_remitted_amount',
            'label' => 'Gross Remitted',
            'type' => 'decimal',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'total_short_amount',
            'label' => 'Short',
            'type' => 'decimal',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'reconciliation_gap_amount',
            'label' => 'Gap',
            'type' => 'decimal',
            'sortable' => true,
            'filterable' => false,
        ]);

        $this->addColumn([
            'index' => 'net_amount',
            'label' => 'Net',
            'type' => 'decimal',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'remitted_at',
            'label' => 'Remitted',
            'type' => 'datetime',
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'datetime_range',
        ]);

        $this->addColumn([
            'index' => 'received_at',
            'label' => 'Received',
            'type' => 'datetime',
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'datetime_range',
        ]);
    }

    public function prepareActions(): void
    {
        $this->addAction([
            'icon' => 'icon-view',
            'title' => 'View',
            'method' => 'GET',
            'url' => fn ($row) => route('admin.sales.settlement-batches.view', $row->id),
        ]);
    }
}
