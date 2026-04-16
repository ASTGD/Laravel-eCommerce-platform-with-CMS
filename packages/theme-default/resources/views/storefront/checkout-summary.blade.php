<section class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Order Summary</p>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">
                Your Order
            </h1>
        </div>

        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium uppercase tracking-[0.2em] text-slate-500">
            @{{ cart.items.length }} Items
        </div>
    </div>

    <div class="mt-6 space-y-4">
        <div class="space-y-4">
            <div
                class="flex gap-4 border-b border-slate-200 pb-4"
                v-for="item in cart.items"
            >
                {!! view_render_event('bagisto.shop.checkout.onepage.summary.item_image.before') !!}

                <img
                    class="h-20 w-20 rounded-xl object-cover"
                    :src="item.base_image.small_image_url"
                    :alt="item.name"
                    width="80"
                    height="80"
                >

                {!! view_render_event('bagisto.shop.checkout.onepage.summary.item_image.after') !!}

                <div class="min-w-0 flex-1">
                    {!! view_render_event('bagisto.shop.checkout.onepage.summary.item_name.before') !!}

                    <p class="truncate text-sm font-medium text-slate-900">
                        @{{ item.name }}
                    </p>

                    {!! view_render_event('bagisto.shop.checkout.onepage.summary.item_name.after') !!}

                    <div class="mt-2 flex items-center justify-between gap-3 text-sm text-slate-500">
                        <span>
                            @lang('shop::app.checkout.onepage.summary.price_and_qty', ['price' => '@{{ item.formatted_price }}', 'qty' => '@{{ item.quantity }}'])
                        </span>

                        <span class="font-semibold text-slate-700">
                            @{{ item.formatted_total }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-3 border-t border-slate-200 pt-4">
            {!! view_render_event('bagisto.shop.checkout.onepage.summary.sub_total.before') !!}

            <div class="flex items-center justify-between text-sm text-slate-600">
                <span>@lang('shop::app.checkout.onepage.summary.sub-total')</span>
                <span class="font-medium text-slate-900">@{{ cart.formatted_sub_total }}</span>
            </div>

            {!! view_render_event('bagisto.shop.checkout.onepage.summary.sub_total.after') !!}

            <div
                class="flex items-center justify-between text-sm text-slate-600"
                v-if="cart.discount_amount && parseFloat(cart.discount_amount) > 0"
            >
                <span>@lang('shop::app.checkout.onepage.summary.discount-amount')</span>
                <span class="font-medium text-slate-900">@{{ cart.formatted_discount_amount }}</span>
            </div>

            <div class="flex items-center justify-between text-sm text-slate-600">
                <span>@lang('shop::app.checkout.onepage.summary.delivery-charges')</span>
                <span class="font-medium text-slate-900">
                    <template v-if="cart.shipping_method_title">
                        @{{ cart.shipping_method_title }}:
                    </template>

                    @{{ cart.formatted_shipping_amount }}
                </span>
            </div>

            <div
                class="flex items-center justify-between border-t border-slate-200 pt-3 text-base font-semibold text-slate-900"
                v-if="! cart.tax_total"
            >
                <span>@lang('shop::app.checkout.onepage.summary.tax')</span>
                <span>@{{ cart.formatted_tax_total }}</span>
            </div>

            <div
                class="space-y-2 border-t border-slate-200 pt-3"
                v-else
            >
                <div
                    class="flex cursor-pointer items-center justify-between text-sm text-slate-600"
                    @click="cart.show_taxes = ! cart.show_taxes"
                >
                    <span>@lang('shop::app.checkout.onepage.summary.tax')</span>

                    <span class="flex items-center gap-1 font-medium text-slate-900">
                        @{{ cart.formatted_tax_total }}
                        <span
                            class="text-xl"
                            :class="{'icon-arrow-up': cart.show_taxes, 'icon-arrow-down': ! cart.show_taxes}"
                        ></span>
                    </span>
                </div>

                <div
                    class="space-y-2"
                    v-show="cart.show_taxes"
                >
                    <div
                        class="flex items-center justify-between text-xs text-slate-500"
                        v-for="(amount, index) in cart.applied_taxes"
                    >
                        <span>@{{ index }}</span>
                        <span class="font-medium text-slate-700">@{{ amount }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-slate-200 pt-4 text-lg font-semibold text-slate-900">
                <span>@lang('shop::app.checkout.onepage.summary.grand-total')</span>
                <span>@{{ cart.formatted_grand_total }}</span>
            </div>
        </div>
    </div>
</section>
