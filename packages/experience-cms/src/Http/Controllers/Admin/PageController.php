<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Platform\ExperienceCms\Contracts\PublishWorkflowContract;
use Platform\ExperienceCms\Http\Requests\Admin\PageRequest;
use Platform\ExperienceCms\Models\FooterConfig;
use Platform\ExperienceCms\Models\HeaderConfig;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\SectionType;
use Platform\ExperienceCms\Models\Template;
use Platform\ExperienceCms\Services\PageEditor;
use Platform\ThemeCore\Models\ThemePreset;

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
            'headerConfig',
            'footerConfig',
            'menu.items',
            'themePreset',
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
                ])->values()->all()]
            ),
            'sectionTypes' => SectionType::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get(),
            'headerConfigs' => HeaderConfig::query()->orderByDesc('is_default')->orderBy('code')->get(),
            'footerConfigs' => FooterConfig::query()->orderByDesc('is_default')->orderBy('code')->get(),
            'menus' => Menu::query()->where('is_active', true)->orderBy('location')->orderBy('name')->get(),
            'themePresets' => ThemePreset::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    protected function previewUrl(Page $page): string
    {
        $expiresAt = now()->addMinutes(30);

        if ($page->slug === 'home') {
            return URL::temporarySignedRoute('platform.storefront.home_preview', $expiresAt);
        }

        return URL::temporarySignedRoute('platform.storefront.pages.preview', $expiresAt, [
            'platformPage' => $page->slug,
        ]);
    }
}
