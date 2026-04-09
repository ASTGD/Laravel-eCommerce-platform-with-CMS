<?php

namespace Platform\ExperienceCms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Platform\ExperienceCms\Contracts\PagePreviewServiceContract;
use Platform\ExperienceCms\Models\Page;
use Webkul\Shop\Http\Controllers\HomeController;

class StorefrontPageController extends Controller
{
    public function __construct(protected PagePreviewServiceContract $previewService) {}

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

        return view('theme-default::storefront.page', $this->previewService->build($page));
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

        return view('theme-default::storefront.page', $this->previewService->build($page));
    }
}
