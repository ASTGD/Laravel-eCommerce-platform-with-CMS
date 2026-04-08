<?php

declare(strict_types=1);

return [
    'brand_name' => env('PLATFORM_BRAND_NAME', env('APP_NAME', 'Commerce Platform')),
    'locale' => env('PLATFORM_LOCALE', 'en_US'),
    'currency' => env('PLATFORM_CURRENCY', 'USD'),
    'homepage_slug' => env('PLATFORM_HOMEPAGE_SLUG', 'home'),
    'admin' => [
        'email' => env('PLATFORM_ADMIN_EMAIL', 'admin@example.test'),
        'password' => env('PLATFORM_ADMIN_PASSWORD', 'password'),
    ],
];
