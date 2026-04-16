<section class="overflow-hidden rounded-[1.75rem] bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)] ring-1 ring-slate-200">
    <div class="border-b border-slate-200 px-6 py-6 text-center lg:px-7">
        <p class="text-xs uppercase tracking-[0.32em] text-slate-400">Order Summary</p>

        <h1 class="mt-2 text-2xl font-semibold uppercase tracking-[0.22em] text-slate-900">
            Your Order
        </h1>
    </div>

    <div class="px-6 py-6 lg:px-7">
        <div class="rounded-[1.5rem] border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">
                <span>Product</span>
                <span>Subtotal</span>
            </div>

            <div class="divide-y divide-slate-200">
                <div
                    class="flex items-start gap-4 px-5 py-4"
                    v-for="item in cart.items"
                >
                    {!! view_render_event('bagisto.shop.checkout.onepage.summary.item_image.before') !!}

                    <img
                        class="h-16 w-16 rounded-xl object-cover ring-1 ring-slate-200"
                        :src="item.base_image.small_image_url"
                        :alt="item.name"
                        width="64"
                        height="64"
                    >

                    {!! view_render_event('bagisto.shop.checkout.onepage.summary.item_image.after') !!}

                    <div class="min-w-0 flex-1">
                        {!! view_render_event('bagisto.shop.checkout.onepage.summary.item_name.before') !!}

                        <p class="truncate text-sm font-medium text-slate-900">
                            @{{ item.name }}
                        </p>

                        {!! view_render_event('bagisto.shop.checkout.onepage.summary.item_name.after') !!}

                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span>
                                @lang('shop::app.checkout.onepage.summary.price_and_qty', ['price' => '@{{ item.formatted_price }}', 'qty' => '@{{ item.quantity }}'])
                            </span>
                        </div>
                    </div>

                    <span class="shrink-0 text-sm font-medium text-slate-500">
                        @{{ item.formatted_total }}
                    </span>
                </div>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            {!! view_render_event('bagisto.shop.checkout.onepage.summary.sub_total.before') !!}

            <div class="flex items-center justify-between text-sm text-slate-600">
                <span>@lang('shop::app.checkout.onepage.summary.sub-total')</span>
                <span class="font-semibold text-slate-900">@{{ cart.formatted_sub_total }}</span>
            </div>

            {!! view_render_event('bagisto.shop.checkout.onepage.summary.sub_total.after') !!}

            <div
                class="flex items-center justify-between text-sm text-slate-600"
                v-if="cart.discount_amount && parseFloat(cart.discount_amount) > 0"
            >
                <span>@lang('shop::app.checkout.onepage.summary.discount-amount')</span>
                <span class="font-semibold text-slate-900">@{{ cart.formatted_discount_amount }}</span>
            </div>

            <div class="flex items-center justify-between text-sm text-slate-600">
                <span>@lang('shop::app.checkout.onepage.summary.delivery-charges')</span>
                <span class="font-semibold text-slate-900">
                    <template v-if="cart.shipping_method_title">
                        @{{ cart.shipping_method_title }}:
                    </template>

                    @{{ cart.formatted_shipping_amount }}
                </span>
            </div>

            <div
                class="flex items-center justify-between border-t border-slate-200 pt-3 text-sm text-slate-600"
                v-if="! cart.tax_total"
            >
                <span>@lang('shop::app.checkout.onepage.summary.tax')</span>
                <span class="font-semibold text-slate-900">@{{ cart.formatted_tax_total }}</span>
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

                    <span class="flex items-center gap-1 font-semibold text-slate-900">
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

            <div class="flex items-center justify-between border-t border-slate-200 pt-4 text-base font-semibold text-slate-900">
                <span>@lang('shop::app.checkout.onepage.summary.grand-total')</span>
                <span class="text-[#2f5ec5]">@{{ cart.formatted_grand_total }}</span>
            </div>
        </div>
    </div>
</section>
