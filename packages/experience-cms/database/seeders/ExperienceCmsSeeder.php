<?php

namespace Platform\ExperienceCms\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\ExperienceCms\Models\ComponentType;
use Platform\ExperienceCms\Models\ContentEntry;
use Platform\ExperienceCms\Models\FooterConfig;
use Platform\ExperienceCms\Models\HeaderConfig;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Models\MenuItem;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageAssignment;
use Platform\ExperienceCms\Models\PageSection;
use Platform\ExperienceCms\Models\SectionComponent;
use Platform\ExperienceCms\Models\SectionType;
use Platform\ExperienceCms\Models\SiteSetting;
use Platform\ExperienceCms\Models\Template;
use Platform\ExperienceCms\Models\TemplateArea;
use Platform\ExperienceCms\Services\ComponentTypeRegistry;
use Platform\ExperienceCms\Services\SectionTypeRegistry;
use Platform\ThemeCore\Models\ThemePreset;

class ExperienceCmsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var SectionTypeRegistry $registry */
        $registry = app(SectionTypeRegistry::class);
        /** @var ComponentTypeRegistry $componentRegistry */
        $componentRegistry = app(ComponentTypeRegistry::class);

        foreach ($registry->all() as $definition) {
            SectionType::query()->updateOrCreate(
                ['code' => $definition->code()],
                $definition->toArray()
            );
        }

        foreach ($componentRegistry->all() as $definition) {
            ComponentType::query()->updateOrCreate(
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

        $categoryTemplate = Template::query()->updateOrCreate(
            ['code' => 'category_default'],
            [
                'name' => 'Category Default',
                'page_type' => 'category_page',
                'schema_json' => [
                    'areas' => [
                        ['code' => 'hero', 'name' => 'Hero', 'rules' => ['allowed_section_codes' => ['hero_banner', 'promo_strip']]],
                        ['code' => 'pre_listing', 'name' => 'Pre Listing', 'rules' => ['allowed_section_codes' => ['category_intro', 'rich_text', 'featured_products', 'promo_strip']]],
                        ['code' => 'post_listing', 'name' => 'Post Listing', 'rules' => ['allowed_section_codes' => ['rich_text', 'featured_products', 'promo_strip']]],
                    ],
                ],
                'is_active' => true,
            ]
        );

        $categoryAreas = [
            'hero' => TemplateArea::query()->updateOrCreate(
                ['template_id' => $categoryTemplate->id, 'code' => 'hero'],
                ['name' => 'Hero', 'rules_json' => ['allowed_section_codes' => ['hero_banner', 'promo_strip']], 'sort_order' => 1]
            ),
            'pre_listing' => TemplateArea::query()->updateOrCreate(
                ['template_id' => $categoryTemplate->id, 'code' => 'pre_listing'],
                ['name' => 'Pre Listing', 'rules_json' => ['allowed_section_codes' => ['category_intro', 'rich_text', 'featured_products', 'promo_strip']], 'sort_order' => 2]
            ),
            'post_listing' => TemplateArea::query()->updateOrCreate(
                ['template_id' => $categoryTemplate->id, 'code' => 'post_listing'],
                ['name' => 'Post Listing', 'rules_json' => ['allowed_section_codes' => ['rich_text', 'featured_products', 'promo_strip']], 'sort_order' => 3]
            ),
        ];

        $productTemplate = Template::query()->updateOrCreate(
            ['code' => 'product_default'],
            [
                'name' => 'Product Default',
                'page_type' => 'product_page',
                'schema_json' => [
                    'areas' => [
                        ['code' => 'gallery', 'name' => 'Gallery', 'rules' => ['allowed_section_codes' => ['product_gallery']]],
                        ['code' => 'summary', 'name' => 'Summary', 'rules' => ['allowed_section_codes' => ['product_summary', 'product_price', 'product_options', 'add_to_cart', 'stock_shipping_info']]],
                        ['code' => 'details', 'name' => 'Details', 'rules' => ['allowed_section_codes' => ['product_details', 'faq_block']]],
                        ['code' => 'related', 'name' => 'Related', 'rules' => ['allowed_section_codes' => ['related_products', 'trust_badges']]],
                    ],
                ],
                'is_active' => true,
            ]
        );

        $productAreas = [
            'gallery' => TemplateArea::query()->updateOrCreate(
                ['template_id' => $productTemplate->id, 'code' => 'gallery'],
                ['name' => 'Gallery', 'rules_json' => ['allowed_section_codes' => ['product_gallery']], 'sort_order' => 1]
            ),
            'summary' => TemplateArea::query()->updateOrCreate(
                ['template_id' => $productTemplate->id, 'code' => 'summary'],
                ['name' => 'Summary', 'rules_json' => ['allowed_section_codes' => ['product_summary', 'product_price', 'product_options', 'add_to_cart', 'stock_shipping_info']], 'sort_order' => 2]
            ),
            'details' => TemplateArea::query()->updateOrCreate(
                ['template_id' => $productTemplate->id, 'code' => 'details'],
                ['name' => 'Details', 'rules_json' => ['allowed_section_codes' => ['product_details', 'faq_block']], 'sort_order' => 3]
            ),
            'related' => TemplateArea::query()->updateOrCreate(
                ['template_id' => $productTemplate->id, 'code' => 'related'],
                ['name' => 'Related', 'rules_json' => ['allowed_section_codes' => ['related_products', 'trust_badges']], 'sort_order' => 4]
            ),
        ];

        $header = HeaderConfig::query()->updateOrCreate(
            ['code' => 'default_header'],
            [
                'settings_json' => [
                    'brand_name' => config('app.name'),
                    'announcement' => 'Reusable commerce platform',
                    'links' => [
                        ['label' => 'Catalog', 'url' => '/'],
                        ['label' => 'Account', 'url' => '/customer/login'],
                    ],
                ],
                'is_default' => true,
            ]
        );

        $footer = FooterConfig::query()->updateOrCreate(
            ['code' => 'default_footer'],
            [
                'settings_json' => [
                    'headline' => config('app.name'),
                    'description' => 'Default structured footer for the reusable platform.',
                ],
                'is_default' => true,
            ]
        );

        $menu = Menu::query()->updateOrCreate(
            ['code' => 'primary_navigation'],
            ['name' => 'Primary Navigation', 'location' => 'primary', 'is_active' => true]
        );

        foreach ([
            ['title' => 'Catalog', 'type' => 'url', 'target' => '/', 'sort_order' => 1],
            ['title' => 'Account', 'type' => 'url', 'target' => '/customer/login', 'sort_order' => 2],
        ] as $item) {
            MenuItem::query()->updateOrCreate(
                ['menu_id' => $menu->id, 'title' => $item['title']],
                $item + ['is_active' => true, 'settings_json' => []]
            );
        }

        $preset = ThemePreset::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->first();

        foreach ([
            'store.identity' => ['group' => 'store', 'value_json' => ['brand_name' => config('app.name'), 'announcement' => 'Reusable commerce platform']],
            'store.contact' => ['group' => 'store', 'value_json' => ['headline' => config('app.name'), 'description' => 'Default structured footer for the reusable platform.']],
            'store.social_links' => ['group' => 'store', 'value_json' => ['links' => [['label' => 'Instagram', 'url' => '#'], ['label' => 'LinkedIn', 'url' => '#']]]],
            'store.trust_badges' => ['group' => 'store', 'value_json' => ['badges' => [['label' => 'Secure checkout'], ['label' => 'Independent install'], ['label' => 'Structured CMS']]]],
            'store.category_page' => ['group' => 'store', 'value_json' => ['default_mode' => 'grid', 'limit' => 12, 'show_toolbar' => true, 'show_description' => true]],
            'store.product_page' => ['group' => 'store', 'value_json' => ['shipping_note' => 'Shipping details are configured per installation.', 'related' => ['limit' => 4]]],
        ] as $key => $payload) {
            SiteSetting::query()->updateOrCreate(
                ['key' => $key],
                ['group' => $payload['group'], 'value_json' => $payload['value_json']]
            );
        }

        $categoryIntroEntry = ContentEntry::query()->updateOrCreate(
            ['slug' => 'default-category-intro'],
            [
                'type' => 'marketing_copy',
                'title' => 'Default Category Intro',
                'body_json' => [
                    'headline' => 'Category Story',
                    'content' => 'This category layout is controlled by structured CMS areas around the live commerce listing.',
                ],
                'status' => ContentEntry::STATUS_PUBLISHED,
            ]
        );

        $productFaqEntry = ContentEntry::query()->updateOrCreate(
            ['slug' => 'default-product-faq'],
            [
                'type' => 'faq',
                'title' => 'Default Product FAQ',
                'body_json' => [
                    'items' => [
                        ['question' => 'How is this page controlled?', 'answer' => 'Through a structured product template and ordered CMS blocks.'],
                        ['question' => 'Where does catalog data come from?', 'answer' => 'Directly from the underlying commerce core.'],
                    ],
                ],
                'status' => ContentEntry::STATUS_PUBLISHED,
            ]
        );

        $page = Page::query()->updateOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Homepage',
                'type' => 'homepage',
                'template_id' => $template->id,
                'header_config_id' => $header->id,
                'footer_config_id' => $footer->id,
                'menu_id' => $menu->id,
                'theme_preset_id' => $preset?->id,
                'settings_json' => [],
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

        $heroSection = $page->sections()->where('title', 'Hero Banner')->first();

        if ($heroSection) {
            $heroSection->components()->delete();

            SectionComponent::query()->create([
                'page_section_id' => $heroSection->id,
                'component_type_id' => ComponentType::query()->where('code', 'badge_list')->value('id'),
                'sort_order' => 1,
                'settings_json' => [
                    'badges' => [
                        ['label' => 'Laravel 12'],
                        ['label' => 'Bagisto Core'],
                        ['label' => 'Structured CMS'],
                    ],
                ],
                'is_active' => true,
            ]);
        }

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

        $categoryPage = Page::query()->updateOrCreate(
            ['slug' => 'category-default-layout'],
            [
                'title' => 'Category Default Layout',
                'type' => 'category_page',
                'template_id' => $categoryTemplate->id,
                'header_config_id' => $header->id,
                'footer_config_id' => $footer->id,
                'menu_id' => $menu->id,
                'theme_preset_id' => $preset?->id,
                'settings_json' => ['listing' => ['default_mode' => 'grid', 'limit' => 12]],
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
            ]
        );

        $categoryPage->sections()->delete();

        PageSection::query()->create([
            'page_id' => $categoryPage->id,
            'template_area_id' => $categoryAreas['hero']->id,
            'section_type_id' => SectionType::query()->where('code', 'promo_strip')->value('id'),
            'sort_order' => 1,
            'title' => 'Category Promo',
            'settings_json' => ['content' => 'Category pages stay structured while the commerce core owns the live listing.'],
            'is_active' => true,
        ]);

        $categoryIntroSection = PageSection::query()->create([
            'page_id' => $categoryPage->id,
            'template_area_id' => $categoryAreas['pre_listing']->id,
            'section_type_id' => SectionType::query()->where('code', 'category_intro')->value('id'),
            'sort_order' => 2,
            'title' => 'Category Intro',
            'settings_json' => ['headline' => 'Category Story'],
            'data_source_type' => 'selected_content_entries',
            'data_source_payload_json' => ['content_entry_ids' => [$categoryIntroEntry->id]],
            'is_active' => true,
        ]);

        SectionComponent::query()->create([
            'page_section_id' => $categoryIntroSection->id,
            'component_type_id' => ComponentType::query()->where('code', 'cta_button_group')->value('id'),
            'sort_order' => 1,
            'settings_json' => [
                'buttons' => [
                    ['label' => 'Browse all products', 'url' => '/'],
                    ['label' => 'Customer account', 'url' => '/customer/login'],
                ],
            ],
            'is_active' => true,
        ]);

        PageSection::query()->create([
            'page_id' => $categoryPage->id,
            'template_area_id' => $categoryAreas['post_listing']->id,
            'section_type_id' => SectionType::query()->where('code', 'featured_products')->value('id'),
            'sort_order' => 3,
            'title' => 'Category Featured Products',
            'settings_json' => ['eyebrow' => 'More Products', 'limit' => 4],
            'data_source_type' => 'featured_products',
            'data_source_payload_json' => ['limit' => 4],
            'is_active' => true,
        ]);

        $productPage = Page::query()->updateOrCreate(
            ['slug' => 'product-default-layout'],
            [
                'title' => 'Product Default Layout',
                'type' => 'product_page',
                'template_id' => $productTemplate->id,
                'header_config_id' => $header->id,
                'footer_config_id' => $footer->id,
                'menu_id' => $menu->id,
                'theme_preset_id' => $preset?->id,
                'settings_json' => ['related' => ['limit' => 4], 'shipping_note' => 'Shipping and stock notes remain installation-specific.'],
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
            ]
        );

        $productPage->sections()->delete();

        foreach ([
            ['area' => 'gallery', 'code' => 'product_gallery', 'title' => 'Gallery', 'sort_order' => 1, 'settings' => []],
            ['area' => 'summary', 'code' => 'product_summary', 'title' => 'Summary', 'sort_order' => 2, 'settings' => ['show_sku' => true]],
            ['area' => 'summary', 'code' => 'product_price', 'title' => 'Price', 'sort_order' => 3, 'settings' => []],
            ['area' => 'summary', 'code' => 'product_options', 'title' => 'Options', 'sort_order' => 4, 'settings' => []],
            ['area' => 'summary', 'code' => 'add_to_cart', 'title' => 'Add To Cart', 'sort_order' => 5, 'settings' => ['default_quantity' => 1]],
            ['area' => 'summary', 'code' => 'stock_shipping_info', 'title' => 'Shipping Note', 'sort_order' => 6, 'settings' => ['shipping_note' => 'Shipping and stock notes remain installation-specific.']],
            ['area' => 'details', 'code' => 'product_details', 'title' => 'Product Details', 'sort_order' => 7, 'settings' => []],
            ['area' => 'details', 'code' => 'faq_block', 'title' => 'FAQ', 'sort_order' => 8, 'settings' => ['headline' => 'Product FAQ'], 'data_source_type' => 'selected_content_entries', 'data_source_payload_json' => ['content_entry_ids' => [$productFaqEntry->id]]],
            ['area' => 'related', 'code' => 'related_products', 'title' => 'Related Products', 'sort_order' => 9, 'settings' => ['headline' => 'You may also like', 'mode' => 'related', 'limit' => 4]],
            ['area' => 'related', 'code' => 'trust_badges', 'title' => 'Trust Badges', 'sort_order' => 10, 'settings' => ['headline' => 'Why shop with us']],
        ] as $section) {
            PageSection::query()->create([
                'page_id' => $productPage->id,
                'template_area_id' => $productAreas[$section['area']]->id,
                'section_type_id' => SectionType::query()->where('code', $section['code'])->value('id'),
                'sort_order' => $section['sort_order'],
                'title' => $section['title'],
                'settings_json' => $section['settings'],
                'data_source_type' => $section['data_source_type'] ?? null,
                'data_source_payload_json' => $section['data_source_payload_json'] ?? null,
                'is_active' => true,
            ]);
        }

        PageAssignment::query()->updateOrCreate(
            ['page_type' => 'category_page', 'scope_type' => PageAssignment::SCOPE_GLOBAL, 'entity_type' => PageAssignment::ENTITY_CATEGORY, 'entity_id' => null],
            ['page_id' => $categoryPage->id, 'priority' => 0, 'is_active' => true]
        );

        PageAssignment::query()->updateOrCreate(
            ['page_type' => 'product_page', 'scope_type' => PageAssignment::SCOPE_GLOBAL, 'entity_type' => PageAssignment::ENTITY_PRODUCT, 'entity_id' => null],
            ['page_id' => $productPage->id, 'priority' => 0, 'is_active' => true]
        );
    }
}
