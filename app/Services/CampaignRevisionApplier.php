<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignAsset;
use App\Models\CampaignRevision;
use App\Models\CampaignVideo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CampaignRevisionApplier
{
    public function __construct(
        protected CampaignTaxonomySyncService $taxonomySyncService,
        protected CreditsMentionService $creditsMentions,
    ) {}

    public function apply(CampaignRevision $revision): void
    {
        DB::transaction(function () use ($revision) {
            $revision->loadMissing('campaign');

            $campaign = $revision->campaign;
            $payload = (array) ($revision->revision_payload ?? []);

            $campaign->update([
                'title' => $payload['title'] ?? $campaign->title,
                'published_at' => $payload['published_at'] ?? $campaign->published_at,
                'description' => $payload['description'] ?? $campaign->description,
                'credits' => $payload['credits'] ?? $campaign->credits,
                'is_student' => (bool) ($payload['is_student'] ?? $campaign->is_student),
                'is_nsfw' => (bool) ($payload['is_nsfw'] ?? $campaign->is_nsfw),
                'submission_notes' => $payload['submission_notes'] ?? $campaign->submission_notes,
            ]);

            $this->applyTaxonomies($campaign, $payload);
            $this->applyPeopleCredits($campaign, $payload);
            $this->applyMedia($campaign, $revision, $payload);
        });
    }

    protected function applyTaxonomies(Campaign $campaign, array $payload): void
    {
        $tax = (array) ($payload['taxonomies'] ?? []);

        $this->taxonomySyncService->syncAll(
            $campaign,
            agencies: (array) ($tax['agencies'] ?? []),
            productionHouses: (array) ($tax['production_houses'] ?? []),
            brands: (array) ($tax['brands'] ?? []),
            industries: (array) ($tax['industries'] ?? []),
            mediumTypes: (array) ($tax['medium_types'] ?? []),
            countries: (array) ($tax['countries'] ?? []),
        );
    }

    protected function applyPeopleCredits(Campaign $campaign, array $payload): void
    {
        $mentionsRaw = $payload['credits_mentions_json'] ?? $payload['credit_mentions'] ?? null;

        if ($mentionsRaw === null && ! array_key_exists('people_credits', $payload)) {
            return;
        }

        $credits = (string) ($payload['credits'] ?? $campaign->credits ?? '');

        if ($mentionsRaw !== null) {
            $mentions = $this->creditsMentions->decodeMentionsInput($mentionsRaw);
            $this->creditsMentions->syncFromCredits($campaign, $credits, $mentions);

            return;
        }

        $legacy = (array) ($payload['people_credits'] ?? []);
        $sync = [];

        foreach ($legacy as $credit) {
            if (! is_array($credit)) {
                continue;
            }

            $personId = isset($credit['person_id']) ? (int) $credit['person_id'] : 0;
            $role = trim((string) ($credit['role'] ?? ''));

            if ($personId < 1 || $role === '') {
                continue;
            }

            $sync[$personId] = ['role' => $role];
        }

        $campaign->people()->sync($sync);
    }

    protected function applyMedia(Campaign $campaign, CampaignRevision $revision, array $payload): void
    {
        $disk = Storage::disk('public');

        if (! empty($payload['thumbnail_path']) && is_string($payload['thumbnail_path'])) {
            $from = ltrim(str_replace('\\', '/', $payload['thumbnail_path']), '/');
            if ($disk->exists($from)) {
                $to = 'campaigns/'.$campaign->id.'/thumbnails/'.basename($from);
                $disk->makeDirectory('campaigns/'.$campaign->id.'/thumbnails');
                $disk->copy($from, $to);
                $campaign->update(['thumbnail_path' => $to]);
            }
        }

        $assetPaths = $payload['assets_paths'] ?? [];
        if (is_array($assetPaths) && $assetPaths !== []) {
            $sortOrder = (int) ($campaign->assets()->max('sort_order') ?? 0);

            foreach ($assetPaths as $path) {
                if (! is_string($path) || trim($path) === '') {
                    continue;
                }

                $from = ltrim(str_replace('\\', '/', $path), '/');
                if (! $disk->exists($from)) {
                    continue;
                }

                $toDir = 'campaigns/'.$campaign->id.'/assets';
                $disk->makeDirectory($toDir);
                $to = $toDir.'/'.basename($from);
                $disk->copy($from, $to);

                $campaign->assets()->create([
                    'file_path' => $to,
                    'file_type' => 'image',
                    'sort_order' => ++$sortOrder,
                ]);
            }
        }

        $videos = $payload['videos'] ?? [];
        if (is_array($videos)) {
            $campaign->videos()->get()->each(fn (CampaignVideo $video) => $video->delete());

            $sortOrder = 0;
            foreach ($videos as $row) {
                if (! is_array($row) || empty($row['type'])) {
                    continue;
                }

                $sortOrder++;
                $type = (string) $row['type'];
                $title = isset($row['title']) ? trim((string) $row['title']) : '';
                $title = $title !== '' ? $title : null;

                $data = [
                    'campaign_id' => $campaign->id,
                    'type' => $type,
                    'title' => $title,
                    'sort_order' => $sortOrder,
                    'url' => null,
                    'file_path' => null,
                ];

                if ($type === 'file') {
                    $from = isset($row['file_path']) ? ltrim(str_replace('\\', '/', (string) $row['file_path']), '/') : '';
                    if ($from !== '' && $disk->exists($from)) {
                        $disk->makeDirectory('campaigns/videos');
                        $to = 'campaigns/videos/'.sprintf('campaign-%d-revision-%d-%d-%s', $campaign->id, $revision->id, now()->timestamp, basename($from));
                        $disk->copy($from, $to);
                        $data['file_path'] = $to;
                    }
                } else {
                    $url = isset($row['url']) ? trim((string) $row['url']) : '';
                    $data['url'] = $url !== '' ? $url : null;
                }

                CampaignVideo::create($data);
            }
        }
    }
}

