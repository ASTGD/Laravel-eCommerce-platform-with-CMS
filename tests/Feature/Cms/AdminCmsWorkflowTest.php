<?php

use Platform\ExperienceCms\Models\ComponentType;
use Platform\ExperienceCms\Models\FooterConfig;
use Platform\ExperienceCms\Models\HeaderConfig;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageSection;
use Platform\ExperienceCms\Models\SectionType;
use Platform\ExperienceCms\Models\Template;
use Platform\ThemeCore\Models\ThemePreset;
use Webkul\Admin\Tests\AdminTestCase;

uses(AdminTestCase::class);

it('renders the CMS admin screens without fatal errors', function () {
    $this->loginAsAdmin();

    $page = Page::query()->where('slug', 'home')->firstOrFail();
    $template = Template::query()->where('code', 'homepage_default')->firstOrFail();
    $sectionType = SectionType::query()->where('code', 'hero_banner')->firstOrFail();
    $componentType = ComponentType::query()->first() ?: ComponentType::query()->create([
        'name' => 'Screen Smoke Component',
        'code' => 'screen_smoke_component',
        'config_schema_json' => ['content' => 'string'],
        'renderer_class' => 'Platform\\ThemeDefault\\Components\\GenericComponent',
        'is_active' => true,
    ]);
    $menu = Menu::query()->firstOrFail();
    $header = HeaderConfig::query()->firstOrFail();
    $footer = FooterConfig::query()->firstOrFail();
    $preset = ThemePreset::query()->firstOrFail();

    foreach ([
        ['admin.cms.pages.index', []],
        ['admin.cms.pages.create', []],
        ['admin.cms.pages.edit', $page],
        ['admin.cms.templates.index', []],
        ['admin.cms.templates.create', []],
        ['admin.cms.templates.edit', $template],
        ['admin.cms.section-types.index', []],
        ['admin.cms.section-types.create', []],
        ['admin.cms.section-types.edit', $sectionType],
        ['admin.cms.component-types.index', []],
        ['admin.cms.component-types.create', []],
        ['admin.cms.component-types.edit', $componentType],
        ['admin.cms.menus.index', []],
        ['admin.cms.menus.create', []],
        ['admin.cms.menus.edit', $menu],
        ['admin.cms.header-configs.index', []],
        ['admin.cms.header-configs.create', []],
        ['admin.cms.header-configs.edit', $header],
        ['admin.cms.footer-configs.index', []],
        ['admin.cms.footer-configs.create', []],
        ['admin.cms.footer-configs.edit', $footer],
        ['admin.theme.presets.index', []],
        ['admin.theme.presets.create', []],
        ['admin.theme.presets.edit', $preset],
    ] as [$route, $parameters]) {
        $url = route($route, $parameters);
        $status = $this->get($url)->status();
        $this->assertSame(200, $status, sprintf('%s returned %s [%s]', $route, $status, $url));
    }
});

