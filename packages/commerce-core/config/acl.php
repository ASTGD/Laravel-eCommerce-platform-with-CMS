<?php

return [
    [
        'key'   => 'sales.pickup_points',
        'name'  => 'Pickup Points',
        'route' => 'admin.sales.pickup-points.index',
        'sort'  => 8,
    ], [
        'key'   => 'sales.pickup_points.create',
        'name'  => 'Create',
        'route' => 'admin.sales.pickup-points.create',
        'sort'  => 1,
    ], [
        'key'   => 'sales.pickup_points.edit',
        'name'  => 'Edit',
        'route' => 'admin.sales.pickup-points.edit',
        'sort'  => 2,
    ], [
        'key'   => 'sales.pickup_points.delete',
        'name'  => 'Delete',
        'route' => 'admin.sales.pickup-points.destroy',
        'sort'  => 3,
    ],
];
