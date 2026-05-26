<?php

namespace App\Services\Import;

use App\Exceptions\CampaignImportException;
use Illuminate\Support\Facades\Http;

class CampaignPageFetcher
{
    public function fetch(string $url, ?int $timeout = null, int $maxAttempts = 1): string
    {
        $timeout ??= (int) config('import.timeout', 30);
        $maxAttempts = max(1, $maxAttempts);
        $lastError = 'Unknown error';

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => config('import.user_agent'),
                    'Accept' => 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                    ->timeout($timeout)
                    ->connectTimeout(min(15, $timeout))
                    ->get($url);

                if ($response->failed()) {
                    $lastError = 'HTTP '.$response->status();

                    if ($attempt < $maxAttempts) {
                        usleep(300_000 * $attempt);

                        continue;
                    }

                    throw CampaignImportException::fetchFailed($lastError);
                }

                $body = $response->body();

                if (trim($body) === '') {
                    $lastError = 'Empty response';

                    if ($attempt < $maxAttempts) {
                        usleep(300_000 * $attempt);

                        continue;
                    }

                    throw CampaignImportException::fetchFailed($lastError);
                }

                return $body;
            } catch (CampaignImportException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();

                if ($attempt < $maxAttempts) {
                    usleep(300_000 * $attempt);

                    continue;
                }

                throw CampaignImportException::fetchFailed($lastError);
            }
        }

        throw CampaignImportException::fetchFailed($lastError);
    }
}
