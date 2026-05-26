@props([
    'name',
    'label',
    'options' => [],
    'selected' => [],
    'max' => 3,
    'helper' => null,
])

@php
    $helperText = $helper ?? config("campaign_taxonomy.helpers.{$name}", '');
    $optionList = collect($options)->map(fn ($item) => [
        'id' => $item->id,
        'name' => $item->name,
    ])->values();
@endphp

<div
    class="taxonomy-multiselect"
    x-data="taxonomyMultiselect({
        name: @js($name),
        max: @js($max),
        options: @js($optionList),
        initial: @js($selected),
    })"
    @click.away="closeDropdown()"
>
    <div class="mb-2 flex items-end justify-between gap-4">
        <label class="section-label block">{{ $label }}</label>
        <span class="text-xs text-archive-gray" x-text="counterLabel"></span>
    </div>

    <div class="border border-archive-border bg-white p-3 transition-colors focus-within:border-archive-black">
        <div class="mb-3 flex flex-wrap gap-2" x-show="selected.length > 0">
            <template x-for="item in selected" :key="item.key">
                <span class="inline-flex items-center gap-2 border border-archive-black bg-archive-light px-2.5 py-1 text-xs">
                    <span x-text="item.name"></span>
                    <button type="button" @click="remove(item)" class="text-archive-gray hover:text-archive-black" aria-label="Remove">&times;</button>
                </span>
            </template>
        </div>

        <template x-for="item in selected" :key="'hidden-'+item.key">
            <input type="hidden" :name="`${name}[]`" :value="hiddenValue(item)">
        </template>

        <div class="relative">
            <input
                type="text"
                x-model="query"
                @focus="open = canAddMore"
                @keydown="onKeydown($event)"
                :disabled="!canAddMore"
                :placeholder="canAddMore ? 'Search or type to add…' : 'Limit reached'"
                class="input-field border-0 p-0 focus:border-0 focus:ring-0 disabled:bg-transparent disabled:text-archive-gray"
                autocomplete="off"
            >

            <div
                x-show="open && canAddMore"
                x-cloak
                class="absolute left-0 right-0 top-full z-30 mt-1 max-h-48 overflow-y-auto border border-archive-border bg-white shadow-sm"
            >
                <template x-if="showCreateOption">
                    <button
                        type="button"
                        @mousedown.prevent="addNewFromQuery()"
                        class="block w-full border-b border-archive-border px-4 py-2.5 text-left text-sm hover:bg-archive-light"
                    >
                        Add "<span x-text="query.trim()"></span>"
                    </button>
                </template>
                <template x-for="option in filteredOptions" :key="option.id">
                    <button
                        type="button"
                        @mousedown.prevent="selectExisting(option)"
                        class="block w-full px-4 py-2.5 text-left text-sm hover:bg-archive-light"
                        x-text="option.name"
                    ></button>
                </template>
                <p
                    x-show="filteredOptions.length === 0 && !showCreateOption"
                    class="px-4 py-3 text-xs text-archive-gray"
                >
                    No matches. Type a name and press Enter to add.
                </p>
            </div>
        </div>
    </div>

    @if($helperText)
        <p class="mt-2 text-xs text-archive-gray">{{ $helperText }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error("{$name}.*")
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
