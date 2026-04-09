<?php

namespace Platform\ExperienceCms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Platform\ExperienceCms\Contracts\PageAssignmentResolverContract;
use Platform\ExperienceCms\Contracts\PagePreviewServiceContract;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Marketing\Repositories\URLRewriteRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Shop\Http\Controllers\ProductsCategoriesProxyController;
use Webkul\Theme\Repositories\ThemeCustomizationRepository;

class CmsAwareProductsCategoriesProxyController extends ProductsCategoriesProxyController
{
    public function __construct(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        ThemeCustomizationRepository $themeCustomizationRepository,
        URLRewriteRepository $urlRewriteRepository,
        protected PageAssignmentResolverContract $assignments,
        protected PagePreviewServiceContract $previewService,
    ) {
        parent::__construct($categoryRepository, $productRepository, $themeCustomizationRepository, $urlRewriteRepository);
    }

    public function index(Request $request)
    {
        $slugOrURLKey = urldecode(trim($request->getPathInfo(), '/'));

        if (! preg_match('/^([\p{L}\p{N}\p{M}\x{0900}-\x{097F}\x{0590}-\x{05FF}\x{0600}-\x{06FF}\x{0400}-\x{04FF}_-]+\/?)+$/u', $slugOrURLKey)) {
            return parent::index($request);
        }

        $category = $this->categoryRepository->findBySlug($slugOrURLKey);

        if ($category) {
            $assignment = $this->assignments->resolveForCategory($category);

            if ($assignment?->page) {
                return view('theme-default::storefront.category-page', $this->previewService->build($assignment->page, [
                    'category' => $category,
                    'query' => $request->query(),
                ]));
            }

            return parent::index($request);
        }

        $searchEngine = core()->getConfigData('catalog.products.search.engine') === 'elastic'
            ? core()->getConfigData('catalog.products.search.storefront_mode')
            : 'database';

        $product = $this->productRepository
            ->setSearchEngine($searchEngine ?: 'database')
            ->findBySlug($slugOrURLKey);

        if ($product) {
            if (! $product->url_key || ! $product->visible_individually || ! $product->status) {
                abort(404);
            }

            $assignment = $this->assignments->resolveForProduct($product);

            if ($assignment?->page) {
                return view('theme-default::storefront.product-page', $this->previewService->build($assignment->page, [
                    'product' => $product,
                    'query' => $request->query(),
                ]));
            }

            return parent::index($request);
        }

        return parent::index($request);
    }
}
