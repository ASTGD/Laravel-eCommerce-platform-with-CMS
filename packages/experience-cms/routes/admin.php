<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureAdminUser;
use ExperienceCms\Http\Controllers\Admin\DashboardController;
use ExperienceCms\Http\Controllers\Admin\MenuController;
use ExperienceCms\Http\Controllers\Admin\MenuItemController;
use ExperienceCms\Http\Controllers\Admin\PageController;
use ExperienceCms\Http\Controllers\Admin\PageSectionController;
use ExperienceCms\Http\Controllers\Admin\SectionTypeController;
use ExperienceCms\Http\Controllers\Admin\TemplateController;
use ExperienceCms\Http\Controllers\Admin\ThemePresetController;
use ExperienceCms\Http\Controllers\Storefront\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureAdminUser::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::resource('pages', PageController::class)->except(['show']);
        Route::post('pages/{page}/publish', [PageController::class, 'publish'])->name('pages.publish');
        Route::post('pages/{page}/unpublish', [PageController::class, 'unpublish'])->name('pages.unpublish');
        Route::get('pages/{page}/preview', [StorefrontController::class, 'preview'])->name('pages.preview');
        Route::resource('pages.sections', PageSectionController::class)->except(['index', 'show']);

        Route::resource('templates', TemplateController::class)->except(['show']);
        Route::resource('section-types', SectionTypeController::class)->except(['show'])->parameter('section-types', 'section_type');
        Route::resource('theme-presets', ThemePresetController::class)->except(['show'])->parameter('theme-presets', 'theme_preset');
        Route::resource('menus', MenuController::class)->except(['show']);
        Route::resource('menus.items', MenuItemController::class)->except(['index', 'show'])->parameter('items', 'item');
    });
