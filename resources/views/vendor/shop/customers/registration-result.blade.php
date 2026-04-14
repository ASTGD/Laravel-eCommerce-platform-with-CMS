@php
    $requiresVerification = (bool) ($notice['requires_verification'] ?? false);
    $email = $notice['email'] ?? null;
@endphp

@push('meta')
    <meta
        name="description"
        content="{{ $requiresVerification ? trans('shop::app.customers.signup-form.verification-required-title') : trans('shop::app.customers.signup-form.registration-success-title') }}"
    />

    <meta
        name="keywords"
        content="{{ $requiresVerification ? trans('shop::app.customers.signup-form.verification-required-title') : trans('shop::app.customers.signup-form.registration-success-title') }}"
    />
@endPush

<x-shop::layouts
    :has-header="false"
    :has-feature="false"
    :has-footer="false"
>
    <x-slot:title>
        {{ $requiresVerification ? trans('shop::app.customers.signup-form.verification-required-title') : trans('shop::app.customers.signup-form.registration-success-title') }}
    </x-slot>

    <div class="container mt-20 max-1180:px-5 max-md:mt-12">
        <div class="flex items-center gap-x-14 max-[1180px]:gap-x-9">
            <a
                href="{{ route('shop.home.index') }}"
                class="m-[0_auto_20px_auto]"
                aria-label="{{ config('app.name') }}"
            >
                <img
                    src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                    alt="{{ config('app.name') }}"
                    width="131"
                    height="29"
                >
            </a>
        </div>

        <div class="m-auto w-full max-w-[870px] rounded-xl border border-zinc-200 p-16 px-[90px] max-md:px-8 max-md:py-8 max-sm:border-none max-sm:p-0">
            <h1 class="font-dmserif text-4xl max-md:text-3xl max-sm:text-xl">
                {{ $requiresVerification ? trans('shop::app.customers.signup-form.verification-required-title') : trans('shop::app.customers.signup-form.registration-success-title') }}
            </h1>

            <p class="mt-4 text-xl text-zinc-500 max-sm:mt-0 max-sm:text-sm">
                {{ $requiresVerification ? trans('shop::app.customers.signup-form.success-verify') : trans('shop::app.customers.signup-form.success') }}
            </p>

            @if ($email)
                <div class="mt-8 rounded-xl border border-zinc-200 bg-zinc-50 px-6 py-5">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-zinc-500">
                        @lang('shop::app.customers.signup-form.email')
                    </p>

                    <p class="mt-2 break-all text-lg text-zinc-900">
                        {{ $email }}
                    </p>

                    <p class="mt-3 text-sm text-zinc-600">
                        {{ $requiresVerification ? trans('shop::app.customers.signup-form.verification-required-help') : trans('shop::app.customers.signup-form.registration-success-help') }}
                    </p>
                </div>
            @endif

            <div class="mt-10 flex flex-wrap items-center gap-4 max-sm:flex-col">
                @if ($requiresVerification && $email)
                    <x-shop::form
                        :action="route('shop.customers.resend.verification')"
                        class="w-full max-w-[374px] max-md:max-w-full"
                    >
                        <input
                            type="hidden"
                            name="email"
                            value="{{ $email }}"
                        >

                        <button
                            class="secondary-button m-0 block w-full rounded-2xl px-11 py-4 text-center text-base max-md:rounded-lg max-md:py-3 max-sm:py-1.5"
                            type="submit"
                        >
                            @lang('shop::app.home.index.resend-verify-email')
                        </button>
                    </x-shop::form>
                @endif

                <a
                    href="{{ route('shop.customer.session.index', ['redirect_to' => 'account']) }}"
                    class="primary-button m-0 block w-full max-w-[374px] rounded-2xl px-11 py-4 text-center text-base max-md:max-w-full max-md:rounded-lg max-md:py-3 max-sm:py-1.5"
                >
                    @lang('shop::app.customers.signup-form.sign-in-button')
                </a>
            </div>
        </div>

        <p class="mb-4 mt-8 text-center text-xs text-zinc-500">
            @lang('shop::app.customers.signup-form.footer', ['current_year'=> date('Y') ])
        </p>
    </div>
</x-shop::layouts>
