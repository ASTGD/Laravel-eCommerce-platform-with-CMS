@extends('experience-cms::admin.layout')

@section('title', 'Menu')
@section('heading', $mode === 'create' ? 'Create Menu' : 'Edit Menu')

@section('content')
    <form method="POST" action="{{ $mode === 'create' ? route('admin.menus.store') : route('admin.menus.update', $menu) }}" class="stack-lg">
        @csrf
        @if ($mode === 'edit') @method('PUT') @endif

        <section class="panel form-grid">
            <label class="field"><span>Name</span><input type="text" name="name" value="{{ old('name', $menu->name) }}">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Code</span><input type="text" name="code" value="{{ old('code', $menu->code) }}">@error('code')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Location</span><input type="text" name="location" value="{{ old('location', $menu->location) }}">@error('location')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Active</span><select name="is_active"><option value="1" @selected(old('is_active', $menu->is_active ?? true))>Yes</option><option value="0" @selected(! old('is_active', $menu->is_active ?? true))>No</option></select></label>
        </section>

        <div class="toolbar">
            <button type="submit" class="button button-primary">{{ $mode === 'create' ? 'Create menu' : 'Save menu' }}</button>
            @if ($mode === 'edit')
                <a href="{{ route('admin.menus.items.create', $menu) }}" class="button button-secondary">Add item</a>
            @endif
        </div>
    </form>

    @if ($mode === 'edit')
        <section class="panel stack-md">
            <h2>Menu items</h2>

            <table class="data-table">
                <thead>
                    <tr><th>Title</th><th>Target</th><th>Order</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($menu->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->title }}</strong>
                                @if ($item->children->isNotEmpty())
                                    <div class="table-meta">{{ $item->children->pluck('title')->join(', ') }}</div>
                                @endif
                            </td>
                            <td>{{ $item->target }}</td>
                            <td>{{ $item->sort_order }}</td>
                            <td class="table-actions">
                                <a href="{{ route('admin.menus.items.edit', [$menu, $item]) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.menus.items.destroy', [$menu, $item]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button-link">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
@endsection
