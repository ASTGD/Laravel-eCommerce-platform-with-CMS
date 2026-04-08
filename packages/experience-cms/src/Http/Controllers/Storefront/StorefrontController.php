<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use ExperienceCms\Enums\PageType;
use ExperienceCms\Models\Page;
use ExperienceCms\Services\PageViewBuilder;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(): View
    {
        $pageViewBuilder = app(PageViewBuilder::class);
        $page = Page::query()
            ->published()
            ->where('type', PageType::Homepage->value)
            ->firstOrFail();

        return view('theme-default::storefront.page', $pageViewBuilder->build($page));
    }

    public function show(string $slug): View
    {
        $pageViewBuilder = app(PageViewBuilder::class);
        $page = Page::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('theme-default::storefront.page', $pageViewBuilder->build($page));
    }

    public function preview(string $page): View
    {
        $page = Page::query()->findOrFail($page);
        $pageViewBuilder = app(PageViewBuilder::class);

        return view('theme-default::storefront.page', $pageViewBuilder->build($page, true));
    }
}
