<?php

return [
    [
        'key'   => 'cms.platform',
        'name'  => 'Platform CMS',
        'route' => 'admin.cms.pages.index',
        'sort'  => 5,
    ], [
        'key'   => 'cms.platform.pages',
        'name'  => 'Pages',
        'route' => 'admin.cms.pages.index',
        'sort'  => 1,
    ], [
        'key'   => 'cms.platform.pages.create',
        'name'  => 'Create',
        'route' => 'admin.cms.pages.store',
        'sort'  => 1,
    ], [
        'key'   => 'cms.platform.pages.edit',
        'name'  => 'Edit',
        'route' => 'admin.cms.pages.update',
        'sort'  => 2,
    ], [
        'key'   => 'cms.platform.pages.publish',
        'name'  => 'Publish',
        'route' => 'admin.cms.pages.publish',
        'sort'  => 3,
    ], [
        'key'   => 'cms.platform.templates',
        'name'  => 'Templates',
        'route' => 'admin.cms.templates.index',
        'sort'  => 2,
    ], [
        'key'   => 'cms.platform.section_types',
        'name'  => 'Section Types',
        'route' => 'admin.cms.section-types.index',
        'sort'  => 3,
    ], [
        'key'   => 'cms.platform.menus',
        'name'  => 'Menus',
        'route' => 'admin.cms.menus.index',
        'sort'  => 4,
    ], [
        'key'   => 'cms.platform.header_configs',
        'name'  => 'Header',
        'route' => 'admin.cms.header-configs.index',
        'sort'  => 5,
    ], [
        'key'   => 'cms.platform.footer_configs',
        'name'  => 'Footer',
        'route' => 'admin.cms.footer-configs.index',
        'sort'  => 6,
    ],
];
