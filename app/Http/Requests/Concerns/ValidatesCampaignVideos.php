<?php

namespace App\Http\Requests\Concerns;

use App\Models\Campaign;
use App\Models\CampaignVideo;
use App\Services\VideoUrlParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

trait ValidatesCampaignVideos
{
    protected function campaignVideoRules(): array
    {
        $maxVideos = (int) config('upload.max_videos');

        return [
            'videos' => ['nullable', 'array', 'max:'.$maxVideos],
            'videos.*.id' => ['nullable', 'integer'],
            'videos.*.type' => ['nullable', 'in:file,youtube,vimeo'],
            'videos.*.title' => ['nullable', 'string', 'max:255'],
            'videos.*.url' => ['nullable', 'url', 'max:500'],
            // File rows validated manually so failed PHP uploads get clear messages.
            'videos.*.file' => ['nullable'],
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
        $field = "videos.{$index}.file";
        $file = $this->file($field);

        if ($file instanceof UploadedFile) {
            if (! $file->isValid()) {
                $validator->errors()->add($field, $this->invalidVideoUploadMessage($file));

                return;
            }

            $this->validateVideoFileSizeAndType($validator, $field, $file);

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

        $validator->errors()->add($field, 'Please upload a video file (MP4, WebM, or MOV), or use a YouTube/Vimeo link instead.');
    }

    protected function validateVideoFileSizeAndType(Validator $validator, string $field, UploadedFile $file): void
    {
        $maxKb = (int) config('upload.max_video_kb', 51200);
        $maxBytes = $maxKb * 1024;
        $maxMb = max(1, (int) round($maxKb / 1024));

        if ($file->getSize() > $maxBytes) {
            $validator->errors()->add(
                $field,
                "Video file is too large. Please upload under {$maxMb}MB or use a video link."
            );

            return;
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = config('upload.allowed_video_mimes', ['mp4', 'webm', 'mov']);

        if (! in_array($extension, $allowed, true)) {
            $validator->errors()->add($field, 'Please upload a video file (MP4, WebM, or MOV).');
        }
    }

    protected function invalidVideoUploadMessage(UploadedFile $file): string
    {
        $error = $file->getError();

        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            $maxMb = max(1, (int) round((int) config('upload.max_video_kb', 51200) / 1024));

            return "Video file is too large. Please upload under {$maxMb}MB or use a Vimeo/YouTube link instead.";
        }

        return 'Video upload failed. The file may be larger than the server upload limit. Please upload a smaller file or use a Vimeo/YouTube link.';
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

    protected function hasValidVideoUrl(): bool
    {
        foreach ($this->input('videos', []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $type = $row['type'] ?? '';

            if (in_array($type, ['youtube', 'vimeo'], true) && trim((string) ($row['url'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    protected function hasInvalidVideoFileUpload(): bool
    {
        foreach ($this->input('videos', []) as $index => $row) {
            if (! is_array($row) || ($row['type'] ?? '') !== 'file') {
                continue;
            }

            $file = $this->file("videos.{$index}.file");

            if ($file instanceof UploadedFile && ! $file->isValid()) {
                return true;
            }
        }

        return false;
    }

    protected function hasValidVideoFileUpload(): bool
    {
        foreach ($this->input('videos', []) as $index => $row) {
            if (! is_array($row) || ($row['type'] ?? '') !== 'file') {
                continue;
            }

            $file = $this->file("videos.{$index}.file");

            if ($file instanceof UploadedFile && $file->isValid()) {
                return true;
            }
        }

        return false;
    }
}
