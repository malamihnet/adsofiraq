@props(['person'])

<x-home-creator-card
    :href="route('person.show', $person)"
    :image-url="$person->avatar_url"
    :name="$person->name"
    :verified="$person->is_verified"
    :subtitle="$person->position"
    type="person"
/>
