<?php

use Illuminate\Support\Facades\Route;
use Platform\CommerceCore\Http\Controllers\Admin\AffiliatePayoutController;
use Platform\CommerceCore\Http\Controllers\Admin\AffiliateProfileController;
use Platform\CommerceCore\Http\Controllers\Admin\AffiliateReportController;
use Platform\CommerceCore\Http\Controllers\Admin\AffiliateSettingsController;
use Platform\CommerceCore\Http\Controllers\Admin\CodSettlementController;
use Platform\CommerceCore\Http\Controllers\Admin\ManualCodReceivableController;
use Platform\CommerceCore\Http\Controllers\Admin\ManualShippedOrderController;
use Platform\CommerceCore\Http\Controllers\Admin\ManualToShipController;
use Platform\CommerceCore\Http\Controllers\Admin\OrderStatusController;
use Platform\CommerceCore\Http\Controllers\Admin\PaymentAttemptController;
use Platform\CommerceCore\Http\Controllers\Admin\PaymentRefundController;
use Platform\CommerceCore\Http\Controllers\Admin\PickupPointController;
use Platform\CommerceCore\Http\Controllers\Admin\SettlementBatchController;
use Platform\CommerceCore\Http\Controllers\Admin\ShipmentCarrierController;
use Platform\CommerceCore\Http\Controllers\Admin\ShipmentRecordController;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

