<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Platform\ExperienceCms\Contracts\PublishWorkflowContract;
use Platform\ExperienceCms\Http\Requests\Admin\PageRequest;
use Platform\ExperienceCms\Models\ComponentType;
use Platform\ExperienceCms\Models\ContentEntry;
use Platform\ExperienceCms\Models\FooterConfig;
use Platform\ExperienceCms\Models\HeaderConfig;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageAssignment;
use Platform\ExperienceCms\Models\SectionType;
use Platform\ExperienceCms\Models\SiteSetting;
use Platform\ExperienceCms\Models\Template;
use Platform\ExperienceCms\Services\PageEditor;
use Platform\ThemeCore\Models\ThemePreset;
use Webkul\Category\Models\Category;
use Webkul\Product\Models\ProductFlat;

class PageController extends Controller
{
    public function __construct(
        protected PublishWorkflowContract $publishWorkflow,
        protected PageEditor $pageEditor,
    ) {}

    public function index(): View
    {
        return view('experience-cms::admin.pages.index', [
            'pages' => Page::query()->with('template')->orderBy('title')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->formView(new Page([
            'type' => 'homepage',
            'status' => Page::STATUS_DRAFT,
        ]));
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = $this->pageEditor->create(
            $request->payload(),
            $request->seoPayload(),
            $request->sectionsPayload()
        );

        return redirect()
            ->route('admin.cms.pages.edit', $page)
            ->with('success', 'Page created.');
    }

    public function edit(Page $platformPage): View
    {
        return $this->formView($platformPage->load([
            'seoMeta',
            'template.areas',
            'sections.sectionType',
            'sections.templateArea',
            'sections.components.componentType',
            'headerConfig',
            'footerConfig',
            'menu.items',
            'themePreset',
            'assignments',
            'versions',
        ]));
    }

    public function update(PageRequest $request, Page $platformPage): RedirectResponse
    {
        $page = $this->pageEditor->update(
            $platformPage,
            $request->payload(),
            $request->seoPayload(),
            $request->sectionsPayload()
        );

        return redirect()
            ->route('admin.cms.pages.edit', $page)
            ->with('success', 'Page updated.');
    }

    public function destroy(Page $platformPage): RedirectResponse
    {
        $platformPage->delete();

        return redirect()
            ->route('admin.cms.pages.index')
            ->with('success', 'Page deleted.');
    }

    public function preview(Page $platformPage): RedirectResponse
    {
        return redirect($this->previewUrl($platformPage));
    }

    public function publish(Page $platformPage): RedirectResponse
    {
        $this->publishWorkflow->publish($platformPage);

        return redirect()
            ->route('admin.cms.pages.edit', $platformPage)
            ->with('success', 'Page published.');
    }

    public function unpublish(Page $platformPage): RedirectResponse
    {
        $this->publishWorkflow->unpublish($platformPage);

        return redirect()
            ->route('admin.cms.pages.edit', $platformPage)
            ->with('success', 'Page reverted to draft.');
    }

    protected function formView(Page $page): View
    {
        $templates = Template::query()
            ->with('areas')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('experience-cms::admin.pages.form', [
            'page' => $page,
            'templates' => $templates,
            'templateAreasByTemplate' => $templates->mapWithKeys(
                fn (Template $template) => [$template->id => $template->areas->map(fn ($area) => [
                    'id' => $area->id,
                    'code' => $area->code,
                    'name' => $area->name,
                    'rules' => $area->rules_json ?? [],
                ])->values()->all()]
            ),
            'sectionTypes' => SectionType::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get(),
            'componentTypes' => ComponentType::query()->where('is_active', true)->orderBy('name')->get(),
            'headerConfigs' => HeaderConfig::query()->orderByDesc('is_default')->orderBy('code')->get(),
            'footerConfigs' => FooterConfig::query()->orderByDesc('is_default')->orderBy('code')->get(),
            'menus' => Menu::query()->where('is_active', true)->orderBy('location')->orderBy('name')->get(),
            'themePresets' => ThemePreset::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
            'contentEntries' => ContentEntry::query()->orderBy('title')->get(),
            'siteSettings' => SiteSetting::query()->orderBy('group')->orderBy('key')->get(),
        ]);
    }

    protected function previewUrl(Page $page): string
    {
        $expiresAt = now()->addMinutes(30);

        if ($page->slug === 'home') {
            return URL::temporarySignedRoute('platform.storefront.home_preview', $expiresAt);
        }

        if ($page->type === 'category_page') {
            $assignment = $page->assignments()->where('page_type', 'category_page')->where('is_active', true)->orderByDesc('priority')->first();
            $category = $assignment?->entity_id
                ? Category::query()->find($assignment->entity_id)
                : Category::query()->orderBy('id')->first();

            if ($category) {
                return URL::temporarySignedRoute('platform.storefront.category-pages.preview', $expiresAt, [
                    'platformPage' => $page->slug,
                    'categorySlug' => $category->slug,
                ]);
            }
        }

        if ($page->type === 'product_page') {
            $assignment = $page->assignments()->where('page_type', 'product_page')->where('is_active', true)->orderByDesc('priority')->first();
            $product = $assignment?->entity_id
                ? ProductFlat::query()->where('product_id', $assignment->entity_id)->first()
                : ProductFlat::query()
                    ->where('channel', core()->getRequestedChannelCode())
                    ->where('locale', core()->getRequestedLocaleCode())
                    ->orderBy('product_id')
                    ->first();

            if ($product) {
                return URL::temporarySignedRoute('platform.storefront.product-pages.preview', $expiresAt, [
                    'platformPage' => $page->slug,
                    'productSlug' => $product->url_key,
                ]);
            }
        }

        return URL::temporarySignedRoute('platform.storefront.pages.preview', $expiresAt, [
            'platformPage' => $page->slug,
        ]);
    }
}
