<?php

return [
    [
        'key'   => 'theme',
        'name'  => 'Theme',
        'route' => 'admin.theme.presets.index',
        'sort'  => 7,
    ], [
        'key'   => 'theme.presets',
        'name'  => 'Theme Presets',
        'route' => 'admin.theme.presets.index',
        'sort'  => 1,
    ], [
        'key'   => 'theme.presets.create',
        'name'  => 'Create',
        'route' => 'admin.theme.presets.store',
        'sort'  => 1,
    ], [
        'key'   => 'theme.presets.edit',
        'name'  => 'Edit',
        'route' => 'admin.theme.presets.update',
        'sort'  => 2,
    ], [
        'key'   => 'theme.presets.delete',
        'name'  => 'Delete',
        'route' => 'admin.theme.presets.destroy',
        'sort'  => 3,
    ],
];
