<?php

namespace Platform\CommerceCore\DataGrids\Sales;

use Illuminate\Support\Facades\DB;
use Platform\CommerceCore\Models\ShipmentRecord;
use Webkul\DataGrid\DataGrid;

class ShipmentRecordDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('shipment_records')
            ->leftJoin('orders', 'shipment_records.order_id', '=', 'orders.id')
            ->leftJoin('shipment_carriers', 'shipment_records.shipment_carrier_id', '=', 'shipment_carriers.id')
            ->addSelect(
                'shipment_records.id',
                'shipment_records.status',
                'shipment_records.carrier_name_snapshot',
                'shipment_records.tracking_number',
                'shipment_records.destination_region',
                'shipment_records.recipient_name',
                'shipment_records.delivery_failure_reason',
                'shipment_records.requires_reattempt',
                'shipment_records.handed_over_at',
                'shipment_records.delivered_at',
                'orders.increment_id as order_increment_id',
                'shipment_carriers.name as carrier_name'
            );

        $this->addFilter('id', 'shipment_records.id');
        $this->addFilter('status', 'shipment_records.status');
        $this->addFilter('order_increment_id', 'orders.increment_id');
        $this->addFilter('tracking_number', 'shipment_records.tracking_number');
        $this->addFilter('carrier_name_snapshot', 'shipment_records.carrier_name_snapshot');
        $this->addFilter('recipient_name', 'shipment_records.recipient_name');
        $this->addFilter('destination_region', 'shipment_records.destination_region');
        $this->addFilter('delivery_failure_reason', 'shipment_records.delivery_failure_reason');
        $this->addFilter('requires_reattempt', 'shipment_records.requires_reattempt');

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
            'index' => 'status',
            'label' => 'Status',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => ShipmentRecord::statusLabels()[$row->status] ?? str($row->status)->replace('_', ' ')->title()->value(),
        ]);

        $this->addColumn([
            'index' => 'carrier_name_snapshot',
            'label' => 'Carrier',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->carrier_name ?? $row->carrier_name_snapshot ?: 'N/A',
        ]);

        $this->addColumn([
            'index' => 'tracking_number',
            'label' => 'Tracking',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'recipient_name',
            'label' => 'Recipient',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'destination_region',
            'label' => 'Region',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'delivery_failure_reason',
            'label' => 'Failure Reason',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->delivery_failure_reason
                ? (ShipmentRecord::failureReasonLabels()[$row->delivery_failure_reason]
                    ?? str($row->delivery_failure_reason)->replace('_', ' ')->title()->value())
                : 'N/A',
        ]);

        $this->addColumn([
            'index' => 'requires_reattempt',
            'label' => 'Reattempt',
            'type' => 'boolean',
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->requires_reattempt ? 'Required' : 'No',
        ]);

        $this->addColumn([
            'index' => 'handed_over_at',
            'label' => 'Handed Over',
            'type' => 'datetime',
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'datetime_range',
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
            'url' => fn ($row) => route('admin.sales.shipment-operations.view', $row->id),
        ]);
    }
}
