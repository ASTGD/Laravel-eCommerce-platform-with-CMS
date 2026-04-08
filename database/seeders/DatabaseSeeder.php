<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\ExperienceCms\Database\Seeders\ExperienceCmsSeeder;
use Platform\ThemeCore\Database\Seeders\ThemeCoreSeeder;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as BagistoDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            BagistoDatabaseSeeder::class,
            ThemeCoreSeeder::class,
            ExperienceCmsSeeder::class,
        ]);
    }
}
