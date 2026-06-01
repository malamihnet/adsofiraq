@props(['agency', 'campaignCount' => null])

@php
    $count = $campaignCount ?? ($agency->campaigns_count ?? $agency->production_house_campaigns_count ?? null);
    $meta = $count !== null ? $count.' campaigns' : null;
@endphp

<x-home-creator-card
    :href="route('agency.show', $agency)"
    :image-url="$agency->logo_url"
    :name="$agency->name"
    :verified="$agency->is_verified"
    :meta="$meta"
>
    @if($agency->roleLabels() !== [])
        <x-slot:badges>
            <x-agency-role-badges :roles="$agency->roleLabels()" class="justify-center gap-1" />
        </x-slot:badges>
    @endif
</x-home-creator-card>
