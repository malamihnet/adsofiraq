@props(['selected' => []])

@php
    $initial = collect($selected)->values()->all();
@endphp

<div
    class="people-credits-manager border border-archive-border bg-white p-4"
    x-data="peopleCreditsManager({
        initial: @js($initial),
        searchUrl: @js(route('api.people.search')),
        createUrl: @js(route('api.people.store')),
        csrfToken: @js(csrf_token()),
    })"
    @click.away="open = false"
>
    <div class="mb-2 flex items-end justify-between gap-4">
        <label class="section-label block">People Credits</label>
        <span class="text-xs text-archive-gray" x-text="`${credits.length} linked`"></span>
    </div>

    <p class="mb-4 text-xs text-archive-gray">Search existing people and attach their role on this campaign. You can create a new profile without leaving this page.</p>

    <div class="mb-4 space-y-3" x-show="credits.length > 0">
        <template x-for="(credit, index) in credits" :key="credit.key">
            <div class="flex flex-wrap items-center gap-3 border border-archive-border bg-archive-light p-3">
                <input type="hidden" :name="`people_credits[${index}][person_id]`" :value="credit.person_id">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <img :src="credit.photo_url" alt="" class="h-10 w-10 rounded-full object-cover">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium" x-text="credit.name"></p>
                        <input
                            type="text"
                            :name="`people_credits[${index}][role]`"
                            x-model="credit.role"
                            placeholder="Role on this campaign"
                            class="input-field mt-1 text-xs"
                        >
                    </div>
                </div>
                <button type="button" @click="removeCredit(credit)" class="text-xs text-archive-gray hover:text-archive-black">Remove</button>
            </div>
        </template>
    </div>

    <div class="relative">
        <input
            type="text"
            x-model="query"
            @input.debounce.250ms="searchPeople()"
            @focus="if (canSearch) searchPeople()"
            @keydown="onKeydown($event)"
            placeholder="Search people by name..."
            class="input-field"
            autocomplete="off"
        >

        <div
            x-show="open && (results.length > 0 || canSearch)"
            x-cloak
            class="absolute left-0 right-0 top-full z-30 mt-1 max-h-56 overflow-y-auto border border-archive-border bg-white shadow-sm"
        >
            <template x-for="person in results" :key="person.id">
                <button
                    type="button"
                    @mousedown.prevent="selectPerson(person)"
                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm hover:bg-archive-light"
                >
                    <img :src="person.photo_url" alt="" class="h-8 w-8 rounded-full object-cover">
                    <span>
                        <span x-text="person.name"></span>
                        <span class="block text-xs text-archive-gray" x-text="person.position"></span>
                    </span>
                </button>
            </template>

            <button
                type="button"
                x-show="canSearch && !loading"
                @mousedown.prevent="openCreateModal()"
                class="block w-full border-t border-archive-border px-4 py-2.5 text-left text-sm hover:bg-archive-light"
            >
                Create "<span x-text="query.trim()"></span>"
            </button>

            <p x-show="loading" class="px-4 py-3 text-xs text-archive-gray">Searching...</p>
        </div>
    </div>

    @error('people_credits')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    @error('people_credits.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror

    <div
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Create person"
    >
        <div class="w-full max-w-md border border-archive-border bg-white p-6 shadow-lg" @click.away="closeCreateModal()">
            <h3 class="font-display text-lg">Create Person</h3>
            <p class="mt-1 text-xs text-archive-gray">New profiles require admin approval before appearing publicly unless you are an admin.</p>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="section-label mb-2 block">Name *</label>
                    <input type="text" x-model="modalName" class="input-field" required>
                </div>
                <div>
                    <label class="section-label mb-2 block">Position / Role *</label>
                    <input type="text" x-model="modalPosition" class="input-field" placeholder="Director, Editor, etc." required>
                </div>
                <div>
                    <label class="section-label mb-2 block">Photo (optional)</label>
                    <input type="file" accept=".jpg,.jpeg,.png,.webp" @change="onModalPhotoChange($event)" class="input-field">
                </div>
                <p x-show="modalError" class="text-sm text-red-600" x-text="modalError"></p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="closeCreateModal()" class="btn-outline text-xs">Cancel</button>
                <button type="button" @click="createPerson()" :disabled="modalSaving" class="btn-primary text-xs">
                    <span x-text="modalSaving ? 'Saving...' : 'Create & Attach'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
