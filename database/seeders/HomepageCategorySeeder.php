<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\Category;

class HomepageCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Personalized Picks',
                'slug' => 'personalized-picks',
                'description' => 'Curated products selected just for you.',
            ],
            [
                'name' => 'New Arrivals',
                'slug' => 'new-arrivals',
                'description' => 'The latest additions to our store.',
            ],
            [
                'name' => 'Featured Picks',
                'slug' => 'featured-picks',
                'description' => 'Our hand-picked featured products.',
            ],
            [
                'name' => 'Limited Sale',
                'slug' => 'limited-sale',
                'description' => 'Exclusive deals available for a limited time.',
            ],
        ];

        foreach ($categories as $index => $data) {
            $this->ensureCategory($data, $index + 10); // Start from position 10 to avoid overlap
        }
    }

    /**
     * Ensure a category exists with the given data.
     */
    private function ensureCategory(array $data, int $position): void
    {
        $existing = DB::table('category_translations')
            ->where('slug', $data['slug'])
            ->first();

        if ($existing) {
            return;
        }

        $translations = [];
        foreach (core()->getAllLocales() as $locale) {
            $translations[$locale->code] = [
                'name'             => $data['name'],
                'slug'             => $data['slug'],
                'description'      => $data['description'],
                'meta_title'       => $data['name'],
                'meta_description' => $data['description'],
                'meta_keywords'    => strtolower($data['name']),
            ];
        }

        Category::query()->create(array_merge([
            'position'     => $position,
            'status'       => 1,
            'display_mode' => 'products_and_description',
            'parent_id'    => 1, // Default root category
        ], $translations));
    }
}
