<?php

namespace Platform\ExperienceCms\Http\Controllers;

use Illuminate\View\View;
use Platform\ExperienceCms\Contracts\PagePreviewServiceContract;
use Platform\ExperienceCms\Models\Page;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Shop\Http\Controllers\HomeController;
use Webkul\Theme\Repositories\ThemeCustomizationRepository;

class CmsAwareHomeController extends HomeController
{
    public function __construct(
        ThemeCustomizationRepository $themeCustomizationRepository,
        CategoryRepository $categoryRepository,
        protected PagePreviewServiceContract $previewService,
    ) {
        parent::__construct($themeCustomizationRepository, $categoryRepository);
    }

    public function index(): View
    {
        $page = Page::query()
            ->published()
            ->where('slug', 'home')
            ->first();

        if (! $page) {
            return parent::index();
        }

        return view('theme-default::storefront.page', $this->previewService->build($page));
    }
}
