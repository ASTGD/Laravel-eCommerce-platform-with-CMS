<?php

namespace Platform\CommerceCore\DataGrids\Sales;

use Illuminate\Support\Facades\DB;
use Platform\CommerceCore\Models\CodSettlement;
use Webkul\DataGrid\DataGrid;

class CodSettlementDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('cod_settlements')
            ->leftJoin('orders', 'cod_settlements.order_id', '=', 'orders.id')
            ->leftJoin('shipment_records', 'cod_settlements.shipment_record_id', '=', 'shipment_records.id')
            ->leftJoin('shipment_carriers', 'cod_settlements.shipment_carrier_id', '=', 'shipment_carriers.id')
            ->addSelect(
                'cod_settlements.id',
                'cod_settlements.status',
                'cod_settlements.expected_amount',
                'cod_settlements.collected_amount',
                'cod_settlements.remitted_amount',
                'cod_settlements.short_amount',
                'cod_settlements.disputed_amount',
                'cod_settlements.net_amount',
                'cod_settlements.remitted_at',
                DB::raw('GREATEST(0, cod_settlements.net_amount - cod_settlements.remitted_amount - cod_settlements.disputed_amount) as outstanding_amount'),
                DB::raw("CASE
                    WHEN cod_settlements.status IN ('short_settled', 'disputed', 'written_off')
                        OR cod_settlements.short_amount > 0
                        OR cod_settlements.disputed_amount > 0
                        OR GREATEST(0, cod_settlements.net_amount - cod_settlements.remitted_amount - cod_settlements.disputed_amount) > 0
                    THEN 1 ELSE 0 END as requires_attention"),
                'orders.increment_id as order_increment_id',
                'shipment_records.id as shipment_record_public_id',
                'shipment_records.status as shipment_status',
                'shipment_records.delivered_at',
                'shipment_carriers.name as carrier_name'
            );

        $this->addFilter('id', 'cod_settlements.id');
        $this->addFilter('status', 'cod_settlements.status');
        $this->addFilter('order_increment_id', 'orders.increment_id');
        $this->addFilter('shipment_record_public_id', 'shipment_records.id');
        $this->addFilter('shipment_status', 'shipment_records.status');
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
            'index' => 'order_increment_id',
            'label' => 'Order',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'shipment_record_public_id',
            'label' => 'Shipment Ops',
            'type' => 'integer',
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
            'index' => 'shipment_status',
            'label' => 'Shipment Status',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->shipment_status
                ? str($row->shipment_status)->replace('_', ' ')->title()->value()
                : 'N/A',
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => 'Settlement Status',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => CodSettlement::statusLabels()[$row->status] ?? str($row->status)->replace('_', ' ')->title()->value(),
        ]);

        $this->addColumn([
            'index' => 'expected_amount',
            'label' => 'Expected',
            'type' => 'decimal',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'collected_amount',
            'label' => 'Collected',
            'type' => 'decimal',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'remitted_amount',
            'label' => 'Remitted',
            'type' => 'decimal',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'short_amount',
            'label' => 'Short',
            'type' => 'decimal',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'outstanding_amount',
            'label' => 'Outstanding',
            'type' => 'decimal',
            'sortable' => true,
            'filterable' => false,
        ]);

        $this->addColumn([
            'index' => 'requires_attention',
            'label' => 'Attention',
            'type' => 'boolean',
            'sortable' => true,
            'filterable' => false,
            'closure' => fn ($row) => (int) $row->requires_attention === 1 ? 'Yes' : 'No',
        ]);

        $this->addColumn([
            'index' => 'net_amount',
            'label' => 'Net',
            'type' => 'decimal',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'delivered_at',
            'label' => 'Delivered',
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
            'url' => fn ($row) => route('admin.sales.cod-settlements.view', $row->id),
        ]);
    }
}
