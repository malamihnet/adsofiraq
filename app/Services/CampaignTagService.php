<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Tag;
use Illuminate\Support\Str;

class CampaignTagService
{
    /**
     * @return list<string>
     */
    public function collectTagNames(Campaign $campaign): array
    {
        $campaign->loadMissing([
            'brands',
            'agencies',
            'productionHouses',
            'industries',
            'mediumTypes',
            'people',
        ]);

        $names = [];

        foreach ($campaign->brands as $brand) {
            $names[] = $brand->name;
        }

        foreach ($campaign->agencies as $agency) {
            $names[] = $agency->name;
        }

        foreach ($campaign->productionHouses as $house) {
            $names[] = $house->name;
        }

        foreach ($campaign->industries as $industry) {
            $names[] = $industry->name;
        }

        foreach ($campaign->mediumTypes as $medium) {
            $names[] = $medium->name;
        }

        foreach ($campaign->people as $person) {
            $names[] = $person->name;

            if ($person->pivot->role) {
                $names[] = $person->pivot->role;
            }
        }

        return collect($names)
            ->map(fn (string $name) => trim($name))
            ->filter(fn (string $name) => $name !== '' && strlen($name) >= 2)
            ->unique(fn (string $name) => Str::lower($name))
            ->values()
            ->all();
    }

    public function syncForCampaign(Campaign $campaign): void
    {
        $names = $this->collectTagNames($campaign);
        $tagIds = [];

        foreach ($names as $name) {
            $existing = Tag::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                ->first();

            if ($existing) {
                $tagIds[] = $existing->id;

                continue;
            }

            $tag = Tag::create([
                'name' => $name,
                'slug' => Tag::generateUniqueSlug($name),
                'source' => 'auto',
            ]);

            $tagIds[] = $tag->id;
        }

        $campaign->tags()->sync($tagIds);

        $this->refreshCounts(array_unique($tagIds));
    }

    /**
     * @param  list<int>  $tagIds
     */
    protected function refreshCounts(array $tagIds): void
    {
        foreach ($tagIds as $tagId) {
            Tag::whereKey($tagId)->update([
                'campaigns_count' => Tag::find($tagId)?->campaigns()->public()->count() ?? 0,
            ]);
        }
    }
}
