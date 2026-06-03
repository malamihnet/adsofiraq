@props([
    'credits' => '',
    'mentions' => [],
])

@php
    $initialMentions = collect($mentions)->values()->all();
    $creditsValue = old('credits', $credits);
    $mentionsValue = old('credit_mentions');
    if ($mentionsValue === null) {
        $mentionsValue = json_encode($initialMentions);
    }
@endphp

<div
    class="credits-mentions-field"
    x-data="creditsMentions({
        initialMentions: @js($initialMentions),
        creditsText: @js($creditsValue),
        peopleSearchUrl: @js(route('api.people.search')),
        positionsUrl: @js(route('api.positions.index')),
        createPersonUrl: @js(route('api.people.store')),
        createPositionUrl: @js(route('api.positions.store')),
        csrfToken: @js(csrf_token()),
    })"
    x-init="init()"
    @click.away="open = false"
>
    <label class="section-label mb-2 block">Credits</label>
    <p class="mb-2 text-xs text-archive-gray">
        Type <code class="text-archive-black">@</code> to tag people and link their profiles. Example:
        <span class="text-archive-black">Director: @Mustafa Amer</span>
        Plain text without @ stays unlinked.
    </p>

    <div class="relative">
        <textarea
            x-ref="creditsTextarea"
            name="credits"
            rows="6"
            class="input-field font-mono text-sm"
            placeholder="Director: @Mustafa Amer&#10;Editor: @Ali Hassan"
            x-model="text"
            @input="onCreditsInput($event)"
            @keydown="onCreditsKeydown($event)"
            @click="detectMentionQuery($refs.creditsTextarea)"
        ></textarea>

        <input type="hidden" name="credit_mentions" :value="mentionsJson">

        <div
            x-show="open && (results.length > 0 || canSearch)"
            x-cloak
            class="absolute left-0 right-0 top-full z-40 mt-1 max-h-64 overflow-y-auto border border-archive-border bg-white shadow-lg"
        >
            <template x-for="(item, index) in dropdownItems" :key="item.type === 'person' ? `p-${item.person.id}` : 'create'">
                <button
                    type="button"
                    x-show="item.type === 'person'"
                    @mousedown.prevent="selectPerson(item.person)"
                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition-colors"
                    :class="activeIndex === index ? 'bg-archive-light' : 'hover:bg-archive-light'"
                >
                    <img :src="item.person.photo_url" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover">
                    <span class="min-w-0">
                        <span class="block font-medium" x-text="item.person.name"></span>
                        <span class="block truncate text-xs text-archive-gray" x-text="item.person.position"></span>
                    </span>
                </button>
            </template>

            <button
                type="button"
                x-show="canSearch && !loading"
                @mousedown.prevent="openCreateModal()"
                class="block w-full border-t border-archive-border px-4 py-3 text-left text-sm hover:bg-archive-light"
                :class="activeIndex === dropdownItems.length - 1 && dropdownItems.length > 0 ? 'bg-archive-light' : ''"
            >
                Create profile: <span class="font-medium" x-text="query.trim()"></span>
            </button>

            <p x-show="loading" class="px-4 py-3 text-xs text-archive-gray">Searching...</p>
        </div>
    </div>

    @error('credits')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    @error('credit_mentions')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror

    <div
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Create profile"
    >
        <div class="w-full max-w-md border border-archive-border bg-white p-6 shadow-lg" @click.away="closeCreateModal()">
            <h3 class="font-display text-lg">Create Profile</h3>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="section-label mb-2 block">Full Name *</label>
                    <input type="text" x-model="modalName" class="input-field" required>
                </div>
                <div>
                    <label class="section-label mb-2 block">Position *</label>
                    <select x-model="modalPositionId" class="input-field" required>
                        <option value="">Select position</option>
                        <template x-for="position in positions" :key="position.id">
                            <option :value="String(position.id)" x-text="position.name"></option>
                        </template>
                    </select>
                    <button type="button" @click="openPositionModal()" class="mt-2 text-xs underline text-archive-gray hover:text-archive-black">
                        Add Position
                    </button>
                </div>
                <div>
                    <label class="section-label mb-2 block">Profile Image (optional)</label>
                    <input type="file" accept=".jpg,.jpeg,.png,.webp" @change="onModalPhotoChange($event)" class="input-field">
                </div>
                <p x-show="modalError" class="text-sm text-red-600" x-text="modalError"></p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="closeCreateModal()" class="btn-outline text-xs">Cancel</button>
                <button type="button" @click="createPerson()" :disabled="modalSaving" class="btn-primary text-xs">
                    <span x-text="modalSaving ? 'Creating...' : 'Create Profile'"></span>
                </button>
            </div>
        </div>
    </div>

    <div
        x-show="positionModalOpen"
        x-cloak
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Add position"
    >
        <div class="w-full max-w-sm border border-archive-border bg-white p-6 shadow-lg">
            <h3 class="font-display text-lg">Add Position</h3>
            <div class="mt-4">
                <label class="section-label mb-2 block">Position Name</label>
                <input type="text" x-model="newPositionName" class="input-field" placeholder="e.g. Director">
            </div>
            <p x-show="positionModalError" class="mt-2 text-sm text-red-600" x-text="positionModalError"></p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="closePositionModal()" class="btn-outline text-xs">Cancel</button>
                <button type="button" @click="createPosition()" :disabled="positionModalSaving" class="btn-primary text-xs">
                    <span x-text="positionModalSaving ? 'Saving...' : 'Create'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
