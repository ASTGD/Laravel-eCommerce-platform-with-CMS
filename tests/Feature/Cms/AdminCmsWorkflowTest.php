<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Platform\ExperienceCms\Models\FooterConfig;
use Platform\ExperienceCms\Models\HeaderConfig;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageSection;
use Platform\ExperienceCms\Models\SectionType;
use Platform\ExperienceCms\Models\Template;
use Platform\ExperienceCms\Models\TemplateArea;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\User\Models\Admin as AdminModel;
use Webkul\User\Models\Role;

uses(AdminTestCase::class);

function cmsStudioAdminWithPermissions(array $permissions): AdminModel
{
    $role = Role::query()->create([
        'name' => 'CMS Studio ACL '.uniqid(),
        'description' => 'CMS Studio ACL test role',
        'permission_type' => 'custom',
        'permissions' => $permissions,
    ]);

    return AdminModel::factory()->create([
        'role_id' => $role->id,
    ]);
}

function cmsStudioMenu(): Menu
{
    $menu = Menu::query()->create([
        'name' => 'CMS Studio Test Menu '.uniqid(),
        'code' => 'cms_studio_test_menu_'.uniqid(),
        'location' => 'header',
        'is_active' => true,
    ]);

    $menu->items()->create([
        'title' => 'Home',
        'type' => 'url',
        'target' => '/',
        'sort_order' => 1,
        'settings_json' => [],
        'is_active' => true,
    ]);

    return $menu;
}

function cmsStudioHomepagePage(): Page
{
    foreach ([
        ['code' => 'hero', 'name' => 'Hero', 'category' => 'hero'],
        ['code' => 'hero_banner', 'name' => 'Hero Banner', 'category' => 'hero'],
        ['code' => 'hero_slider', 'name' => 'Hero Slider', 'category' => 'hero'],
        ['code' => 'promo_strip', 'name' => 'Promo Strip', 'category' => 'merchandising'],
        ['code' => 'rich_text', 'name' => 'Rich Text', 'category' => 'content'],
        ['code' => 'featured_products', 'name' => 'Featured Products', 'category' => 'catalog'],
    ] as $sectionType) {
        SectionType::query()->updateOrCreate(
            ['code' => $sectionType['code']],
            [
                ...$sectionType,
                'config_schema_json' => [],
                'supports_components' => false,
                'allowed_data_sources_json' => [],
                'is_active' => true,
            ]
        );
    }

    $template = Template::query()->updateOrCreate(
        ['code' => 'homepage_default'],
        [
            'name' => 'Homepage Default',
            'page_type' => 'homepage',
            'schema_json' => [],
            'is_active' => true,
        ]
    );

    TemplateArea::query()->updateOrCreate(
        ['template_id' => $template->id, 'code' => 'hero'],
        ['name' => 'Hero', 'rules_json' => ['max_sections' => 1], 'sort_order' => 1]
    );

    TemplateArea::query()->updateOrCreate(
        ['template_id' => $template->id, 'code' => 'content'],
        ['name' => 'Content', 'rules_json' => ['max_sections' => 12], 'sort_order' => 2]
    );

    return Page::query()->updateOrCreate(
        ['slug' => 'home'],
        [
            'title' => 'Homepage',
            'type' => 'homepage',
            'template_id' => $template->id,
            'settings_json' => [],
            'status' => Page::STATUS_PUBLISHED,
            'published_at' => now(),
        ]
    );
}

it('renders the business friendly CMS Studio workspace', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.cms.index'));

    $response->assertOk()
        ->assertSeeText('CMS Studio')
        ->assertSeeText('My Website')
        ->assertSeeText('Header')
        ->assertSeeText('Footer')
        ->assertSeeText('Navigation')
        ->assertSeeText('Homepage')
        ->assertSeeText('Pages')
        ->assertSeeText('Reusable Blocks')
        ->assertSeeText('Site Settings')
        ->assertSeeText('Header Builder')
        ->assertDontSee('settings_json')
        ->assertDontSeeText('Section Types')
        ->assertDontSeeText('Component Types')
        ->assertDontSeeText('Page Assignments');
});

