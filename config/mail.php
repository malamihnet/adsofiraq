<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | Ads of Iraq uses Resend API delivery. Set MAIL_MAILER=resend in .env.
    | On Resend API failure, the failover mailer logs the message locally.
    |
    */

    'default' => env('MAIL_MAILER', 'resend'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    */

    'mailers' => [

        'resend' => [
            'transport' => 'resend',
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'resend',
                'log',
            ],
            'retry_after' => (int) env('MAIL_FAILOVER_RETRY_AFTER', 60),
        ],

        'array' => [
            'transport' => 'array',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'verify@adsofiraq.com'),
        'name' => env('MAIL_FROM_NAME', 'Ads of Iraq'),
    ],

    'markdown' => [
        'theme' => env('MAIL_MARKDOWN_THEME', 'default'),

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];
