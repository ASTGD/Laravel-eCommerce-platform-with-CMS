@php
    $brandName = $brandName ?? 'ASTGD Ecommerce';
    $logo = core()->getConfigData('general.design.admin_logo.logo_image');
    $link = $link ?? null;
    $showName = $showName ?? true;
    $containerClass = $containerClass ?? 'flex items-center gap-3';
    $imageClass = $imageClass ?? 'h-10 w-auto';
    $markClass = $markClass ?? 'flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm';
    $eyebrowClass = $eyebrowClass ?? 'text-base font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-400';
    $nameClass = $nameClass ?? 'text-lg font-bold text-gray-900 dark:text-white';
    $tag = $link ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($link)
        href="{{ $link }}"
    @endif
    class="{{ $containerClass }}"
>
    @if ($logo)
        <img
            class="{{ $imageClass }}"
            src="{{ Storage::url($logo) }}"
            alt="{{ $brandName }}"
        />
    @else
        @include('admin.partials.brand-mark', ['class' => $markClass])
    @endif

    @if ($showName)
        <span class="flex flex-col leading-none">
            <span class="{{ $eyebrowClass }}">ASTGD</span>
            <span class="{{ $nameClass }}">Ecommerce</span>
        </span>
    @endif
</{{ $tag }}>