it('renders every CMS Studio local section without exposing raw JSON editing', function (string $area, string $label) {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.cms.index', ['area' => $area]));

    $response->assertOk()
        ->assertSeeText($label)
        ->assertDontSee('settings_json')
        ->assertDontSee('name="settings_json"', false)
        ->assertDontSeeText('Raw JSON');
})->with([
    ['header', 'Header Builder'],
    ['footer', 'Footer Builder'],
    ['navigation', 'Navigation'],
    ['homepage', 'Homepage Builder'],
    ['pages', 'Pages'],
    ['reusable-blocks', 'Reusable Blocks'],
    ['settings', 'Site Settings'],
]);

it('saves structured header settings through CMS Studio', function () {
    $this->loginAsAdmin();

    HeaderConfig::query()->update(['is_default' => false]);

    $header = HeaderConfig::query()->create([
        'code' => 'cms_studio_test_header_'.uniqid(),
        'settings_json' => [],
        'is_default' => true,
    ]);

    $menu = cmsStudioMenu();

    $response = $this->post(route('admin.cms.header.update'), [
        'name' => 'Studio Header',
        'logo_url' => 'https://example.com/logo.svg',
        'announcement_enabled' => '1',
        'announcement_text' => 'Free delivery this week',
        'announcement_link' => 'https://example.com/sale',
        'menu_id' => $menu->id,
        'show_search' => '1',
        'show_account' => '1',
        'show_cart' => '1',
        'sticky' => '1',
        'variant' => 'centered',
    ]);

    $response->assertRedirect(route('admin.cms.index', ['area' => 'header']));

    $header->refresh();

    expect(data_get($header->settings_json, 'name'))->toBe('Studio Header')
        ->and(data_get($header->settings_json, 'logo_url'))->toBe('https://example.com/logo.svg')
        ->and(data_get($header->settings_json, 'announcement.enabled'))->toBeTrue()
        ->and(data_get($header->settings_json, 'announcement.text'))->toBe('Free delivery this week')
        ->and(data_get($header->settings_json, 'announcement.link'))->toBe('https://example.com/sale')
        ->and(data_get($header->settings_json, 'navigation.menu_id'))->toBe($menu->id)
        ->and(data_get($header->settings_json, 'features.show_search'))->toBeTrue()
        ->and(data_get($header->settings_json, 'features.show_account'))->toBeTrue()
        ->and(data_get($header->settings_json, 'features.show_cart'))->toBeTrue()
        ->and(data_get($header->settings_json, 'features.sticky'))->toBeTrue()
        ->and($header->settings_json['variant'])->toBe('centered');
});

it('shows header logo upload controls without inactive brand and style cards', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.cms.index', ['area' => 'header']));

    $response->assertOk()
        ->assertSeeText('Logo')
        ->assertSee('name="logo_file"', false)
        ->assertDontSeeText('Brand')
        ->assertDontSeeText('Choose a theme-supported header layout style.');
});

it('uploads a header logo through CMS Studio', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    HeaderConfig::query()->update(['is_default' => false]);

    $header = HeaderConfig::query()->create([
        'code' => 'cms_studio_logo_header_'.uniqid(),
        'settings_json' => [
            'logo_url' => 'https://example.com/old-logo.svg',
        ],
        'is_default' => true,
    ]);

    $response = $this->post(route('admin.cms.header.update'), [
        'name' => 'Studio Header',
        'logo_url' => 'https://example.com/old-logo.svg',
        'logo_file' => UploadedFile::fake()->image('cms-logo.png', 640, 240),
        'announcement_enabled' => '0',
        'show_search' => '1',
        'show_account' => '1',
        'show_cart' => '1',
        'sticky' => '0',
        'variant' => 'classic',
    ]);

    $response->assertRedirect(route('admin.cms.index', ['area' => 'header']));

    $header->refresh();

    expect(data_get($header->settings_json, 'logo_url'))->toStartWith('/storage/cms/header/');

    Storage::disk('public')->assertExists(str_replace('/storage/', '', data_get($header->settings_json, 'logo_url')));
});

it('shows footer logo upload controls without inactive identity and style fields', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.cms.index', ['area' => 'footer']));

    $response->assertOk()
        ->assertSeeText('Logo')
        ->assertSeeText('Footer Description')
        ->assertSeeText('Footer Menu Columns')
        ->assertSeeText('Column 1')
        ->assertSeeText('Column 2')
        ->assertSee('name="footer_description"', false)
        ->assertSee('name="footer_columns[0][menu_id]"', false)
        ->assertSee('name="logo_file"', false)
        ->assertDontSeeText('Identity')
        ->assertDontSeeText('Footer variant')
        ->assertDontSeeText('Use an image URL for now. Media picker will be added later.');
});

