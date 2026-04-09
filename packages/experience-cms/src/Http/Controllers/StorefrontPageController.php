<?php

namespace Platform\ExperienceCms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Platform\ExperienceCms\Contracts\PagePreviewServiceContract;
use Platform\ExperienceCms\Models\Page;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Shop\Http\Controllers\HomeController;

class StorefrontPageController extends Controller
{
    public function __construct(
        protected PagePreviewServiceContract $previewService,
        protected CategoryRepository $categories,
        protected ProductRepository $products,
    ) {}

    public function home(): View
    {
        $page = Page::query()
            ->published()
            ->where('slug', 'home')
            ->first();

        if (! $page) {
            return app(HomeController::class)->index();
        }

        return view('theme-default::storefront.page', $this->previewService->build($page));
    }

    public function homePreview(): View
    {
        $page = Page::query()->where('slug', 'home')->firstOrFail();

        return view('theme-default::storefront.page', $this->previewService->build($page, ['preview' => true]));
    }

    public function show(Page $platformPage): View|Response
    {
        $page = $platformPage;

        abort_unless($page->isPublished(), 404);

        return view('theme-default::storefront.page', $this->previewService->build($page));
    }

    public function preview(Page $platformPage): View
    {
        $page = $platformPage;

        return view('theme-default::storefront.page', $this->previewService->build($page, ['preview' => true]));
    }

    public function previewCategory(Page $platformPage, string $categorySlug): View
    {
        $category = $this->categories->findBySlugOrFail($categorySlug);

        return view('theme-default::storefront.category-page', $this->previewService->build($platformPage, [
            'category' => $category,
            'query' => request()->query(),
            'preview' => true,
        ]));
    }

    public function previewProduct(Page $platformPage, string $productSlug): View
    {
        $searchEngine = core()->getConfigData('catalog.products.search.engine') === 'elastic'
            ? core()->getConfigData('catalog.products.search.storefront_mode')
            : 'database';

        $product = $this->products
            ->setSearchEngine($searchEngine ?: 'database')
            ->findBySlugOrFail($productSlug);

        return view('theme-default::storefront.product-page', $this->previewService->build($platformPage, [
            'product' => $product,
            'query' => request()->query(),
            'preview' => true,
        ]));
    }
}
