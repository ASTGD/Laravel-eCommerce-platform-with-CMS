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

it('saves structured footer settings through CMS Studio', function () {
    $this->loginAsAdmin();

    FooterConfig::query()->update(['is_default' => false]);

    $footer = FooterConfig::query()->create([
        'code' => 'cms_studio_test_footer_'.uniqid(),
        'settings_json' => [],
        'is_default' => true,
    ]);

    $response = $this->post(route('admin.cms.footer.update'), [
        'name' => 'Studio Footer',
        'logo_url' => 'https://example.com/footer-logo.svg',
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
        ->assertSeeText('Homepage Sections')
        ->assertSeeText('Hero Banner')
        ->assertSeeText('Hero Slider')
        ->assertSeeText('Promo Strip')
        ->assertSeeText('Rich Text')
        ->assertSeeText('Featured Products')
        ->assertSeeText('theme-managed section')
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
                'section_code' => 'hero_banner',
                'title' => 'Launch Hero',
                'sort_order' => 2,
                'is_active' => '1',
                'settings' => [
                    'eyebrow' => 'Homepage',
                    'headline' => 'Updated homepage hero',
                    'body' => 'A safer structured homepage section.',
                    'primary_cta_label' => 'Shop now',
                    'primary_cta_url' => '/catalog',
                    'secondary_cta_label' => 'Learn more',
                    'secondary_cta_url' => '/about-us',
                ],
            ],
            [
                'id' => $featured->id,
                'section_code' => 'featured_products',
                'title' => 'Featured Products',
                'sort_order' => 3,
                'is_active' => '0',
            ],
            [
                'section_code' => 'promo_strip',
                'title' => 'Announcement Strip',
                'sort_order' => 1,
                'is_active' => '1',
                'settings' => [
                    'content' => 'Free delivery on selected orders',
                ],
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.cms.index', ['area' => 'homepage']));

    $hero->refresh();
    $featured->refresh();
    $promo = $page->sections()->where('title', 'Announcement Strip')->first();

    expect($hero->title)->toBe('Launch Hero')
        ->and($hero->sort_order)->toBe(2)
        ->and(data_get($hero->settings_json, 'headline'))->toBe('Updated homepage hero')
        ->and($featured->is_active)->toBeFalse()
        ->and($featured->data_source_type)->toBe('featured_products')
        ->and(data_get($featured->settings_json, 'limit'))->toBe(8)
        ->and($promo)->not->toBeNull()
        ->and($promo->sort_order)->toBe(1)
        ->and($promo->is_active)->toBeTrue()
        ->and(data_get($promo->settings_json, 'content'))->toBe('Free delivery on selected orders');
});

it('uploads hero slider images through the homepage builder', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    $page = cmsStudioHomepagePage();
    $page->sections()->delete();

    $response = $this->post(route('admin.cms.homepage.update'), [
        'sections' => [
            [
                'section_code' => 'hero_slider',
                'title' => 'Homepage Slider',
                'sort_order' => 1,
                'is_active' => '1',
                'settings' => [
                    'slides' => [
                        [
                            'image_file' => UploadedFile::fake()->image('slide-one.jpg', 1920, 700),
                            'title' => 'Launch sale slide',
                            'link' => '/sale',
                        ],
                        [
                            'image_file' => UploadedFile::fake()->image('slide-two.jpg', 1920, 700),
                            'title' => 'New arrivals slide',
                            'link' => '/new-arrivals',
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.cms.index', ['area' => 'homepage']));

    $slider = $page->sections()
        ->whereHas('sectionType', fn ($query) => $query->where('code', 'hero_slider'))
        ->first();

    expect($slider)->not->toBeNull()
        ->and($slider->title)->toBe('Homepage Slider')
        ->and(data_get($slider->settings_json, 'slides'))->toHaveCount(2)
        ->and(data_get($slider->settings_json, 'slides.0.title'))->toBe('Launch sale slide')
        ->and(data_get($slider->settings_json, 'slides.0.link'))->toBe('/sale');

    Storage::disk('public')->assertExists(str_replace('storage/', '', data_get($slider->settings_json, 'slides.0.image')));
    Storage::disk('public')->assertExists(str_replace('storage/', '', data_get($slider->settings_json, 'slides.1.image')));
});

it('keeps header and footer saves behind explicit CMS Studio permissions', function () {
    $admin = cmsStudioAdminWithPermissions(['dashboard']);

    $this->loginAsAdmin($admin);

    $this->post(route('admin.cms.header.update'), [])->assertStatus(401);
    $this->post(route('admin.cms.footer.update'), [])->assertStatus(401);
    $this->post(route('admin.cms.navigation.update'), [])->assertStatus(401);
    $this->post(route('admin.cms.homepage.update'), [])->assertStatus(401);
});
