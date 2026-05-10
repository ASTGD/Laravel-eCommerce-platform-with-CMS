<x-admin::layouts.anonymous>
    <!-- Page Title -->
    <x-slot:title>
        @lang("admin::app.errors.{$errorCode}.title")
    </x-slot>

    <!-- Error page Information -->
	<div class="flex h-[100vh] items-center justify-center bg-white dark:bg-gray-900">
        <div class="flex max-w-[745px] items-center gap-5">
            <div class="w-full">
                @php
                    $configuredLogo = core()->getConfigData('general.design.admin_logo.logo_image');
                    $fallbackLogo = public_path('images/astgd-ecommerce-logo.webp');

                    $logoUrl = $configuredLogo
                        ? Storage::url($configuredLogo)
                        : (file_exists($fallbackLogo) ? asset('images/astgd-ecommerce-logo.webp') : null);
                @endphp

                @if ($logoUrl)
                    <img
                        class="mb-6 h-10 object-contain"
                        src="{{ $logoUrl }}"
                        id="logo-image"
                        alt="{{ config('app.name') }}"
                    />
                @else
                    <div class="mb-6 text-xl font-semibold text-gray-800 dark:text-white">
                        {{ config('app.name') }}
                    </div>
                @endif

				<div class="text-[38px] font-bold text-gray-800 dark:text-white">
                    {{ $errorCode }}
                </div>

                <p class="mb-6 text-sm text-gray-800">
                    @lang("admin::app.errors.{$errorCode}.description")
                </p>

                <div class="mb-6">
                    <div class="flex items-center gap-2.5">
                        <a
                            onclick="history.back()"
                            class="text-sm font-semibold text-blue-600 transition-all hover:underline"
                        >
                            @lang('admin::app.errors.go-back')
                        </a>

                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="6" height="7" viewBox="0 0 6 7" fill="none">
                                <circle cx="3" cy="3.5" r="3" fill="#9CA3AF"/>
                            </svg>
                        </span>

                        <a
                            href="{{ route('admin.dashboard.index') }}"
                            class="text-sm font-semibold text-blue-600 transition-all hover:underline"
                        >
                            @lang('admin::app.errors.dashboard')
                        </a>
                    </div>
                </div>

                <p class="text-sm text-gray-800">
                    @lang('admin::app.errors.support', [
                        'link'  => 'mailto:' . (core()->getAdminEmailDetails()['email'] ?? 'support@example.com'),
                        'email' => core()->getAdminEmailDetails()['email'] ?? 'support@example.com',
                        'class' => 'font-semibold text-blue-600 transition-all hover:underline',
                    ])
                </p>
            </div>

            <div class="w-full">
                <img src="{{ bagisto_asset('images/error.svg') }}" />
            </div>
        </div>
	</div>
</x-admin::layouts.anonymous>
