<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Platform\PlatformSupport\Services\SquareCanvasImageService;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Category\Models\Category;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;

class SampleCatalogSeeder extends Seeder
{
    /**
     * Seed a small, clean demo catalog for local development.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        DB::transaction(function () {
            $this->clearDemoCategories();
            $familyId = $this->ensureMensShirtFamily();
            $brandOptionId = $this->ensureBrandOption();
            $brandAttributeId = $this->resolveAttributeId('brand');
            $colorAttributeId = $this->resolveAttributeId('color');
            $sizeAttributeId = $this->resolveAttributeId('size');
            $blackColorId = $this->resolveOptionId('color', 'Black');
            $whiteColorId = $this->resolveOptionId('color', 'White');
            $mediumSizeId = $this->resolveOptionId('size', 'M');
            $largeSizeId = $this->resolveOptionId('size', 'L');

            $defaultChannelId = (int) core()->getDefaultChannel()->id;
            $inventorySourceId = (int) DB::table('inventory_sources')->orderBy('id')->value('id');

            $productRepository = app(ProductRepository::class);

            $this->clearDemoProducts();

            $configurableProduct = $productRepository->create([
                'type' => 'configurable',
                'sku' => 'ASTGD-SHIRT-RF',
                'attribute_family_id' => $familyId,
            ]);

            $configurableProduct->super_attributes()->sync([$colorAttributeId, $sizeAttributeId]);

            $this->persistCatalogProduct(
                $configurableProduct,
                [
                    'name' => 'ASTGD Relaxed Fit Shirt',
                    'product_number' => 'ASTGD-SHIRT-RF',
                    'url_key' => 'astgd-relaxed-fit-shirt',
                    'short_description' => 'A clean configurable shirt sample for local development.',
                    'description' => 'A clean configurable shirt sample that demonstrates color and size variants with concise product content and real gallery media.',
                    'price' => 2499,
                    'weight' => 0.35,
                    'status' => 1,
                    'new' => 1,
                    'featured' => 1,
                    'visible_individually' => 1,
                    'manage_stock' => 0,
                    'brand' => $brandOptionId,
                ],
                [
                    'packages/Webkul/Installer/src/Resources/assets/images/seeders/products/61/1.webp',
                    'packages/Webkul/Installer/src/Resources/assets/images/seeders/products/61/2.webp',
                ],
                [
                    $inventorySourceId => 0,
                ]
            );

            $this->seedVariantProduct(
                $productRepository,
                $familyId,
                $configurableProduct->id,
                $brandOptionId,
                $inventorySourceId,
                $defaultChannelId,
                'ASTGD-SHIRT-BLACK-M',
                'ASTGD Relaxed Fit Shirt - Black / M',
                'astgd-relaxed-fit-shirt-black-m',
                'Relaxed fit shirt variant in Black / M.',
                'Clean demo variant for testing color and size selection in Bagisto.',
                2499,
                0.35,
                [
                    'color' => $blackColorId,
                    'size' => $mediumSizeId,
                ],
                [
                    'packages/Webkul/Installer/src/Resources/assets/images/seeders/products/95/1.webp',
                ]
            );

            $this->seedVariantProduct(
                $productRepository,
                $familyId,
                $configurableProduct->id,
                $brandOptionId,
                $inventorySourceId,
                $defaultChannelId,
                'ASTGD-SHIRT-BLACK-L',
                'ASTGD Relaxed Fit Shirt - Black / L',
                'astgd-relaxed-fit-shirt-black-l',
                'Relaxed fit shirt variant in Black / L.',
                'Clean demo variant for testing color and size selection in Bagisto.',
                2499,
                0.35,
                [
                    'color' => $blackColorId,
                    'size' => $largeSizeId,
                ],
                [
                    'packages/Webkul/Installer/src/Resources/assets/images/seeders/products/95/2.webp',
                ]
            );

            $this->seedVariantProduct(
                $productRepository,
                $familyId,
                $configurableProduct->id,
                $brandOptionId,
                $inventorySourceId,
                $defaultChannelId,
                'ASTGD-SHIRT-WHITE-M',
                'ASTGD Relaxed Fit Shirt - White / M',
                'astgd-relaxed-fit-shirt-white-m',
                'Relaxed fit shirt variant in White / M.',
                'Clean demo variant for testing color and size selection in Bagisto.',
                2499,
                0.35,
                [
                    'color' => $whiteColorId,
                    'size' => $mediumSizeId,
                ],
                [
                    'packages/Webkul/Installer/src/Resources/assets/images/seeders/products/95/3.webp',
                ]
            );

            $this->seedVariantProduct(
                $productRepository,
                $familyId,
                $configurableProduct->id,
                $brandOptionId,
                $inventorySourceId,
                $defaultChannelId,
                'ASTGD-SHIRT-WHITE-L',
                'ASTGD Relaxed Fit Shirt - White / L',
                'astgd-relaxed-fit-shirt-white-l',
                'Relaxed fit shirt variant in White / L.',
                'Clean demo variant for testing color and size selection in Bagisto.',
                2499,
                0.35,
                [
                    'color' => $whiteColorId,
                    'size' => $largeSizeId,
                ],
                [
                    'packages/Webkul/Installer/src/Resources/assets/images/seeders/products/132/1.webp',
                ]
            );

            $this->seedSimpleProduct(
                $productRepository,
                $familyId,
                $brandOptionId,
                $inventorySourceId,
                'ASTGD-SHIRT-001',
                'ASTGD Classic Oxford Shirt',
                'astgd-classic-oxford-shirt',
                'A clean sample shirt for testing the product form and storefront.',
                'A clean sample Oxford shirt with simple content and gallery media for demo use.',
                1999,
                0.32,
                [
                    'color' => 'Black',
                    'size' => 'M',
                ],
                [
                    'packages/Webkul/Installer/src/Resources/assets/images/seeders/products/59/1.webp',
                    'packages/Webkul/Installer/src/Resources/assets/images/seeders/products/59/2.webp',
                ]
            );

            $this->seedSimpleProduct(
                $productRepository,
                $familyId,
                $brandOptionId,
                $inventorySourceId,
                'ASTGD-SHIRT-002',
                'ASTGD Everyday Cotton Shirt',
                'astgd-everyday-cotton-shirt',
                'A second clean sample product for storefront and admin testing.',
                'A lightweight everyday cotton shirt with concise content and real product images.',
                1799,
                0.30,
                [
                    'color' => 'White',
                    'size' => 'L',
                ],
                [
                    'packages/Webkul/Installer/src/Resources/assets/images/seeders/products/132/2.webp',
                    'packages/Webkul/Installer/src/Resources/assets/images/seeders/products/132/1.webp',
                ]
            );

            $this->ensureAliExpressColorAttribute();
            $this->ensureAliExpressSizeAttribute();

            $aliExpressFamilyId = $this->ensureAliExpressShirtFamily();
            $aliExpressBrandOptionId = $this->ensureBrandOption('AliExpress');
            $aliExpressColorAttributeId = $this->resolveAttributeId('ae_color');
            $aliExpressSizeAttributeId = $this->resolveAttributeId('ae_size');

            $this->seedAliExpressShirtProduct(
                $productRepository,
                $aliExpressFamilyId,
                $aliExpressBrandOptionId,
                $inventorySourceId,
                $defaultChannelId,
                $aliExpressColorAttributeId,
                $aliExpressSizeAttributeId
            );

            $mensShirtsCategory = $this->ensureDemoCategory([
                $brandAttributeId,
                $colorAttributeId,
                $sizeAttributeId,
            ]);

            $this->attachDemoProductsToCategory($mensShirtsCategory);
        });

        Artisan::call('indexer:index', [
            '--mode' => ['full'],
            '--quiet' => true,
        ]);
    }

    private function ensureMensShirtFamily(): int
    {
        $family = DB::table('attribute_families')
            ->where('code', 'MensShirt')
            ->first(['id']);

        if ($family) {
            return (int) $family->id;
        }

        $family = app(AttributeFamilyRepository::class)->create([
            'code' => 'MensShirt',
            'name' => 'Mens Shirt',
            'attribute_groups' => [
                [
                    'code' => 'general',
                    'name' => 'General',
                    'column' => 1,
                    'position' => 1,
                    'is_user_defined' => 0,
                    'custom_attributes' => [
                        ['code' => 'sku'],
                        ['code' => 'product_number'],
                        ['code' => 'name'],
                        ['code' => 'url_key'],
                        ['code' => 'tax_category_id'],
                        ['code' => 'color'],
                        ['code' => 'size'],
                        ['code' => 'brand'],
                    ],
                ],
                [
                    'code' => 'description',
                    'name' => 'Description',
                    'column' => 1,
                    'position' => 2,
                    'is_user_defined' => 0,
                    'custom_attributes' => [
                        ['code' => 'short_description'],
                        ['code' => 'description'],
                    ],
                ],
                [
                    'code' => 'meta_description',
                    'name' => 'Meta Description',
                    'column' => 1,
                    'position' => 3,
                    'is_user_defined' => 0,
                    'custom_attributes' => [
                        ['code' => 'meta_title'],
                        ['code' => 'meta_keywords'],
                        ['code' => 'meta_description'],
                    ],
                ],
                [
                    'code' => 'price',
                    'name' => 'Price',
                    'column' => 2,
                    'position' => 1,
                    'is_user_defined' => 0,
                    'custom_attributes' => [
                        ['code' => 'price'],
                        ['code' => 'cost'],
                        ['code' => 'special_price'],
                        ['code' => 'special_price_from'],
                        ['code' => 'special_price_to'],
                    ],
                ],
                [
                    'code' => 'shipping',
                    'name' => 'Shipping',
                    'column' => 2,
                    'position' => 2,
                    'is_user_defined' => 0,
                    'custom_attributes' => [
                        ['code' => 'length'],
                        ['code' => 'width'],
                        ['code' => 'height'],
                        ['code' => 'weight'],
                    ],
                ],
                [
                    'code' => 'settings',
                    'name' => 'Settings',
                    'column' => 2,
                    'position' => 3,
                    'is_user_defined' => 0,
                    'custom_attributes' => [
                        ['code' => 'new'],
                        ['code' => 'featured'],
                        ['code' => 'visible_individually'],
                        ['code' => 'status'],
                        ['code' => 'guest_checkout'],
                        ['code' => 'manage_stock'],
                    ],
                ],
                [
                    'code' => 'inventories',
                    'name' => 'Inventories',
                    'column' => 2,
                    'position' => 4,
                    'is_user_defined' => 0,
                    'custom_attributes' => [
                        ['code' => 'allow_rma'],
                        ['code' => 'rma_rule_id'],
                    ],
                ],
            ],
        ]);

        return (int) $family->id;
    }

    private function ensureDemoCategory(array $attributeIds): Category
    {
        $translations = [];

        foreach (core()->getAllLocales() as $locale) {
            $translations[$locale->code] = [
                'name' => 'Mens Shirts',
                'slug' => 'mens-shirts',
                'description' => 'A clean demo category for local storefront smoke testing.',
                'meta_title' => 'Mens Shirts',
                'meta_description' => 'ASTGD sample category for local storefront smoke testing.',
                'meta_keywords' => 'mens shirts, astgd, sample catalog',
            ];
        }

        $category = Category::query()->create(array_merge([
            'position' => 1,
            'status' => 1,
            'display_mode' => 'products_and_description',
            'parent_id' => 1,
        ], $translations));

        DB::table('categories')
            ->where('id', $category->id)
            ->update([
                'logo_path' => $this->storeDemoMediaFile(
                    'category/'.$category->id.'/logo',
                    'packages/Webkul/Installer/src/Resources/assets/images/seeders/category/2/1.webp'
                ),
                'banner_path' => $this->storeDemoMediaFile(
                    'category/'.$category->id.'/banner',
                    'packages/Webkul/Installer/src/Resources/assets/images/seeders/category/3/1.webp'
                ),
            ]);

        if (! empty($attributeIds)) {
            $category->filterableAttributes()->sync(array_values(array_filter($attributeIds)));
        }

        return $category->fresh();
    }

    private function ensureBrandOption(): int
    {
        return $this->ensureBrandOptionFor('ASTGD');
    }

    private function ensureBrandOptionFor(string $brandName): int
    {
        $attributeId = $this->resolveAttributeId('brand');

        $optionId = DB::table('attribute_options')
            ->where('attribute_id', $attributeId)
            ->where('admin_name', $brandName)
            ->value('id');

        if (! $optionId) {
            $optionId = DB::table('attribute_options')->insertGetId([
                'attribute_id' => $attributeId,
                'admin_name' => $brandName,
                'sort_order' => 1,
                'swatch_value' => null,
            ]);
        }

        foreach (DB::table('locales')->pluck('code') as $locale) {
            DB::table('attribute_option_translations')->updateOrInsert(
                [
                    'attribute_option_id' => $optionId,
                    'locale' => $locale,
                ],
                [
                    'label' => $brandName,
                ]
            );
        }

        return (int) $optionId;
    }

    private function resolveAttributeId(string $attributeCode): int
    {
        $attributeId = (int) Attribute::query()->where('code', $attributeCode)->value('id');

        if (! $attributeId) {
            throw new \RuntimeException("Unable to resolve attribute '{$attributeCode}' for sample catalog seeding.");
        }

        return $attributeId;
    }

    private function seedSimpleProduct(
        ProductRepository $productRepository,
        int $familyId,
        int $brandOptionId,
        int $inventorySourceId,
        string $sku,
        string $name,
        string $urlKey,
        string $shortDescription,
        string $description,
        float $price,
        float $weight,
        array $attributeValues,
        array $imagePaths,
        string $colorAttributeCode = 'color',
        string $sizeAttributeCode = 'size'
    ): void {
        $product = $productRepository->create([
            'type' => 'simple',
            'sku' => $sku,
            'attribute_family_id' => $familyId,
        ]);

        $this->persistCatalogProduct(
            $product,
            [
                'name' => $name,
                'product_number' => $sku,
                'url_key' => $urlKey,
                'short_description' => $shortDescription,
                'description' => $description,
                'price' => $price,
                'weight' => $weight,
                'status' => 1,
                'new' => 1,
                'featured' => 1,
                'visible_individually' => 1,
                'manage_stock' => 1,
                'brand' => $brandOptionId,
                $colorAttributeCode => isset($attributeValues['color']) ? $this->resolveOptionId($colorAttributeCode, (string) $attributeValues['color']) : null,
                $sizeAttributeCode => isset($attributeValues['size']) ? $this->resolveOptionId($sizeAttributeCode, (string) $attributeValues['size']) : null,
            ],
            $imagePaths,
            [
                $inventorySourceId => 25,
            ]
        );
    }

    private function seedVariantProduct(
        ProductRepository $productRepository,
        int $familyId,
        int $parentId,
        int $brandOptionId,
        int $inventorySourceId,
        int $defaultChannelId,
        string $sku,
        string $name,
        string $urlKey,
        string $shortDescription,
        string $description,
        float $price,
        float $weight,
        array $attributeValues,
        array $imagePaths,
        string $colorAttributeCode = 'color',
        string $sizeAttributeCode = 'size'
    ): void {
        $product = $productRepository->create([
            'type' => 'simple',
            'sku' => $sku,
            'attribute_family_id' => $familyId,
            'parent_id' => $parentId,
        ]);

        $this->persistCatalogProduct(
            $product,
            [
                'name' => $name,
                'product_number' => $sku,
                'url_key' => $urlKey,
                'short_description' => $shortDescription,
                'description' => $description,
                'price' => $price,
                'weight' => $weight,
                'status' => 1,
                'new' => 1,
                'featured' => 0,
                'visible_individually' => 0,
                'manage_stock' => 1,
                'brand' => $brandOptionId,
                $colorAttributeCode => $attributeValues[$colorAttributeCode] ?? null,
                $sizeAttributeCode => $attributeValues[$sizeAttributeCode] ?? null,
            ],
            $imagePaths,
            [
                $inventorySourceId => 10,
            ]
        );

        $product->channels()->sync(
            $product->parent?->channels?->pluck('id')->toArray() ?: [$defaultChannelId]
        );
    }

    private function persistCatalogProduct($product, array $data, array $imagePaths, array $inventories): void
    {
        $payload = array_merge($data, [
            'channel' => core()->getDefaultChannelCode(),
            'locale' => core()->getDefaultLocaleCodeFromDefaultChannel(),
        ]);

        $attributeFamily = $product->attribute_family()->first();

        if (! $attributeFamily) {
            throw new \RuntimeException("Unable to load the attribute family for product {$product->sku} during sample catalog seeding.");
        }

        $customAttributes = $attributeFamily->custom_attributes()->get();

        app(ProductAttributeValueRepository::class)->saveValues($payload, $product, $customAttributes);

        app(ProductInventoryRepository::class)->saveInventories([
            'inventories' => $inventories,
        ], $product);

        app(ProductImageRepository::class)->upload([
            'images' => [
                'files' => array_map(fn (string $path) => $this->imageFile($path), $imagePaths),
            ],
        ], $product, 'images');
    }

    private function resolveOptionId(string $attributeCode, string $label): int
    {
        $attributeId = (int) Attribute::query()->where('code', $attributeCode)->value('id');

        if (! $attributeId) {
            throw new \RuntimeException("Unable to resolve attribute '{$attributeCode}' for sample catalog seeding.");
        }

        $optionId = DB::table('attribute_options as options')
            ->leftJoin('attribute_option_translations as translations', 'translations.attribute_option_id', '=', 'options.id')
            ->where('options.attribute_id', $attributeId)
            ->where(function ($query) use ($label) {
                $query->whereRaw('LOWER(options.admin_name) = ?', [Str::lower($label)])
                    ->orWhereRaw('LOWER(translations.label) = ?', [Str::lower($label)]);
            })
            ->value('options.id');

        if (! $optionId) {
            throw new \RuntimeException("Unable to resolve option '{$label}' for attribute '{$attributeCode}' during sample catalog seeding.");
        }

        return (int) $optionId;
    }

    private function ensureAliExpressShirtFamily(): int
    {
        $family = DB::table('attribute_families')
            ->where('code', 'AliExpressShirt')
            ->first(['id']);

        if ($family) {
            return (int) $family->id;
        }

        $family = app(AttributeFamilyRepository::class)->create([
            'code' => 'AliExpressShirt',
            'name' => 'AliExpress Shirt',
            'attribute_groups' => [
                [
                    'code' => 'general',
                    'name' => 'General',
                    'column' => 1,
                    'position' => 1,
                    'is_user_defined' => 0,
                    'custom_attributes' => [
                        ['code' => 'sku'],
                        ['code' => 'product_number'],
                        ['code' => 'name'],
                        ['code' => 'url_key'],
                        ['code' => 'tax_category_id'],
                        ['code' => 'ae_color'],
                        ['code' => 'ae_size'],
                        ['code' => 'brand'],
                    ],
                ],
                [
                    'code' => 'description',
                    'name' => 'Description',
                    'column' => 1,
                    'position' => 2,
                    'is_user_defined' => 0,
                    'custom_attributes' => [
                        ['code' => 'short_description'],
                        ['code' => 'description'],
                    ],
                ],
                [
                    'code' => 'meta_description',
                    'name' => 'Meta Description',
                    'column' => 1,
                    'position' => 3,
                    'is_user_defined' => 0,
                    'custom_attributes' => [
                        ['code' => 'meta_title'],
                        ['code' => 'meta_keywords'],
                        ['code' => 'meta_description'],
                    ],
                ],
                [
                    'code' => 'price',
                    'name' => 'Price',
                    'column' => 2,
                    'position' => 1,
                    'is_user_defined' => 0,
                    'custom_attributes' => [
                        ['code' => 'price'],
                        ['code' => 'cost'],
                        ['code' => 'special_price'],
                        ['code' => 'special_price_from'],
                        ['code' => 'special_price_to'],
                    ],
                ],
                [
                    'code' => 'shipping',
                    'name' => 'Shipping',
                    'column' => 2,
                    'position' => 2,
                    'is_user_defined' => 0,
                    'custom_attributes' => [
                        ['code' => 'length'],
                        ['code' => 'width'],
                        ['code' => 'height'],
                        ['code' => 'weight'],
                    ],
                ],
                [
                    'code' => 'settings',
                    'name' => 'Settings',
                    'column' => 2,
                    'position' => 3,
                    'is_user_defined' => 0,
                    'custom_attributes' => [
                        ['code' => 'new'],
                        ['code' => 'featured'],
                        ['code' => 'visible_individually'],
                        ['code' => 'status'],
                        ['code' => 'guest_checkout'],
                        ['code' => 'manage_stock'],
                    ],
                ],
                [
                    'code' => 'inventories',
                    'name' => 'Inventories',
                    'column' => 2,
                    'position' => 4,
                    'is_user_defined' => 0,
                    'custom_attributes' => [
                        ['code' => 'allow_rma'],
                        ['code' => 'rma_rule_id'],
                    ],
                ],
            ],
        ]);

        return (int) $family->id;
    }

    private function ensureAliExpressColorAttribute(): int
    {
        $attributeId = $this->ensureAttribute([
            'code' => 'ae_color',
            'admin_name' => 'Color',
            'type' => 'select',
            'swatch_type' => 'image',
            'position' => 1,
            'is_required' => 1,
            'is_unique' => 0,
            'is_filterable' => 1,
            'is_comparable' => 1,
            'is_configurable' => 1,
            'is_user_defined' => 1,
            'is_visible_on_front' => 1,
            'value_per_locale' => 0,
            'value_per_channel' => 0,
            'enable_wysiwyg' => 0,
        ]);

        $swatches = [
            ['label' => 'Dark Blue', 'sort_order' => 1, 'swatch_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S9f94fafe8077415d80bb4626c8b6ce95K.jpg_220x220q75.jpg_.avif'],
            ['label' => 'Gray', 'sort_order' => 2, 'swatch_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sac9a3b2810b84f119c2be8b92366362bx.jpg_220x220q75.jpg_.avif'],
            ['label' => 'Khaki', 'sort_order' => 3, 'swatch_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S4b909a119f514e9d9063e7148cb8072fj.jpg_220x220q75.jpg_.avif'],
            ['label' => 'White', 'sort_order' => 4, 'swatch_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Saf1bfe95145e45f696f723b0a35e3709K.jpg_220x220q75.jpg_.avif'],
            ['label' => 'Light Blue', 'sort_order' => 5, 'swatch_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S676978f5d03246c89be6de49521b7971N.jpg_220x220q75.jpg_.avif'],
        ];

        foreach ($swatches as $swatch) {
            $this->ensureAttributeOption('ae_color', $swatch['label'], $swatch['sort_order'], $swatch['swatch_url']);
        }

        return $attributeId;
    }

    private function ensureAliExpressSizeAttribute(): int
    {
        $attributeId = $this->ensureAttribute([
            'code' => 'ae_size',
            'admin_name' => 'Size',
            'type' => 'select',
            'swatch_type' => null,
            'position' => 2,
            'is_required' => 1,
            'is_unique' => 0,
            'is_filterable' => 1,
            'is_comparable' => 1,
            'is_configurable' => 1,
            'is_user_defined' => 1,
            'is_visible_on_front' => 1,
            'value_per_locale' => 0,
            'value_per_channel' => 0,
            'enable_wysiwyg' => 0,
        ]);

        $sizes = ['S', 'M', 'L', 'XL', '2XL', '3XL', '4XL'];

        foreach ($sizes as $index => $size) {
            $this->ensureAttributeOption('ae_size', $size, $index + 1);
        }

        return $attributeId;
    }

    private function ensureAttribute(array $data): int
    {
        $now = now();

        $payload = array_merge([
            'swatch_type' => null,
            'validation' => null,
            'position' => null,
            'is_required' => 0,
            'is_unique' => 0,
            'is_filterable' => 0,
            'is_comparable' => 0,
            'is_configurable' => 0,
            'is_user_defined' => 1,
            'is_visible_on_front' => 0,
            'value_per_locale' => 0,
            'value_per_channel' => 0,
            'enable_wysiwyg' => 0,
            'updated_at' => $now,
        ], $data);

        $attributeId = DB::table('attributes')
            ->where('code', $payload['code'])
            ->value('id');

        if ($attributeId) {
            DB::table('attributes')
                ->where('id', $attributeId)
                ->update($payload);

            return (int) $attributeId;
        }

        $payload['created_at'] = $now;

        return (int) DB::table('attributes')->insertGetId($payload);
    }

    private function ensureAttributeOption(string $attributeCode, string $label, int $sortOrder, ?string $swatchUrl = null): int
    {
        $attributeId = $this->resolveAttributeId($attributeCode);
        $now = now();
        $swatchValue = $swatchUrl ? $this->downloadRemoteSquareCanvasMediaFile($swatchUrl) : null;

        $option = DB::table('attribute_options')
            ->where('attribute_id', $attributeId)
            ->whereRaw('LOWER(admin_name) = ?', [Str::lower($label)])
            ->first();

        $payload = [
            'attribute_id' => $attributeId,
            'admin_name' => $label,
            'sort_order' => $sortOrder,
            'swatch_value' => $swatchValue,
        ];

        if ($option) {
            DB::table('attribute_options')
                ->where('id', $option->id)
                ->update($payload);

            $optionId = (int) $option->id;
        } else {
            $payload['id'] = DB::table('attribute_options')->insertGetId($payload);
            $optionId = (int) $payload['id'];
        }

        foreach (DB::table('locales')->pluck('code') as $locale) {
            DB::table('attribute_option_translations')->updateOrInsert(
                [
                    'attribute_option_id' => $optionId,
                    'locale' => $locale,
                ],
                [
                    'label' => $label,
                ]
            );
        }

        if ($swatchValue) {
            DB::table('attribute_options')
                ->where('id', $optionId)
                ->update([
                    'swatch_value' => $swatchValue,
                ]);
        }

        return $optionId;
    }

    private function seedAliExpressShirtProduct(
        ProductRepository $productRepository,
        int $familyId,
        int $brandOptionId,
        int $inventorySourceId,
        int $defaultChannelId,
        int $colorAttributeId,
        int $sizeAttributeId
    ): void {
        $product = $productRepository->create([
            'type' => 'configurable',
            'sku' => 'ALIEXPRESS-SHIRT-001',
            'attribute_family_id' => $familyId,
        ]);

        $product->super_attributes()->sync([$colorAttributeId, $sizeAttributeId]);

        $galleryImages = [
            'https://ae-pic-a1.aliexpress-media.com/kf/S9d0a462a7035491d9ccd6940fdc1cedc0.jpg_480x480q75.jpg_.avif',
            'https://ae-pic-a1.aliexpress-media.com/kf/Sd9daab5604014997af9d9dfc8aa45dbcn.jpg_480x480q75.jpg_.avif',
            'https://ae-pic-a1.aliexpress-media.com/kf/S3a8302b2775c449a8e8ac9eb4e45d309V.jpg_480x480q75.jpg_.avif',
            'https://ae-pic-a1.aliexpress-media.com/kf/Sb5cd3bb87d264f8f8f25bb06e050e04f1.jpg_480x480q75.jpg_.avif',
            'https://ae-pic-a1.aliexpress-media.com/kf/See5126c7b00f45978f21cc120adba697H.jpg_480x480q75.jpg_.avif',
        ];

            $this->persistCatalogProduct(
                $product,
                [
                    'name' => 'Mens Summer Solid Color Short Sleeve Tees and Premium Breathable Elegant Casual Shirts Hawaiian Inspired Cotton Linen Tops',
                'product_number' => 'ALIEXPRESS-SHIRT-001',
                'url_key' => 'mens-summer-solid-color-short-sleeve-shirt',
                'short_description' => 'A breathable AliExpress shirt sample with image swatches, sizes, and product gallery media.',
                'description' => 'AliExpress demo shirt for local development with image swatches, configurable color options, and size variants.',
                'price' => 601.84,
                'weight' => 0.28,
                'status' => 1,
                'new' => 1,
                'featured' => 1,
                'visible_individually' => 1,
                'manage_stock' => 0,
                'brand' => $brandOptionId,
                ],
                [
                    $this->downloadRemoteMediaFile($galleryImages[0]),
                    $this->downloadRemoteMediaFile($galleryImages[1]),
                    $this->downloadRemoteMediaFile($galleryImages[2]),
                    $this->downloadRemoteMediaFile($galleryImages[3]),
                    $this->downloadRemoteMediaFile($galleryImages[4]),
                ],
                [
                    $inventorySourceId => 0,
                ],
            'ae_color',
            'ae_size'
        );

        $colors = [
            [
                'label' => 'Dark Blue',
                'slug' => 'dark-blue',
                'gallery_url' => $galleryImages[0],
                'swatch_url' => $galleryImages[0],
            ],
            [
                'label' => 'Gray',
                'slug' => 'gray',
                'gallery_url' => $galleryImages[1],
                'swatch_url' => $galleryImages[1],
            ],
            [
                'label' => 'Khaki',
                'slug' => 'khaki',
                'gallery_url' => $galleryImages[2],
                'swatch_url' => $galleryImages[2],
            ],
            [
                'label' => 'White',
                'slug' => 'white',
                'gallery_url' => $galleryImages[3],
                'swatch_url' => $galleryImages[3],
            ],
            [
                'label' => 'Light Blue',
                'slug' => 'light-blue',
                'gallery_url' => $galleryImages[4],
                'swatch_url' => $galleryImages[4],
            ],
        ];

        $sizes = ['S', 'M', 'L', 'XL', '2XL', '3XL', '4XL'];

        foreach ($colors as $colorIndex => $color) {
            $colorOptionId = $this->ensureAttributeOption('ae_color', $color['label'], $colorIndex + 1, $color['swatch_url']);

            foreach ($sizes as $sizeIndex => $size) {
                $sizeOptionId = $this->ensureAttributeOption('ae_size', $size, $sizeIndex + 1);

                $sku = sprintf('ALIEXPRESS-SHIRT-%s-%s', Str::upper(Str::slug($color['label'], '-')), Str::upper($size));
                $urlKey = sprintf('ali-express-shirt-%s-%s', Str::slug($color['label'], '-'), Str::slug($size, '-'));

                $this->seedVariantProduct(
                    $productRepository,
                    $familyId,
                    $product->id,
                    $brandOptionId,
                    $inventorySourceId,
                    $defaultChannelId,
                    $sku,
                    'Mens Summer Solid Color Short Sleeve Tees and Premium Breathable Elegant Casual Shirts Hawaiian Inspired Cotton Linen Tops - '.$color['label'].' / '.$size,
                    $urlKey,
                    'AliExpress shirt variant in '.$color['label'].' / '.$size.'.',
                    'AliExpress demo shirt variant for testing image swatches, size selection, and configurable storefront behavior.',
                    601.84,
                    0.28,
                    [
                        'ae_color' => $colorOptionId,
                        'ae_size' => $sizeOptionId,
                    ],
                    [
                        $this->downloadRemoteMediaFile($color['gallery_url']),
                    ],
                    'ae_color',
                    'ae_size'
                );
            }
        }
    }

    private function downloadRemoteMediaFile(string $url): string
    {
        $disk = Storage::disk('public');
        $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'bin';
        $relativePath = 'sample-catalog/aliexpress/'.sha1($url).'.'.$extension;

        if ($disk->exists($relativePath)) {
            return $relativePath;
        }

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            'Referer' => 'https://www.aliexpress.com/',
        ])
            ->retry(2, 500)
            ->timeout(60)
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("Unable to download sample media from {$url}.");
        }

        $disk->put($relativePath, $response->body());

        return $relativePath;
    }

    private function downloadRemoteSquareCanvasMediaFile(string $url, int $size = 220): string
    {
        $sourceRelativePath = $this->downloadRemoteMediaFile($url);

        return app(SquareCanvasImageService::class)->fromRelativePath(
            $sourceRelativePath,
            'sample-catalog/aliexpress/swatches',
            $size
        );
    }

    private function storeDemoMediaFile(string $targetPath, string $relativePath): ?string
    {
        $source = base_path($relativePath);

        if (! file_exists($source)) {
            return null;
        }

        return Storage::putFile($targetPath, new File($source));
    }

    private function attachDemoProductsToCategory(Category $category): void
    {
        $productIds = DB::table('products')
            ->whereIn('sku', [
                'ASTGD-SHIRT-RF',
                'ASTGD-SHIRT-001',
                'ASTGD-SHIRT-002',
                'ALIEXPRESS-SHIRT-001',
            ])
            ->pluck('id')
            ->all();

        if (empty($productIds)) {
            throw new \RuntimeException('Unable to resolve sample products for the demo category.');
        }

        $category->products()->syncWithoutDetaching($productIds);
    }

    private function imageFile(string $relativePath): UploadedFile
    {
        if (Str::startsWith($relativePath, ['http://', 'https://'])) {
            $relativePath = $this->downloadRemoteMediaFile($relativePath);
        }

        $path = base_path($relativePath);

        if (! file_exists($path)) {
            $path = Storage::disk('public')->path($relativePath);
        }

        if (! file_exists($path)) {
            throw new \RuntimeException("Sample catalog image is missing: {$relativePath}");
        }

        return new UploadedFile(
            $path,
            basename($path),
            null,
            null,
            true
        );
    }

    private function clearDemoProducts(): void
    {
        $productIds = DB::table('products')
            ->where(function ($query) {
                $query->where('sku', 'like', 'ASTGD-%')
                    ->orWhere('sku', 'like', 'ALIEXPRESS-%');
            })
            ->pluck('id')
            ->all();

        if (empty($productIds)) {
            return;
        }

        DB::table('products')->whereIn('id', $productIds)->delete();
    }

    private function clearDemoCategories(): void
    {
        Category::query()
            ->whereTranslation('slug', 'mens-shirts')
            ->delete();
    }
}
