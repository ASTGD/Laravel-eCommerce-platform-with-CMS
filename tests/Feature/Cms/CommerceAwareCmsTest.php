<?php

use Illuminate\Support\Facades\URL;
use Platform\ExperienceCms\Contracts\ContentEntryResolverContract;
use Platform\ExperienceCms\Contracts\PageAssignmentResolverContract;
use Platform\ExperienceCms\Contracts\PageVersionRestoreContract;
use Platform\ExperienceCms\Contracts\SiteSettingsResolverContract;
use Platform\ExperienceCms\Models\FooterConfig;
use Platform\ExperienceCms\Models\HeaderConfig;
use Platform\ExperienceCms\Models\ComponentType;
use Platform\ExperienceCms\Models\ContentEntry;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageAssignment;
use Platform\ExperienceCms\Models\PageSection;
use Platform\ExperienceCms\Models\SectionType;
use Platform\ThemeCore\Models\ThemePreset;
use Webkul\Faker\Helpers\Category as CategoryFaker;
use Webkul\Category\Models\Category;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Shop\Tests\ShopTestCase;

uses(ShopTestCase::class);

beforeEach(function () {
    $this->withoutVite();
});

function cmsTestCategory(): Category
{
    return Category::query()->whereNotNull('parent_id')->where('parent_id', '!=', 0)->first()
        ?: (new CategoryFaker)->factory()->create();
}

it('prefers exact entity assignments over global defaults for category pages', function () {
    $category = cmsTestCategory();
    $globalPage = Page::query()->where('slug', 'category-default-layout')->firstOrFail();

    $overridePage = Page::query()->create([
        'title' => 'Specific Category Override',
        'slug' => 'specific-category-override',
        'type' => 'category_page',
        'template_id' => $globalPage->template_id,
        'status' => Page::STATUS_DRAFT,
    ]);

    PageAssignment::query()->create([
        'page_id' => $overridePage->id,
        'page_type' => 'category_page',
        'scope_type' => 'entity',
        'entity_type' => 'category',
        'entity_id' => $category->id,
        'priority' => 100,
        'is_active' => true,
    ]);

    $resolved = app(PageAssignmentResolverContract::class)->resolveForCategory($category);

    expect($resolved)->not->toBeNull()
        ->and($resolved->page_id)->toBe($overridePage->id);
});

it('falls back to the active global category assignment when no exact override applies', function () {
    $category = cmsTestCategory();

    $globalPage = Page::query()->where('slug', 'category-default-layout')->firstOrFail();

    PageAssignment::query()->create([
        'page_id' => $globalPage->id,
        'page_type' => 'category_page',
        'scope_type' => 'entity',
        'entity_type' => 'category',
        'entity_id' => $category->id,
        'priority' => 999,
        'is_active' => false,
    ]);

    $resolved = app(PageAssignmentResolverContract::class)->resolveForCategory($category);

    expect($resolved)->not->toBeNull()
        ->and($resolved->scope_type)->toBe(PageAssignment::SCOPE_GLOBAL)
        ->and($resolved->page_id)->toBe($globalPage->id);
});

it('falls back to the active global product assignment when no exact override applies', function () {
    $product = (new ProductFaker)->getSimpleProductFactory()->create([
        'sku' => 'cms-product-global-fallback',
    ]);

    $globalPage = Page::query()->where('slug', 'product-default-layout')->firstOrFail();

    PageAssignment::query()->create([
        'page_id' => $globalPage->id,
        'page_type' => 'product_page',
        'scope_type' => 'entity',
        'entity_type' => 'product',
        'entity_id' => $product->id,
        'priority' => 999,
        'is_active' => false,
    ]);

    $resolved = app(PageAssignmentResolverContract::class)->resolveForProduct($product);

    expect($resolved)->not->toBeNull()
        ->and($resolved->scope_type)->toBe(PageAssignment::SCOPE_GLOBAL)
        ->and($resolved->page_id)->toBe($globalPage->id);
});

