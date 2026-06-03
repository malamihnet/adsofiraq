<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Person;
use App\Models\Position;
use Illuminate\Support\Collection;

class CreditsMentionService
{
    public function __construct(
        protected CampaignPeopleCreditService $peopleCredits,
    ) {}

    /**
     * @return array<int, array{person_id: int, role: string, name: string}>
     */
    public function decodeMentionsInput(mixed $raw): array
    {
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);

            $raw = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($raw)) {
            return [];
        }

        $mentions = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $personId = isset($item['person_id']) ? (int) $item['person_id'] : 0;
            $name = trim((string) ($item['name'] ?? ''));
            $role = trim((string) ($item['role'] ?? ''));

            if ($personId < 1 || $name === '') {
                continue;
            }

            $mentions[] = [
                'person_id' => $personId,
                'name' => $name,
                'role' => $role !== '' ? $role : 'Credit',
            ];
        }

        return $mentions;
    }

    /**
     * @param  array<int, array{person_id: int, role: string, name: string}>  $mentions
     */
    public function syncFromCredits(Campaign $campaign, ?string $credits, array $mentions): void
    {
        $credits = (string) ($credits ?? '');
        $active = $this->filterMentionsInCredits($credits, $mentions);

        $this->peopleCredits->sync($campaign, array_map(fn (array $row) => [
            'person_id' => $row['person_id'],
            'role' => $row['role'],
        ], $active));
    }

    /**
     * @param  array<int, array{person_id: int, role: string, name: string}>  $mentions
     * @return array<int, array{person_id: int, role: string, name: string}>
     */
    public function filterMentionsInCredits(string $credits, array $mentions): array
    {
        return array_values(array_filter($mentions, function (array $mention) use ($credits) {
            return $this->mentionTokenInCredits($credits, $mention['name']);
        }));
    }

    public function mentionTokenInCredits(string $credits, string $name): bool
    {
        return str_contains($credits, $this->mentionToken($name));
    }

    public function mentionToken(string $name): string
    {
        return '@'.trim($name);
    }

    /**
     * @return array<int, array{person_id: int, role: string, name: string, slug: string, photo_url: string}>
     */
    public function mentionsForForm(Campaign $campaign): array
    {
        if (! $campaign->relationLoaded('people')) {
            $campaign->load('people');
        }

        return $this->hydrateMentionsForForm(
            $campaign->people
                ->map(fn (Person $person) => [
                    'person_id' => $person->id,
                    'role' => $person->pivot->role,
                    'name' => $person->name,
                ])
                ->all()
        );
    }

    /**
     * @param  array<int, array{person_id: int, role?: string, name?: string}>  $mentions
     * @return array<int, array{person_id: int, role: string, name: string, slug: string, photo_url: string}>
     */
    public function hydrateMentionsForForm(array $mentions): array
    {
        if ($mentions === []) {
            return [];
        }

        $people = Person::query()
            ->whereIn('id', collect($mentions)->pluck('person_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $hydrated = [];

        foreach ($mentions as $mention) {
            $person = $people->get($mention['person_id'] ?? 0);

            if (! $person) {
                continue;
            }

            $hydrated[] = [
                'person_id' => $person->id,
                'role' => trim((string) ($mention['role'] ?? '')) ?: 'Credit',
                'name' => $mention['name'] ?? $person->name,
                'slug' => $person->slug,
                'photo_url' => $person->photo_url,
            ];
        }

        return $hydrated;
    }

    public function renderCreditsHtml(Campaign $campaign): string
    {
        $credits = (string) ($campaign->credits ?? '');

        if ($credits === '') {
            return '';
        }

        if (! $campaign->relationLoaded('people')) {
            $campaign->load('people');
        }

        $people = $campaign->people->keyBy('id');
        $escaped = e($credits);
        $lines = preg_split("/\r\n|\n|\r/", $escaped) ?: [$escaped];

        $htmlLines = array_map(function (string $line) use ($people) {
            return $this->linkMentionsInLine($line, $people);
        }, $lines);

        return implode('<br>', $htmlLines);
    }

    /**
     * @param  Collection<int, Person>  $people
     */
    protected function linkMentionsInLine(string $line, Collection $people): string
    {
        foreach ($people as $person) {
            $token = e($this->mentionToken($person->name));

            if (! str_contains($line, $token)) {
                continue;
            }

            if ($person->status === 'approved') {
                $url = e(route('person.show', $person));
                $replacement = '<a href="'.$url.'" class="underline hover:text-archive-black">'.e($person->name).'</a>';
            } else {
                $replacement = e($person->name);
            }

            $line = str_replace($token, $replacement, $line);
        }

        return $line;
    }

    public function resolvePositionName(?int $positionId, ?string $fallback = null): string
    {
        if ($positionId) {
            $position = Position::query()->find($positionId);

            if ($position) {
                return $position->name;
            }
        }

        return trim((string) $fallback) !== '' ? trim((string) $fallback) : 'Creative';
    }
}
