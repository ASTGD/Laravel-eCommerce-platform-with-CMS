<?php

namespace Platform\CommerceCore\DataGrids\Sales;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class PickupPointDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('pickup_points')
            ->addSelect(
                'id',
                'code',
                'name',
                'courier_name',
                'city',
                'country',
                'sort_order',
                'is_active',
            );

        $this->addFilter('id', 'pickup_points.id');
        $this->addFilter('code', 'pickup_points.code');
        $this->addFilter('name', 'pickup_points.name');
        $this->addFilter('courier_name', 'pickup_points.courier_name');
        $this->addFilter('city', 'pickup_points.city');
        $this->addFilter('country', 'pickup_points.country');
        $this->addFilter('is_active', 'pickup_points.is_active');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => 'ID',
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => 'Code',
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => 'Name',
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'courier_name',
            'label'      => 'Courier',
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'city',
            'label'      => 'City',
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'country',
            'label'      => 'Country',
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'closure'    => fn ($row) => core()->country_name($row->country),
        ]);

        $this->addColumn([
            'index'      => 'sort_order',
            'label'      => 'Sort',
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'is_active',
            'label'      => 'Status',
            'type'       => 'boolean',
            'sortable'   => true,
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
            'icon'   => 'icon-edit',
            'title'  => 'Edit',
            'method' => 'GET',
            'url'    => fn ($row) => route('admin.sales.pickup-points.edit', $row->id),
        ]);

        $this->addAction([
            'icon'   => 'icon-delete',
            'title'  => 'Delete',
            'method' => 'DELETE',
            'url'    => fn ($row) => route('admin.sales.pickup-points.destroy', $row->id),
        ]);
    }
}