it('uploads a footer logo through CMS Studio', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    FooterConfig::query()->update(['is_default' => false]);

    $footer = FooterConfig::query()->create([
        'code' => 'cms_studio_logo_footer_'.uniqid(),
        'settings_json' => [
            'logo_url' => 'https://example.com/old-footer-logo.svg',
        ],
        'is_default' => true,
    ]);

    $response = $this->post(route('admin.cms.footer.update'), [
        'name' => 'Studio Footer',
        'logo_url' => 'https://example.com/old-footer-logo.svg',
        'logo_file' => UploadedFile::fake()->image('cms-footer-logo.png', 640, 240),
        'newsletter_enabled' => '0',
        'variant' => 'simple',
    ]);

    $response->assertRedirect(route('admin.cms.index', ['area' => 'footer']));

    $footer->refresh();

    expect(data_get($footer->settings_json, 'logo_url'))->toStartWith('/storage/cms/footer/');

    Storage::disk('public')->assertExists(str_replace('/storage/', '', data_get($footer->settings_json, 'logo_url')));
});

it('saves structured footer settings through CMS Studio', function () {
    $this->loginAsAdmin();

    FooterConfig::query()->update(['is_default' => false]);

    $footer = FooterConfig::query()->create([
        'code' => 'cms_studio_test_footer_'.uniqid(),
        'settings_json' => [],
        'is_default' => true,
    ]);
    $menu = cmsStudioMenu();
    $supportMenu = cmsStudioMenu();
    $supportMenu->update([
        'name' => 'CMS Studio Support Menu '.uniqid(),
        'code' => 'cms_studio_support_menu_'.uniqid(),
        'location' => 'footer',
    ]);

    $response = $this->post(route('admin.cms.footer.update'), [
        'name' => 'Studio Footer',
        'logo_url' => 'https://example.com/footer-logo.svg',
        'footer_description' => 'A managed footer description.',
        'footer_columns' => [
            [
                'enabled' => '1',
                'title' => 'Company',
                'menu_id' => $menu->id,
                'sort_order' => 1,
            ],
            [
                'enabled' => '1',
                'title' => 'Support',
                'menu_id' => $supportMenu->id,
                'sort_order' => 2,
            ],
        ],
        'newsletter_enabled' => '1',
        'newsletter_heading' => 'Join our list',
        'newsletter_text' => 'Get new arrivals and offers.',
        'contact_email' => 'hello@example.com',
        'contact_phone' => '+1 555 0100',
        'social_facebook' => 'https://facebook.com/example',
        'social_instagram' => 'https://instagram.com/example',
        'social_x' => 'https://x.com/example',
        'social_youtube' => 'https://youtube.com/@example',
        'social_tiktok' => 'https://tiktok.com/@example',
        'copyright_text' => 'Copyright Storefront.',
        'variant' => 'multi_column',
    ]);

    $response->assertRedirect(route('admin.cms.index', ['area' => 'footer']));

    $footer->refresh();

    expect(data_get($footer->settings_json, 'name'))->toBe('Studio Footer')
        ->and(data_get($footer->settings_json, 'logo_url'))->toBe('https://example.com/footer-logo.svg')
        ->and(data_get($footer->settings_json, 'description'))->toBe('A managed footer description.')
        ->and(data_get($footer->settings_json, 'navigation.menu_id'))->toBe($menu->id)
        ->and(data_get($footer->settings_json, 'navigation.columns'))->toHaveCount(2)
        ->and(data_get($footer->settings_json, 'navigation.columns.0.title'))->toBe('Company')
        ->and(data_get($footer->settings_json, 'navigation.columns.0.menu_id'))->toBe($menu->id)
        ->and(data_get($footer->settings_json, 'navigation.columns.1.title'))->toBe('Support')
        ->and(data_get($footer->settings_json, 'navigation.columns.1.menu_id'))->toBe($supportMenu->id)
        ->and(data_get($footer->settings_json, 'newsletter.enabled'))->toBeTrue()
        ->and(data_get($footer->settings_json, 'newsletter.heading'))->toBe('Join our list')
        ->and(data_get($footer->settings_json, 'newsletter.text'))->toBe('Get new arrivals and offers.')
        ->and(data_get($footer->settings_json, 'contact.email'))->toBe('hello@example.com')
        ->and(data_get($footer->settings_json, 'contact.phone'))->toBe('+1 555 0100')
        ->and(data_get($footer->settings_json, 'social.facebook'))->toBe('https://facebook.com/example')
        ->and(data_get($footer->settings_json, 'social.instagram'))->toBe('https://instagram.com/example')
        ->and(data_get($footer->settings_json, 'social.x'))->toBe('https://x.com/example')
        ->and(data_get($footer->settings_json, 'social.youtube'))->toBe('https://youtube.com/@example')
        ->and(data_get($footer->settings_json, 'social.tiktok'))->toBe('https://tiktok.com/@example')
        ->and($footer->settings_json['copyright_text'])->toBe('Copyright Storefront.')
        ->and($footer->settings_json['variant'])->toBe('multi_column');
});

