@extends('experience-cms::admin.layout')

@section('title', 'Page Section')
@section('heading', $mode === 'create' ? 'Add Page Section' : 'Edit Page Section')

@section('content')
    <form method="POST" action="{{ $mode === 'create' ? route('admin.pages.sections.store', $page) : route('admin.pages.sections.update', [$page, $section]) }}" class="stack-lg">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <section class="panel form-grid">
            <label class="field">
                <span>Section Type</span>
                <select name="section_type_id" onchange="window.location='{{ route($mode === 'create' ? 'admin.pages.sections.create' : 'admin.pages.sections.edit', $mode === 'create' ? [$page] : [$page, $section]) }}?section_type_id='+this.value;">
                    @foreach ($sectionTypes as $sectionType)
                        <option value="{{ $sectionType->id }}" @selected((int) old('section_type_id', $section->section_type_id ?? $selectedType?->id) === $sectionType->id)>{{ $sectionType->name }}</option>
                    @endforeach
                </select>
                @error('section_type_id')<small class="field-error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Title</span>
                <input type="text" name="title" value="{{ old('title', $section->title) }}">
                @error('title')<small class="field-error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Sort Order</span>
                <input type="number" name="sort_order" value="{{ old('sort_order', $section->sort_order) }}" min="0">
                @error('sort_order')<small class="field-error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Active</span>
                <select name="is_active">
                    <option value="1" @selected(old('is_active', $section->is_active ?? true))>Yes</option>
                    <option value="0" @selected(! old('is_active', $section->is_active ?? true))>No</option>
                </select>
            </label>

            @include('experience-cms::admin.partials.schema-fields', ['definition' => $definition, 'settings' => $section->settings_json ?? []])

            @if (($definition?->supportedDataSources() ?? []) !== [])
                <label class="field">
                    <span>Data Source Type</span>
                    <select name="data_source_type">
                        <option value="">No data source</option>
                        @foreach ($definition->supportedDataSources() as $source)
                            <option value="{{ $source }}" @selected(old('data_source_type', $section->data_source_type) === $source)>{{ str($source)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                    @error('data_source_type')<small class="field-error">{{ $message }}</small>@enderror
                </label>

                <label class="field field-span-2">
                    <span>Data Source Payload JSON</span>
                    <textarea name="data_source_payload_json" rows="10">{{ is_array(old('data_source_payload_json', $section->data_source_payload_json)) ? json_encode(old('data_source_payload_json', $section->data_source_payload_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : old('data_source_payload_json', json_encode($section->data_source_payload_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
                    @error('data_source_payload_json')<small class="field-error">{{ $message }}</small>@enderror
                </label>
            @endif
        </section>

        <div class="toolbar">
            <button type="submit" class="button button-primary">{{ $mode === 'create' ? 'Add section' : 'Save section' }}</button>
            <a href="{{ route('admin.pages.edit', $page) }}" class="button button-secondary">Back to page</a>
        </div>
    </form>
@endsection
