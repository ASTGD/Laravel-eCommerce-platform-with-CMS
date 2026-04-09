<?php

use Platform\ExperienceCms\Models\ComponentType;
use Platform\ExperienceCms\Models\FooterConfig;
use Platform\ExperienceCms\Models\HeaderConfig;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageSection;
use Platform\ExperienceCms\Models\SectionComponent;
use Platform\ExperienceCms\Models\SectionType;
use Platform\ExperienceCms\Models\Template;
use Platform\ExperienceCms\Models\TemplateArea;
use Platform\SeoTools\Models\SeoMeta;
use Platform\ThemeCore\Models\ThemePreset;

uses(Tests\TestCase::class);

it('persists the CMS entity relationships for a structured page', function () {
    $template = Template::query()->create([
        'name' => 'Relationship Template',
        'code' => 'relationship_template',
        'page_type' => 'homepage',
        'schema_json' => ['areas' => [['code' => 'hero', 'name' => 'Hero']]],
        'is_active' => true,
    ]);

    $area = TemplateArea::query()->create([
        'template_id' => $template->id,
        'code' => 'hero',
        'name' => 'Hero',
        'rules_json' => ['max_sections' => 1],
        'sort_order' => 1,
    ]);

    $sectionType = SectionType::query()->create([
        'name' => 'Relationship Hero',
        'code' => 'relationship_hero',
        'category' => 'hero',
        'config_schema_json' => ['headline' => 'string'],
        'supports_components' => true,
        'allowed_data_sources_json' => [],
        'renderer_class' => 'Tests\\Support\\RelationshipHero',
        'is_active' => true,
    ]);

    $componentType = ComponentType::query()->create([
        'name' => 'Relationship Headline',
        'code' => 'relationship_headline',
        'config_schema_json' => ['content' => 'string'],
        'renderer_class' => 'Tests\\Support\\RelationshipHeadline',
        'is_active' => true,
    ]);

    $header = HeaderConfig::query()->create([
        'code' => 'relationship_header',
        'settings_json' => ['brand_name' => 'Relationship Header'],
        'is_default' => false,
    ]);

    $footer = FooterConfig::query()->create([
        'code' => 'relationship_footer',
        'settings_json' => ['headline' => 'Relationship Footer'],
        'is_default' => false,
    ]);

    $menu = Menu::query()->create([
        'name' => 'Relationship Menu',
        'code' => 'relationship_menu',
        'location' => 'primary',
        'is_active' => true,
    ]);

    $preset = ThemePreset::query()->create([
        'name' => 'Relationship Preset',
        'code' => 'relationship_preset',
        'tokens_json' => ['code' => 'relationship_preset'],
        'settings_json' => [],
        'is_default' => false,
        'is_active' => true,
    ]);

    $seoMeta = SeoMeta::query()->create([
        'title' => 'Relationship SEO',
        'description' => 'Relationship description',
    ]);

    $page = Page::query()->create([
        'title' => 'Relationship Page',
        'slug' => 'relationship-page',
        'type' => 'homepage',
        'template_id' => $template->id,
        'header_config_id' => $header->id,
        'footer_config_id' => $footer->id,
        'menu_id' => $menu->id,
        'theme_preset_id' => $preset->id,
        'seo_meta_id' => $seoMeta->id,
        'status' => Page::STATUS_DRAFT,
    ]);

    $section = PageSection::query()->create([
        'page_id' => $page->id,
        'template_area_id' => $area->id,
        'section_type_id' => $sectionType->id,
        'sort_order' => 1,
        'title' => 'Relationship Section',
        'settings_json' => ['headline' => 'Structured relationship'],
        'is_active' => true,
    ]);

    SectionComponent::query()->create([
        'page_section_id' => $section->id,
        'component_type_id' => $componentType->id,
        'sort_order' => 1,
        'settings_json' => ['content' => 'Nested component'],
        'is_active' => true,
    ]);

    $page->load([
        'template.areas',
        'seoMeta',
        'headerConfig',
        'footerConfig',
        'menu',
        'themePreset',
        'sections.templateArea',
        'sections.sectionType',
        'sections.components.componentType',
    ]);

    expect($page->template->is($template))->toBeTrue()
        ->and($page->seoMeta->is($seoMeta))->toBeTrue()
        ->and($page->headerConfig->is($header))->toBeTrue()
        ->and($page->footerConfig->is($footer))->toBeTrue()
        ->and($page->menu->is($menu))->toBeTrue()
        ->and($page->themePreset->is($preset))->toBeTrue()
        ->and($page->sections)->toHaveCount(1)
        ->and($page->sections->first()->templateArea->is($area))->toBeTrue()
        ->and($page->sections->first()->sectionType->is($sectionType))->toBeTrue()
        ->and($page->sections->first()->components)->toHaveCount(1)
        ->and($page->sections->first()->components->first()->componentType->is($componentType))->toBeTrue();
});
