<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Person;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PersonRankingService
{
    public function __construct(
        protected RankingScoreService $rankings,
    ) {}

    /**
     * @return Collection<int, Person>
     */
    public function topDirectors(int $limit = 30): Collection
    {
        return $this->rankByRolePatterns(
            ['director'],
            excludePatterns: ['creative director', 'art director', 'assistant director'],
            limit: $limit,
        );
    }

    /**
     * @return Collection<int, Person>
     */
    public function topEditors(int $limit = 30): Collection
    {
        return $this->rankByRolePatterns(['editor', 'edit'], limit: $limit);
    }

    /**
     * @return Collection<int, Person>
     */
    public function topCreativeDirectors(int $limit = 30): Collection
    {
        return $this->rankByRolePatterns(
            ['creative director', 'cd', 'chief creative'],
            limit: $limit,
        );
    }

    /**
     * @param  list<string>  $patterns
     * @param  list<string>  $excludePatterns
     * @return Collection<int, Person>
     */
    protected function rankByRolePatterns(array $patterns, array $excludePatterns = [], int $limit = 30): Collection
    {
        $rows = DB::table('campaign_person')
            ->join('people', 'people.id', '=', 'campaign_person.person_id')
            ->join('campaigns', 'campaigns.id', '=', 'campaign_person.campaign_id')
            ->where('people.status', 'approved')
            ->where('campaigns.status', 'approved')
            ->where('campaigns.is_draft', false)
            ->whereNull('campaigns.deleted_at')
            ->where(function ($query) use ($patterns) {
                foreach ($patterns as $pattern) {
                    $query->orWhere('campaign_person.role', 'like', '%'.$pattern.'%');
                }
            })
            ->when($excludePatterns !== [], function ($query) use ($excludePatterns) {
                foreach ($excludePatterns as $pattern) {
                    $query->where('campaign_person.role', 'not like', '%'.$pattern.'%');
                }
            })
            ->groupBy('people.id', 'people.name', 'people.slug', 'people.position', 'people.photo_path', 'people.ranking_score', 'people.is_verified')
            ->selectRaw('
                people.id,
                people.name,
                people.slug,
                people.position,
                people.photo_path,
                people.ranking_score,
                people.is_verified,
                COUNT(DISTINCT campaigns.id) as campaign_count,
                COALESCE(SUM(campaigns.views_count), 0) as total_views,
                COALESCE(SUM(campaigns.bookmarks_count), 0) as total_bookmarks,
                COALESCE(SUM(CASE WHEN campaigns.is_featured = 1 OR campaigns.editorial_label IS NOT NULL THEN 1 ELSE 0 END), 0) as featured_campaigns,
                COALESCE(SUM(campaigns.ranking_score), 0) as aggregate_score
            ')
            ->orderByDesc('aggregate_score')
            ->orderByDesc('campaign_count')
            ->orderBy('people.name')
            ->limit($limit)
            ->get();

        return $rows->map(function ($row) {
            $person = Person::query()->find($row->id) ?? new Person([
                'id' => $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
                'position' => $row->position,
                'photo_path' => $row->photo_path,
                'ranking_score' => $row->ranking_score,
                'is_verified' => (bool) $row->is_verified,
                'status' => 'approved',
            ]);

            $person->setAttribute('ranking_campaign_count', (int) $row->campaign_count);
            $person->setAttribute('ranking_total_views', (int) $row->total_views);
            $person->setAttribute('ranking_total_bookmarks', (int) $row->total_bookmarks);
            $person->setAttribute('ranking_featured_campaigns', (int) $row->featured_campaigns);
            $person->setAttribute('ranking_display_score', (float) $row->aggregate_score);

            return $person;
        });
    }
}
