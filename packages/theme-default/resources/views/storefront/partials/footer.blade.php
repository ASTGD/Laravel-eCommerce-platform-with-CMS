@php
    $footerView = app(\Platform\ThemeDefault\ViewModels\StorefrontFooterViewModel::class)->build([
        'description' => 'Structured commerce experience powered by reusable CMS-driven storefront composition.',
        'columns' => [
            ['title' => 'Support', 'links' => [['label' => 'Contact', 'url' => '/contact-us']]],
            ['title' => 'Account', 'links' => [['label' => 'Orders', 'url' => '/customer/account/orders']]],
        ],
    ]);
@endphp
<footer class="mt-16 bg-slate-950 text-slate-100">
    <div class="mx-auto max-w-6xl px-6 py-12">
        <div class="grid gap-10 lg:grid-cols-[minmax(260px,0.36fr)_minmax(0,1fr)]">
            <div>
                <a href="{{ $footerView['homeUrl'] }}" class="inline-flex items-center gap-3 text-lg font-semibold text-white">
                    @if (filled($footerView['logoUrl']))
                        <img src="{{ $footerView['logoUrl'] }}" alt="" class="max-h-12 max-w-[160px] object-contain" onerror="this.remove()">
                    @else
                        <span>{{ $footerView['brandName'] }}</span>
                    @endif
                </a>

                @if (filled($footerView['description']))
                    <p class="mt-3 max-w-xl text-sm text-slate-300">
                        {{ $footerView['description'] }}
                    </p>
                @endif

                @if (filled($footerView['contact']['email']) || filled($footerView['contact']['phone']))
                    <div class="mt-4 space-y-2 text-sm text-slate-300">
                        @if (filled($footerView['contact']['email']))
                            <a href="mailto:{{ $footerView['contact']['email'] }}" class="block hover:text-white">{{ $footerView['contact']['email'] }}</a>
                        @endif

                        @if (filled($footerView['contact']['phone']))
                            <a href="tel:{{ $footerView['contact']['phone'] }}" class="block hover:text-white">{{ $footerView['contact']['phone'] }}</a>
                        @endif
                    </div>
                @endif

                @if (! empty($footerView['socialLinks']))
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach ($footerView['socialLinks'] as $link)
                            <a href="{{ $link['url'] }}" aria-label="{{ $link['label'] }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-700 text-slate-200 hover:border-white hover:text-white">
                                @include('theme-default::storefront.partials.social-icon', ['icon' => $link['icon'] ?? 'link', 'class' => 'h-4 w-4'])
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="mt-4">
                    <a
                        href="{{ route('shop.shipment-tracking.index') }}"
                        class="inline-flex items-center rounded-xl border border-slate-600 px-4 py-2 text-sm font-medium text-slate-100 transition hover:border-white hover:text-white"
                    >
                        Track Shipment
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-2 justify-items-end gap-x-8 gap-y-7 text-right text-sm text-slate-300 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($footerView['columns'] as $column)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $column['title'] ?? 'Links' }}</p>
                        <div class="mt-3 space-y-1.5">
                            @foreach (($column['links'] ?? []) as $link)
                                <a
                                    href="{{ $link['url'] }}"
                                    class="block text-sm font-normal leading-6 text-slate-300 hover:text-white"
                                    @if ($link['open_in_new_tab']) target="_blank" rel="noopener noreferrer" @endif
                                >
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($footerView['newsletter']['enabled'])
            <div class="mt-10 grid gap-5 rounded-2xl border border-slate-800 bg-slate-900 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(360px,520px)] lg:items-center">
                <div>
                    <p class="font-medium text-white">{{ $footerView['newsletter']['heading'] }}</p>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">{{ $footerView['newsletter']['text'] }}</p>
                </div>

                <form
                    method="POST"
                    action="{{ route('shop.subscription.store') }}"
                    class="flex flex-col gap-3 sm:flex-row"
                >
                    @csrf

                    <label class="sr-only" for="default-footer-newsletter-email">Email address</label>
                    <input
                        id="default-footer-newsletter-email"
                        type="email"
                        name="email"
                        required
                        placeholder="email@example.com"
                        class="min-h-12 flex-1 rounded-xl border border-slate-700 bg-slate-950 px-4 text-sm text-white outline-none placeholder:text-slate-500"
                    >

                    <button type="submit" class="min-h-12 rounded-xl bg-white px-5 text-sm font-semibold text-slate-950">
                        Subscribe
                    </button>
                </form>
            </div>
        @endif

        <p class="mt-10 border-t border-slate-800 pt-6 text-sm text-slate-400">
            {{ $footerView['copyrightText'] }}
        </p>
    </div>
</footer>
