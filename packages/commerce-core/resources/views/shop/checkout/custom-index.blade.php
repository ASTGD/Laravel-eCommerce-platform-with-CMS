@extends('theme-default::layouts.storefront')

@section('title', __('shop::app.checkout.onepage.index.checkout'))

@section('content')
    @include('theme-default::storefront.checkout-page')
@endsection
