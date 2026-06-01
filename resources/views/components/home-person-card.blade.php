@props(['person'])

<div class="contents">
    <x-home-creator-card
        :href="route('person.show', $person)"
        :name="$person->name"
        :verified="$person->is_verified"
        :subtitle="$person->position"
    />
</div>
