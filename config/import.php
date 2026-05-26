<?php

return [

    'timeout' => (int) env('IMPORT_HTTP_TIMEOUT', 30),

    'country_page_timeout' => (int) env('IMPORT_COUNTRY_PAGE_TIMEOUT', 15),

    'country_page_retries' => (int) env('IMPORT_COUNTRY_PAGE_RETRIES', 3),

    'max_image_bytes' => (int) env('IMPORT_MAX_IMAGE_MB', 10) * 1024 * 1024,

    'max_video_bytes' => (int) env('IMPORT_MAX_VIDEO_MB', 200) * 1024 * 1024,

    'user_agent' => env('IMPORT_USER_AGENT', 'AdsOfIraqImporter/1.0 (compatible; Laravel)'),

    'allowed_image_mimes' => [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/gif',
    ],

    'allowed_video_mimes' => [
        'video/mp4',
        'video/webm',
        'video/quicktime',
    ],

    'image_max_width' => (int) env('IMPORT_IMAGE_MAX_WIDTH', 2560),

    'webp_quality' => (int) env('IMPORT_WEBP_QUALITY', 82),

    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),

    'ffmpeg_timeout' => (int) env('IMPORT_FFMPEG_TIMEOUT', 120),

    'video_webm_crf' => (int) env('IMPORT_VIDEO_WEBM_CRF', 32),

    'max_country_pages' => (int) env('IMPORT_MAX_COUNTRY_PAGES', 500),

    'bulk_items_per_request' => 1,

    'bulk_process_timeout' => (int) env('IMPORT_BULK_PROCESS_TIMEOUT', 120),

    'bulk_process_delay_ms' => (int) env('BULK_IMPORT_PROCESS_DELAY_MS', 3000),

    'bulk_process_delay_max_ms' => (int) env('BULK_IMPORT_PROCESS_DELAY_MAX_MS', 5000),

    'bulk_stuck_item_minutes' => (int) env('IMPORT_BULK_STUCK_ITEM_MINUTES', 3),

    'bulk_convert_videos' => filter_var(env('BULK_IMPORT_CONVERT_VIDEOS', false), FILTER_VALIDATE_BOOL),

    'new_import_stop_after_existing' => (int) env('NEW_IMPORT_STOP_AFTER_EXISTING', 20),

];
