@extends('experience-cms::admin.layout')

@section('title', 'Theme Preset')
@section('heading', $mode === 'create' ? 'Create Theme Preset' : 'Edit Theme Preset')

@section('content')
    <form method="POST" action="{{ $mode === 'create' ? route('admin.theme-presets.store') : route('admin.theme-presets.update', $themePreset) }}" class="stack-lg">
        @csrf
        @if ($mode === 'edit') @method('PUT') @endif

        <section class="panel form-grid">
            <label class="field"><span>Name</span><input type="text" name="name" value="{{ old('name', $themePreset->name) }}">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Code</span><input type="text" name="code" value="{{ old('code', $themePreset->code) }}">@error('code')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Default</span><select name="is_default"><option value="1" @selected(old('is_default', $themePreset->is_default))>Yes</option><option value="0" @selected(! old('is_default', $themePreset->is_default))>No</option></select></label>
            <label class="field"><span>Active</span><select name="is_active"><option value="1" @selected(old('is_active', $themePreset->is_active ?? true))>Yes</option><option value="0" @selected(! old('is_active', $themePreset->is_active ?? true))>No</option></select></label>
            <label class="field field-span-2"><span>Tokens JSON</span><textarea name="tokens_json" rows="14">{{ is_array(old('tokens_json', $themePreset->tokens_json)) ? json_encode(old('tokens_json', $themePreset->tokens_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : old('tokens_json', json_encode($themePreset->tokens_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>@error('tokens_json')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field field-span-2"><span>Settings JSON</span><textarea name="settings_json" rows="10">{{ is_array(old('settings_json', $themePreset->settings_json)) ? json_encode(old('settings_json', $themePreset->settings_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : old('settings_json', json_encode($themePreset->settings_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>@error('settings_json')<small class="field-error">{{ $message }}</small>@enderror</label>
        </section>

        <div class="toolbar"><button type="submit" class="button button-primary">{{ $mode === 'create' ? 'Create preset' : 'Save preset' }}</button></div>
    </form>
@endsection
