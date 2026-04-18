@if (
    $order->canConfirm()
    && bouncer()->hasPermission('sales.orders.confirm')
)
    <form
        method="POST"
        ref="confirmOrderForm"
        action="{{ route('admin.sales.orders.confirm', $order->id) }}"
    >
        @csrf
    </form>

    <div
        class="transparent-button px-1 py-1.5 hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
        @click="$emitter.emit('open-confirm-modal', {
            message: '@lang('admin::app.sales.orders.view.confirm-msg')',
            agree: () => {
                this.$refs['confirmOrderForm'].submit()
            }
        })"
    >
        <span
            class="icon-sort-right text-2xl"
            role="presentation"
            tabindex="0"
        >
        </span>

        <a href="javascript:void(0);">
            @lang('admin::app.sales.orders.view.confirm')
        </a>
    </div>
@endif
