<?php

return [
    'models' => [
        'auto_discover' => true,
        'paths' => [
            app_path('Models'),
        ],
        'list' => [
            // App\Models\Product::class,
        ],
        'namespace_map' => [
            // 'product' => App\Models\Product::class,
        ],
    ],

    'locales' => ['en', 'fr', 'de', 'es'],

    'default_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'ignore_groups' => [
        'passwords',
        'validation',
        'auth',
    ],

    'export_path' => resource_path('lang'),

    'export' => [
        'overwrite' => true,
        'pretty_print' => true,
        'sort_keys' => true,
    ],

    'sync' => [
        'stages' => [
            'export_models' => true,
            'larex_export' => true,
            'larex_import' => true,
            'import_models' => true,
            'export_files' => true,
        ],
    ],
];
