<?php

namespace Platform\ExperienceCms\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\ExperienceCms\Models\FooterConfig;
use Platform\ExperienceCms\Models\HeaderConfig;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageSection;
use Platform\ExperienceCms\Models\SectionType;
use Platform\ExperienceCms\Models\Template;
use Platform\ExperienceCms\Models\TemplateArea;
use Platform\ExperienceCms\Services\SectionTypeRegistry;

class ExperienceCmsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var SectionTypeRegistry $registry */
        $registry = app(SectionTypeRegistry::class);

        foreach ($registry->all() as $definition) {
            SectionType::query()->updateOrCreate(
                ['code' => $definition->code()],
                $definition->toArray()
            );
        }

        $template = Template::query()->updateOrCreate(
            ['code' => 'homepage_default'],
            [
                'name' => 'Homepage Default',
                'page_type' => 'homepage',
                'schema_json' => [
                    'areas' => [
                        ['code' => 'hero', 'name' => 'Hero'],
                        ['code' => 'content', 'name' => 'Content'],
                    ],
                ],
                'is_active' => true,
            ]
        );

        $heroArea = TemplateArea::query()->updateOrCreate(
            ['template_id' => $template->id, 'code' => 'hero'],
            ['name' => 'Hero', 'rules_json' => ['max_sections' => 1], 'sort_order' => 1]
        );

        $contentArea = TemplateArea::query()->updateOrCreate(
            ['template_id' => $template->id, 'code' => 'content'],
            ['name' => 'Content', 'rules_json' => ['max_sections' => 12], 'sort_order' => 2]
        );

        HeaderConfig::query()->updateOrCreate(
            ['code' => 'default_header'],
            [
                'settings_json' => [
                    'brand_name' => config('app.name'),
                    'announcement' => 'Reusable commerce platform',
                    'links' => [
                        ['label' => 'Catalog', 'url' => '/'],
                        ['label' => 'Home Preview', 'url' => '/home-preview'],
                    ],
                ],
                'is_default' => true,
            ]
        );

        FooterConfig::query()->updateOrCreate(
            ['code' => 'default_footer'],
            [
                'settings_json' => [
                    'headline' => config('app.name'),
                    'description' => 'Default structured footer for the reusable platform.',
                ],
                'is_default' => true,
            ]
        );

        Menu::query()->updateOrCreate(
            ['code' => 'primary_navigation'],
            ['name' => 'Primary Navigation', 'location' => 'primary', 'is_active' => true]
        );

        $page = Page::query()->updateOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Homepage',
                'type' => 'homepage',
                'template_id' => $template->id,
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $page->sections()->delete();

        PageSection::query()->create([
            'page_id' => $page->id,
            'template_area_id' => $heroArea->id,
            'section_type_id' => SectionType::query()->where('code', 'hero_banner')->value('id'),
            'sort_order' => 1,
            'title' => 'Hero Banner',
            'settings_json' => [
                'eyebrow' => 'Reusable E-Commerce Product',
                'headline' => 'Structured CMS. Repeatable installs. Clean theme variation.',
                'body' => 'This homepage slice proves the product architecture without creating a one-off storefront codebase.',
                'primary_cta_label' => 'Browse Catalog',
                'primary_cta_url' => '/',
                'secondary_cta_label' => 'Read Docs',
                'secondary_cta_url' => '/pages/home',
            ],
            'is_active' => true,
        ]);

        PageSection::query()->create([
            'page_id' => $page->id,
            'template_area_id' => $contentArea->id,
            'section_type_id' => SectionType::query()->where('code', 'featured_products')->value('id'),
            'sort_order' => 2,
            'title' => 'Featured Products',
            'settings_json' => [
                'eyebrow' => 'Commerce-Aware Section',
                'limit' => 8,
            ],
            'data_source_type' => 'featured_products',
            'data_source_payload_json' => ['limit' => 8],
            'is_active' => true,
        ]);

        PageSection::query()->create([
            'page_id' => $page->id,
            'template_area_id' => $contentArea->id,
            'section_type_id' => SectionType::query()->where('code', 'rich_text')->value('id'),
            'sort_order' => 3,
            'title' => 'Structured CMS',
            'settings_json' => [
                'content' => "Admins work with approved templates, sections, and presets.\nThe platform stays structured and reusable across installs.",
            ],
            'is_active' => true,
        ]);
    }
}
