<x-admin::layouts>
    <x-slot:title>
        Payment Attempt #{{ $paymentAttempt->id }}
    </x-slot>

    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Payment Attempt #{{ $paymentAttempt->id }}
            </p>

            <p class="text-sm text-gray-500 dark:text-gray-300">
                {{ strtoupper($paymentAttempt->provider) }} / {{ $paymentAttempt->method_code }}
            </p>
        </div>

        <div class="flex gap-2">
            <a
                href="{{ route('admin.sales.payments.index') }}"
                class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
            >
                Back
            </a>

            @if (bouncer()->hasPermission('sales.payments.reconcile'))
                <form method="POST" action="{{ route('admin.sales.payments.reconcile', $paymentAttempt) }}">
                    @csrf

                    <button type="submit" class="primary-button">
                        Reconcile Payment
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                Attempt Details
            </p>

            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <p><span class="font-semibold text-gray-800 dark:text-white">Merchant Transaction:</span> {{ $paymentAttempt->merchant_tran_id }}</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Gateway Transaction:</span> {{ $paymentAttempt->gateway_tran_id ?: 'N/A' }}</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Status:</span> {{ strtoupper($paymentAttempt->status) }}</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Validation:</span> {{ $paymentAttempt->validation_status ?: 'N/A' }}</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Amount:</span> {{ number_format((float) $paymentAttempt->amount, 2) }} {{ $paymentAttempt->currency }}</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Finalized Via:</span> {{ $paymentAttempt->finalized_via ?: 'N/A' }}</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Finalized At:</span> {{ $paymentAttempt->finalized_at ? core()->formatDate($paymentAttempt->finalized_at, 'd M Y H:i') : 'N/A' }}</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Last Reconciled:</span> {{ $paymentAttempt->last_reconciled_at ? core()->formatDate($paymentAttempt->last_reconciled_at, 'd M Y H:i') : 'Never' }}</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Reconcile Status:</span> {{ $paymentAttempt->last_reconciled_status ?: 'N/A' }}</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Callback Count:</span> {{ $paymentAttempt->callback_count }}</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">IPN Count:</span> {{ $paymentAttempt->ipn_count }}</p>

                @if ($paymentAttempt->order)
                    <p>
                        <span class="font-semibold text-gray-800 dark:text-white">Order:</span>
                        <a class="text-blue-600 hover:underline" href="{{ route('admin.sales.orders.view', $paymentAttempt->order->id) }}">
                            #{{ $paymentAttempt->order->increment_id }}
                        </a>
                    </p>
                @endif

                @if ($paymentAttempt->last_reconcile_error)
                    <p><span class="font-semibold text-gray-800 dark:text-white">Last Reconcile Error:</span> {{ $paymentAttempt->last_reconcile_error }}</p>
                @endif
            </div>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                Event Timeline
            </p>

            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                @forelse ($paymentAttempt->events as $event)
                    <div class="rounded border border-slate-200 p-3 dark:border-gray-800">
                        <p class="font-semibold text-gray-800 dark:text-white">{{ $event->event_type }}</p>
                        <p>Received: {{ $event->received_at ? core()->formatDate($event->received_at, 'd M Y H:i') : 'N/A' }}</p>
                        <p>Result: {{ $event->result ?: 'Pending' }}</p>

                        @if ($event->notes)
                            <p>Notes: {{ $event->notes }}</p>
                        @endif
                    </div>
                @empty
                    <p>No gateway events have been recorded for this attempt yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin::layouts>
