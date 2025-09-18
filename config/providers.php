<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    | The default provider to use when none is specified
    */
    'default' => env('DEFAULT_PROVIDER', 'manual'),

    /*
    |--------------------------------------------------------------------------
    | Provider Configurations
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'manual' => [
            'name' => 'Manual Provision',
            'enabled' => true,
            'config' => []
        ],

        'cpanel' => [
            'name' => 'cPanel/WHM',
            'enabled' => env('CPANEL_ENABLED', false),
            'config' => [
                'host' => env('CPANEL_HOST'),
                'username' => env('CPANEL_USERNAME'),
                'password' => env('CPANEL_PASSWORD'),
                'port' => env('CPANEL_PORT', 2087),
                'ssl' => env('CPANEL_SSL', true),
                'timeout' => env('CPANEL_TIMEOUT', 30),
            ]
        ],

        'cloudflare' => [
            'name' => 'Cloudflare',
            'enabled' => env('CLOUDFLARE_ENABLED', false),
            'config' => [
                'api_token' => env('CLOUDFLARE_API_TOKEN'),
                'email' => env('CLOUDFLARE_EMAIL'),
                'zone_id' => env('CLOUDFLARE_ZONE_ID'),
                'timeout' => env('CLOUDFLARE_TIMEOUT', 30),
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Provision Settings
    |--------------------------------------------------------------------------
    */
    'settings' => [
        'auto_provision' => env('AUTO_PROVISION', false),
        'retry_failed' => env('RETRY_FAILED_PROVISION', true),
        'max_retries' => env('MAX_PROVISION_RETRIES', 3),
        'retry_delay' => env('PROVISION_RETRY_DELAY', 300), // seconds
        'timeout' => env('PROVISION_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'provision',
        'level' => 'info',
    ]
];