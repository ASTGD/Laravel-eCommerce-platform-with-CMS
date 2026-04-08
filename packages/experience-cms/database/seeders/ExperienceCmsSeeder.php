<?php

declare(strict_types=1);

namespace ExperienceCms\Database\Seeders;

use ExperienceCms\Actions\PublishPageAction;
use ExperienceCms\Enums\PageStatus;
use ExperienceCms\Enums\PageType;
use ExperienceCms\Models\FooterConfig;
use ExperienceCms\Models\HeaderConfig;
use ExperienceCms\Models\Menu;
use ExperienceCms\Models\MenuItem;
use ExperienceCms\Models\Page;
use ExperienceCms\Models\PageSection;
use ExperienceCms\Models\SectionType;
use ExperienceCms\Models\SiteSetting;
use ExperienceCms\Models\Template;
use ExperienceCms\Services\SectionRegistry;
use Illuminate\Database\Seeder;
use SeoTools\Models\SeoMeta;

class ExperienceCmsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var SectionRegistry $registry */
        $registry = app(SectionRegistry::class);

        $template = Template::query()->updateOrCreate(
            ['code' => 'default-homepage'],
            [
                'name' => 'Default Homepage',
                'page_type' => PageType::Homepage->value,
                'schema_json' => [
                    'areas' => ['main'],
                    'allowed_sections' => $registry->all()->map->code()->all(),
                ],
                'is_active' => true,
            ],
        );

        $registry->all()->each(function ($definition): void {
            SectionType::query()->updateOrCreate(
                ['code' => $definition->code()],
                [
                    'name' => $definition->name(),
                    'category' => $definition->category(),
                    'config_schema_json' => $definition->configSchema(),
                    'supports_components' => $definition->supportsComponents(),
                    'allowed_data_sources_json' => $definition->supportedDataSources(),
                    'is_active' => true,
                ],
            );
        });

        $primaryMenu = Menu::query()->updateOrCreate(
            ['code' => 'primary-navigation'],
            ['name' => 'Primary Navigation', 'location' => 'primary', 'is_active' => true],
        );

        $footerMenu = Menu::query()->updateOrCreate(
            ['code' => 'footer-navigation'],
            ['name' => 'Footer Navigation', 'location' => 'footer', 'is_active' => true],
        );

        foreach ([
            [$primaryMenu, 'Home', 'url', '/'],
            [$primaryMenu, 'Story', 'url', '/pages/story'],
            [$footerMenu, 'Delivery', 'url', '/pages/delivery'],
            [$footerMenu, 'Privacy', 'url', '/pages/privacy'],
        ] as [$menu, $title, $type, $target]) {
            MenuItem::query()->updateOrCreate(
                ['menu_id' => $menu->id, 'title' => $title],
                ['type' => $type, 'target' => $target, 'sort_order' => 0, 'is_active' => true],
            );
        }

        HeaderConfig::query()->updateOrCreate(
            ['code' => 'default-header'],
            [
                'settings_json' => [
                    'announcement' => 'One platform. Many standalone storefront deployments.',
                    'primary_menu_code' => $primaryMenu->code,
                    'show_search' => true,
                    'show_account' => true,
                    'show_cart' => true,
                    'sticky' => true,
                    'variant' => 'modern',
                ],
                'is_default' => true,
            ],
        );

        FooterConfig::query()->updateOrCreate(
            ['code' => 'default-footer'],
            [
                'settings_json' => [
                    'footer_menu_code' => $footerMenu->code,
                    'company_text' => 'Built for repeatable deployments with bounded content flexibility.',
                    'newsletter_heading' => 'Stay in the loop',
                    'legal_links' => [
                        ['label' => 'Privacy', 'url' => '/pages/privacy'],
                        ['label' => 'Terms', 'url' => '/pages/terms'],
                    ],
                    'variant' => 'modern',
                ],
                'is_default' => true,
            ],
        );

        foreach ([
            ['key' => 'brand.name', 'value_json' => config('platform.brand_name'), 'group' => 'global'],
            ['key' => 'theme.active_preset_code', 'value_json' => 'modern', 'group' => 'theme'],
            ['key' => 'header.active_code', 'value_json' => 'default-header', 'group' => 'theme'],
            ['key' => 'footer.active_code', 'value_json' => 'default-footer', 'group' => 'theme'],
            ['key' => 'catalog.product_card_defaults', 'value_json' => ['show_badge' => true], 'group' => 'catalog'],
        ] as $setting) {
            SiteSetting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }

        $seo = SeoMeta::query()->updateOrCreate(
            ['title' => 'Commerce Platform Home'],
            [
                'description' => 'Reusable commerce platform with a structured CMS and theme presets.',
                'open_graph_title' => 'Commerce Platform',
                'open_graph_description' => 'Reusable commerce platform with structured CMS composition.',
            ],
        );

        $page = Page::query()->updateOrCreate(
            ['slug' => config('platform.homepage_slug')],
            [
                'title' => 'Home',
                'type' => PageType::Homepage->value,
                'template_id' => $template->id,
                'status' => PageStatus::Draft->value,
                'seo_meta_id' => $seo->id,
            ],
        );

        $this->seedHomepageSections($page);

        app(PublishPageAction::class)->execute($page, null, 'Seeded homepage');
    }

    private function seedHomepageSections(Page $page): void
    {
        $page->sections()->delete();

        $hero = SectionType::query()->where('code', 'hero_banner')->firstOrFail();
        $featured = SectionType::query()->where('code', 'featured_products')->firstOrFail();
        $richText = SectionType::query()->where('code', 'rich_text')->firstOrFail();

        PageSection::query()->create([
            'page_id' => $page->id,
            'section_type_id' => $hero->id,
            'sort_order' => 1,
            'title' => 'Hero',
            'settings_json' => [
                'eyebrow' => 'Reusable product blueprint',
                'headline' => 'Deploy the same commerce platform to many independent storefronts.',
                'body' => 'This foundation combines structured CMS composition, preset-driven theming, and clean installation isolation.',
                'primary_label' => 'View featured products',
                'primary_url' => '#featured-products',
                'secondary_label' => 'Read architecture',
                'secondary_url' => '/internal/docs/architecture',
                'image_url' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=1200&q=80',
            ],
            'is_active' => true,
        ]);

        PageSection::query()->create([
            'page_id' => $page->id,
            'section_type_id' => $featured->id,
            'sort_order' => 2,
            'title' => 'Featured Products',
            'settings_json' => [
                'headline' => 'Featured products',
                'body' => 'The first storefront slice uses manual product cards until catalog integration lands.',
                'items' => [
                    ['title' => 'Canvas Weekender', 'subtitle' => 'Preset-ready hero item', 'price' => '$129.00', 'url' => '#', 'badge' => 'New'],
                    ['title' => 'Utility Jacket', 'subtitle' => 'Structured CMS sample card', 'price' => '$158.00', 'url' => '#', 'badge' => 'Featured'],
                    ['title' => 'Trail Sneaker', 'subtitle' => 'Manual preview data source', 'price' => '$96.00', 'url' => '#', 'badge' => 'Popular'],
                ],
            ],
            'data_source_type' => 'manual_products',
            'data_source_payload_json' => [
                'items' => [
                    ['title' => 'Canvas Weekender', 'subtitle' => 'Preset-ready hero item', 'price' => '$129.00', 'url' => '#', 'badge' => 'New'],
                    ['title' => 'Utility Jacket', 'subtitle' => 'Structured CMS sample card', 'price' => '$158.00', 'url' => '#', 'badge' => 'Featured'],
                    ['title' => 'Trail Sneaker', 'subtitle' => 'Manual preview data source', 'price' => '$96.00', 'url' => '#', 'badge' => 'Popular'],
                ],
            ],
            'is_active' => true,
        ]);

        PageSection::query()->create([
            'page_id' => $page->id,
            'section_type_id' => $richText->id,
            'sort_order' => 3,
            'title' => 'Rich Text',
            'settings_json' => [
                'eyebrow' => 'Why this model works',
                'headline' => 'Bounded content control keeps every deployment maintainable.',
                'body' => '<p>Admins can compose approved sections, update global areas, and switch presets without touching templates or creating one-off layout drift.</p>',
            ],
            'is_active' => true,
        ]);
    }
}
