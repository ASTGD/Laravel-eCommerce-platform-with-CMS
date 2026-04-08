@extends('experience-cms::admin.layout')

@section('title', 'Theme Presets')
@section('heading', 'Theme Presets')

@section('content')
    <div class="toolbar">
        <a href="{{ route('admin.theme-presets.create') }}" class="button button-primary">Create preset</a>
    </div>

    <div class="panel">
        <table class="data-table">
            <thead>
                <tr><th>Name</th><th>Code</th><th>Default</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($themePresets as $themePreset)
                    <tr>
                        <td>{{ $themePreset->name }}</td>
                        <td>{{ $themePreset->code }}</td>
                        <td>{{ $themePreset->is_default ? 'Yes' : 'No' }}</td>
                        <td class="table-actions"><a href="{{ route('admin.theme-presets.edit', $themePreset) }}">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
