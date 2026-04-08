<?php

declare(strict_types=1);

namespace Database\Seeders;

use ExperienceCms\Database\Seeders\ExperienceCmsSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use PlatformSupport\Database\Seeders\PlatformSupportSeeder;
use ThemeDefault\Database\Seeders\ThemeDefaultSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PlatformSupportSeeder::class,
            ThemeDefaultSeeder::class,
            ExperienceCmsSeeder::class,
        ]);
    }
}
