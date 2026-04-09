<?php

use Illuminate\Support\Facades\Route;
use Platform\ExperienceCms\Http\Controllers\Admin\ComponentTypeController;
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
            Route::get('{platformPage}/edit', 'edit')->name('admin.cms.pages.edit');
            Route::put('{platformPage}', 'update')->name('admin.cms.pages.update');
            Route::delete('{platformPage}', 'destroy')->name('admin.cms.pages.destroy');
            Route::get('{platformPage}/preview', 'preview')->name('admin.cms.pages.preview');
            Route::post('{platformPage}/publish', 'publish')->name('admin.cms.pages.publish');
            Route::post('{platformPage}/unpublish', 'unpublish')->name('admin.cms.pages.unpublish');
        });

    Route::prefix('cms/templates')
        ->controller(TemplateController::class)
        ->group(function () {
            Route::get('', 'index')->name('admin.cms.templates.index');
            Route::get('create', 'create')->name('admin.cms.templates.create');
            Route::post('', 'store')->name('admin.cms.templates.store');
            Route::get('{platformTemplate}/edit', 'edit')->name('admin.cms.templates.edit');
            Route::put('{platformTemplate}', 'update')->name('admin.cms.templates.update');
            Route::delete('{platformTemplate}', 'destroy')->name('admin.cms.templates.destroy');
        });

    Route::prefix('cms/section-types')
        ->controller(SectionTypeController::class)
        ->group(function () {
            Route::get('', 'index')->name('admin.cms.section-types.index');
            Route::get('create', 'create')->name('admin.cms.section-types.create');
            Route::post('', 'store')->name('admin.cms.section-types.store');
            Route::get('{platformSectionType}/edit', 'edit')->name('admin.cms.section-types.edit');
            Route::put('{platformSectionType}', 'update')->name('admin.cms.section-types.update');
            Route::delete('{platformSectionType}', 'destroy')->name('admin.cms.section-types.destroy');
        });

    Route::prefix('cms/menus')
        ->controller(MenuController::class)
        ->group(function () {
            Route::get('', 'index')->name('admin.cms.menus.index');
            Route::get('create', 'create')->name('admin.cms.menus.create');
            Route::post('', 'store')->name('admin.cms.menus.store');
            Route::get('{platformMenu}/edit', 'edit')->name('admin.cms.menus.edit');
            Route::put('{platformMenu}', 'update')->name('admin.cms.menus.update');
            Route::delete('{platformMenu}', 'destroy')->name('admin.cms.menus.destroy');
        });

    Route::prefix('cms/component-types')
        ->controller(ComponentTypeController::class)
        ->group(function () {
            Route::get('', 'index')->name('admin.cms.component-types.index');
            Route::get('create', 'create')->name('admin.cms.component-types.create');
            Route::post('', 'store')->name('admin.cms.component-types.store');
            Route::get('{platformComponentType}/edit', 'edit')->name('admin.cms.component-types.edit');
            Route::put('{platformComponentType}', 'update')->name('admin.cms.component-types.update');
            Route::delete('{platformComponentType}', 'destroy')->name('admin.cms.component-types.destroy');
        });

    Route::prefix('cms/header-configs')
        ->controller(HeaderConfigController::class)
        ->group(function () {
            Route::get('', 'index')->name('admin.cms.header-configs.index');
            Route::get('create', 'create')->name('admin.cms.header-configs.create');
            Route::post('', 'store')->name('admin.cms.header-configs.store');
            Route::get('{platformHeaderConfig}/edit', 'edit')->name('admin.cms.header-configs.edit');
            Route::put('{platformHeaderConfig}', 'update')->name('admin.cms.header-configs.update');
            Route::delete('{platformHeaderConfig}', 'destroy')->name('admin.cms.header-configs.destroy');
        });

    Route::prefix('cms/footer-configs')
        ->controller(FooterConfigController::class)
        ->group(function () {
            Route::get('', 'index')->name('admin.cms.footer-configs.index');
            Route::get('create', 'create')->name('admin.cms.footer-configs.create');
            Route::post('', 'store')->name('admin.cms.footer-configs.store');
            Route::get('{platformFooterConfig}/edit', 'edit')->name('admin.cms.footer-configs.edit');
            Route::put('{platformFooterConfig}', 'update')->name('admin.cms.footer-configs.update');
            Route::delete('{platformFooterConfig}', 'destroy')->name('admin.cms.footer-configs.destroy');
        });
});
