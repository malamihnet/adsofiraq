<?php

return [

    'max_thumbnail_kb' => env('UPLOAD_MAX_THUMBNAIL', 2048),
    'max_asset_kb' => env('UPLOAD_MAX_ASSET', 5120),
    'max_video_kb' => env('UPLOAD_MAX_VIDEO', 51200),
    'max_videos' => env('UPLOAD_MAX_VIDEOS', 5),
    'allowed_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    'allowed_video_mimes' => ['mp4', 'webm', 'mov'],
    'campaign_path' => 'campaigns',
    'placeholder' => 'placeholders/placeholder-landscape.jpg',
    'placeholder_fallback' => 'placeholders/placeholder-landscape.jpg',
    'thumbnail_width' => 1280,
    'thumbnail_height' => 720,
    'webp_quality' => 82,

    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),

];
