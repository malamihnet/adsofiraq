<?php

namespace App\Services;

use App\Models\CampaignRevision;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class CampaignRevisionUploadService
{
    public function storeThumbnail(CampaignRevision $revision, UploadedFile $file): string
    {
        return $file->store('campaign-revisions/'.$revision->id.'/thumbnails', 'public');
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return list<string> Stored paths in upload order.
     */
    public function storeAssets(CampaignRevision $revision, array $files): array
    {
        $paths = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $paths[] = $file->store('campaign-revisions/'.$revision->id.'/assets', 'public');
        }

        return $paths;
    }

    public function storeVideoFile(CampaignRevision $revision, UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $filename = sprintf('revision-%d-%d.%s', $revision->id, now()->timestamp, $extension);

        return $file->storeAs('campaign-revisions/'.$revision->id.'/videos', $filename, 'public');
    }

    /**
     * Convert request's videos[] into a revision-safe array, storing any uploaded files.
     *
     * @return list<array{type: string, title: string|null, url: string|null, file_path: string|null}>
     */
    public function buildVideosPayload(CampaignRevision $revision, Request $request): array
    {
        $rows = collect($request->input('videos', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['type']))
            ->values();

        if ($rows->isEmpty()) {
            return [];
        }

        $payload = [];

        foreach ($rows as $index => $row) {
            $type = (string) ($row['type'] ?? '');
            $title = isset($row['title']) ? trim((string) $row['title']) : '';
            $title = $title !== '' ? $title : null;

            $item = [
                'type' => $type,
                'title' => $title,
                'url' => null,
                'file_path' => null,
            ];

            if ($type === 'file') {
                $file = $request->file("videos.{$index}.file");

                if ($file instanceof UploadedFile) {
                    $item['file_path'] = $this->storeVideoFile($revision, $file);
                }
            } else {
                $url = trim((string) ($row['url'] ?? ''));
                $item['url'] = $url !== '' ? $url : null;
            }

            $payload[] = $item;
        }

        return $payload;
    }
}

