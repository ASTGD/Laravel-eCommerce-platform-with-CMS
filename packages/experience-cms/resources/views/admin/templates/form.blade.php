@extends('experience-cms::admin.layout')

@section('title', 'Template')
@section('heading', $mode === 'create' ? 'Create Template' : 'Edit Template')

@section('content')
    <form method="POST" action="{{ $mode === 'create' ? route('admin.templates.store') : route('admin.templates.update', $template) }}" class="stack-lg">
        @csrf
        @if ($mode === 'edit') @method('PUT') @endif

        <section class="panel form-grid">
            <label class="field"><span>Name</span><input type="text" name="name" value="{{ old('name', $template->name) }}">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Code</span><input type="text" name="code" value="{{ old('code', $template->code) }}">@error('code')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Page Type</span><input type="text" name="page_type" value="{{ old('page_type', $template->page_type) }}">@error('page_type')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Active</span><select name="is_active"><option value="1" @selected(old('is_active', $template->is_active ?? true))>Yes</option><option value="0" @selected(! old('is_active', $template->is_active ?? true))>No</option></select></label>
            <label class="field field-span-2">
                <span>Schema JSON</span>
                <textarea name="schema_json" rows="12">{{ is_array(old('schema_json', $template->schema_json)) ? json_encode(old('schema_json', $template->schema_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : old('schema_json', json_encode($template->schema_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
                @error('schema_json')<small class="field-error">{{ $message }}</small>@enderror
            </label>
        </section>

        <div class="toolbar"><button type="submit" class="button button-primary">{{ $mode === 'create' ? 'Create template' : 'Save template' }}</button></div>
    </form>
@endsection
