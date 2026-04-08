<?php

declare(strict_types=1);

namespace PlatformSupport\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use PlatformSupport\Enums\UserRole;

class PlatformSupportSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => (string) config('platform.admin.email')],
            [
                'name' => 'Platform Admin',
                'role' => UserRole::SuperAdmin->value,
                'password' => (string) config('platform.admin.password'),
            ],
        );
    }
}
