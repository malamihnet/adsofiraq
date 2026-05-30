<?php

return [
    'editorial_labels' => [
        'editors_featured' => 'Featured by Editors',
        'staff_pick' => 'Staff Pick',
        'worth_watching' => 'Worth Watching',
        'new_talent' => 'New Iraqi Talent',
    ],

    'ranking' => [
        'weights' => [
            'views' => 1.0,
            'bookmarks' => 5.0,
            'watchers' => 2.0,
            'featured' => 50.0,
            'hero' => 75.0,
            'editorial' => 35.0,
            'verified' => 15.0,
        ],
        'recency_half_life_days' => 180,
    ],

    'company_ranking' => [
        'cache_ttl_seconds' => 3600,
        'recent_months' => 12,
        'profiles' => [
            'production_house' => [
                'campaign_count' => 10,
                'views' => 0.02,
                'bookmarks' => 3,
                'featured_campaign' => 15,
                'verified_bonus' => 25,
                'recent_activity_bonus' => 15,
                'recent_campaign_bonus' => 3,
                'recent_campaign_bonus_cap' => 5,
            ],
            'agency' => [
                'campaign_count' => 10,
                'views' => 0.02,
                'bookmarks' => 3,
                'featured_campaign' => 15,
                'verified_bonus' => 25,
                'recent_activity_bonus' => 15,
                'recent_campaign_bonus' => 3,
                'recent_campaign_bonus_cap' => 5,
            ],
        ],
    ],
];
