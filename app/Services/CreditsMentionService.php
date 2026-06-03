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
     * @param  array<int, array{person_id: int, role: string, name: string}>  $clientMentions
     * @return array<int, array{person_id: int, role: string}>
     */
    public function resolveMentionsForSync(string $credits, array $clientMentions): array
    {
        $credits = (string) $credits;
        $byPersonId = [];

        foreach ($this->parseRoleMentionsFromCredits($credits) as $row) {
            if (! isset($byPersonId[$row['person_id']])) {
                $byPersonId[$row['person_id']] = $row;
            }
        }

        foreach ($this->filterMentionsInCredits($credits, $clientMentions) as $mention) {
            $personId = $mention['person_id'];

            if (! isset($byPersonId[$personId])) {
                $byPersonId[$personId] = [
                    'person_id' => $personId,
                    'role' => $mention['role'],
                ];
            }
        }

        return array_values($byPersonId);
    }

    /**
     * @return list<array{person_id: int, role: string}>
     */
    protected function parseRoleMentionsFromCredits(string $credits): array
    {
        $resolved = [];

        foreach (preg_split("/\r\n|\n|\r/", $credits) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || ! str_contains($line, '@')) {
                continue;
            }

            if (! preg_match('/^\s*([^:@\n]{2,60})\s*:\s*(.+)$/u', $line, $matches)) {
                continue;
            }

            $role = trim($matches[1]);

            if ($role === '') {
                continue;
            }

            foreach ($this->extractMentionNames($matches[2]) as $name) {
                $personId = $this->resolvePersonIdByName($name);

                if ($personId < 1) {
                    continue;
                }

                if (! isset($resolved[$personId])) {
                    $resolved[$personId] = [
                        'person_id' => $personId,
                        'role' => $role,
                    ];
                }
            }
        }

        return array_values($resolved);
    }

    /**
     * @return list<string>
     */
    protected function extractMentionNames(string $segment): array
    {
        preg_match_all('/@([^\n@]+)/u', $segment, $matches);

        $names = [];

        foreach ($matches[1] ?? [] as $raw) {
            $name = trim($raw);

            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    protected function resolvePersonIdByName(string $name): int
    {
        $person = Person::query()
            ->where('name', $name)
            ->orderByRaw("CASE WHEN status = 'approved' THEN 0 ELSE 1 END")
            ->first();

        return $person?->id ?? 0;
    }

    /**
     * @param  array<int, array{person_id: int, role: string, name: string}>  $mentions
     */
    public function syncFromCredits(Campaign $campaign, ?string $credits, array $mentions): void
    {
        $resolved = $this->resolveMentionsForSync((string) ($credits ?? ''), $mentions);

        $this->peopleCredits->sync($campaign, $resolved);
    }

    /**
     * @param  array<int, array{person_id: int, role: string, name: string}>  $mentions
     * @return array<int, array{person_id: int, role: string, name: string}>
     */
    public function filterMentionsInCredits(string $credits, array $mentions): array
    {
        $seen = [];

        return array_values(array_filter($mentions, function (array $mention) use ($credits, &$seen) {
            if (! $this->mentionTokenInCredits($credits, $mention['name'])) {
                return false;
            }

            if (isset($seen[$mention['person_id']])) {
                return false;
            }

            $seen[$mention['person_id']] = true;

            return true;
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
        $sorted = $people->sortByDesc(fn (Person $person) => strlen($person->name));

        foreach ($sorted as $person) {
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
