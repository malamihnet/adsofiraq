<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Person;
use Illuminate\Support\Collection;

class CampaignPeopleCreditService
{
    /**
     * @param  array<int, array{person_id?: int|string|null, role?: string|null}>  $credits
     */
    public function sync(Campaign $campaign, array $credits): void
    {
        $sync = [];

        foreach ($credits as $credit) {
            if (! is_array($credit)) {
                continue;
            }

            $personId = isset($credit['person_id']) ? (int) $credit['person_id'] : 0;
            $role = trim((string) ($credit['role'] ?? ''));

            if ($personId < 1 || $role === '') {
                continue;
            }

            if (! Person::query()->whereKey($personId)->exists()) {
                continue;
            }

            $sync[$personId] = ['role' => $role];
        }

        $campaign->people()->sync($sync);
    }

    /**
     * @return array<int, array{person_id: int, role: string, name: string, slug: string, photo_url: string}>
     */
    public function selectedForForm(Campaign $campaign): array
    {
        return $campaign->people()
            ->orderBy('campaign_person.role')
            ->get()
            ->map(fn (Person $person) => [
                'person_id' => $person->id,
                'role' => $person->pivot->role,
                'name' => $person->name,
                'slug' => $person->slug,
                'photo_url' => $person->photo_url,
            ])
            ->values()
            ->all();
    }

    /**
     * @deprecated Use credit_mentions JSON via CreditsMentionService
     * @return array<int, array{person_id: int, role: string}>
     */
    public function fromOldInput(): array
    {
        return [];
    }

    /**
     * @return Collection<int, array{label: string, person: Person}>
     */
    public function creditLines(Campaign $campaign): Collection
    {
        if (! $campaign->relationLoaded('people')) {
            $campaign->load('people');
        }

        return $campaign->people
            ->map(fn (Person $person) => [
                'label' => $person->pivot->role,
                'person' => $person,
            ])
            ->sortBy(fn (array $line) => strtolower($line['label']))
            ->values();
    }
}
