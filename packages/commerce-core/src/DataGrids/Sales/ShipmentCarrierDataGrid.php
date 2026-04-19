<?php

namespace Platform\CommerceCore\DataGrids\Sales;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ShipmentCarrierDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('shipment_carriers')
            ->addSelect(
                'id',
                'code',
                'name',
                'contact_phone',
                'integration_driver',
                'tracking_sync_enabled',
                'default_payout_method',
                'supports_cod',
                'sort_order',
                'is_active',
            );

        $this->addFilter('id', 'shipment_carriers.id');
        $this->addFilter('code', 'shipment_carriers.code');
        $this->addFilter('name', 'shipment_carriers.name');
        $this->addFilter('contact_phone', 'shipment_carriers.contact_phone');
        $this->addFilter('integration_driver', 'shipment_carriers.integration_driver');
        $this->addFilter('tracking_sync_enabled', 'shipment_carriers.tracking_sync_enabled');
        $this->addFilter('default_payout_method', 'shipment_carriers.default_payout_method');
        $this->addFilter('supports_cod', 'shipment_carriers.supports_cod');
        $this->addFilter('is_active', 'shipment_carriers.is_active');

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
            'index' => 'code',
            'label' => 'Code',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => 'Name',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'contact_phone',
            'label' => 'Phone',
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'integration_driver',
            'label' => 'Tracking Driver',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->integration_driver
                ? str($row->integration_driver)->replace('_', ' ')->title()->value()
                : 'Manual',
        ]);

        $this->addColumn([
            'index' => 'tracking_sync_enabled',
            'label' => 'Sync',
            'type' => 'boolean',
            'sortable' => true,
            'filterable' => true,
            'filterable_options' => [
                ['label' => 'Enabled', 'value' => 1],
                ['label' => 'Disabled', 'value' => 0],
            ],
        ]);

        $this->addColumn([
            'index' => 'default_payout_method',
            'label' => 'Payout',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->default_payout_method
                ? str($row->default_payout_method)->replace('_', ' ')->title()->value()
                : 'N/A',
        ]);

        $this->addColumn([
            'index' => 'supports_cod',
            'label' => 'COD',
            'type' => 'boolean',
            'sortable' => true,
            'filterable' => true,
            'filterable_options' => [
                ['label' => 'Supports COD', 'value' => 1],
                ['label' => 'No COD', 'value' => 0],
            ],
        ]);

        $this->addColumn([
            'index' => 'sort_order',
            'label' => 'Sort',
            'type' => 'integer',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'is_active',
            'label' => 'Status',
            'type' => 'boolean',
            'sortable' => true,
            'filterable' => true,
            'filterable_options' => [
                ['label' => 'Active', 'value' => 1],
                ['label' => 'Inactive', 'value' => 0],
            ],
        ]);
    }

    public function prepareActions(): void
    {
        $this->addAction([
            'icon' => 'icon-edit',
            'title' => 'Edit',
            'method' => 'GET',
            'url' => fn ($row) => route('admin.sales.carriers.edit', $row->id),
        ]);

        $this->addAction([
            'icon' => 'icon-delete',
            'title' => 'Delete',
            'method' => 'DELETE',
            'url' => fn ($row) => route('admin.sales.carriers.destroy', $row->id),
        ]);
    }
}
