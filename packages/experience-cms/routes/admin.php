<?php

use Illuminate\Support\Facades\Route;
use Platform\ExperienceCms\Http\Controllers\Admin\FooterConfigController;
use Platform\ExperienceCms\Http\Controllers\Admin\HeaderConfigController;
use Platform\ExperienceCms\Http\Controllers\Admin\MenuController;
use Platform\ExperienceCms\Http\Controllers\Admin\PageController;
use Platform\ExperienceCms\Http\Controllers\Admin\SectionTypeController;
use Platform\ExperienceCms\Http\Controllers\Admin\TemplateController;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

Route::group([
    'middleware' => ['admin', NoCacheMiddleware::class],
    'prefix'     => config('app.admin_url'),
], function () {
    Route::prefix('cms/pages')
        ->controller(PageController::class)
        ->group(function () {
            Route::get('', 'index')->name('admin.cms.pages.index');
            Route::get('create', 'create')->name('admin.cms.pages.create');
            Route::post('', 'store')->name('admin.cms.pages.store');
            Route::get('{page}/edit', 'edit')->name('admin.cms.pages.edit');
            Route::put('{page}', 'update')->name('admin.cms.pages.update');
            Route::delete('{page}', 'destroy')->name('admin.cms.pages.destroy');
            Route::get('{page}/preview', 'preview')->name('admin.cms.pages.preview');
            Route::post('{page}/publish', 'publish')->name('admin.cms.pages.publish');
        });

    Route::prefix('cms/templates')
        ->controller(TemplateController::class)
        ->group(function () {
            Route::get('', 'index')->name('admin.cms.templates.index');
            Route::get('create', 'create')->name('admin.cms.templates.create');
            Route::post('', 'store')->name('admin.cms.templates.store');
            Route::get('{template}/edit', 'edit')->name('admin.cms.templates.edit');
            Route::put('{template}', 'update')->name('admin.cms.templates.update');
            Route::delete('{template}', 'destroy')->name('admin.cms.templates.destroy');
        });

    Route::prefix('cms/section-types')
        ->controller(SectionTypeController::class)
        ->group(function () {
            Route::get('', 'index')->name('admin.cms.section-types.index');
            Route::get('create', 'create')->name('admin.cms.section-types.create');
            Route::post('', 'store')->name('admin.cms.section-types.store');
            Route::get('{sectionType}/edit', 'edit')->name('admin.cms.section-types.edit');
            Route::put('{sectionType}', 'update')->name('admin.cms.section-types.update');
            Route::delete('{sectionType}', 'destroy')->name('admin.cms.section-types.destroy');
        });

    Route::prefix('cms/menus')
        ->controller(MenuController::class)
        ->group(function () {
            Route::get('', 'index')->name('admin.cms.menus.index');
            Route::get('create', 'create')->name('admin.cms.menus.create');
            Route::post('', 'store')->name('admin.cms.menus.store');
            Route::get('{menu}/edit', 'edit')->name('admin.cms.menus.edit');
            Route::put('{menu}', 'update')->name('admin.cms.menus.update');
            Route::delete('{menu}', 'destroy')->name('admin.cms.menus.destroy');
        });

    Route::prefix('cms/header-configs')
        ->controller(HeaderConfigController::class)
        ->group(function () {
            Route::get('', 'index')->name('admin.cms.header-configs.index');
            Route::get('create', 'create')->name('admin.cms.header-configs.create');
            Route::post('', 'store')->name('admin.cms.header-configs.store');
            Route::get('{headerConfig}/edit', 'edit')->name('admin.cms.header-configs.edit');
            Route::put('{headerConfig}', 'update')->name('admin.cms.header-configs.update');
            Route::delete('{headerConfig}', 'destroy')->name('admin.cms.header-configs.destroy');
        });

    Route::prefix('cms/footer-configs')
        ->controller(FooterConfigController::class)
        ->group(function () {
            Route::get('', 'index')->name('admin.cms.footer-configs.index');
            Route::get('create', 'create')->name('admin.cms.footer-configs.create');
            Route::post('', 'store')->name('admin.cms.footer-configs.store');
            Route::get('{footerConfig}/edit', 'edit')->name('admin.cms.footer-configs.edit');
            Route::put('{footerConfig}', 'update')->name('admin.cms.footer-configs.update');
            Route::delete('{footerConfig}', 'destroy')->name('admin.cms.footer-configs.destroy');
        });
});