it('renders the published category page through the CMS assignment and supports signed preview', function () {
    if (config('experience-cms.storefront_mode') !== 'cms') {
        $this->markTestSkipped('CMS storefront mode is disabled by default.');
    }

    $category = cmsTestCategory();

    $product = (new ProductFaker)->getSimpleProductFactory()->create([
        'sku' => 'cms-category-smoke-product',
    ]);

    $product->categories()->syncWithoutDetaching([$category->id]);

    $this->get(route('shop.product_or_category.index', $category->slug))
        ->assertOk()
        ->assertSeeText($category->name)
        ->assertSeeText('Category Story');

    $page = Page::query()->where('slug', 'category-default-layout')->firstOrFail();
    $signedPreviewUrl = URL::temporarySignedRoute('platform.storefront.category-pages.preview', now()->addMinutes(30), [
        'platformPage' => $page->slug,
        'categorySlug' => $category->slug,
    ]);

    $this->get($signedPreviewUrl)
        ->assertOk()
        ->assertSeeText($category->name)
        ->assertSeeText('Category Story');
});

it('renders the published product page through the CMS assignment and supports signed preview', function () {
    if (config('experience-cms.storefront_mode') !== 'cms') {
        $this->markTestSkipped('CMS storefront mode is disabled by default.');
    }

    $product = (new ProductFaker)->getSimpleProductFactory()->create([
        'sku' => 'cms-product-smoke-product',
    ]);

    $this->get(route('shop.product_or_category.index', $product->url_key))
        ->assertOk()
        ->assertSeeText($product->name)
        ->assertSeeText('Shipping and stock notes remain installation-specific.');

    $page = Page::query()->where('slug', 'product-default-layout')->firstOrFail();
    $signedPreviewUrl = URL::temporarySignedRoute('platform.storefront.product-pages.preview', now()->addMinutes(30), [
        'platformPage' => $page->slug,
        'productSlug' => $product->url_key,
    ]);

    $this->get($signedPreviewUrl)
        ->assertOk()
        ->assertSeeText($product->name)
        ->assertSeeText('Shipping and stock notes remain installation-specific.');
});

it('restores a page snapshot and records the pre-restore version for undo safety', function () {
    $page = Page::query()->where('slug', 'home')->firstOrFail();
    $workflow = app(\Platform\ExperienceCms\Contracts\PublishWorkflowContract::class);
    $restore = app(PageVersionRestoreContract::class);

    $workflow->publish($page, 'Baseline publish for restore test.');

    $page->update(['title' => 'Modified Homepage Draft']);
    $page->sections()->firstOrFail()->update(['settings_json' => ['headline' => 'Modified draft headline']]);

    $version = $page->versions()->latest('version_number')->firstOrFail();

    $restore->restore($page, $version, 'Restore baseline snapshot');

    $page->refresh();
    $page->load('sections', 'versions');

    expect($page->title)->toBe($version->snapshot_json['page']['title'])
        ->and($page->sections->first()->settings_json['headline'])->toBe($version->snapshot_json['sections'][0]['settings_json']['headline'])
        ->and($page->versions()->count())->toBeGreaterThanOrEqual(2);
});

it('restores page-owned composition without mutating shared records or assignments', function () {
    $page = Page::query()->where('slug', 'category-default-layout')->firstOrFail();
    $workflow = app(\Platform\ExperienceCms\Contracts\PublishWorkflowContract::class);
    $restore = app(PageVersionRestoreContract::class);

    $assignmentCount = PageAssignment::query()->where('page_id', $page->id)->count();
    $header = HeaderConfig::query()->findOrFail($page->header_config_id);
    $footer = FooterConfig::query()->findOrFail($page->footer_config_id);
    $menu = Menu::query()->findOrFail($page->menu_id);
    $preset = ThemePreset::query()->findOrFail($page->theme_preset_id);

    $headerSettings = $header->settings_json;
    $footerSettings = $footer->settings_json;
    $menuName = $menu->name;
    $presetTokens = $preset->tokens_json;

    $workflow->publish($page, 'Baseline publish for shared dependency restore test.');

    $page->update(['title' => 'Changed Category Layout Title']);
    $page->sections()->firstOrFail()->update(['settings_json' => ['headline' => 'Changed category headline']]);

    $version = $page->versions()->latest('version_number')->firstOrFail();

    $restore->restore($page, $version, 'Restore without mutating shared dependencies');

    expect(PageAssignment::query()->where('page_id', $page->id)->count())->toBe($assignmentCount)
        ->and($header->fresh()->settings_json)->toBe($headerSettings)
        ->and($footer->fresh()->settings_json)->toBe($footerSettings)
        ->and($menu->fresh()->name)->toBe($menuName)
        ->and($preset->fresh()->tokens_json)->toBe($presetTokens);
});

