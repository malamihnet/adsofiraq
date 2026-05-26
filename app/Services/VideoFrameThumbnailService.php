<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignVideo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoFrameThumbnailService
{
    protected const VIDEOS_DIR = 'campaigns/videos';

    protected const FRAME_TIMESTAMPS = ['00:00:01', '00:00:00.1'];

    /**
     * Extract a frame from the first uploaded file video on the campaign.
     */
    public function extractFrameBinary(Campaign $campaign): ?string
    {
        $video = $campaign->firstFileVideoForThumbnail();

        if (! $video) {
            return null;
        }

        return $this->extractFrameBinaryFromVideo($campaign, $video);
    }

    public function extractFrameBinaryFromVideo(Campaign $campaign, CampaignVideo $video): ?string
    {
        if ($video->type !== 'file' || empty($video->file_path)) {
            return null;
        }

        if (! $this->isExecAvailable()) {
            Log::warning('exec disabled; cannot extract video thumbnail on this server.', [
                'campaign_id' => $campaign->id,
                'video_id' => $video->id,
            ]);

            return null;
        }

        if (! $this->isFfmpegAvailable()) {
            Log::warning('FFmpeg not available; video thumbnail generation skipped.', [
                'campaign_id' => $campaign->id,
                'video_id' => $video->id,
                'ffmpeg_path' => $this->ffmpegPath(),
            ]);

            return null;
        }

        $inputPath = $this->resolveVideoAbsolutePath($video->file_path);

        if ($inputPath === null) {
            return null;
        }

        foreach (self::FRAME_TIMESTAMPS as $timestamp) {
            $binary = $this->runFfmpegExtraction($campaign, $video, $inputPath, $timestamp);

            if ($binary !== null) {
                Log::info('Campaign thumbnail: video frame extracted.', [
                    'campaign_id' => $campaign->id,
                    'video_id' => $video->id,
                    'timestamp' => $timestamp,
                ]);

                return $binary;
            }
        }

        Log::warning('Campaign thumbnail: failed to extract frame from uploaded video.', [
            'campaign_id' => $campaign->id,
            'video_id' => $video->id,
            'file_path' => $video->file_path,
        ]);

        return null;
    }

    public function resolveVideoAbsolutePath(?string $filePath): ?string
    {
        $relativePath = $this->normalizeVideoPath($filePath);

        if ($relativePath === null) {
            return null;
        }

        if (! Storage::disk('public')->exists($relativePath)) {
            Log::warning('Campaign thumbnail: uploaded video file missing.', [
                'file_path' => $filePath,
            ]);

            return null;
        }

        $absolutePath = Storage::disk('public')->path($relativePath);
        $realPath = realpath($absolutePath);

        if ($realPath === false || ! is_file($realPath)) {
            return null;
        }

        $videosRoot = realpath(Storage::disk('public')->path(self::VIDEOS_DIR));

        if ($videosRoot === false || ! str_starts_with($realPath, $videosRoot.DIRECTORY_SEPARATOR)) {
            Log::warning('Campaign thumbnail: video path outside allowed directory.', [
                'file_path' => $filePath,
            ]);

            return null;
        }

        return $realPath;
    }

    public function isExecAvailable(): bool
    {
        if (! function_exists('exec')) {
            return false;
        }

        $disabled = ini_get('disable_functions');

        if (! is_string($disabled) || $disabled === '') {
            return true;
        }

        $disabledFunctions = array_map('trim', explode(',', strtolower($disabled)));

        return ! in_array('exec', $disabledFunctions, true);
    }

    public function isFfmpegAvailable(): bool
    {
        if (! $this->isExecAvailable()) {
            return false;
        }

        $command = sprintf('%s -version 2>&1', escapeshellarg($this->ffmpegPath()));

        try {
            exec($command, $output, $returnCode);
        } catch (\Throwable $e) {
            Log::warning('FFmpeg availability check failed.', [
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        return $returnCode === 0;
    }

    protected function runFfmpegExtraction(Campaign $campaign, CampaignVideo $video, string $inputPath, string $timestamp): ?string
    {
        $tempOutput = tempnam(sys_get_temp_dir(), 'aoi-frame-');

        if ($tempOutput === false) {
            return null;
        }

        $tempJpg = $tempOutput.'.jpg';
        @unlink($tempOutput);

        $command = sprintf(
            '%s -y -i %s -ss %s -vframes 1 %s 2>&1',
            escapeshellarg($this->ffmpegPath()),
            escapeshellarg($inputPath),
            escapeshellarg($timestamp),
            escapeshellarg($tempJpg)
        );

        try {
            exec($command, $output, $returnCode);
        } catch (\Throwable $e) {
            @unlink($tempJpg);

            Log::warning('Campaign thumbnail: FFmpeg execution failed.', [
                'campaign_id' => $campaign->id,
                'video_id' => $video->id,
                'timestamp' => $timestamp,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }

        if ($returnCode !== 0 || ! is_file($tempJpg) || filesize($tempJpg) === 0) {
            Log::info('Campaign thumbnail: FFmpeg frame attempt failed.', [
                'campaign_id' => $campaign->id,
                'video_id' => $video->id,
                'timestamp' => $timestamp,
                'return_code' => $returnCode,
                'output' => implode("\n", array_slice($output, -5)),
            ]);

            @unlink($tempJpg);

            return null;
        }

        $binary = file_get_contents($tempJpg);
        @unlink($tempJpg);

        return is_string($binary) && $binary !== '' ? $binary : null;
    }

    protected function normalizeVideoPath(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        foreach (['public/storage/', 'public/', 'storage/app/public/', 'app/public/', 'storage/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        if ($path === '' || str_contains($path, '..') || ! str_starts_with($path, self::VIDEOS_DIR.'/')) {
            return null;
        }

        return $path;
    }

    protected function ffmpegPath(): string
    {
        $path = config('upload.ffmpeg_path', 'ffmpeg');

        return is_string($path) && trim($path) !== '' ? trim($path) : 'ffmpeg';
    }
}