it('creates supporting CMS records from the admin forms', function () {
    $this->loginAsAdmin();

    $templateResponse = $this->post(route('admin.cms.templates.store'), [
        'name' => 'Admin Workflow Template',
        'code' => 'admin_workflow_template',
        'page_type' => 'homepage',
        'schema_json' => json_encode([
            'areas' => [
                ['code' => 'hero', 'name' => 'Hero', 'sort_order' => 1],
                ['code' => 'content', 'name' => 'Content', 'sort_order' => 2],
            ],
        ]),
        'is_active' => 1,
    ]);

    $template = Template::query()->where('code', 'admin_workflow_template')->first();

    $templateResponse->assertRedirect(route('admin.cms.templates.edit', $template));

    $sectionTypeResponse = $this->post(route('admin.cms.section-types.store'), [
        'name' => 'Admin Workflow Section',
        'code' => 'admin_workflow_section',
        'category' => 'content',
        'config_schema_json' => json_encode(['headline' => 'string']),
        'allowed_data_sources_json' => json_encode([]),
        'renderer_class' => 'Platform\\ThemeDefault\\Sections\\GenericSection',
        'is_active' => 1,
    ]);

    $sectionType = SectionType::query()->where('code', 'admin_workflow_section')->first();

    $sectionTypeResponse->assertRedirect(route('admin.cms.section-types.edit', $sectionType));

    $menuResponse = $this->post(route('admin.cms.menus.store'), [
        'name' => 'Admin Workflow Menu',
        'code' => 'admin_workflow_menu',
        'location' => 'header',
        'is_active' => 1,
        'items' => [
            [
                'title' => 'Workflow Link',
                'type' => 'url',
                'target' => '/workflow',
                'sort_order' => 1,
                'is_active' => 1,
            ],
        ],
    ]);

    $menu = Menu::query()->where('code', 'admin_workflow_menu')->first();

    $menuResponse->assertRedirect(route('admin.cms.menus.edit', $menu));

    $headerResponse = $this->post(route('admin.cms.header-configs.store'), [
        'code' => 'admin_workflow_header',
        'settings_json' => json_encode(['brand_name' => 'Workflow Header']),
    ]);

    $header = HeaderConfig::query()->where('code', 'admin_workflow_header')->first();

    $headerResponse->assertRedirect(route('admin.cms.header-configs.edit', $header));

    $footerResponse = $this->post(route('admin.cms.footer-configs.store'), [
        'code' => 'admin_workflow_footer',
        'settings_json' => json_encode(['headline' => 'Workflow Footer']),
    ]);

    $footer = FooterConfig::query()->where('code', 'admin_workflow_footer')->first();

    $footerResponse->assertRedirect(route('admin.cms.footer-configs.edit', $footer));

    $presetResponse = $this->post(route('admin.theme.presets.store'), [
        'name' => 'Admin Workflow Preset',
        'code' => 'admin_workflow_preset',
        'tokens_json' => json_encode(['palette' => ['brand' => '#112233']]),
        'settings_json' => json_encode(['button_variant' => 'solid']),
        'is_active' => 1,
    ]);

    $preset = ThemePreset::query()->where('code', 'admin_workflow_preset')->first();

    $presetResponse->assertRedirect(route('admin.theme.presets.edit', $preset));

    expect($template)->not->toBeNull()
        ->and($template->areas()->count())->toBe(2)
        ->and($sectionType)->not->toBeNull()
        ->and($menu)->not->toBeNull()
        ->and($menu->items()->count())->toBe(1)
        ->and($header)->not->toBeNull()
        ->and($footer)->not->toBeNull()
        ->and($preset)->not->toBeNull();
});

