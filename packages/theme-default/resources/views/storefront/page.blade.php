@extends('theme-default::layouts.storefront')

@section('content')
    @foreach ($renderedSections as $sectionHtml)
        {!! $sectionHtml !!}
    @endforeach
@endsection