it('creates and edits flat navigation menus through CMS Studio', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.cms.index', ['area' => 'navigation', 'menu' => 'new']))
        ->assertOk()
        ->assertSeeText('Create New Menu')
        ->assertSeeText('Create Menu')
        ->assertSeeText('Add Menu Item')
        ->assertSee('data-navigation-builder', false)
        ->assertSee('data-menu-item-template', false)
        ->assertSee('data-remove-menu-item', false)
        ->assertSee('name="items[0][title]"', false)
        ->assertSee('type="button"', false)
        ->assertSee('window.cmsNavigationAddItem', false)
        ->assertSee('window.__cmsNavigationBuilderEventsBound', false);

    $menuName = 'Studio Header Menu '.uniqid();
    $updatedMenuName = 'Studio Footer Menu '.uniqid();

    $response = $this->post(route('admin.cms.navigation.update'), [
        'name' => $menuName,
        'location' => 'header',
        'is_active' => '1',
        'items' => [
            [
                'title' => 'Shop',
                'type' => 'url',
                'target' => '/shop',
                'sort_order' => 1,
                'is_active' => '1',
                'open_in_new_tab' => '0',
            ],
            [
                'title' => 'Campaign',
                'type' => 'url',
                'target' => 'https://example.com/campaign',
                'sort_order' => 2,
                'is_active' => '1',
                'open_in_new_tab' => '1',
            ],
        ],
    ]);

    $menu = Menu::query()->where('name', $menuName)->first();

    $response->assertRedirect(route('admin.cms.index', ['area' => 'navigation', 'menu' => $menu->id]));

    expect($menu)->not->toBeNull()
        ->and($menu->location)->toBe('header')
        ->and($menu->is_active)->toBeTrue()
        ->and($menu->items()->count())->toBe(2)
        ->and(data_get($menu->items()->where('title', 'Campaign')->first()->settings_json, 'open_in_new_tab'))->toBeTrue();

    $this->get(route('admin.cms.index', ['area' => 'header']))
        ->assertOk()
        ->assertSeeText($menuName);

    $this->post(route('admin.cms.navigation.update'), [
        'menu_id' => $menu->id,
        'name' => $updatedMenuName,
        'location' => 'footer',
        'is_active' => '1',
        'items' => [
            [
                'title' => 'Privacy',
                'type' => 'page',
                'target' => '/privacy-policy',
                'sort_order' => 1,
                'is_active' => '1',
                'open_in_new_tab' => '0',
            ],
        ],
    ])->assertRedirect(route('admin.cms.index', ['area' => 'navigation', 'menu' => $menu->id]));

    $menu->refresh();

    expect($menu->name)->toBe($updatedMenuName)
        ->and($menu->location)->toBe('footer')
        ->and($menu->items()->count())->toBe(1)
        ->and($menu->items()->first()->title)->toBe('Privacy');
});

