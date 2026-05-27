<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public storage copy sync (cPanel / public_html)
    |--------------------------------------------------------------------------
    |
    | When set, campaign media saved to storage/app/public is also copied here
    | so URLs like /storage/campaigns/{id}/... work without a symlink.
    | Example: /home/adsofiraq/public_html/storage
    |
    */
    'public_sync_path' => env('PUBLIC_STORAGE_SYNC_PATH'),

];
