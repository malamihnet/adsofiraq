<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#000000">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#000000">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-favicon />

    <title>@yield('title', config('seo.site_name', 'Ads Of Iraq'))</title>
    <meta name="description" content="@yield('meta_description', 'Explore Iraqi advertising campaigns, agencies, production houses, creative professionals, and brands on Ads Of Iraq.')">

    <x-seo-head
        :canonical="$seoCanonical ?? null"
        :noindex="$seoNoindex ?? false"
    />

    @stack('meta')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">

    @stack('head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-black font-sans text-archive-black antialiased">
    @include('components.header')

    @include('components.unverified-email-banner')

    @if(session('success'))
        <div class="border-b border-green-200 bg-green-50 px-4 py-3 text-center text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="border-b border-red-200 bg-red-50 px-4 py-3 text-center text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <main class="relative z-0 bg-white">
        @yield('content')
    </main>

    @include('components.footer')

    @stack('modals')
    @stack('scripts')
</body>
</html>
