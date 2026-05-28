@extends('layouts.admin')

@section('title', 'Clean Duplicate Media — Admin')

@section('content')
<h1 class="section-title mb-2">Maintenance</h1>
<p class="mb-8 text-sm text-archive-gray">Clean duplicate imported campaign media before any full re-import.</p>

<div class="max-w-2xl border border-archive-border bg-white p-6">
    <h2 class="font-display text-lg">Clean Duplicate Media</h2>
    <p class="mt-2 text-sm text-archive-gray">
        Removes duplicate stills (same visual / source URL — prefers original jpg/png, smaller file, lowest sort order),
        duplicate videos (same YouTube/Vimeo ID or file hash), and still assets that duplicate the campaign thumbnail.
        Rebuilds <code class="text-xs">sort_order</code> after cleanup.
    </p>

    <form method="POST" action="{{ route('admin.maintenance.clean-duplicate-media.run') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="campaign_id" class="section-label mb-2 block">Single campaign ID (optional)</label>
            <input type="number" name="campaign_id" id="campaign_id" min="1" class="input-field" placeholder="Leave empty to scan all campaigns">
        </div>
        <div>
            <label for="limit" class="section-label mb-2 block">Limit (optional)</label>
            <input type="number" name="limit" id="limit" min="1" class="input-field" placeholder="No limit when scanning all">
        </div>
        <input type="hidden" name="dry_run" value="0">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="dry_run" value="1" class="rounded border-archive-border" checked>
            Dry run (report only — no deletes)
        </label>
        <input type="hidden" name="delete_files" value="0">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="delete_files" value="1" class="rounded border-archive-border" checked>
            Delete duplicate physical files from storage (when not dry run)
        </label>
        <button type="submit" class="btn-secondary">Run Cleanup</button>
    </form>
</div>

<div class="mt-6 max-w-2xl border border-archive-border bg-white p-6">
    <h2 class="font-display text-lg">Clean Non-Gallery Stills</h2>
    <p class="mt-2 text-sm text-archive-gray">
        Re-fetches each campaign&apos;s Ads of the World page and removes imported stills that are not real uploaded gallery images
        (e.g. og:image, JSON-LD thumbnails, video posters, related campaign cards, or legacy broad URL scraping).
        Thumbnails stay on the campaign card only.
    </p>

    <form method="POST" action="{{ route('admin.maintenance.clean-non-gallery-stills') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="ng_campaign_id" class="section-label mb-2 block">Single campaign ID (optional)</label>
            <input type="number" name="campaign_id" id="ng_campaign_id" min="1" class="input-field" placeholder="Leave empty to scan imported campaigns">
        </div>
        <div>
            <label for="ng_limit" class="section-label mb-2 block">Limit (optional)</label>
            <input type="number" name="limit" id="ng_limit" min="1" class="input-field" placeholder="No limit when scanning all">
        </div>
        <input type="hidden" name="dry_run" value="0">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="dry_run" value="1" class="rounded border-archive-border" checked>
            Dry run (report only)
        </label>
        <input type="hidden" name="delete_files" value="0">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="delete_files" value="1" class="rounded border-archive-border" checked>
            Delete removed still files from storage (when not dry run)
        </label>
        <button type="submit" class="btn-secondary">Clean Non-Gallery Stills</button>
    </form>
</div>

<div class="mt-6 max-w-2xl border border-archive-border bg-white p-6">
    <h2 class="font-display text-lg">Orphan Media Files</h2>
    <p class="mt-2 text-sm text-archive-gray">
        Scan <code class="text-xs">storage/app/public/campaigns</code> for files not referenced by any campaign asset, thumbnail, or video.
    </p>

    <form method="POST" action="{{ route('admin.maintenance.cleanup-orphans') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="orphan_campaign_id" class="section-label mb-2 block">Single campaign folder ID (optional)</label>
            <input type="number" name="campaign_id" id="orphan_campaign_id" min="1" class="input-field" placeholder="Leave empty to scan all campaign folders">
        </div>
        <input type="hidden" name="dry_run" value="0">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="dry_run" value="1" class="rounded border-archive-border" checked>
            Dry run (list orphans only)
        </label>
        <button type="submit" class="btn-secondary">Scan / Clean Orphans</button>
    </form>
</div>

<div class="mt-6 max-w-2xl border-2 border-red-600 bg-red-50 p-6">
    <h2 class="font-display text-lg text-red-900">Reset All Campaigns</h2>
    <p class="mt-2 text-sm text-red-950">
        Wipe every campaign and all campaign media to start a fresh Iraq re-import. Includes dry run, confirmation phrase, and chunked progress.
    </p>
    <a href="{{ route('admin.maintenance.reset-all-campaigns') }}" class="btn-secondary mt-4 inline-block border-red-700 text-red-900">
        Open Reset Tool
    </a>
</div>

<div class="mt-6 max-w-2xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-950">
    <h2 class="font-display text-lg">CLI commands</h2>
    <ul class="mt-3 list-inside list-disc space-y-1 font-mono text-xs">
        <li>php artisan campaigns:clean-duplicate-media --dry-run --all</li>
        <li>php artisan campaigns:clean-duplicate-media --all</li>
        <li>php artisan campaigns:clean-non-gallery-stills --dry-run</li>
        <li>php artisan campaigns:cleanup-orphan-media --dry-run</li>
        <li>php artisan campaigns:reset-imported-iraq --dry-run</li>
    </ul>
    <p class="mt-3">Full Iraq re-import reset is prepared but disabled until cleanup passes. Use <code class="text-xs">--execute</code> only when intentional.</p>
</div>
@endsection
