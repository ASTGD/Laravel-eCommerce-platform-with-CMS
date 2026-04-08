@extends('experience-cms::admin.layout')

@section('title', 'Templates')
@section('heading', 'Templates')

@section('content')
    <div class="toolbar">
        <a href="{{ route('admin.templates.create') }}" class="button button-primary">Create template</a>
    </div>

    <div class="panel">
        <table class="data-table">
            <thead>
                <tr><th>Name</th><th>Code</th><th>Page Type</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($templates as $template)
                    <tr>
                        <td>{{ $template->name }}</td>
                        <td>{{ $template->code }}</td>
                        <td>{{ $template->page_type }}</td>
                        <td class="table-actions"><a href="{{ route('admin.templates.edit', $template) }}">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
