@extends('experience-cms::admin.layout')

@section('title', 'Menus')
@section('heading', 'Menus')

@section('content')
    <div class="toolbar">
        <a href="{{ route('admin.menus.create') }}" class="button button-primary">Create menu</a>
    </div>

    <div class="panel">
        <table class="data-table">
            <thead>
                <tr><th>Name</th><th>Code</th><th>Location</th><th>Items</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($menus as $menu)
                    <tr>
                        <td>{{ $menu->name }}</td>
                        <td>{{ $menu->code }}</td>
                        <td>{{ $menu->location }}</td>
                        <td>{{ $menu->items_count }}</td>
                        <td class="table-actions"><a href="{{ route('admin.menus.edit', $menu) }}">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
