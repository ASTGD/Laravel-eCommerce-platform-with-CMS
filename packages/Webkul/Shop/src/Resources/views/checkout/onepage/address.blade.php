{!! view_render_event('bagisto.shop.checkout.onepage.address.before') !!}

<section class="flex h-full flex-col rounded-[2rem] bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)] ring-1 ring-slate-200 lg:p-8">
    <div class="border-b border-slate-200 pb-5">
        <p class="text-xs uppercase tracking-[0.32em] text-slate-400">
            Checkout Details
        </p>

        <h2 class="mt-2 text-2xl font-semibold uppercase tracking-[0.2em] text-slate-900 max-md:text-xl">
            Billing &amp; Shipping
        </h2>
    </div>

    <div class="flex-1 pt-6">
        <!-- If the customer is guest -->
        <template v-if="cart.is_guest">
            @include('shop::checkout.onepage.address.guest')
        </template>

        <!-- If the customer is logged in -->
        <template v-else>
            @include('shop::checkout.onepage.address.customer')
        </template>
    </div>
</section>

{!! view_render_event('bagisto.shop.checkout.onepage.address.after') !!}
