<?php

namespace App\Http\Requests\Concerns;

use App\Models\Campaign;
use App\Models\CampaignVideo;
use App\Services\VideoUrlParser;
use Illuminate\Validation\Validator;

trait ValidatesCampaignVideos
{
    protected function campaignVideoRules(): array
    {
        $maxVideo = (int) config('upload.max_video_kb');
        $maxVideos = (int) config('upload.max_videos');
        $videoMimes = config('upload.allowed_video_mimes');

        return [
            'videos' => ['nullable', 'array', 'max:'.$maxVideos],
            'videos.*.id' => ['nullable', 'integer'],
            'videos.*.type' => ['nullable', 'in:file,youtube,vimeo'],
            'videos.*.title' => ['nullable', 'string', 'max:255'],
            'videos.*.url' => ['nullable', 'url', 'max:500'],
            'videos.*.file' => [
                'nullable',
                'file',
                'mimes:'.implode(',', $videoMimes),
                'mimetypes:video/mp4,video/webm,video/quicktime',
                'max:'.$maxVideo,
            ],
        ];
    }

    protected function validateCampaignVideos(Validator $validator): void
    {
        $rows = collect($this->input('videos', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['type']));

        $maxVideos = (int) config('upload.max_videos');

        if ($rows->count() > $maxVideos) {
            $validator->errors()->add('videos', "You can add up to {$maxVideos} videos per campaign.");

            return;
        }

        $campaign = $this->route('campaign');

        foreach ($rows->values() as $index => $row) {
            $type = $row['type'] ?? null;

            if (! in_array($type, ['file', 'youtube', 'vimeo'], true)) {
                $validator->errors()->add("videos.{$index}.type", 'Please choose a valid video source.');

                continue;
            }

            $existingId = $row['id'] ?? null;

            if ($existingId && $campaign instanceof Campaign) {
                $owned = CampaignVideo::query()
                    ->where('campaign_id', $campaign->id)
                    ->where('id', $existingId)
                    ->exists();

                if (! $owned) {
                    $validator->errors()->add("videos.{$index}.id", 'Invalid video reference.');

                    continue;
                }
            }

            if ($type === 'file') {
                $this->validateFileVideoRow($validator, $index, $row, $campaign);

                continue;
            }

            if ($type === 'youtube') {
                $this->validateUrlVideoRow($validator, $index, $row, 'youtube');

                continue;
            }

            $this->validateUrlVideoRow($validator, $index, $row, 'vimeo');
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function validateFileVideoRow(Validator $validator, int $index, array $row, mixed $campaign): void
    {
        $hasUpload = $this->hasFile("videos.{$index}.file");

        if ($hasUpload) {
            return;
        }

        $existingId = $row['id'] ?? null;

        if ($existingId && $campaign instanceof Campaign) {
            $existing = CampaignVideo::query()
                ->where('campaign_id', $campaign->id)
                ->where('id', $existingId)
                ->where('type', 'file')
                ->whereNotNull('file_path')
                ->exists();

            if ($existing) {
                return;
            }
        }

        $validator->errors()->add("videos.{$index}.file", 'Please upload a video file (MP4, WebM, or MOV).');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function validateUrlVideoRow(Validator $validator, int $index, array $row, string $expectedType): void
    {
        $url = trim((string) ($row['url'] ?? ''));

        if ($url === '') {
            $validator->errors()->add(
                "videos.{$index}.url",
                $expectedType === 'youtube' ? 'Please enter a YouTube URL.' : 'Please enter a Vimeo URL.'
            );

            return;
        }

        $parsed = $expectedType === 'youtube'
            ? VideoUrlParser::parseYouTube($url)
            : VideoUrlParser::parseVimeo($url);

        if (! $parsed) {
            $validator->errors()->add(
                "videos.{$index}.url",
                $expectedType === 'youtube'
                    ? 'Please provide a valid YouTube URL.'
                    : 'Please provide a valid Vimeo URL.'
            );
        }
    }
}
