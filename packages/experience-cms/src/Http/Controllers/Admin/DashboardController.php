<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
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
use Platform\ThemeCore\Models\ThemePreset;

class DashboardController extends Controller
{
    public function index(): View
    {
        $pages = Page::query()
            ->with('template')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $mostUsedTemplate = Template::query()
            ->withCount('pages')
            ->orderByDesc('pages_count')
            ->orderBy('name')
            ->first();

        $latestPublishedPage = Page::query()
            ->published()
            ->orderByDesc('published_at')
            ->first();

        return view('experience-cms::admin.dashboard.index', [
            'pages' => $pages,
            'stats' => [
                [
                    'label' => 'Pages',
                    'value' => Page::query()->count(),
                    'help' => 'Structured pages available in this workspace',
                ],
                [
                    'label' => 'Published',
                    'value' => Page::query()->published()->count(),
                    'help' => 'Pages currently live on the storefront',
                ],
                [
                    'label' => 'Drafts',
                    'value' => Page::query()->where('status', Page::STATUS_DRAFT)->count(),
                    'help' => 'Pages still waiting on review or publish',
                ],
                [
                    'label' => 'Templates',
                    'value' => Template::query()->where('is_active', true)->count(),
                    'help' => 'Active layouts ready for new page builds',
                ],
                [
                    'label' => 'Theme presets',
                    'value' => ThemePreset::query()->where('is_active', true)->count(),
                    'help' => 'Active visual presets available to pages',
                ],
                [
                    'label' => 'Menus',
                    'value' => Menu::query()->where('is_active', true)->count(),
                    'help' => 'Navigation trees available to the storefront',
                ],
            ],
            'inventory' => [
                [
                    'label' => 'Section types',
                    'value' => SectionType::query()->where('is_active', true)->count(),
                ],
                [
                    'label' => 'Component types',
                    'value' => ComponentType::query()->where('is_active', true)->count(),
                ],
                [
                    'label' => 'Assignments',
                    'value' => PageAssignment::query()->where('is_active', true)->count(),
                ],
                [
                    'label' => 'Content entries',
                    'value' => ContentEntry::query()->count(),
                ],
                [
                    'label' => 'Header configs',
                    'value' => HeaderConfig::query()->count(),
                ],
                [
                    'label' => 'Footer configs',
                    'value' => FooterConfig::query()->count(),
                ],
                [
                    'label' => 'Site settings',
                    'value' => SiteSetting::query()->count(),
                ],
            ],
            'quickLinks' => [
                [
                    'label' => 'New page',
                    'route' => route('admin.cms.pages.create'),
                    'description' => 'Start a structured page from the CMS workflow.',
                ],
                [
                    'label' => 'Templates',
                    'route' => route('admin.cms.templates.index'),
                    'description' => 'Review reusable page layouts and area schemas.',
                ],
                [
                    'label' => 'Section types',
                    'route' => route('admin.cms.section-types.index'),
                    'description' => 'Inspect the approved section registry.',
                ],
                [
                    'label' => 'Component types',
                    'route' => route('admin.cms.component-types.index'),
                    'description' => 'Manage reusable block and control definitions.',
                ],
                [
                    'label' => 'Menus',
                    'route' => route('admin.cms.menus.index'),
                    'description' => 'Adjust navigation and link structure.',
                ],
                [
                    'label' => 'Site settings',
                    'route' => route('admin.cms.site-settings.index'),
                    'description' => 'Update structured store and CMS settings.',
                ],
            ],
            'mostUsedTemplate' => $mostUsedTemplate,
            'latestPublishedPage' => $latestPublishedPage,
        ]);
    }
}