Route::group([
    'middleware' => ['admin', NoCacheMiddleware::class],
    'prefix' => config('app.admin_url'),
], function () {
    Route::prefix('affiliates')
        ->group(function () {
            Route::prefix('profiles')
                ->controller(AffiliateProfileController::class)
                ->group(function () {
                    Route::get('', 'index')->middleware('platform.acl:affiliates.profiles')->name('admin.affiliates.profiles.index');
                    Route::get('{affiliateProfile}', 'show')->middleware('platform.acl:affiliates.profiles.view')->name('admin.affiliates.profiles.show');
                    Route::post('{affiliateProfile}/approve', 'approve')->middleware('platform.acl:affiliates.profiles.approve')->name('admin.affiliates.profiles.approve');
                    Route::post('{affiliateProfile}/reject', 'reject')->middleware('platform.acl:affiliates.profiles.reject')->name('admin.affiliates.profiles.reject');
                    Route::post('{affiliateProfile}/suspend', 'suspend')->middleware('platform.acl:affiliates.profiles.suspend')->name('admin.affiliates.profiles.suspend');
                    Route::post('{affiliateProfile}/reactivate', 'reactivate')->middleware('platform.acl:affiliates.profiles.reactivate')->name('admin.affiliates.profiles.reactivate');
                    Route::post('{affiliateProfile}/payouts', 'storePayout')->middleware('platform.acl:affiliates.payouts.manage')->name('admin.affiliates.profiles.payouts.store');
                });

            Route::prefix('payouts')
                ->controller(AffiliatePayoutController::class)
                ->group(function () {
                    Route::get('', 'index')->middleware('platform.acl:affiliates.payouts')->name('admin.affiliates.payouts.index');
                    Route::post('{affiliatePayout}/approve', 'approve')->middleware('platform.acl:affiliates.payouts.manage')->name('admin.affiliates.payouts.approve');
                    Route::post('{affiliatePayout}/mark-paid', 'markPaid')->middleware('platform.acl:affiliates.payouts.manage')->name('admin.affiliates.payouts.mark-paid');
                    Route::post('{affiliatePayout}/reject', 'reject')->middleware('platform.acl:affiliates.payouts.manage')->name('admin.affiliates.payouts.reject');
                });

            Route::get('reports', [AffiliateReportController::class, 'index'])
                ->middleware('platform.acl:affiliates.reports')
                ->name('admin.affiliates.reports.index');

            Route::controller(AffiliateSettingsController::class)
                ->prefix('settings')
                ->middleware('platform.acl:affiliates.settings')
                ->group(function () {
                    Route::get('', 'index')->name('admin.affiliates.settings.index');
                    Route::post('', 'update')->name('admin.affiliates.settings.update');
                });
        });

    Route::prefix('sales/pickup-points')
        ->controller(PickupPointController::class)
        ->group(function () {
            Route::get('', 'index')->middleware('platform.acl:sales.pickup_points')->name('admin.sales.pickup-points.index');
            Route::get('create', 'create')->middleware('platform.acl:sales.pickup_points.create')->name('admin.sales.pickup-points.create');
            Route::post('', 'store')->middleware('platform.acl:sales.pickup_points.create')->name('admin.sales.pickup-points.store');
            Route::get('{pickupPoint}/edit', 'edit')->middleware('platform.acl:sales.pickup_points.edit')->name('admin.sales.pickup-points.edit');
            Route::put('{pickupPoint}', 'update')->middleware('platform.acl:sales.pickup_points.edit')->name('admin.sales.pickup-points.update');
            Route::delete('{pickupPoint}', 'destroy')->middleware('platform.acl:sales.pickup_points.delete')->name('admin.sales.pickup-points.destroy');
        });

    Route::prefix('sales/payments')
        ->controller(PaymentAttemptController::class)
        ->group(function () {
            Route::get('', 'index')->middleware('platform.acl:sales.payments')->name('admin.sales.payments.index');
            Route::get('{paymentAttempt}', 'show')->middleware('platform.acl:sales.payments.view')->name('admin.sales.payments.view');
            Route::post('{paymentAttempt}/reconcile', 'reconcile')->middleware('platform.acl:sales.payments.reconcile')->name('admin.sales.payments.reconcile');
        });

    Route::prefix('sales/carriers')
        ->controller(ShipmentCarrierController::class)
        ->group(function () {
            Route::get('', 'index')->middleware('platform.acl:sales.carriers')->name('admin.sales.carriers.index');
            Route::get('create', 'create')->middleware('platform.acl:sales.carriers.create')->name('admin.sales.carriers.create');
            Route::post('', 'store')->middleware('platform.acl:sales.carriers.create')->name('admin.sales.carriers.store');
            Route::get('{carrier}/edit', 'edit')->middleware('platform.acl:sales.carriers.edit')->name('admin.sales.carriers.edit');
            Route::put('{carrier}', 'update')->middleware('platform.acl:sales.carriers.edit')->name('admin.sales.carriers.update');
            Route::delete('{carrier}', 'destroy')->middleware('platform.acl:sales.carriers.delete')->name('admin.sales.carriers.destroy');
        });

    Route::prefix('sales/to-ship')
        ->controller(ManualToShipController::class)
        ->middleware('commerce.shipping-mode:manual_basic')
        ->group(function () {
            Route::get('', 'index')->middleware('platform.acl:sales.to_ship')->name('admin.sales.to-ship.index');
            Route::get('{order}/booking-draft', 'showBookingDraft')->middleware('platform.acl:sales.to_ship')->name('admin.sales.to-ship.booking-draft.show');
            Route::post('{order}/booking-draft', 'saveBookingDraft')->middleware('platform.acl:sales.to_ship')->name('admin.sales.to-ship.booking-draft.save');
            Route::post('{order}/booking-draft/print/{document}', 'printDraftDocuments')->middleware('platform.acl:sales.to_ship')->name('admin.sales.to-ship.booking-draft.print');
            Route::get('{order}/booking-draft/document/{document}', 'showDraftDocument')->middleware('platform.acl:sales.to_ship')->name('admin.sales.to-ship.booking-draft.document');
            Route::post('{order}/booking-draft/complete', 'completeBooking')->middleware('platform.acl:sales.to_ship')->name('admin.sales.to-ship.booking-draft.complete');
            Route::post('{order}/print/{document}', 'printDocuments')->middleware('platform.acl:sales.to_ship')->name('admin.sales.to-ship.print-documents');
            Route::post('handover-batches', 'createHandoverBatch')->middleware('platform.acl:sales.to_ship')->name('admin.sales.to-ship.create-handover-batch');
            Route::post('handover-batches/print', 'printManifest')->middleware('platform.acl:sales.to_ship')->name('admin.sales.to-ship.print-manifest');
            Route::get('handover-batches/{handoverBatch}/sheet', 'showHandoverSheet')->middleware('platform.acl:sales.to_ship')->name('admin.sales.to-ship.handover-sheet.preview');
            Route::get('handover-batches/{handoverBatch}/sheet/pdf', 'downloadHandoverSheetPdf')->middleware('platform.acl:sales.to_ship')->name('admin.sales.to-ship.handover-sheet.pdf');
            Route::post('handover-batches/confirm', 'confirmHandover')->middleware('platform.acl:sales.to_ship')->name('admin.sales.to-ship.confirm-handover');
        });

    Route::prefix('sales/shipped-orders')
        ->controller(ManualShippedOrderController::class)
        ->middleware('commerce.shipping-mode:manual_basic')
        ->group(function () {
            Route::get('', 'index')->middleware('platform.acl:sales.shipped_orders')->name('admin.sales.shipped-orders.index');
            Route::post('{shipmentRecord}/mark-delivered', 'markDelivered')->middleware('platform.acl:sales.shipped_orders.mark_delivered')->name('admin.sales.shipped-orders.mark-delivered');
        });

    Route::prefix('sales/cod-receivables')
        ->controller(ManualCodReceivableController::class)
        ->middleware('commerce.shipping-mode:manual_basic')
        ->group(function () {
            Route::get('', 'index')->middleware('platform.acl:sales.cod_receivables')->name('admin.sales.cod-receivables.index');
            Route::post('record-received', 'recordReceived')->middleware('platform.acl:sales.cod_receivables')->name('admin.sales.cod-receivables.record-received');
        });

    Route::prefix('sales/shipment-operations')
        ->controller(ShipmentRecordController::class)
        ->middleware('commerce.shipping-mode:advanced_pro')
        ->group(function () {
            Route::get('', 'index')->middleware('platform.acl:sales.shipment_operations')->name('admin.sales.shipment-operations.index');
            Route::get('{shipmentRecord}', 'show')->middleware('platform.acl:sales.shipment_operations.view')->name('admin.sales.shipment-operations.view');
            Route::post('{shipmentRecord}/status', 'updateStatus')->middleware('platform.acl:sales.shipment_operations.update_status')->name('admin.sales.shipment-operations.update-status');
            Route::post('{shipmentRecord}/events', 'storeEvent')->middleware('platform.acl:sales.shipment_operations.add_event')->name('admin.sales.shipment-operations.store-event');
            Route::post('{shipmentRecord}/sync-tracking', 'syncTracking')->middleware('platform.acl:sales.shipment_operations.sync_tracking')->name('admin.sales.shipment-operations.sync-tracking');
            Route::post('{shipmentRecord}/book-with-carrier', 'createCarrierBooking')->middleware('platform.acl:sales.shipment_operations.book_with_carrier')->name('admin.sales.shipment-operations.book-with-carrier');
            Route::post('{shipmentRecord}/booking-references', 'updateBookingReferences')->middleware('platform.acl:sales.shipment_operations.manage_booking_references')->name('admin.sales.shipment-operations.update-booking-references');
            Route::post('{shipmentRecord}/delivery-failure', 'recordDeliveryFailure')->middleware('platform.acl:sales.shipment_operations.record_failure')->name('admin.sales.shipment-operations.record-delivery-failure');
            Route::post('{shipmentRecord}/reattempt', 'approveReattempt')->middleware('platform.acl:sales.shipment_operations.approve_reattempt')->name('admin.sales.shipment-operations.approve-reattempt');
            Route::post('{shipmentRecord}/return/initiate', 'initiateReturn')->middleware('platform.acl:sales.shipment_operations.manage_returns')->name('admin.sales.shipment-operations.initiate-return');
            Route::post('{shipmentRecord}/return/complete', 'completeReturn')->middleware('platform.acl:sales.shipment_operations.manage_returns')->name('admin.sales.shipment-operations.complete-return');
        });

    Route::prefix('sales/cod-settlements')
        ->controller(CodSettlementController::class)
        ->middleware('commerce.shipping-mode:advanced_pro')
        ->group(function () {
            Route::get('', 'index')->middleware('platform.acl:sales.cod_settlements')->name('admin.sales.cod-settlements.index');
            Route::get('{codSettlement}', 'show')->middleware('platform.acl:sales.cod_settlements.view')->name('admin.sales.cod-settlements.view');
            Route::post('{codSettlement}', 'update')->middleware('platform.acl:sales.cod_settlements.update')->name('admin.sales.cod-settlements.update');
        });

    Route::prefix('sales/settlement-batches')
        ->controller(SettlementBatchController::class)
        ->middleware('commerce.shipping-mode:advanced_pro')
        ->group(function () {
            Route::get('', 'index')->middleware('platform.acl:sales.settlement_batches')->name('admin.sales.settlement-batches.index');
            Route::get('create', 'create')->middleware('platform.acl:sales.settlement_batches.create')->name('admin.sales.settlement-batches.create');
            Route::post('', 'store')->middleware('platform.acl:sales.settlement_batches.create')->name('admin.sales.settlement-batches.store');
            Route::get('import', 'import')->middleware('platform.acl:sales.settlement_batches.create')->name('admin.sales.settlement-batches.import');
            Route::post('import', 'storeImport')->middleware('platform.acl:sales.settlement_batches.create')->name('admin.sales.settlement-batches.import-store');
            Route::get('{settlementBatch}', 'show')->middleware('platform.acl:sales.settlement_batches.view')->name('admin.sales.settlement-batches.view');
            Route::post('{settlementBatch}', 'update')->middleware('platform.acl:sales.settlement_batches.update')->name('admin.sales.settlement-batches.update');
        });

    Route::post('sales/orders/{order}/payments/reconcile', [PaymentAttemptController::class, 'reconcileOrder'])
        ->middleware('platform.acl:sales.orders.reconcile_payment')
        ->name('admin.sales.orders.payments.reconcile');

    Route::post('sales/orders/{order}/confirm', [OrderStatusController::class, 'confirm'])
        ->middleware('platform.acl:sales.orders.confirm')
        ->name('admin.sales.orders.confirm');

    Route::post('sales/orders/payment-refunds/{paymentRefund}/refresh', [PaymentRefundController::class, 'refresh'])
        ->middleware('platform.acl:sales.orders.refresh_refund_status')
        ->name('admin.sales.orders.payment_refunds.refresh');
});
