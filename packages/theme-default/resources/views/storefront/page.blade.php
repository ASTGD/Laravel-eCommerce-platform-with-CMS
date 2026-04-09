@extends('theme-default::layouts.storefront')

@section('title', $page->seoMeta?->title ?: $page->title)

@section('content')
    @include('theme-default::storefront.partials.header', ['header' => $header, 'menu' => $menu, 'preset' => $preset])

    <main>
        @foreach ($sections as $section)
            {!! $section['html'] !!}
        @endforeach
    </main>

    @include('theme-default::storefront.partials.footer', ['footer' => $footer, 'menu' => $menu, 'preset' => $preset])
@endsection
