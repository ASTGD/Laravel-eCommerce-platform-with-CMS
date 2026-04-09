<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Platform\ExperienceCms\Http\Requests\Admin\PageAssignmentRequest;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageAssignment;
use Webkul\Category\Models\Category;
use Webkul\Product\Models\ProductFlat;

class PageAssignmentController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.assignments.index', [
            'assignments' => PageAssignment::query()
                ->with('page')
                ->orderBy('page_type')
                ->orderByDesc('priority')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return $this->formView(new PageAssignment([
            'page_type' => 'category_page',
            'scope_type' => PageAssignment::SCOPE_GLOBAL,
            'priority' => 0,
            'is_active' => true,
        ]));
    }

    public function store(PageAssignmentRequest $request): RedirectResponse
    {
        $assignment = PageAssignment::query()->create($request->payload());

        return redirect()
            ->route('admin.cms.assignments.edit', $assignment)
            ->with('success', 'Assignment created.');
    }

    public function edit(PageAssignment $platformAssignment): View
    {
        return $this->formView($platformAssignment->load('page'));
    }

    public function update(PageAssignmentRequest $request, PageAssignment $platformAssignment): RedirectResponse
    {
        $platformAssignment->update($request->payload());

        return redirect()
            ->route('admin.cms.assignments.edit', $platformAssignment)
            ->with('success', 'Assignment updated.');
    }

    public function destroy(PageAssignment $platformAssignment): RedirectResponse
    {
        $platformAssignment->delete();

        return redirect()
            ->route('admin.cms.assignments.index')
            ->with('success', 'Assignment deleted.');
    }

    public function preview(PageAssignment $platformAssignment): RedirectResponse
    {
        $expiresAt = now()->addMinutes(30);

        if ($platformAssignment->page_type === 'category_page') {
            $category = Category::query()->findOrFail($platformAssignment->entity_id);

            return redirect(URL::temporarySignedRoute(
                'platform.storefront.category-pages.preview',
                $expiresAt,
                ['platformPage' => $platformAssignment->page->slug, 'categorySlug' => $category->slug]
            ));
        }

        $product = ProductFlat::query()->where('product_id', $platformAssignment->entity_id)->firstOrFail();

        return redirect(URL::temporarySignedRoute(
            'platform.storefront.product-pages.preview',
            $expiresAt,
            ['platformPage' => $platformAssignment->page->slug, 'productSlug' => $product->url_key]
        ));
    }

    protected function formView(PageAssignment $assignment): View
    {
        return view('experience-cms::admin.assignments.form', [
            'assignment' => $assignment,
            'categoryPages' => Page::query()->where('type', 'category_page')->orderBy('title')->get(),
            'productPages' => Page::query()->where('type', 'product_page')->orderBy('title')->get(),
            'categories' => Category::query()
                ->get()
                ->sortBy(fn (Category $category) => mb_strtolower($category->name ?: ''))
                ->values(),
            'products' => ProductFlat::query()
                ->where('channel', core()->getRequestedChannelCode())
                ->where('locale', core()->getRequestedLocaleCode())
                ->orderBy('name')
                ->limit(200)
                ->get(),
        ]);
    }
}