it('renders the structured homepage section builder without raw JSON editing', function () {
    $this->loginAsAdmin();

    $page = cmsStudioHomepagePage();
    $page->sections()->delete();

    PageSection::query()->create([
        'page_id' => $page->id,
        'template_area_id' => $page->template->areas->firstWhere('code', 'hero')->id,
        'section_type_id' => SectionType::query()->where('code', 'hero_banner')->value('id'),
        'sort_order' => 1,
        'title' => 'Hero Banner',
        'settings_json' => ['headline' => 'Original hero'],
        'is_active' => true,
    ]);

    PageSection::query()->create([
        'page_id' => $page->id,
        'template_area_id' => $page->template->areas->firstWhere('code', 'content')->id,
        'section_type_id' => SectionType::query()->where('code', 'featured_products')->value('id'),
        'sort_order' => 2,
        'title' => 'Featured Products',
        'settings_json' => ['limit' => 8],
        'data_source_type' => 'featured_products',
        'data_source_payload_json' => ['limit' => 8],
        'is_active' => true,
    ]);

    $this->get(route('admin.cms.index', ['area' => 'homepage']))
        ->assertOk()
        ->assertSeeText('Homepage Builder')
        ->assertSeeText('Homepage Hero')
        ->assertSeeText('Open Storefront')
        ->assertSeeText('Hero')
        ->assertSeeText('Homepage content below the Hero is rendered by the active theme.')
        ->assertDontSeeText('Homepage Status')
        ->assertDontSeeText('Open Signed Preview')
        ->assertDontSeeText('Signed Preview')
        ->assertDontSee('>Hero Banner</option>', false)
        ->assertDontSee('>Hero Slider</option>', false)
        ->assertDontSeeText('Promo Strip')
        ->assertDontSeeText('Rich Text')
        ->assertDontSeeText('Featured Products')
        ->assertDontSeeText('theme-managed section')
        ->assertDontSee('settings_json')
        ->assertDontSee('name="settings_json"', false)
        ->assertDontSeeText('Raw JSON');
});

