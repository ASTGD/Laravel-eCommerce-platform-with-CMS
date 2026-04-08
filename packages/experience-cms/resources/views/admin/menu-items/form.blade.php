@extends('experience-cms::admin.layout')

@section('title', 'Menu Item')
@section('heading', $mode === 'create' ? 'Create Menu Item' : 'Edit Menu Item')

@section('content')
    <form method="POST" action="{{ $mode === 'create' ? route('admin.menus.items.store', $menu) : route('admin.menus.items.update', [$menu, $item]) }}" class="stack-lg">
        @csrf
        @if ($mode === 'edit') @method('PUT') @endif

        <section class="panel form-grid">
            <label class="field">
                <span>Parent Item</span>
                <select name="parent_id">
                    <option value="">Top level</option>
                    @foreach ($menu->items as $candidate)
                        <option value="{{ $candidate->id }}" @selected((int) old('parent_id', $item->parent_id) === $candidate->id)>{{ $candidate->title }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field"><span>Title</span><input type="text" name="title" value="{{ old('title', $item->title) }}">@error('title')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Type</span><input type="text" name="type" value="{{ old('type', $item->type ?: 'url') }}">@error('type')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Target</span><input type="text" name="target" value="{{ old('target', $item->target) }}">@error('target')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Sort Order</span><input type="number" name="sort_order" value="{{ old('sort_order', $item->sort_order) }}" min="0"></label>
            <label class="field"><span>Active</span><select name="is_active"><option value="1" @selected(old('is_active', $item->is_active ?? true))>Yes</option><option value="0" @selected(! old('is_active', $item->is_active ?? true))>No</option></select></label>
            <label class="field field-span-2"><span>Settings JSON</span><textarea name="settings_json" rows="8">{{ is_array(old('settings_json', $item->settings_json)) ? json_encode(old('settings_json', $item->settings_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : old('settings_json', json_encode($item->settings_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>@error('settings_json')<small class="field-error">{{ $message }}</small>@enderror</label>
        </section>

        <div class="toolbar"><button type="submit" class="button button-primary">{{ $mode === 'create' ? 'Create item' : 'Save item' }}</button><a href="{{ route('admin.menus.edit', $menu) }}" class="button button-secondary">Back to menu</a></div>
    </form>
@endsection
