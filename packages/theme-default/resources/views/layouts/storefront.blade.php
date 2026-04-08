<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->seoMeta?->title ?: $page->title }} · {{ config('app.name') }}</title>
    <meta name="description" content="{{ $page->seoMeta?->description }}">
    <link rel="stylesheet" href="{{ asset('assets/platform/storefront.css') }}">
    @php($colors = data_get($preset->tokens_json, 'colors', []))
    @php($radius = data_get($preset->tokens_json, 'radius', []))
    <style>
        :root {
            --surface-background: {{ $colors['background'] ?? '#f7f5ef' }};
            --surface-card: {{ $colors['surface'] ?? '#fffdf8' }};
            --text-primary: {{ $colors['text'] ?? '#1f2933' }};
            --text-muted: {{ $colors['muted'] ?? '#52606d' }};
            --accent: {{ $colors['accent'] ?? '#9f4f2b' }};
            --accent-contrast: {{ $colors['accent_contrast'] ?? '#fff8f1' }};
            --border-color: {{ $colors['border'] ?? '#dbc9b5' }};
            --radius-card: {{ $radius['card'] ?? '24px' }};
            --radius-button: {{ $radius['button'] ?? '999px' }};
        }
    </style>
</head>
<body class="storefront-shell">
    @if ($preview)
        <div class="preview-banner">Preview mode · This page is rendered from the current draft state.</div>
    @endif

    @include('theme-default::storefront.partials.header', ['header' => $header, 'primaryMenu' => $primaryMenu])

    <main>
        @yield('content')
    </main>

    @include('theme-default::storefront.partials.footer', ['footer' => $footer, 'footerMenu' => $footerMenu])
</body>
</html>
