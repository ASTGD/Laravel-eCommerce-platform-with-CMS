@extends('theme-default::layouts.storefront')

@section('title', $page->seoMeta?->title ?: $category->name)

@section('content')
    @include('theme-default::storefront.partials.header', ['header' => $header, 'menu' => $menu, 'preset' => $preset, 'siteSettings' => $siteSettings ?? []])

    <main>
        @foreach ($heroSections as $section)
            {!! $section['html'] !!}
        @endforeach

        <section class="mx-auto max-w-6xl px-6 py-12">
            @foreach ($preListingSections as $section)
                <div class="mb-6">{!! $section['html'] !!}</div>
            @endforeach

            <div class="rounded-[2rem] bg-white p-8 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Category</p>
                        <h1 class="mt-2 text-3xl font-semibold text-slate-900">{{ $category->name }}</h1>

                        @if (($listing['settings']['show_description'] ?? true) && $category->description)
                            <div class="prose mt-4 max-w-3xl text-slate-600">{!! $category->description !!}</div>
                        @endif
                    </div>

                    @if ($listing['settings']['show_toolbar'] ?? true)
                        <div class="rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-600">
                            Mode: {{ strtoupper($listing['mode']) }}<br>
                            Limit: {{ $listing['paginator']->perPage() }}
                        </div>
                    @endif
                </div>

                <div class="mt-8 grid gap-5 {{ ($listing['mode'] ?? 'grid') === 'list' ? 'grid-cols-1' : 'md:grid-cols-2 xl:grid-cols-3' }}">
                    @forelse ($listing['items'] as $item)
                        <article class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ $item['sku'] ?? 'SKU' }}</p>
                            <h2 class="mt-3 text-lg font-semibold text-slate-900">
                                <a href="{{ route('shop.product_or_category.index', $item['url_key']) }}">{{ $item['name'] }}</a>
                            </h2>

                            <div class="mt-3 text-sm text-slate-600">{!! $item['price_html'] !!}</div>
                        </article>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-100 p-6 text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                            {{ $listing['settings']['empty_state_heading'] ?? 'No products matched this category yet.' }}
                        </div>
                    @endforelse
                </div>

                <div class="mt-8">
                    {{ $listing['paginator']->links() }}
                </div>
            </div>

            @foreach ($postListingSections as $section)
                <div class="mt-6">{!! $section['html'] !!}</div>
            @endforeach
        </section>
    </main>

    @include('theme-default::storefront.partials.footer', ['footer' => $footer, 'menu' => $menu, 'preset' => $preset, 'siteSettings' => $siteSettings ?? []])
@endsection
