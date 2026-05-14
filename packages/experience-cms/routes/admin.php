<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Platform\ExperienceCms\Http\Controllers\Admin\CmsStudioController;
use Platform\ExperienceCms\Http\Controllers\Admin\DashboardController;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

Route::group([
    'middleware' => [NoCacheMiddleware::class, 'admin'],
    'prefix' => config('app.admin_url'),
], function () {
    Route::controller(DashboardController::class)
        ->prefix('cms/dashboard')
        ->group(function () {
            Route::get('', 'index')->middleware('platform.acl:cms.platform')->name('admin.cms.dashboard.index');
        });

    Route::prefix('cms')
        ->group(function () {
            Route::get('', fn (Request $request) => redirect()->route('admin.cms.index', $request->query()))
                ->middleware('platform.acl:cms.platform');

            Route::controller(CmsStudioController::class)->group(function () {
                Route::get('studio', 'index')->middleware('platform.acl:cms.platform')->name('admin.cms.index');
                Route::get('settings', 'settings')->middleware('platform.acl:cms.platform.settings')->name('admin.cms.settings.index');
                Route::post('header', 'updateHeader')->middleware('platform.acl:cms.platform.header')->name('admin.cms.header.update');
                Route::post('footer', 'updateFooter')->middleware('platform.acl:cms.platform.footer')->name('admin.cms.footer.update');
                Route::post('navigation', 'updateNavigation')->middleware('platform.acl:cms.platform.navigation')->name('admin.cms.navigation.update');
                Route::post('homepage', 'updateHomepage')->middleware('platform.acl:cms.platform.homepage')->name('admin.cms.homepage.update');
            });
        });
});
