<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/platform/admin.css') }}">
</head>
<body class="admin-shell">
    @php($navigation = \App\Support\AdminNavigation::groups())

    <div class="admin-grid">
        <aside class="admin-sidebar">
            <div class="sidebar-brand">
                <span class="eyebrow">Admin</span>
                <strong>{{ config('app.name') }}</strong>
            </div>

            @foreach ($navigation as $group => $items)
                <section class="nav-group">
                    <h2>{{ $group }}</h2>

                    @foreach ($items as $item)
                        @if (($item['implemented'] ?? false) && isset($item['route']))
                            <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['route'].'*') ? 'is-active' : '' }}">
                                {{ $item['label'] }}
                            </a>
                        @else
                            <span class="nav-placeholder">{{ $item['label'] }}</span>
                        @endif
                    @endforeach
                </section>
            @endforeach
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <div>
                    <span class="eyebrow">Reusable platform</span>
                    <h1>@yield('heading', 'Admin')</h1>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="button button-secondary">Sign out</button>
                </form>
            </header>

            @include('experience-cms::admin.partials.flash')

            <main class="admin-content">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
