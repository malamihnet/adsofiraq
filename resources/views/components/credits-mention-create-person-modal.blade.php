@php
    $positionsUrl = auth()->check() && auth()->user()->isAdmin()
        ? route('admin.api.positions.index')
        : route('api.positions.index');
    $positionsStoreUrl = auth()->check() && auth()->user()->isAdmin()
        ? route('admin.api.positions.store')
        : route('api.positions.store');
@endphp

<div
    id="credits-mention-create-modal"
    class="fixed inset-0 z-[100000] hidden items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="credits-mention-create-title"
    aria-hidden="true"
    data-positions-url="{{ $positionsUrl }}"
    data-positions-store-url="{{ $positionsStoreUrl }}"
>
    <div class="absolute inset-0 bg-black/40" data-credits-mention-modal-close></div>
    <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-xl border border-neutral-200 bg-white p-6 shadow-xl">
        <h3 id="credits-mention-create-title" class="font-display text-lg font-medium text-archive-black">
            Create person profile
        </h3>
        <p class="mt-1 text-xs text-archive-gray">Add this person to credits. Profile can be reviewed before appearing publicly.</p>

        <form id="credits-mention-create-form" class="mt-5 space-y-4">
            <div>
                <label class="section-label mb-1 block text-xs" for="credits-mention-create-name">Full name</label>
                <input
                    type="text"
                    id="credits-mention-create-name"
                    name="name"
                    required
                    class="input-field text-sm"
                    autocomplete="name"
                >
            </div>

            <div>
                <label class="section-label mb-1 block text-xs" for="credits-mention-position-search">Position</label>
                <input
                    type="search"
                    id="credits-mention-position-search"
                    class="input-field mb-2 text-sm"
                    placeholder="Search positions…"
                    autocomplete="off"
                >
                <select id="credits-mention-create-position" name="position_id" required class="input-field max-h-48 text-sm" size="8">
                    <option value="">Loading positions…</option>
                </select>
            </div>

            <div class="hidden space-y-2 rounded-lg border border-neutral-200 bg-neutral-50 p-3" id="credits-mention-new-position-wrap">
                <label class="section-label mb-1 block text-xs" for="credits-mention-new-position-name">New position name</label>
                <div class="flex gap-2">
                    <input
                        type="text"
                        id="credits-mention-new-position-name"
                        class="input-field flex-1 text-sm"
                        placeholder="e.g. Director"
                    >
                    <button type="button" id="credits-mention-add-position-btn" class="btn-primary shrink-0 text-xs">
                        Add
                    </button>
                </div>
            </div>

            <button
                type="button"
                id="credits-mention-toggle-position-btn"
                class="text-xs text-archive-gray underline hover:text-archive-black"
            >
                + Add new position
            </button>

            <div>
                <label class="section-label mb-1 block text-xs" for="credits-mention-create-photo">Profile image (optional)</label>
                <input
                    type="file"
                    id="credits-mention-create-photo"
                    name="photo"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    class="input-field text-sm"
                >
            </div>

            <p id="credits-mention-create-error" class="hidden text-sm text-red-600"></p>
            <p id="credits-mention-create-success" class="hidden text-sm text-green-700"></p>

            <div class="flex justify-end gap-2 border-t border-neutral-100 pt-4">
                <button type="button" data-credits-mention-modal-close class="rounded border border-archive-border px-4 py-2 text-sm hover:bg-neutral-50">
                    Cancel
                </button>
                <button type="submit" id="credits-mention-create-save" class="btn-primary text-xs">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>
