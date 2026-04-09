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
            Route::get('', 'index')->name('admin.theme.presets.index');
            Route::get('create', 'create')->name('admin.theme.presets.create');
            Route::post('', 'store')->name('admin.theme.presets.store');
            Route::get('{platformThemePreset}/edit', 'edit')->name('admin.theme.presets.edit');
            Route::put('{platformThemePreset}', 'update')->name('admin.theme.presets.update');
            Route::delete('{platformThemePreset}', 'destroy')->name('admin.theme.presets.destroy');
        });
});
