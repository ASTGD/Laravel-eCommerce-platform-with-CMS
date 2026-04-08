@extends('experience-cms::admin.layout')

@section('title', 'Section Types')
@section('heading', 'Section Types')

@section('content')
    <div class="toolbar">
        <a href="{{ route('admin.section-types.create') }}" class="button button-primary">Create section type</a>
    </div>

    <div class="panel">
        <table class="data-table">
            <thead>
                <tr><th>Name</th><th>Code</th><th>Category</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($sectionTypes as $sectionType)
                    <tr>
                        <td>{{ $sectionType->name }}</td>
                        <td>{{ $sectionType->code }}</td>
                        <td>{{ $sectionType->category }}</td>
                        <td class="table-actions"><a href="{{ route('admin.section-types.edit', $sectionType) }}">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
