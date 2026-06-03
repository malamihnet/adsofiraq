@props([
    'canonical' => null,
    'noindex' => false,
])

@php
    $defaultOg = app(\App\Services\SeoService::class)->defaultOgImageUrl();
@endphp

@if($noindex)
    <meta name="robots" content="noindex, follow">
@endif

@if($canonical)
    <link rel="canonical" href="{{ $canonical }}">
@endif

@hasSection('og_image')
    @php($resolvedOgImage = trim($__env->yieldContent('og_image')))
@else
    @php($resolvedOgImage = $defaultOg)
@endif

@php
    $pageTitle = trim($__env->yieldContent('title') ?: config('seo.site_name', 'Ads Of Iraq'));
    $pageDescription = trim($__env->yieldContent('meta_description') ?: 'Explore Iraqi advertising campaigns on Ads Of Iraq.');
    $ogType = trim($__env->yieldContent('og_type') ?: 'website');
@endphp

<meta property="og:site_name" content="{{ config('seo.site_name', 'Ads Of Iraq') }}">
<meta property="og:locale" content="en_US">
<meta property="og:image" content="{{ $resolvedOgImage }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ url()->current() }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
<meta name="twitter:image" content="{{ $resolvedOgImage }}">
@if(config('seo.twitter_site'))
    <meta name="twitter:site" content="{{ config('seo.twitter_site') }}">
@endif