it('resolves active content entries and site settings for the storefront payload layer', function () {
    $entry = ContentEntry::query()->where('slug', 'default-category-intro')->firstOrFail();

    $resolvedEntries = app(ContentEntryResolverContract::class)->resolve([
        'content_entry_ids' => [$entry->id],
    ]);

    $setting = app(SiteSettingsResolverContract::class)->value('store.trust_badges');

    expect($resolvedEntries)->toHaveCount(1)
        ->and($resolvedEntries->first()->is($entry))->toBeTrue()
        ->and($setting['badges'])->not->toBeEmpty();
});

it('filters draft content entries outside preview and preserves requested ordering in preview', function () {
    $published = ContentEntry::query()->create([
        'type' => 'marketing_copy',
        'title' => 'Published Ordered Entry',
        'slug' => 'published-ordered-entry',
        'body_json' => ['content' => 'Published body'],
        'status' => ContentEntry::STATUS_PUBLISHED,
    ]);

    $draft = ContentEntry::query()->create([
        'type' => 'marketing_copy',
        'title' => 'Draft Ordered Entry',
        'slug' => 'draft-ordered-entry',
        'body_json' => ['content' => 'Draft body'],
        'status' => ContentEntry::STATUS_DRAFT,
    ]);

    $liveEntries = app(ContentEntryResolverContract::class)->resolve([
        'content_entry_ids' => [$draft->id, $published->id],
    ]);

    $previewEntries = app(ContentEntryResolverContract::class)->resolve([
        'content_entry_ids' => [$draft->id, $published->id],
    ], [
        'preview' => true,
    ]);

    expect($liveEntries->pluck('id')->all())->toBe([$published->id])
        ->and($previewEntries->pluck('id')->all())->toBe([$draft->id, $published->id]);
});

it('validates nested component authoring against the component schema', function () {
    $this->actingAs(\Webkul\User\Models\Admin::factory()->create(), 'admin');

    $template = Page::query()->where('slug', 'home')->firstOrFail()->template;
    $area = $template->areas()->firstOrFail();
    $sectionType = SectionType::query()->where('code', 'hero_banner')->firstOrFail();
    $componentType = ComponentType::query()->where('code', 'headline')->firstOrFail();

    $response = $this->from(route('admin.cms.pages.create'))->post(route('admin.cms.pages.store'), [
        'title' => 'Invalid Nested Component Page',
        'slug' => 'invalid-nested-component-page',
        'type' => 'homepage',
        'template_id' => $template->id,
        'sections' => [
            [
                'template_area_id' => $area->id,
                'section_type_id' => $sectionType->id,
                'title' => 'Hero',
                'sort_order' => 1,
                'is_active' => 1,
                'settings_json' => json_encode(['headline' => 'Valid Headline']),
                'data_source_payload_json' => json_encode([]),
                'components' => [
                    [
                        'component_type_id' => $componentType->id,
                        'sort_order' => 1,
                        'is_active' => 1,
                        'settings_json' => json_encode(['content' => str_repeat('A', 300)]),
                    ],
                ],
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.cms.pages.create'))
        ->assertSessionHasErrors('sections.0.components.0.settings_json');
});
