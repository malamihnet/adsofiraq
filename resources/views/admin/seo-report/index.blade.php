@extends('layouts.admin')

@section('title', 'SEO Report | Admin')

@section('content')
<div class="mb-8">
    <h1 class="section-title">SEO Implementation Report</h1>
    <p class="mt-2 max-w-3xl text-sm text-archive-gray">
        Reference for Search Console validation, indexing coverage, and structured data checks on Ads Of Iraq.
    </p>
</div>

<div class="grid gap-8 lg:grid-cols-2">
    <section class="border border-archive-border p-6">
        <h2 class="section-label mb-4">Sitemap URLs</h2>
        <ul class="space-y-2 text-sm">
            <li><a href="{{ url('/sitemap.xml') }}" class="underline" target="_blank" rel="noopener">/sitemap.xml</a> (index)</li>
            @foreach($sitemapUrls as $url)
                <li><a href="{{ $url }}" class="underline" target="_blank" rel="noopener">{{ parse_url($url, PHP_URL_PATH) }}</a></li>
            @endforeach
        </ul>
        <p class="mt-4 text-xs text-archive-gray">Submit the sitemap index URL in Google Search Console. Child sitemaps refresh on each request.</p>
    </section>

    <section class="border border-archive-border p-6">
        <h2 class="section-label mb-4">Structured data (JSON-LD)</h2>
        <ul class="list-inside list-disc space-y-1 text-sm">
            @foreach($structuredData as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </section>

    <section class="border border-archive-border p-6">
        <h2 class="section-label mb-4">Title formulas</h2>
        <dl class="space-y-3 text-sm">
            @foreach($titleFormulas as $label => $formula)
                <div>
                    <dt class="font-medium text-archive-black">{{ $label }}</dt>
                    <dd class="text-archive-gray">{{ $formula }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="border border-archive-border p-6">
        <h2 class="section-label mb-4">Noindex coverage</h2>
        <ul class="list-inside list-disc space-y-1 text-sm text-archive-gray">
            @foreach($noindexRoutes as $route)
                <li>{{ $route }}</li>
            @endforeach
        </ul>
    </section>

    <section class="border border-archive-border p-6 lg:col-span-2">
        <h2 class="section-label mb-4">Arabic keyword strategy (hidden)</h2>
        <p class="mb-4 text-sm text-archive-gray">
            Arabic terms are appended only in meta descriptions and JSON-LD. They are not shown as visible blocks on pages.
        </p>
        <dl class="grid gap-4 sm:grid-cols-2 text-sm">
            @foreach($arabicContexts as $key => $phrase)
                <div class="border border-archive-border p-3">
                    <dt class="font-medium uppercase tracking-wider text-xs text-archive-gray">{{ $key }}</dt>
                    <dd class="mt-1" dir="rtl">{{ $phrase }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="border border-archive-border p-6">
        <h2 class="section-label mb-4">Favicon URLs</h2>
        <ul class="space-y-2 text-sm">
            @foreach($faviconUrls as $url)
                <li><a href="{{ $url }}" class="underline" target="_blank" rel="noopener">{{ parse_url($url, PHP_URL_PATH) }}</a></li>
            @endforeach
        </ul>
        <p class="mt-4 text-xs text-archive-gray">Google uses a square favicon (48px+). Organization logo schema points to 512x512 PNG.</p>
    </section>

    <section class="border border-archive-border p-6 lg:col-span-2">
        <h2 class="section-label mb-4">Authority & SEO expansion</h2>
        <ul class="list-inside list-disc space-y-1 text-sm text-archive-gray">
            @foreach($authorityFeatures as $feature)
                <li>{{ $feature }}</li>
            @endforeach
        </ul>
        <p class="mt-4 text-xs text-archive-gray">Backfill tags for existing campaigns: <code class="text-archive-black">php artisan campaigns:sync-tags</code></p>
    </section>

    <section class="border border-archive-border p-6 lg:col-span-2">
        <h2 class="section-label mb-4">Google Search Console checklist</h2>
        <ol class="list-inside list-decimal space-y-2 text-sm text-archive-gray">
            @foreach($googleSearchConsoleChecklist as $step)
                <li>{{ $step }}</li>
            @endforeach
        </ol>
    </section>
</div>
@endsection
