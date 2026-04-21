<?php

return [
    [
        'key'   => 'sales.pickup_points',
        'name'  => 'Pickup Points',
        'route' => 'admin.sales.pickup-points.index',
        'sort'  => 8,
        'icon'  => '',
    ], [
        'key'   => 'sales.payments',
        'name'  => 'Payments',
        'route' => 'admin.sales.payments.index',
        'sort'  => 9,
        'icon'  => '',
    ], [
        'key'            => 'shipments',
        'name'           => 'Shipments',
        'route'          => 'admin.sales.shipments.index',
        'sort'           => 2,
        'icon'           => 'icon-ship',
        'permission_key' => null,
    ], [
        'key'            => 'shipments.order_shipments',
        'name'           => 'Order Shipments',
        'route'          => 'admin.sales.shipments.index',
        'sort'           => 1,
        'icon'           => '',
        'permission_key' => 'sales.shipments',
    ], [
        'key'            => 'shipments.shipped_orders',
        'name'           => 'Shipped Orders',
        'route'          => 'admin.sales.shipped-orders.index',
        'sort'           => 2,
        'icon'           => '',
        'permission_key' => 'sales.shipped_orders',
    ], [
        'key'            => 'shipments.shipment_operations',
        'name'           => 'Shipment Ops',
        'route'          => 'admin.sales.shipment-operations.index',
        'sort'           => 3,
        'icon'           => '',
        'permission_key' => 'sales.shipment_operations',
    ], [
        'key'            => 'shipments.cod_settlements',
        'name'           => 'COD Settlements',
        'route'          => 'admin.sales.cod-settlements.index',
        'sort'           => 4,
        'icon'           => '',
        'permission_key' => 'sales.cod_settlements',
    ], [
        'key'            => 'shipments.settlement_batches',
        'name'           => 'Settlement Batches',
        'route'          => 'admin.sales.settlement-batches.index',
        'sort'           => 5,
        'icon'           => '',
        'permission_key' => 'sales.settlement_batches',
    ],
];
