<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Webkul\Attribute\Repositories\AttributeOptionRepository;

uses(TestCase::class);

it('stores swatch uploads as square canvases for admin image swatches', function () {
    Storage::fake('public');

    $attributeId = DB::table('attributes')->insertGetId([
        'code' => 'test_color_swatch',
        'admin_name' => 'Test Color Swatch',
        'type' => 'select',
        'swatch_type' => 'image',
        'validation' => null,
        'position' => 1,
        'is_required' => 1,
        'is_unique' => 0,
        'is_filterable' => 1,
        'is_comparable' => 1,
        'is_configurable' => 1,
        'is_user_defined' => 1,
        'is_visible_on_front' => 1,
        'value_per_locale' => 0,
        'value_per_channel' => 0,
        'enable_wysiwyg' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $file = UploadedFile::fake()->image('swatch-source.jpg', 320, 180);

    $option = app(AttributeOptionRepository::class)->create([
        'attribute_id' => $attributeId,
        'admin_name' => 'Sky Blue',
        'sort_order' => 1,
        'swatch_value' => $file,
    ]);

    expect($option->swatch_value)->toStartWith('attribute_option/');
    expect($option->swatch_value_url)->toContain('/storage/attribute_option/');

    Storage::disk('public')->assertExists($option->swatch_value);

    $image = imagecreatefromstring(Storage::disk('public')->get($option->swatch_value));

    expect(imagesx($image))->toBe(220)
        ->and(imagesy($image))->toBe(220);

    imagedestroy($image);
});
