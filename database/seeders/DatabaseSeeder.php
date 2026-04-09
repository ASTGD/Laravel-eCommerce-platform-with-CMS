<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        if (! Schema::hasTable('channels') || DB::table('channels')->count() === 0) {
            $this->call(BagistoDatabaseSeeder::class);
        }

        $this->call([
            ThemeCoreSeeder::class,
            ExperienceCmsSeeder::class,
        ]);
    }
}
