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
    ], [
        'key'   => 'sales.payments',
        'name'  => 'Payments',
        'route' => 'admin.sales.payments.index',
        'sort'  => 9,
    ], [
        'key'   => 'sales.payments.view',
        'name'  => 'View',
        'route' => 'admin.sales.payments.view',
        'sort'  => 1,
    ], [
        'key'   => 'sales.payments.reconcile',
        'name'  => 'Reconcile',
        'route' => 'admin.sales.payments.reconcile',
        'sort'  => 2,
    ], [
        'key'   => 'sales.orders.reconcile_payment',
        'name'  => 'Reconcile Payment',
        'route' => 'admin.sales.orders.payments.reconcile',
        'sort'  => 10,
    ],
];
