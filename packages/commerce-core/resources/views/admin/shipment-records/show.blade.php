<x-admin::layouts>
    <x-slot:title>
        Shipment Ops #{{ $shipmentRecord->id }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Shipment Ops #{{ $shipmentRecord->id }}
        </p>

        <div class="flex items-center gap-x-2.5">
            <a
                href="{{ route('admin.sales.shipment-operations.index') }}"
                class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
            >
                Back
            </a>

            @if ($shipmentRecord->order)
                <a
                    href="{{ route('admin.sales.orders.view', $shipmentRecord->order_id) }}"
                    class="primary-button"
                >
                    View Order
                </a>
            @endif
        </div>
    </div>

    <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
        <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-4 max-md:flex-wrap">
                    <div>
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            Operational Summary
                        </p>

                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->status_label }}
                        </p>
                    </div>

                    <div class="grid gap-3 md:min-w-[340px]">
                        @if ($shipmentRecord->carrier)
                            <div class="grid gap-2 rounded border border-gray-200 p-3 dark:border-gray-800">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                    Tracking Sync
                                </p>

                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    <p>
                                        Driver:
                                        <span class="font-medium text-gray-800 dark:text-white">
                                            {{ str($shipmentRecord->carrier->trackingDriver())->replace('_', ' ')->title() }}
                                        </span>
                                    </p>

                                    <p>
                                        Sync Enabled:
                                        <span class="font-medium text-gray-800 dark:text-white">
                                            {{ $shipmentRecord->carrier->tracking_sync_enabled ? 'Yes' : 'No' }}
                                        </span>
                                    </p>

                                    <p>
                                        Last Sync:
                                        <span class="font-medium text-gray-800 dark:text-white">
                                            {{ $shipmentRecord->last_tracking_synced_at?->format('d M Y H:i') ?? 'Never' }}
                                        </span>
                                    </p>

                                    <p>
                                        Last Result:
                                        <span class="font-medium text-gray-800 dark:text-white">
                                            {{ $shipmentRecord->last_tracking_sync_status ? str($shipmentRecord->last_tracking_sync_status)->replace('_', ' ')->title() : 'Not synced yet' }}
                                        </span>
                                    </p>
                                </div>

                                @if ($shipmentRecord->last_tracking_sync_message)
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ $shipmentRecord->last_tracking_sync_message }}
                                    </p>
                                @endif

                                @if (bouncer()->hasPermission('sales.shipment_operations.sync_tracking'))
                                    <form
                                        method="POST"
                                        action="{{ route('admin.sales.shipment-operations.sync-tracking', $shipmentRecord) }}"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="secondary-button"
                                        >
                                            Sync Carrier Tracking
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif

                        @if (
                            $canCreateCarrierBooking
                            && bouncer()->hasPermission('sales.shipment_operations.book_with_carrier')
                        )
                            <form
                                method="POST"
                                action="{{ route('admin.sales.shipment-operations.book-with-carrier', $shipmentRecord) }}"
                                class="grid gap-2 rounded border border-gray-200 p-3 dark:border-gray-800"
                            >
                                @csrf

                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                    Courier Booking
                                </p>

                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    Create the Steadfast booking directly from Shipment Ops and persist the returned consignment and tracking identifiers.
                                </p>

                                <textarea
                                    name="note"
                                    rows="2"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                    placeholder="Optional courier note"
                                >{{ old('note') }}</textarea>

                                <button
                                    type="submit"
                                    class="secondary-button"
                                    @disabled($shipmentRecord->carrier_consignment_id)
                                >
                                    {{ $shipmentRecord->carrier_consignment_id ? 'Carrier Booking Already Created' : 'Book With Steadfast' }}
                                </button>
                            </form>
                        @endif

                        @if (bouncer()->hasPermission('sales.shipment_operations.manage_booking_references'))
                            <form
                                method="POST"
                                action="{{ route('admin.sales.shipment-operations.update-booking-references', $shipmentRecord) }}"
                                class="grid gap-2 rounded border border-gray-200 p-3 dark:border-gray-800"
                            >
                                @csrf

                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                    Carrier Booking References
                                </p>

                                <input
                                    type="text"
                                    name="carrier_booking_reference"
                                    value="{{ old('carrier_booking_reference', $shipmentRecord->carrier_booking_reference) }}"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                    placeholder="Carrier booking reference"
                                >

                                <input
                                    type="text"
                                    name="carrier_consignment_id"
                                    value="{{ old('carrier_consignment_id', $shipmentRecord->carrier_consignment_id) }}"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                    placeholder="Carrier consignment ID"
                                >

                                <input
                                    type="text"
                                    name="carrier_invoice_reference"
                                    value="{{ old('carrier_invoice_reference', $shipmentRecord->carrier_invoice_reference) }}"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                    placeholder="Carrier invoice reference"
                                >

                                <input
                                    type="datetime-local"
                                    name="carrier_booked_at"
                                    value="{{ old('carrier_booked_at', $shipmentRecord->carrier_booked_at?->format('Y-m-d\\TH:i')) }}"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                >

                                <textarea
                                    name="note"
                                    rows="2"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                    placeholder="Optional note"
                                >{{ old('note') }}</textarea>

                                <button
                                    type="submit"
                                    class="secondary-button"
                                >
                                    Save Booking References
                                </button>
                            </form>
                        @endif

                        @if (bouncer()->hasPermission('sales.shipment_operations.update_status'))
                            <form
                                method="POST"
                                action="{{ route('admin.sales.shipment-operations.update-status', $shipmentRecord) }}"
                                class="grid gap-2 rounded border border-gray-200 p-3 dark:border-gray-800"
                            >
                                @csrf

                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                    Update Status
                                </p>

                                <select
                                    name="status"
                                    class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                                >
                                    @foreach ($statusOptions as $status => $label)
                                        <option
                                            value="{{ $status }}"
                                            @selected(old('status', $shipmentRecord->status) === $status)
                                        >
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                <textarea
                                    name="note"
                                    rows="2"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                    placeholder="Optional note"
                                >{{ old('note') }}</textarea>

                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    Update Shipment Status
                                </button>
                            </form>
                        @endif

                        @if (bouncer()->hasPermission('sales.shipment_operations.record_failure'))
                            <form
                                method="POST"
                                action="{{ route('admin.sales.shipment-operations.record-delivery-failure', $shipmentRecord) }}"
                                class="grid gap-2 rounded border border-gray-200 p-3 dark:border-gray-800"
                            >
                                @csrf

                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                    Record Delivery Failure
                                </p>

                                <select
                                    name="failure_reason"
                                    class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                                >
                                    <option value="">
                                        Select failure reason
                                    </option>

                                    @foreach ($failureReasonOptions as $failureReason => $label)
                                        <option
                                            value="{{ $failureReason }}"
                                            @selected(old('failure_reason', $shipmentRecord->delivery_failure_reason) === $failureReason)
                                        >
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input
                                        type="checkbox"
                                        name="requires_reattempt"
                                        value="1"
                                        @checked(old('requires_reattempt', $shipmentRecord->requires_reattempt))
                                    >

                                    Reattempt required
                                </label>

                                <textarea
                                    name="note"
                                    rows="2"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                    placeholder="Reason or courier note"
                                >{{ old('note') }}</textarea>

                                <button
                                    type="submit"
                                    class="secondary-button"
                                >
                                    Save Delivery Failure
                                </button>
                            </form>
                        @endif

                        @if (
                            $shipmentRecord->requires_reattempt
                            && bouncer()->hasPermission('sales.shipment_operations.approve_reattempt')
                        )
                            <form
                                method="POST"
                                action="{{ route('admin.sales.shipment-operations.approve-reattempt', $shipmentRecord) }}"
                                class="grid gap-2 rounded border border-gray-200 p-3 dark:border-gray-800"
                            >
                                @csrf

                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                    Approve Reattempt
                                </p>

                                <textarea
                                    name="note"
                                    rows="2"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                    placeholder="Optional reattempt note"
                                >{{ old('reattempt_note') }}</textarea>

                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    Approve Reattempt
                                </button>
                            </form>
                        @endif

                        @if (
                            $shipmentRecord->status === \Platform\CommerceCore\Models\ShipmentRecord::STATUS_DELIVERY_FAILED
                            && ! $shipmentRecord->return_initiated_at
                            && ! $shipmentRecord->returned_at
                            && bouncer()->hasPermission('sales.shipment_operations.manage_returns')
                        )
                            <form
                                method="POST"
                                action="{{ route('admin.sales.shipment-operations.initiate-return', $shipmentRecord) }}"
                                class="grid gap-2 rounded border border-gray-200 p-3 dark:border-gray-800"
                            >
                                @csrf

                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                    Initiate Return to Origin
                                </p>

                                <textarea
                                    name="note"
                                    rows="2"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                    placeholder="Optional return note"
                                >{{ old('return_note') }}</textarea>

                                <button
                                    type="submit"
                                    class="secondary-button"
                                >
                                    Initiate Return
                                </button>
                            </form>
                        @endif

                        @if (
                            $shipmentRecord->return_initiated_at
                            && ! $shipmentRecord->returned_at
                            && bouncer()->hasPermission('sales.shipment_operations.manage_returns')
                        )
                            <form
                                method="POST"
                                action="{{ route('admin.sales.shipment-operations.complete-return', $shipmentRecord) }}"
                                class="grid gap-2 rounded border border-gray-200 p-3 dark:border-gray-800"
                            >
                                @csrf

                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                    Complete Return
                                </p>

                                <textarea
                                    name="note"
                                    rows="2"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                    placeholder="Optional completion note"
                                >{{ old('return_complete_note') }}</textarea>

                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    Complete Return
                                </button>
                            </form>
                        @endif

                        @if (bouncer()->hasPermission('sales.shipment_operations.add_event'))
                            <form
                                method="POST"
                                action="{{ route('admin.sales.shipment-operations.store-event', $shipmentRecord) }}"
                                class="grid gap-2 rounded border border-gray-200 p-3 dark:border-gray-800"
                            >
                                @csrf

                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                    Log Operational Event
                                </p>

                                <select
                                    name="event_type"
                                    class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                                >
                                    <option value="">
                                        Select event
                                    </option>

                                    @foreach ($eventTypeOptions as $eventType => $label)
                                        <option
                                            value="{{ $eventType }}"
                                            @selected(old('event_type') === $eventType)
                                        >
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                <select
                                    name="status_after_event"
                                    class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                                >
                                    <option value="">
                                        Keep current status
                                    </option>

                                    @foreach ($statusOptions as $status => $label)
                                        <option
                                            value="{{ $status }}"
                                            @selected(old('status_after_event') === $status)
                                        >
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                <textarea
                                    name="note"
                                    rows="2"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                    placeholder="Optional note"
                                >{{ old('note') }}</textarea>

                                <button
                                    type="submit"
                                    class="secondary-button"
                                >
                                    Record Shipment Event
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4 max-md:grid-cols-1">
                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Carrier
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->carrier?->name ?? $shipmentRecord->carrier_name_snapshot ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Tracking Number
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->tracking_number ?: 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Carrier Booking Reference
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->carrier_booking_reference ?: 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Carrier Consignment ID
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->carrier_consignment_id ?: 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Carrier Invoice Reference
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->carrier_invoice_reference ?: 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Carrier Booked At
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->carrier_booked_at?->format('d M, Y h:i a') ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Tracking Driver
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->carrier ? str($shipmentRecord->carrier->trackingDriver())->replace('_', ' ')->title() : 'Manual' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Last Tracking Sync
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->last_tracking_synced_at?->format('d M Y H:i') ?? 'Never' }}
                        </p>
                    </div>

                    <div class="col-span-2 max-md:col-span-1">
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Tracking Sync Message
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->last_tracking_sync_message ?: 'No sync message yet.' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Native Shipment
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            @if ($shipmentRecord->nativeShipment)
                                <a
                                    href="{{ route('admin.sales.shipments.view', $shipmentRecord->nativeShipment->id) }}"
                                    class="text-blue-600 transition-all hover:underline"
                                >
                                    #{{ $shipmentRecord->nativeShipment->id }}
                                </a>
                            @else
                                N/A
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Order
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->order?->increment_id ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Handed Over
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->handed_over_at?->format('d M, Y h:i a') ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Delivered
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->delivered_at?->format('d M, Y h:i a') ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Delivery Attempts
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ (int) $shipmentRecord->delivery_attempt_count }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Last Delivery Attempt
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->last_delivery_attempt_at?->format('d M, Y h:i a') ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Failure Reason
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->delivery_failure_reason_label ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Reattempt Required
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->requires_reattempt ? 'Yes' : 'No' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Return Initiated
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->return_initiated_at?->format('d M, Y h:i a') ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Returned
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->returned_at?->format('d M, Y h:i a') ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Inventory Source
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $shipmentRecord->inventorySource?->name ?? $shipmentRecord->inventory_source_name ?? 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    Shipment Items
                </p>

                <div class="grid gap-3">
                    @forelse ($shipmentRecord->items as $item)
                        <div class="rounded border border-gray-200 p-3 dark:border-gray-800">
                            <p class="font-semibold text-gray-800 dark:text-white">
                                {{ $item->name ?? $item->orderItem?->name ?? 'Item' }}
                            </p>

                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                SKU: {{ $item->sku ?? 'N/A' }}
                            </p>

                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                Qty: {{ number_format((float) $item->qty, 2) }} | Weight: {{ number_format((float) $item->weight, 2) }}
                            </p>
                        </div>
                    @empty
                        <p class="text-gray-600 dark:text-gray-300">
                            No shipment items recorded.
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    Timeline
                </p>

                <div class="grid gap-3">
                    @forelse ($shipmentRecord->events as $event)
                        <div class="rounded border border-gray-200 p-3 dark:border-gray-800">
                            @php
                                $eventMeta = $event->meta ?? [];
                                $failureReasonLabel = isset($eventMeta['failure_reason'])
                                    ? (\Platform\CommerceCore\Models\ShipmentRecord::failureReasonLabels()[$eventMeta['failure_reason']] ?? str($eventMeta['failure_reason'])->replace('_', ' ')->title()->value())
                                    : null;
                            @endphp

                            <div class="flex items-start justify-between gap-4 max-md:flex-wrap">
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-white">
                                        {{ $event->event_type_label }}
                                    </p>

                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ $event->event_at?->format('d M, Y h:i a') }}
                                    </p>
                                </div>

                                <div class="text-right text-sm text-gray-600 dark:text-gray-300">
                                    @if ($event->status_after_event)
                                        <p>
                                            {{ \Platform\CommerceCore\Models\ShipmentRecord::statusLabels()[$event->status_after_event] ?? str($event->status_after_event)->replace('_', ' ')->title() }}
                                        </p>
                                    @endif

                                    @if ($event->actor)
                                        <p>
                                            {{ $event->actor->name }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if ($event->note)
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $event->note }}
                                </p>
                            @endif

                            @if ($failureReasonLabel || isset($eventMeta['attempt_count']) || array_key_exists('requires_reattempt', $eventMeta))
                                <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-600 dark:text-gray-300">
                                    @if (isset($eventMeta['attempt_count']))
                                        <span class="rounded bg-gray-100 px-2 py-1 dark:bg-gray-800">
                                            Attempt #{{ $eventMeta['attempt_count'] }}
                                        </span>
                                    @endif

                                    @if ($failureReasonLabel)
                                        <span class="rounded bg-gray-100 px-2 py-1 dark:bg-gray-800">
                                            {{ $failureReasonLabel }}
                                        </span>
                                    @endif

                                    @if (array_key_exists('requires_reattempt', $eventMeta))
                                        <span class="rounded bg-gray-100 px-2 py-1 dark:bg-gray-800">
                                            Reattempt: {{ $eventMeta['requires_reattempt'] ? 'Required' : 'No' }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-gray-600 dark:text-gray-300">
                            No shipment events recorded.
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    Communications
                </p>

                <div class="grid gap-3">
                    @forelse ($shipmentRecord->communications as $communication)
                        <div class="rounded border border-gray-200 p-3 dark:border-gray-800">
                            <div class="flex items-start justify-between gap-4 max-md:flex-wrap">
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-white">
                                        {{ $communication->notification_label }}
                                    </p>

                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ ucfirst($communication->audience) }} via {{ strtoupper($communication->channel) }}
                                    </p>
                                </div>

                                <div class="text-right text-sm text-gray-600 dark:text-gray-300">
                                    <p class="font-medium text-gray-800 dark:text-white">
                                        {{ $communication->status_label }}
                                    </p>

                                    <p>
                                        {{ $communication->queued_at?->format('d M, Y h:i a') ?? $communication->failed_at?->format('d M, Y h:i a') ?? $communication->created_at?->format('d M, Y h:i a') }}
                                    </p>
                                </div>
                            </div>

                            @if ($communication->recipient_email)
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $communication->recipient_name ? $communication->recipient_name.' · ' : '' }}{{ $communication->recipient_email }}
                                </p>
                            @endif

                            @if ($communication->shipmentEvent)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Triggered by {{ $communication->shipmentEvent->event_type_label }}
                                </p>
                            @endif

                            @if ($communication->reason)
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $communication->reason }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="text-gray-600 dark:text-gray-300">
                            No shipment communications recorded yet.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="flex w-[360px] max-w-full flex-col gap-2 max-xl:w-full">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    Recipient Snapshot
                </p>

                <div class="grid gap-3 text-sm text-gray-600 dark:text-gray-300">
                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Recipient
                        </p>

                        <p>{{ $shipmentRecord->recipient_name ?: 'N/A' }}</p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Phone
                        </p>

                        <p>{{ $shipmentRecord->recipient_phone ?: 'N/A' }}</p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Destination
                        </p>

                        <p>
                            {{ collect([$shipmentRecord->destination_city, $shipmentRecord->destination_region, $shipmentRecord->destination_country])->filter()->implode(', ') ?: 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Address
                        </p>

                        <p class="whitespace-pre-line">{{ $shipmentRecord->recipient_address ?: 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    COD Snapshot
                </p>

                <div class="grid gap-3 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex items-center justify-between gap-4">
                        <span>Expected</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($shipmentRecord->cod_amount_expected) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Collected</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($shipmentRecord->cod_amount_collected) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Carrier Fee</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($shipmentRecord->carrier_fee_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>COD Fee</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($shipmentRecord->cod_fee_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Return Fee</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($shipmentRecord->return_fee_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4 border-t pt-3 dark:border-gray-800">
                        <span>Net Remittable</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($shipmentRecord->net_remittable_amount) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
