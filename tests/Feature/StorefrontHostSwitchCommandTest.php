<?php

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Core\Models\Channel;

uses(TestCase::class);

it('synchronizes app url, channel hostname, and local footer links', function () {
    $channel = Channel::query()->firstOrFail();

    $channel->forceFill([
        'hostname' => 'http://127.0.0.1:8001',
    ])->save();

    $themeCustomizationId = DB::table('theme_customizations')->insertGetId([
        'theme_code' => $channel->theme,
        'type' => 'footer_links',
        'name' => 'Test Footer Links',
        'sort_order' => 999,
        'status' => 1,
        'channel_id' => $channel->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $translationId = DB::table('theme_customization_translations')->insertGetId([
        'theme_customization_id' => $themeCustomizationId,
        'locale' => 'en',
        'options' => json_encode([
            'column_1' => [
                [
                    'title' => 'About Us',
                    'url' => 'http://127.0.0.1:8001/page/about-us',
                    'sort_order' => 1,
                ],
                [
                    'title' => 'External',
                    'url' => 'https://example.com/help',
                    'sort_order' => 2,
                ],
            ],
        ], JSON_UNESCAPED_SLASHES),
    ]);

    $envFile = tempnam(sys_get_temp_dir(), 'storefront-host-env-');

    file_put_contents($envFile, implode(PHP_EOL, [
        'APP_NAME=ASTGD',
        'APP_URL=http://127.0.0.1:8001',
        'APP_ENV=testing',
        '',
    ]));

    $this->artisan('platform:storefront-host', [
        'target' => '192.168.1.136:8001',
        '--channel' => $channel->code,
        '--env-file' => $envFile,
    ])->assertExitCode(0);

    expect(file_get_contents($envFile))
        ->toContain('APP_URL=http://192.168.1.136:8001');

    expect($channel->fresh()->hostname)
        ->toBe('http://192.168.1.136:8001');

    $options = json_decode(DB::table('theme_customization_translations')->where('id', $translationId)->value('options'), true);

    expect($options['column_1'][0]['url'])
        ->toBe('/page/about-us')
        ->and($options['column_1'][1]['url'])
        ->toBe('https://example.com/help');

    @unlink($envFile);
});
