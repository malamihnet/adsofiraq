<?php

namespace App\Services\Import;

use App\Exceptions\CampaignImportException;
use App\Models\Campaign;
use App\Models\User;
use App\Services\CampaignTaxonomySyncService;
use App\Services\CampaignUploadService;
use App\Services\VideoThumbnailService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CampaignImporterService
{
    public function __construct(
        protected CampaignUrlNormalizer $urlNormalizer,
        protected CampaignPageFetcher $fetcher,
        protected CampaignPageParser $parser,
        protected CampaignImportMediaService $mediaService,
        protected CampaignTaxonomySyncService $taxonomySync,
        protected CampaignUploadService $uploadService,
        protected VideoThumbnailService $videoThumbnailService,
        protected CampaignImportSlugService $slugService,
    ) {}

    public function import(string $url, User $admin): Campaign
    {
        $normalizedUrl = $this->urlNormalizer->normalize($url);
        $this->guardDuplicate($normalizedUrl);

        $html = $this->fetcher->fetch($normalizedUrl);
        $parsed = $this->parser->parse($html, $normalizedUrl);

        if (! $this->looksSupported($parsed, $normalizedUrl)) {
            throw CampaignImportException::unsupportedPage();
        }

        return $this->createCampaignFromParsed($parsed, $normalizedUrl, $admin, [
            'status' => 'pending',
            'is_hero' => false,
            'source_batch_id' => null,
        ]);
    }

    public function importBulkCampaign(string $url, User $admin, string $batchId): Campaign
    {
        $normalizedUrl = $this->urlNormalizer->normalize($url);

        $html = $this->fetcher->fetch($normalizedUrl);
        $parsed = $this->parser->parse($html, $normalizedUrl);

        if (! $this->looksSupported($parsed, $normalizedUrl)) {
            throw CampaignImportException::unsupportedPage();
        }

        return $this->createCampaignFromParsed($parsed, $normalizedUrl, $admin, [
            'status' => 'approved',
            'is_hero' => true,
            'source_batch_id' => $batchId,
            'convert_videos' => (bool) config('import.bulk_convert_videos', false),
        ]);
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  array{status: string, is_hero: bool, source_batch_id: ?string, convert_videos?: bool}  $options
     */
    protected function createCampaignFromParsed(
        array $parsed,
        string $sourceUrl,
        User $admin,
        array $options,
    ): Campaign {
        return DB::transaction(function () use ($parsed, $admin, $sourceUrl, $options) {
            $isApproved = ($options['status'] ?? 'pending') === 'approved';
            $importedAt = now()->format('Y-m-d H:i');

            $adminNotes = $isApproved
                ? "Imported from {$sourceUrl} on {$importedAt}"
                : implode("\n\n", array_filter([
                    'Imported from: '.$sourceUrl,
                    'Please verify usage rights before publishing imported media.',
                ]));

            $title = $parsed['title'] ?? 'Imported Campaign';
            $slug = $this->slugService->resolveForImport($title, $sourceUrl);

            $campaign = $this->createImportedCampaign([
                'user_id' => $admin->id,
                'title' => $title,
                'slug' => $slug,
                'description' => $parsed['description'] ?? '',
                'credits' => CampaignCreditsExtractor::sanitizeCredits($parsed['credits'] ?? null),
                'status' => $options['status'] ?? 'pending',
                'published_at' => $isApproved ? now() : null,
                'approved_at' => $isApproved ? now() : null,
                'is_hero' => (bool) ($options['is_hero'] ?? false),
                'source_url' => $sourceUrl,
                'source_batch_id' => $options['source_batch_id'] ?? null,
                'admin_notes' => $adminNotes,
            ], $title, $sourceUrl);

            $this->syncTaxonomies($campaign, $parsed);

            $convertVideos = (bool) ($options['convert_videos'] ?? true);

            $this->mediaService->importVideos(
                $campaign,
                is_array($parsed['videos'] ?? null) ? $parsed['videos'] : [],
                is_array($parsed['direct_video_urls'] ?? null) ? $parsed['direct_video_urls'] : [],
                convertVideos: $convertVideos,
            );

            $assets = $this->mediaService->importStills(
                $campaign,
                is_array($parsed['image_urls'] ?? null) ? $parsed['image_urls'] : [],
            );

            $thumbnailPath = $this->mediaService->downloadThumbnail(
                $campaign,
                $parsed['og_image'] ?? null,
            );

            if ($thumbnailPath) {
                $campaign->update(['thumbnail_path' => $thumbnailPath]);
            }

            $campaign = $campaign->fresh(['videos', 'assets']);

            $this->uploadService->resolveThumbnail($campaign, false, $campaign->assets->first());

            if (! $this->uploadService->hasValidThumbnail($campaign->fresh())) {
                try {
                    $this->videoThumbnailService->applyFallbackIfNeeded($campaign->fresh(), false);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return $campaign->fresh([
                'brands', 'agencies', 'industries', 'mediumTypes', 'countries', 'videos', 'assets',
            ]);
        });
    }

    public function findDuplicate(string $url): ?Campaign
    {
        $normalized = $this->urlNormalizer->normalize($url);

        return Campaign::query()
            ->where(function ($query) use ($normalized, $url) {
                $query->where('source_url', $normalized)
                    ->orWhere('source_url', $url);
            })
            ->first();
    }

    public function findBySlug(string $slug): ?Campaign
    {
        $slug = Str::slug($slug);

        if ($slug === '') {
            return null;
        }

        return Campaign::withTrashed()->where('slug', $slug)->first();
    }

    protected function guardDuplicate(string $normalizedUrl): void
    {
        if ($this->findDuplicate($normalizedUrl)) {
            throw CampaignImportException::alreadyImported();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createImportedCampaign(array $attributes, string $title, string $sourceUrl): Campaign
    {
        try {
            return Campaign::create($attributes);
        } catch (QueryException $e) {
            if (! $this->isSlugUniqueViolation($e)) {
                throw $e;
            }

            $attributes['slug'] = $this->slugService->resolveForImport(
                $title.'-'.now()->timestamp,
                $sourceUrl
            );

            Log::warning('Campaign import: slug unique constraint hit, retrying with new slug.', [
                'source_url' => $sourceUrl,
                'slug' => $attributes['slug'],
            ]);

            return Campaign::create($attributes);
        }
    }

    protected function isSlugUniqueViolation(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'campaigns_slug_unique')
            || (str_contains($message, 'duplicate entry') && str_contains($message, 'slug'));
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    protected function looksSupported(array $parsed, string $url): bool
    {
        if (str_contains(strtolower($url), 'adsoftheworld.com/campaigns')) {
            return true;
        }

        return ($parsed['title'] ?? '') !== ''
            && (
                ! empty($parsed['description'])
                || ! empty($parsed['videos'])
                || ! empty($parsed['og_image'])
            );
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    protected function syncTaxonomies(Campaign $campaign, array $parsed): void
    {
        $map = fn (array $names) => array_map(
            fn (string $name) => 'new:'.trim($name),
            array_filter($names)
        );

        $this->taxonomySync->syncAll(
            $campaign,
            agencies: $map($parsed['agencies'] ?? []),
            brands: $map($parsed['brands'] ?? []),
            industries: $map($parsed['industries'] ?? []),
            mediumTypes: $map($parsed['medium_types'] ?? []),
            countries: $map($parsed['countries'] ?? []),
        );
    }
}
