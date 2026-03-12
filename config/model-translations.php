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
        'stop_on_error' => false,
        'pipeline' => [
            [
                'command' => 'translations:export-models',
                'enabled' => true,
            ],
            [
                'command' => 'translations:vendor-export',
                'enabled' => false,
            ],
            [
                'command' => 'translations:vendor-import',
                'enabled' => false,
            ],
            [
                'command' => 'translations:import-models',
                'enabled' => true,
            ],
            [
                'command' => 'translations:export-files',
                'enabled' => true,
            ],
        ],
    ],

    'translatable_migration' => [
        'paths' => [
            app_path('Models'),
        ],
        'output_path' => database_path('migrations'),
        'default_locale' => env('APP_FALLBACK_LOCALE', 'en'),
        'chunk' => 500,
    ],
];
