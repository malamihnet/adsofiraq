<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignVideo;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CampaignVideoService
{
    protected const VIDEOS_DIR = 'campaigns/videos';

    public function __construct(
        protected CampaignUploadService $uploadService,
    ) {}

  /**
     * Sync campaign videos from the request videos[] array.
     */
    public function syncFromRequest(Campaign $campaign, Request $request): void
    {
        $rows = collect($request->input('videos', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['type']))
            ->values();

        if ($rows->isEmpty()) {
            $this->deleteAllVideos($campaign);

            return;
        }

        $keptIds = [];
        $sortOrder = 0;

        foreach ($rows as $index => $row) {
            $sortOrder++;
            $existingId = $row['id'] ?? null;
            $existing = $existingId
                ? CampaignVideo::query()->where('campaign_id', $campaign->id)->find($existingId)
                : null;

            if ($existing) {
                $this->updateVideo($campaign, $existing, $row, $request, (int) $index, $sortOrder);
                $keptIds[] = $existing->id;

                continue;
            }

            $created = $this->createVideo($campaign, $row, $request, (int) $index, $sortOrder);

            if ($created) {
                $keptIds[] = $created->id;
            }
        }

        $removed = CampaignVideo::query()
            ->where('campaign_id', $campaign->id)
            ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))
            ->get();

        foreach ($removed as $video) {
            $this->deleteVideoRecord($video);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function createVideo(Campaign $campaign, array $row, Request $request, int $index, int $sortOrder): ?CampaignVideo
    {
        $type = $row['type'];

        $data = [
            'campaign_id' => $campaign->id,
            'type' => $type,
            'title' => $this->nullableString($row['title'] ?? null),
            'sort_order' => $sortOrder,
            'url' => null,
            'file_path' => null,
        ];

        if ($type === 'file') {
            $file = $request->file("videos.{$index}.file");

            if (! $file instanceof UploadedFile) {
                return null;
            }

            $data['file_path'] = $this->uploadService->storeVideoFile($campaign, $file);
        } else {
            $data['url'] = trim((string) ($row['url'] ?? ''));
        }

        return CampaignVideo::create($data);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function updateVideo(
        Campaign $campaign,
        CampaignVideo $video,
        array $row,
        Request $request,
        int $index,
        int $sortOrder,
    ): void {
        $type = $row['type'];

        $updates = [
            'type' => $type,
            'title' => $this->nullableString($row['title'] ?? null),
            'sort_order' => $sortOrder,
        ];

        if ($type === 'file') {
            $updates['url'] = null;
            $file = $request->file("videos.{$index}.file");

            if ($file instanceof UploadedFile) {
                $this->deleteStoredVideoFile($video->file_path);
                $updates['file_path'] = $this->uploadService->storeVideoFile($campaign, $file);
            } elseif ($video->type !== 'file') {
                $updates['file_path'] = null;
            }
        } else {
            if ($video->file_path) {
                $this->deleteStoredVideoFile($video->file_path);
            }

            $updates['url'] = trim((string) ($row['url'] ?? ''));
            $updates['file_path'] = null;
        }

        $video->update($updates);
    }

    public function deleteAllVideos(Campaign $campaign): void
    {
        $campaign->videos()->get()->each(fn (CampaignVideo $video) => $this->deleteVideoRecord($video));
    }

    public function deleteVideoRecord(CampaignVideo $video): void
    {
        $this->deleteStoredVideoFile($video->file_path);
        $video->delete();
    }

    public function deleteStoredVideoFile(?string $path): void
    {
        if ($path === null || trim($path) === '') {
            return;
        }

        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');

        if ($normalized === '' || str_contains($normalized, '..') || ! str_starts_with($normalized, self::VIDEOS_DIR.'/')) {
            return;
        }

        if (Storage::disk('public')->exists($normalized)) {
            Storage::disk('public')->delete($normalized);
        }
    }

    /**
     * @return list<array{label: string, type: string, url: string|null, file_url: string|null, title: string|null}>
     */
    public function listForAdmin(Campaign $campaign): array
    {
        return $campaign->resolvedVideos()
            ->map(fn (CampaignVideo $video) => [
                'label' => match ($video->type) {
                    'file' => 'Uploaded file',
                    'youtube' => 'YouTube',
                    'vimeo' => 'Vimeo',
                    default => ucfirst($video->type),
                },
                'type' => $video->type,
                'url' => $video->url,
                'file_url' => $video->file_url,
                'title' => $video->title,
            ])
            ->values()
            ->all();
    }

    protected function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
