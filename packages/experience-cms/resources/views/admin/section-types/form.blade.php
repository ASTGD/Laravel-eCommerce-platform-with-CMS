@extends('experience-cms::admin.layout')

@section('title', 'Section Type')
@section('heading', $mode === 'create' ? 'Create Section Type' : 'Edit Section Type')

@section('content')
    <form method="POST" action="{{ $mode === 'create' ? route('admin.section-types.store') : route('admin.section-types.update', $sectionType) }}" class="stack-lg">
        @csrf
        @if ($mode === 'edit') @method('PUT') @endif

        <section class="panel form-grid">
            <label class="field"><span>Name</span><input type="text" name="name" value="{{ old('name', $sectionType->name) }}">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Code</span><input type="text" name="code" value="{{ old('code', $sectionType->code) }}">@error('code')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Category</span><input type="text" name="category" value="{{ old('category', $sectionType->category) }}">@error('category')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Supports Components</span><select name="supports_components"><option value="1" @selected(old('supports_components', $sectionType->supports_components))>Yes</option><option value="0" @selected(! old('supports_components', $sectionType->supports_components))>No</option></select></label>
            <label class="field"><span>Active</span><select name="is_active"><option value="1" @selected(old('is_active', $sectionType->is_active ?? true))>Yes</option><option value="0" @selected(! old('is_active', $sectionType->is_active ?? true))>No</option></select></label>
            <label class="field field-span-2"><span>Config Schema JSON</span><textarea name="config_schema_json" rows="12">{{ is_array(old('config_schema_json', $sectionType->config_schema_json)) ? json_encode(old('config_schema_json', $sectionType->config_schema_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : old('config_schema_json', json_encode($sectionType->config_schema_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>@error('config_schema_json')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field field-span-2"><span>Allowed Data Sources JSON</span><textarea name="allowed_data_sources_json" rows="8">{{ is_array(old('allowed_data_sources_json', $sectionType->allowed_data_sources_json)) ? json_encode(old('allowed_data_sources_json', $sectionType->allowed_data_sources_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : old('allowed_data_sources_json', json_encode($sectionType->allowed_data_sources_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>@error('allowed_data_sources_json')<small class="field-error">{{ $message }}</small>@enderror</label>
        </section>

        <div class="toolbar"><button type="submit" class="button button-primary">{{ $mode === 'create' ? 'Create section type' : 'Save section type' }}</button></div>
    </form>
@endsection
