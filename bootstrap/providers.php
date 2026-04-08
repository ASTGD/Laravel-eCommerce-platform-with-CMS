<?php

use App\Providers\AppServiceProvider;
use CommerceCore\Providers\CommerceCoreServiceProvider;
use ExperienceCms\Providers\ExperienceCmsServiceProvider;
use MediaTools\Providers\MediaToolsServiceProvider;
use PlatformSupport\Providers\PlatformSupportServiceProvider;
use SeoTools\Providers\SeoToolsServiceProvider;
use ThemeCore\Providers\ThemeCoreServiceProvider;
use ThemeDefault\Providers\ThemeDefaultServiceProvider;

return [
    AppServiceProvider::class,
    PlatformSupportServiceProvider::class,
    SeoToolsServiceProvider::class,
    MediaToolsServiceProvider::class,
    CommerceCoreServiceProvider::class,
    ThemeCoreServiceProvider::class,
    ThemeDefaultServiceProvider::class,
    ExperienceCmsServiceProvider::class,
];
