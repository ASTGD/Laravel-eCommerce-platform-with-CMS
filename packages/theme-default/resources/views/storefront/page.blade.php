@extends('theme-default::layouts.storefront')

@section('title', $page->title)

@section('content')
    @include('theme-default::storefront.partials.header', ['header' => $header, 'preset' => $preset])

    <main>
        @foreach ($sections as $section)
            {!! $section['html'] !!}
        @endforeach
    </main>

    @include('theme-default::storefront.partials.footer', ['footer' => $footer, 'preset' => $preset])
@endsection
