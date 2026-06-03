<?php

return [
    'site_name' => 'Ads Of Iraq',
    'site_tagline' => 'Iraqi Advertising Archive',
    'default_og_image' => '/favicon-96x96.png',
    'twitter_site' => '@adsofiraq',

    'arabic_keywords' => [
        'global' => 'اعلانات العراق، الأرشيف الإعلاني العراقي',
        'campaigns' => 'الحملات الإعلانية العراقية',
        'agencies' => 'وكالات الإعلان في العراق',
        'production_houses' => 'شركات الإنتاج في العراق',
        'brands' => 'العلامات التجارية العراقية',
        'people' => 'المبدعون في الإعلان العراقي',
        'categories' => 'حملات إعلانية حسب التصنيف',
    ],

    'listing_noindex_params' => [
        'page',
        'sort',
        'search',
        'medium',
        'country',
        'year',
        'brand',
        'agency',
        'industry',
        'q',
    ],

    'static_pages' => [
        ['route' => 'home', 'priority' => '1.0', 'changefreq' => 'daily'],
        ['route' => 'campaigns.index', 'priority' => '0.9', 'changefreq' => 'daily'],
        ['route' => 'agencies.index', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['route' => 'brands.index', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['route' => 'people.index', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['route' => 'rankings.index', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['route' => 'rankings.top-agencies', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['route' => 'rankings.top-production-houses', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['route' => 'rankings.most-viewed', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['route' => 'rankings.trending', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['route' => 'rankings.most-appreciated', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['route' => 'made-by-iraq.index', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['route' => 'featured.index', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['route' => 'awards.index', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ['route' => 'pages.about', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['route' => 'pages.contact', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['route' => 'pages.help', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['route' => 'pages.submit-advertise', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['route' => 'pages.terms-policies', 'priority' => '0.4', 'changefreq' => 'yearly'],
        ['route' => 'pages.editorial-standards', 'priority' => '0.5', 'changefreq' => 'yearly'],
    ],

    'sitemaps' => [
        'campaigns' => 'sitemap-campaigns.xml',
        'agencies' => 'sitemap-agencies.xml',
        'production_houses' => 'sitemap-production-houses.xml',
        'brands' => 'sitemap-brands.xml',
        'people' => 'sitemap-people.xml',
        'categories' => 'sitemap-categories.xml',
        'pages' => 'sitemap-pages.xml',
    ],
];
