@php($settings = array_replace($siteSettings['store.contact'] ?? [], $footer?->settings_json ?? []))
<footer class="mt-16 bg-slate-950 text-slate-100">
    <div class="mx-auto max-w-6xl px-6 py-12">
        <div class="grid gap-8 md:grid-cols-2">
            <div>
                <p class="text-lg font-semibold">{{ $settings['headline'] ?? config('app.name') }}</p>
                <p class="mt-3 max-w-xl text-sm text-slate-300">
                    {{ $settings['description'] ?? 'Structured commerce experience powered by reusable CMS-driven storefront composition.' }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm text-slate-300">
                @foreach (($settings['columns'] ?? [['title' => 'Support', 'links' => [['label' => 'Contact', 'url' => '/contact-us']]], ['title' => 'Account', 'links' => [['label' => 'Orders', 'url' => '/customer/account/orders']]]]) as $column)
                    <div>
                        <p class="font-medium text-white">{{ $column['title'] ?? 'Links' }}</p>
                        <div class="mt-3 space-y-2">
                            @foreach (($column['links'] ?? []) as $link)
                                <a href="{{ $link['url'] ?? '#' }}" class="block hover:text-white">{{ $link['label'] ?? 'Link' }}</a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</footer>
