@props([
    'agencies',
    'productionHouses' => null,
    'brands',
    'industries',
    'mediumTypes',
    'countries',
    'selected' => [],
])

<div class="grid gap-6 md:grid-cols-2">
    <x-taxonomy-multiselect
        name="agencies"
        label="Agencies / Schools"
        :options="$agencies"
        :selected="$selected['agencies'] ?? []"
        :max="config('campaign_taxonomy.limits.agencies')"
    />

    <x-taxonomy-multiselect
        name="production_houses"
        label="Production House"
        :options="$productionHouses ?? $agencies"
        :selected="$selected['production_houses'] ?? []"
        :max="config('campaign_taxonomy.limits.production_houses')"
    />

    <x-taxonomy-multiselect
        name="brands"
        label="Brands"
        :options="$brands"
        :selected="$selected['brands'] ?? []"
        :max="config('campaign_taxonomy.limits.brands')"
    />

    <x-taxonomy-multiselect
        name="industries"
        label="Industries"
        :options="$industries"
        :selected="$selected['industries'] ?? []"
        :max="config('campaign_taxonomy.limits.industries')"
    />

    <x-taxonomy-multiselect
        name="medium_types"
        label="Medium Types"
        :options="$mediumTypes"
        :selected="$selected['medium_types'] ?? []"
        :max="config('campaign_taxonomy.limits.medium_types')"
    />

    <x-taxonomy-multiselect
        name="countries"
        label="Countries"
        :options="$countries"
        :selected="$selected['countries'] ?? []"
        :max="config('campaign_taxonomy.limits.countries')"
    />
</div>