it('creates a structured homepage draft from the admin screen', function () {
    $this->loginAsAdmin();

    $template = Template::query()->where('code', 'homepage_default')->firstOrFail();
    $areas = $template->areas()->orderBy('sort_order')->get()->values();
    $heroType = SectionType::query()->where('code', 'hero_banner')->firstOrFail();
    $featuredType = SectionType::query()->where('code', 'featured_products')->firstOrFail();
    $richTextType = SectionType::query()->where('code', 'rich_text')->firstOrFail();
    $header = HeaderConfig::query()->firstOrFail();
    $footer = FooterConfig::query()->firstOrFail();
    $menu = Menu::query()->firstOrFail();
    $preset = ThemePreset::query()->firstOrFail();

    $response = $this->post(route('admin.cms.pages.store'), [
        'title' => 'Admin Draft Homepage',
        'slug' => 'admin-draft-homepage',
        'type' => 'homepage',
        'template_id' => $template->id,
        'header_config_id' => $header->id,
        'footer_config_id' => $footer->id,
        'menu_id' => $menu->id,
        'theme_preset_id' => $preset->id,
        'seo' => [
            'title' => 'Admin Draft SEO Title',
            'description' => 'Admin draft SEO description',
            'og_json' => json_encode(['title' => 'Admin Draft OG']),
        ],
        'sections' => [
            [
                'template_area_id' => $areas[0]->id,
                'section_type_id' => $heroType->id,
                'title' => 'Admin Hero',
                'sort_order' => 1,
                'is_active' => 1,
                'settings_json' => json_encode([
                    'headline' => 'Admin Draft Hero',
                    'body' => 'Draft hero body',
                ]),
                'data_source_payload_json' => json_encode([]),
            ],
            [
                'template_area_id' => $areas[1]->id,
                'section_type_id' => $featuredType->id,
                'title' => 'Admin Featured',
                'sort_order' => 2,
                'is_active' => 1,
                'settings_json' => json_encode([
                    'eyebrow' => 'Featured',
                    'limit' => 4,
                ]),
                'data_source_type' => 'featured_products',
                'data_source_payload_json' => json_encode(['limit' => 4]),
            ],
            [
                'template_area_id' => $areas[1]->id,
                'section_type_id' => $richTextType->id,
                'title' => 'Admin Rich Text',
                'sort_order' => 3,
                'is_active' => 1,
                'settings_json' => json_encode([
                    'content' => 'Structured CMS draft body.',
                ]),
                'data_source_payload_json' => json_encode([]),
            ],
        ],
    ]);

    $page = Page::query()->where('slug', 'admin-draft-homepage')->first();

    $response->assertRedirect(route('admin.cms.pages.edit', $page));

    expect($page)->not->toBeNull()
        ->and($page->status)->toBe(Page::STATUS_DRAFT)
        ->and($page->header_config_id)->toBe($header->id)
        ->and($page->footer_config_id)->toBe($footer->id)
        ->and($page->menu_id)->toBe($menu->id)
        ->and($page->theme_preset_id)->toBe($preset->id)
        ->and($page->seoMeta)->not->toBeNull()
        ->and($page->sections()->count())->toBe(3);
});

it('redirects admin preview to a signed storefront preview URL and records publish transitions', function () {
    $this->loginAsAdmin();

    $page = Page::query()->create([
        'title' => 'Workflow Preview Page',
        'slug' => 'workflow-preview-page',
        'type' => 'homepage',
        'template_id' => Template::query()->where('code', 'homepage_default')->value('id'),
        'status' => Page::STATUS_DRAFT,
    ]);

    PageSection::query()->create([
        'page_id' => $page->id,
        'template_area_id' => Template::query()->where('code', 'homepage_default')->firstOrFail()->areas()->orderBy('sort_order')->value('id'),
        'section_type_id' => SectionType::query()->where('code', 'hero_banner')->value('id'),
        'sort_order' => 1,
        'title' => 'Workflow Hero',
        'settings_json' => ['headline' => 'Workflow Draft Hero'],
        'is_active' => true,
    ]);

    $previewResponse = $this->get(route('admin.cms.pages.preview', $page));

    $previewResponse->assertRedirect();

    $previewUrl = $previewResponse->headers->get('Location');

    expect($previewUrl)->toContain('signature=')
        ->and($previewUrl)->toContain('/preview/pages/workflow-preview-page');

    $this->get($previewUrl)
        ->assertOk()
        ->assertSeeText('Workflow Draft Hero');

    $this->post(route('admin.cms.pages.publish', $page))
        ->assertRedirect();

    $page->refresh();

    expect($page->isPublished())->toBeTrue()
        ->and($page->versions()->count())->toBe(1);

    $this->post(route('admin.cms.pages.unpublish', $page))
        ->assertRedirect();

    $page->refresh();

    expect($page->status)->toBe(Page::STATUS_DRAFT)
        ->and($page->versions()->count())->toBe(2);
});

it('allows admins to manage component type records', function () {
    $this->loginAsAdmin();

    $response = $this->post(route('admin.cms.component-types.store'), [
        'name' => 'Admin CTA Group',
        'code' => 'admin_cta_group',
        'config_schema_json' => json_encode(['buttons' => 'array']),
        'renderer_class' => 'Platform\\ThemeDefault\\Components\\CtaGroup',
        'is_active' => 1,
    ]);

    $componentType = ComponentType::query()->where('code', 'admin_cta_group')->first();

    $response->assertRedirect(route('admin.cms.component-types.edit', $componentType));

    expect($componentType)->not->toBeNull()
        ->and($componentType->name)->toBe('Admin CTA Group');
});
