<?php

namespace Platform\ExperienceCms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Platform\ExperienceCms\Contracts\PagePreviewServiceContract;
use Platform\ExperienceCms\Models\Page;

class StorefrontPageController extends Controller
{
    public function __construct(protected PagePreviewServiceContract $previewService) {}

    public function home(): View
    {
        $page = Page::query()->where('slug', 'home')->firstOrFail();

        return view('theme-default::storefront.page', $this->previewService->build($page));
    }

    public function show(Page $page): View|Response
    {
        abort_unless($page->status === 'published', 404);

        return view('theme-default::storefront.page', $this->previewService->build($page));
    }

    public function preview(Page $page): View
    {
        return view('theme-default::storefront.page', $this->previewService->build($page));
    }
}
