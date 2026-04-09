@extends('theme-default::layouts.storefront')

@section('title', $page->seoMeta?->title ?: $product->name)

@section('content')
    @include('theme-default::storefront.partials.header', ['header' => $header, 'menu' => $menu, 'preset' => $preset, 'siteSettings' => $siteSettings ?? []])

    <main class="mx-auto max-w-6xl px-6 py-12">
        <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="space-y-6">
                @foreach ($gallerySections as $section)
                    {!! $section['html'] !!}
                @endforeach
            </div>

            <div class="space-y-6">
                @foreach ($summarySections as $section)
                    {!! $section['html'] !!}
                @endforeach
            </div>
        </div>

        <div class="mt-10 space-y-6">
            @foreach ($detailsSections as $section)
                {!! $section['html'] !!}
            @endforeach
        </div>

        <div class="mt-10 space-y-6">
            @foreach ($relatedSections as $section)
                {!! $section['html'] !!}
            @endforeach
        </div>
    </main>

    @include('theme-default::storefront.partials.footer', ['footer' => $footer, 'menu' => $menu, 'preset' => $preset, 'siteSettings' => $siteSettings ?? []])
@endsection
