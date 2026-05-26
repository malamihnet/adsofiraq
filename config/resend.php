<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Resend API Key
    |--------------------------------------------------------------------------
    |
    | Used by resend/resend-laravel. Set RESEND_KEY in .env (Resend dashboard).
    |
    */

    'api_key' => env('RESEND_KEY'),

    'domain' => env('RESEND_DOMAIN'),

    'path' => env('RESEND_PATH', 'resend'),

    'webhook' => [
        'secret' => env('RESEND_WEBHOOK_SECRET'),
        'tolerance' => env('RESEND_WEBHOOK_TOLERANCE', 300),
    ],

];
