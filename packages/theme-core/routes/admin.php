<?php

use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Platform\ThemeCore\Http\Controllers\Admin\ThemePresetController;

Route::group([
    'middleware' => ['admin', NoCacheMiddleware::class],
    'prefix'     => config('app.admin_url'),
], function () {
    Route::prefix('theme/presets')
        ->controller(ThemePresetController::class)
        ->group(function () {
            Route::get('', 'index')->middleware('platform.acl:theme.presets')->name('admin.theme.presets.index');
            Route::get('create', 'create')->middleware('platform.acl:theme.presets.create')->name('admin.theme.presets.create');
            Route::post('', 'store')->middleware('platform.acl:theme.presets.create')->name('admin.theme.presets.store');
            Route::get('{platformThemePreset}/edit', 'edit')->middleware('platform.acl:theme.presets.edit')->name('admin.theme.presets.edit');
            Route::put('{platformThemePreset}', 'update')->middleware('platform.acl:theme.presets.edit')->name('admin.theme.presets.update');
            Route::delete('{platformThemePreset}', 'destroy')->middleware('platform.acl:theme.presets.delete')->name('admin.theme.presets.destroy');
        });
});
