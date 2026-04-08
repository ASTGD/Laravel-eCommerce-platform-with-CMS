@extends('experience-cms::admin.layout')

@section('title', 'Pages')
@section('heading', 'Pages')

@section('content')
    <div class="toolbar">
        <a href="{{ route('admin.pages.create') }}" class="button button-primary">Create page</a>
    </div>

    <div class="panel">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Template</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pages as $page)
                    <tr>
                        <td>
                            <strong>{{ $page->title }}</strong>
                            <div class="table-meta">/{{ $page->slug }}</div>
                        </td>
                        <td>{{ $page->type?->value ?? $page->type }}</td>
                        <td>{{ $page->status?->value ?? $page->status }}</td>
                        <td>{{ $page->template?->name ?? 'None' }}</td>
                        <td class="table-actions">
                            <a href="{{ route('admin.pages.edit', $page) }}">Edit</a>
                            <a href="{{ route('admin.pages.preview', $page) }}" target="_blank" rel="noreferrer">Preview</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
