<?php

return [
    [
        'key' => 'sales.pickup_points',
        'name' => 'Pickup Points',
        'route' => 'admin.sales.pickup-points.index',
        'sort' => 8,
    ], [
        'key' => 'sales.pickup_points.create',
        'name' => 'Create',
        'route' => 'admin.sales.pickup-points.create',
        'sort' => 1,
    ], [
        'key' => 'sales.pickup_points.edit',
        'name' => 'Edit',
        'route' => 'admin.sales.pickup-points.edit',
        'sort' => 2,
    ], [
        'key' => 'sales.pickup_points.delete',
        'name' => 'Delete',
        'route' => 'admin.sales.pickup-points.destroy',
        'sort' => 3,
    ], [
        'key' => 'sales.payments',
        'name' => 'Payments',
        'route' => 'admin.sales.payments.index',
        'sort' => 9,
    ], [
        'key' => 'sales.payments.view',
        'name' => 'View',
        'route' => 'admin.sales.payments.view',
        'sort' => 1,
    ], [
        'key' => 'sales.payments.reconcile',
        'name' => 'Reconcile',
        'route' => 'admin.sales.payments.reconcile',
        'sort' => 2,
    ], [
        'key' => 'sales.carriers',
        'name' => 'Courier Services',
        'route' => 'admin.sales.carriers.index',
        'sort' => 10,
    ], [
        'key' => 'sales.carriers.create',
        'name' => 'Create',
        'route' => 'admin.sales.carriers.create',
        'sort' => 1,
    ], [
        'key' => 'sales.carriers.edit',
        'name' => 'Edit',
        'route' => 'admin.sales.carriers.edit',
        'sort' => 2,
    ], [
        'key' => 'sales.carriers.delete',
        'name' => 'Delete',
        'route' => 'admin.sales.carriers.destroy',
        'sort' => 3,
    ], [
        'key' => 'sales.to_ship',
        'name' => 'To Ship',
        'route' => 'admin.sales.to-ship.index',
        'sort' => 11,
    ], [
        'key' => 'sales.shipped_orders',
        'name' => 'In Delivery',
        'route' => 'admin.sales.shipped-orders.index',
        'sort' => 12,
    ], [
        'key' => 'sales.shipped_orders.mark_delivered',
        'name' => 'Mark Delivered',
        'route' => 'admin.sales.shipped-orders.mark-delivered',
        'sort' => 1,
    ], [
        'key' => 'sales.cod_receivables',
        'name' => 'COD Receivables',
        'route' => 'admin.sales.cod-receivables.index',
        'sort' => 13,
    ], [
        'key' => 'sales.shipment_operations',
        'name' => 'Shipment Ops',
        'route' => 'admin.sales.shipment-operations.index',
        'sort' => 14,
    ], [
        'key' => 'sales.shipment_operations.view',
        'name' => 'View',
        'route' => 'admin.sales.shipment-operations.view',
        'sort' => 1,
    ], [
        'key' => 'sales.shipment_operations.update_status',
        'name' => 'Update Status',
        'route' => 'admin.sales.shipment-operations.update-status',
        'sort' => 2,
    ], [
        'key' => 'sales.shipment_operations.add_event',
        'name' => 'Add Event',
        'route' => 'admin.sales.shipment-operations.store-event',
        'sort' => 3,
    ], [
        'key' => 'sales.shipment_operations.sync_tracking',
        'name' => 'Sync Tracking',
        'route' => 'admin.sales.shipment-operations.sync-tracking',
        'sort' => 4,
    ], [
        'key' => 'sales.shipment_operations.book_with_carrier',
        'name' => 'Book With Carrier',
        'route' => 'admin.sales.shipment-operations.book-with-carrier',
        'sort' => 5,
    ], [
        'key' => 'sales.shipment_operations.manage_booking_references',
        'name' => 'Manage Booking References',
        'route' => 'admin.sales.shipment-operations.update-booking-references',
        'sort' => 6,
    ], [
        'key' => 'sales.shipment_operations.record_failure',
        'name' => 'Record Delivery Failure',
        'route' => 'admin.sales.shipment-operations.record-delivery-failure',
        'sort' => 7,
    ], [
        'key' => 'sales.shipment_operations.approve_reattempt',
        'name' => 'Approve Reattempt',
        'route' => 'admin.sales.shipment-operations.approve-reattempt',
        'sort' => 8,
    ], [
        'key' => 'sales.shipment_operations.manage_returns',
        'name' => 'Manage Returns',
        'route' => 'admin.sales.shipment-operations.initiate-return',
        'sort' => 9,
    ], [
        'key' => 'sales.cod_settlements',
        'name' => 'COD Settlements',
        'route' => 'admin.sales.cod-settlements.index',
        'sort' => 15,
    ], [
        'key' => 'sales.cod_settlements.view',
        'name' => 'View',
        'route' => 'admin.sales.cod-settlements.view',
        'sort' => 1,
    ], [
        'key' => 'sales.cod_settlements.update',
        'name' => 'Update',
        'route' => 'admin.sales.cod-settlements.update',
        'sort' => 2,
    ], [
        'key' => 'sales.settlement_batches',
        'name' => 'Settlement Batches',
        'route' => 'admin.sales.settlement-batches.index',
        'sort' => 16,
    ], [
        'key' => 'sales.settlement_batches.create',
        'name' => 'Create',
        'route' => 'admin.sales.settlement-batches.create',
        'sort' => 1,
    ], [
        'key' => 'sales.settlement_batches.view',
        'name' => 'View',
        'route' => 'admin.sales.settlement-batches.view',
        'sort' => 2,
    ], [
        'key' => 'sales.settlement_batches.update',
        'name' => 'Update',
        'route' => 'admin.sales.settlement-batches.update',
        'sort' => 3,
    ], [
        'key' => 'sales.orders.confirm',
        'name' => 'Confirm Order',
        'route' => 'admin.sales.orders.confirm',
        'sort' => 17,
    ], [
        'key' => 'sales.orders.reconcile_payment',
        'name' => 'Reconcile Payment',
        'route' => 'admin.sales.orders.payments.reconcile',
        'sort' => 18,
    ], [
        'key' => 'sales.orders.refresh_refund_status',
        'name' => 'Refresh Refund Status',
        'route' => 'admin.sales.orders.payment_refunds.refresh',
        'sort' => 19,
    ], [
        'key' => 'affiliates',
        'name' => 'Affiliates',
        'route' => 'admin.affiliates.profiles.index',
        'sort' => 20,
    ], [
        'key' => 'affiliates.profiles',
        'name' => 'Affiliate Profiles',
        'route' => 'admin.affiliates.profiles.index',
        'sort' => 1,
    ], [
        'key' => 'affiliates.profiles.view',
        'name' => 'View',
        'route' => 'admin.affiliates.profiles.show',
        'sort' => 1,
    ], [
        'key' => 'affiliates.profiles.create',
        'name' => 'Create',
        'route' => 'admin.affiliates.profiles.create',
        'sort' => 2,
    ], [
        'key' => 'affiliates.profiles.approve',
        'name' => 'Approve',
        'route' => 'admin.affiliates.profiles.approve',
        'sort' => 3,
    ], [
        'key' => 'affiliates.profiles.reject',
        'name' => 'Reject',
        'route' => 'admin.affiliates.profiles.reject',
        'sort' => 4,
    ], [
        'key' => 'affiliates.profiles.suspend',
        'name' => 'Suspend',
        'route' => 'admin.affiliates.profiles.suspend',
        'sort' => 5,
    ], [
        'key' => 'affiliates.profiles.reactivate',
        'name' => 'Reactivate',
        'route' => 'admin.affiliates.profiles.reactivate',
        'sort' => 6,
    ], [
        'key' => 'affiliates.profiles.regenerate_referral_code',
        'name' => 'Regenerate Referral Code',
        'route' => 'admin.affiliates.profiles.regenerate-referral-code',
        'sort' => 7,
    ], [
        'key' => 'affiliates.payouts',
        'name' => 'Payouts',
        'route' => 'admin.affiliates.payouts.index',
        'sort' => 2,
    ], [
        'key' => 'affiliates.payouts.manage',
        'name' => 'Manage Payouts',
        'route' => 'admin.affiliates.payouts.update',
        'sort' => 1,
    ], [
        'key' => 'affiliates.reports',
        'name' => 'Reports',
        'route' => 'admin.affiliates.reports.index',
        'sort' => 3,
    ], [
        'key' => 'affiliates.settings',
        'name' => 'Settings',
        'route' => 'admin.affiliates.settings.index',
        'sort' => 4,
    ],
];