it('saves and reorders structured homepage sections through CMS Studio', function () {
    $this->loginAsAdmin();

    $page = cmsStudioHomepagePage();
    $page->sections()->delete();

    $heroArea = $page->template->areas->firstWhere('code', 'hero');
    $contentArea = $page->template->areas->firstWhere('code', 'content');

    $hero = PageSection::query()->create([
        'page_id' => $page->id,
        'template_area_id' => $heroArea->id,
        'section_type_id' => SectionType::query()->where('code', 'hero_banner')->value('id'),
        'sort_order' => 1,
        'title' => 'Hero Banner',
        'settings_json' => ['headline' => 'Old headline'],
        'is_active' => true,
    ]);

    $featured = PageSection::query()->create([
        'page_id' => $page->id,
        'template_area_id' => $contentArea->id,
        'section_type_id' => SectionType::query()->where('code', 'featured_products')->value('id'),
        'sort_order' => 2,
        'title' => 'Featured Products',
        'settings_json' => ['limit' => 8],
        'data_source_type' => 'featured_products',
        'data_source_payload_json' => ['limit' => 8],
        'is_active' => true,
    ]);

    $response = $this->post(route('admin.cms.homepage.update'), [
        'sections' => [
            [
                'id' => $hero->id,
                'section_code' => 'hero',
                'title' => 'Launch Hero',
                'sort_order' => 2,
                'is_active' => '1',
                'settings' => [
                    'mode' => 'static',
                    'slides' => [
                        [
                            'current_image' => 'storage/cms/homepage/hero/static.jpg',
                            'title' => 'Launch hero image',
                            'headline' => 'Updated homepage hero',
                            'body' => 'A safer structured homepage section.',
                            'primary_cta_label' => 'Shop now',
                            'primary_cta_url' => '/catalog',
                            'secondary_cta_label' => 'Learn more',
                            'secondary_cta_url' => '/about-us',
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.cms.index', ['area' => 'homepage']));

    $hero->refresh();
    $featured->refresh();

    expect($hero->title)->toBe('Launch Hero')
        ->and($hero->sort_order)->toBe(2)
        ->and($hero->sectionType?->code)->toBe('hero')
        ->and(data_get($hero->settings_json, 'mode'))->toBe('static')
        ->and(data_get($hero->settings_json, 'slides.0.headline'))->toBe('Updated homepage hero')
        ->and($featured->is_active)->toBeTrue()
        ->and($featured->data_source_type)->toBe('featured_products')
        ->and(data_get($featured->settings_json, 'limit'))->toBe(8)
        ->and($page->sections()->where('title', 'Announcement Strip')->exists())->toBeFalse();
});

it('uploads hero slider images through the homepage builder', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    $page = cmsStudioHomepagePage();
    $page->sections()->delete();

    $response = $this->post(route('admin.cms.homepage.update'), [
        'sections' => [
            [
                'section_code' => 'hero',
                'title' => 'Homepage Hero',
                'sort_order' => 1,
                'is_active' => '1',
                'settings' => [
                    'mode' => 'slider',
                    'slides' => [
                        [
                            'image_file' => UploadedFile::fake()->image('slide-one.jpg', 1920, 700),
                            'title' => 'Launch sale slide',
                            'headline' => 'Launch sale',
                            'primary_cta_label' => 'Shop sale',
                            'primary_cta_url' => '/sale',
                        ],
                        [
                            'image_file' => UploadedFile::fake()->image('slide-two.jpg', 1920, 700),
                            'title' => 'New arrivals slide',
                            'headline' => 'New arrivals',
                            'primary_cta_label' => 'Shop arrivals',
                            'primary_cta_url' => '/new-arrivals',
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.cms.index', ['area' => 'homepage']));

    $slider = $page->sections()
        ->whereHas('sectionType', fn ($query) => $query->where('code', 'hero'))
        ->first();

    expect($slider)->not->toBeNull()
        ->and($slider->title)->toBe('Homepage Hero')
        ->and(data_get($slider->settings_json, 'mode'))->toBe('slider')
        ->and(data_get($slider->settings_json, 'slides'))->toHaveCount(2)
        ->and(data_get($slider->settings_json, 'slides.0.title'))->toBe('Launch sale slide')
        ->and(data_get($slider->settings_json, 'slides.0.primary_cta_url'))->toBe('/sale');

    Storage::disk('public')->assertExists(str_replace('storage/', '', data_get($slider->settings_json, 'slides.0.image')));
    Storage::disk('public')->assertExists(str_replace('storage/', '', data_get($slider->settings_json, 'slides.1.image')));
});

it('uploads a static hero image through the homepage builder', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    $page = cmsStudioHomepagePage();
    $page->sections()->delete();

    $response = $this->post(route('admin.cms.homepage.update'), [
        'sections' => [
            [
                'section_code' => 'hero',
                'title' => 'Homepage Hero',
                'sort_order' => 1,
                'is_active' => '1',
                'settings' => [
                    'mode' => 'static',
                    'slides' => [
                        [
                            'enabled' => '1',
                            'image_file' => UploadedFile::fake()->image('static-hero.jpg', 1920, 700),
                            'title' => 'Static hero image',
                            'headline' => 'Static launch hero',
                            'primary_cta_label' => 'Shop now',
                            'primary_cta_url' => '/shop',
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.cms.index', ['area' => 'homepage']));

    $hero = $page->sections()
        ->whereHas('sectionType', fn ($query) => $query->where('code', 'hero'))
        ->first();

    expect($hero)->not->toBeNull()
        ->and(data_get($hero->settings_json, 'mode'))->toBe('static')
        ->and(data_get($hero->settings_json, 'slides'))->toHaveCount(1)
        ->and(data_get($hero->settings_json, 'slides.0.title'))->toBe('Static hero image')
        ->and(data_get($hero->settings_json, 'slides.0.image'))->toStartWith('storage/cms/homepage/hero/');

    Storage::disk('public')->assertExists(str_replace('storage/', '', data_get($hero->settings_json, 'slides.0.image')));
});

it('rejects hero slider slides without images', function () {
    $this->loginAsAdmin();

    cmsStudioHomepagePage()->sections()->delete();

    $this->from(route('admin.cms.index', ['area' => 'homepage']))
        ->post(route('admin.cms.homepage.update'), [
            'sections' => [
                [
                    'section_code' => 'hero',
                    'title' => 'Homepage Hero',
                    'sort_order' => 1,
                    'is_active' => '1',
                    'settings' => [
                        'mode' => 'slider',
                        'slides' => [
                            [
                                'title' => 'Missing image slide',
                                'headline' => 'Missing image',
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('admin.cms.index', ['area' => 'homepage']))
        ->assertSessionHasErrors('slides.0.image');
});

it('keeps header and footer saves behind explicit CMS Studio permissions', function () {
    $admin = cmsStudioAdminWithPermissions(['dashboard']);

    $this->loginAsAdmin($admin);

    $this->post(route('admin.cms.header.update'), [])->assertStatus(401);
    $this->post(route('admin.cms.footer.update'), [])->assertStatus(401);
    $this->post(route('admin.cms.navigation.update'), [])->assertStatus(401);
    $this->post(route('admin.cms.homepage.update'), [])->assertStatus(401);
});
