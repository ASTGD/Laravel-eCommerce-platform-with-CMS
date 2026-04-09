<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @php($seo = $page->seoMeta ?? null)
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $seo?->title ?: config('app.name'))</title>
    @if ($seo?->description)
        <meta name="description" content="{{ $seo->description }}">
    @endif
    @if ($seo?->canonical_url)
        <link rel="canonical" href="{{ $seo->canonical_url }}">
    @endif
    {!! app(\Illuminate\Foundation\Vite::class)
        ->useHotFile('hot')
        ->useBuildDirectory('build')
        ->withEntryPoints(['resources/css/app.css', 'resources/js/app.js'])
        ->toHtml() !!}
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    @yield('content')
</body>
</html>
