@php($settings = $settings ?? [])

@foreach ($definition?->configSchema() ?? [] as $field)
    @php($key = $field['key'])
    @php($label = $field['label'] ?? str($key)->headline())
    @php($type = $field['type'] ?? 'text')
    @php($value = old("settings_json.$key", $settings[$key] ?? data_get($definition?->defaultSettings(), $key)))

    <label class="field field-span-2">
        <span>{{ $label }}</span>

        @if ($type === 'textarea')
            <textarea name="settings_json[{{ $key }}]" rows="6">{{ $value }}</textarea>
        @elseif ($type === 'json')
            <textarea name="settings_json[{{ $key }}]" rows="10">{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $value }}</textarea>
        @else
            <input type="text" name="settings_json[{{ $key }}]" value="{{ $value }}">
        @endif

        @error("settings_json.$key")
            <small class="field-error">{{ $message }}</small>
        @enderror
    </label>
@endforeach
