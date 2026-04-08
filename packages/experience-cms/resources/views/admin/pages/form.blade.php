@extends('experience-cms::admin.layout')

@section('title', 'Page')
@section('heading', $mode === 'create' ? 'Create Page' : 'Edit Page')

@section('content')
    <form method="POST" action="{{ $mode === 'create' ? route('admin.pages.store') : route('admin.pages.update', $page) }}" class="stack-lg">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <section class="panel form-grid">
            <label class="field">
                <span>Title</span>
                <input type="text" name="title" value="{{ old('title', $page->title) }}" required>
                @error('title')<small class="field-error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Slug</span>
                <input type="text" name="slug" value="{{ old('slug', $page->slug) }}" required>
                @error('slug')<small class="field-error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Page Type</span>
                <select name="type">
                    @foreach (\ExperienceCms\Enums\PageType::cases() as $case)
                        <option value="{{ $case->value }}" @selected(old('type', $page->type?->value ?? $page->type) === $case->value)>{{ str($case->value)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
                @error('type')<small class="field-error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Template</span>
                <select name="template_id">
                    @foreach ($templates as $template)
                        <option value="{{ $template->id }}" @selected((int) old('template_id', $page->template_id) === $template->id)>{{ $template->name }}</option>
                    @endforeach
                </select>
                @error('template_id')<small class="field-error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Status</span>
                <select name="status">
                    @foreach (\ExperienceCms\Enums\PageStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected(old('status', $page->status?->value ?? $page->status) === $case->value)>{{ str($case->value)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
                @error('status')<small class="field-error">{{ $message }}</small>@enderror
            </label>
        </section>

        <div class="toolbar">
            <button type="submit" class="button button-primary">{{ $mode === 'create' ? 'Create page' : 'Save page' }}</button>

            @if ($mode === 'edit')
                <a href="{{ route('admin.pages.preview', $page) }}" class="button button-secondary" target="_blank" rel="noreferrer">Preview</a>
            @endif
        </div>
    </form>

    @if ($mode === 'edit')
        <div class="toolbar">
            <form method="POST" action="{{ route('admin.pages.publish', $page) }}">
                @csrf
                <button type="submit" class="button button-secondary">Publish</button>
            </form>

            <form method="POST" action="{{ route('admin.pages.unpublish', $page) }}">
                @csrf
                <button type="submit" class="button button-secondary">Unpublish</button>
            </form>
        </div>
    @endif

    @if ($mode === 'edit')
        <section class="panel stack-md">
            <div class="toolbar">
                <div>
                    <span class="eyebrow">Page Sections</span>
                    <h2>{{ $page->sections->count() }} configured</h2>
                </div>
                <a href="{{ route('admin.pages.sections.create', [$page, 'section_type_id' => optional($page->sections->first()?->sectionType)->id]) }}" class="button button-primary">Add section</a>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($page->sections as $section)
                        <tr>
                            <td>{{ $section->sort_order }}</td>
                            <td>{{ $section->title ?: 'Untitled section' }}</td>
                            <td>{{ $section->sectionType?->name ?? 'Unknown type' }}</td>
                            <td class="table-actions">
                                <a href="{{ route('admin.pages.sections.edit', [$page, $section]) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.pages.sections.destroy', [$page, $section]) }}">
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
