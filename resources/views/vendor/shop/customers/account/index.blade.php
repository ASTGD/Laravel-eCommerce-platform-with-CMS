<x-shop::layouts.account>
    <x-slot:title>
        @lang('shop::app.layouts.my-account')
    </x-slot>

    <div class="mx-4">
        <x-shop::layouts.account.navigation />
    </div>

    <div class="mx-4 flex-1 rounded-2xl border border-zinc-200 bg-white p-8 max-sm:p-5">
        <h1 class="font-dmserif text-3xl max-sm:text-2xl">
            @lang('shop::app.layouts.my-account')
        </h1>

        <p class="mt-3 text-base text-zinc-600 max-sm:text-sm">
            {{ auth()->guard('customer')->user()?->first_name }}
        </p>

        <div class="mt-8">
            <div class="mx-auto w-full max-w-[400px] rounded-lg border border-navyBlue py-2.5 text-center max-sm:max-w-full max-sm:py-1.5">
                <x-shop::form
                    method="DELETE"
                    action="{{ route('shop.customer.session.destroy') }}"
                    id="customerLogout"
                />

                <a
                    class="flex items-center justify-center gap-1.5 text-base hover:bg-gray-100"
                    href="{{ route('shop.customer.session.destroy') }}"
                    onclick="event.preventDefault(); document.getElementById('customerLogout').submit();"
                >
                    @lang('shop::app.components.layouts.header.desktop.bottom.logout')
                </a>
            </div>
        </div>
    </div>
</x-shop::layouts.account>
