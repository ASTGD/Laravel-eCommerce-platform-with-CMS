@extends('experience-cms::admin.layout')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
    <div class="stats-grid">
        <article class="stat-card">
            <span class="eyebrow">Pages</span>
            <strong>{{ $stats['pages'] }}</strong>
        </article>
        <article class="stat-card">
            <span class="eyebrow">Templates</span>
            <strong>{{ $stats['templates'] }}</strong>
        </article>
        <article class="stat-card">
            <span class="eyebrow">Section Types</span>
            <strong>{{ $stats['sectionTypes'] }}</strong>
        </article>
        <article class="stat-card">
            <span class="eyebrow">Theme Presets</span>
            <strong>{{ $stats['themePresets'] }}</strong>
        </article>
        <article class="stat-card">
            <span class="eyebrow">Menus</span>
            <strong>{{ $stats['menus'] }}</strong>
        </article>
    </div>

    <section class="panel stack-md">
        <span class="eyebrow">Current slice</span>
        <h2>Milestone 1 foundation with the first CMS/theme vertical slice.</h2>
        <p>The admin currently supports pages, page sections, templates, section types, theme presets, and menus. The storefront renders the seeded homepage through the section registry, preset resolver, and default theme.</p>
    </section>
@endsection
