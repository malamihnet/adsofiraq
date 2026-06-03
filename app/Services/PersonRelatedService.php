<?php

namespace App\Services;

use App\Models\Person;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PersonRelatedService
{
    /**
     * @return Collection<int, Person>
     */
    public function related(Person $person, int $limit = 6): Collection
    {
        $campaignIds = $person->campaigns()
            ->public()
            ->pluck('campaigns.id');

        if ($campaignIds->isEmpty()) {
            return $this->bySamePosition($person, $limit);
        }

        $agencyIds = DB::table('agency_campaign')
            ->whereIn('campaign_id', $campaignIds)
            ->pluck('agency_id')
            ->unique()
            ->filter();

        $productionIds = DB::table('agency_campaign')
            ->whereIn('campaign_id', $campaignIds)
            ->where('role', 'production_house')
            ->pluck('agency_id')
            ->unique()
            ->filter();

        $relatedIds = Person::query()
            ->public()
            ->where('people.id', '!=', $person->id)
            ->where(function ($query) use ($person, $campaignIds, $agencyIds, $productionIds) {
                if ($person->position_id) {
                    $query->orWhere('position_id', $person->position_id);
                }

                $query->orWhere('position', $person->position);

                $query->orWhereHas('campaigns', function ($campaigns) use ($campaignIds) {
                    $campaigns->public()->whereIn('campaigns.id', $campaignIds);
                });

                if ($agencyIds->isNotEmpty()) {
                    $query->orWhereHas('campaigns', function ($campaigns) use ($agencyIds) {
                        $campaigns->public()->whereHas('agencies', fn ($agencies) => $agencies->whereIn('agencies.id', $agencyIds));
                    });
                }

                if ($productionIds->isNotEmpty()) {
                    $query->orWhereHas('campaigns', function ($campaigns) use ($productionIds) {
                        $campaigns->public()->whereHas('productionHouses', fn ($houses) => $houses->whereIn('agencies.id', $productionIds));
                    });
                }
            })
            ->orderByDesc('ranking_score')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        if ($relatedIds->count() >= $limit) {
            return $relatedIds;
        }

        $fallback = $this->bySamePosition($person, $limit)
            ->reject(fn (Person $candidate) => $relatedIds->contains(fn (Person $existing) => $existing->id === $candidate->id));

        return $relatedIds->concat($fallback)->take($limit)->values();
    }

    /**
     * @return Collection<int, Person>
     */
    protected function bySamePosition(Person $person, int $limit): Collection
    {
        return Person::query()
            ->public()
            ->where('id', '!=', $person->id)
            ->when($person->position_id, fn ($q) => $q->where('position_id', $person->position_id))
            ->when(! $person->position_id && $person->position, fn ($q) => $q->where('position', $person->position))
            ->orderByDesc('ranking_score')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }
}
