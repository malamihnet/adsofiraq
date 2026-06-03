@php
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    $peopleStoreUrl = $isAdmin ? route('admin.api.people.store') : route('api.people.store');
    $positionsUrl = $isAdmin ? route('admin.api.positions.index') : route('api.positions.index');
    $positionsStoreUrl = $isAdmin ? route('admin.api.positions.store') : route('api.positions.store');
@endphp

<div
    id="credits-mention-create-modal"
    class="fixed inset-0 z-[100000] hidden items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="credits-mention-create-title"
    aria-hidden="true"
    data-people-store-url="{{ $peopleStoreUrl }}"
    data-positions-url="{{ $positionsUrl }}"
    data-positions-store-url="{{ $positionsStoreUrl }}"
    data-is-admin="{{ $isAdmin ? '1' : '0' }}"
>
    <div class="absolute inset-0 bg-black/40" data-credits-mention-modal-close></div>
    <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-xl border border-neutral-200 bg-white p-6 shadow-xl">
        <h3 id="credits-mention-create-title" class="font-display text-lg font-medium text-archive-black">
            Create person profile
        </h3>
        <p class="mt-1 text-xs text-archive-gray">Add this person to credits. Profile can be reviewed before appearing publicly.</p>
        <p class="mt-1 font-mono text-[10px] text-archive-gray break-all">POST {{ $peopleStoreUrl }}</p>

        <form id="credits-mention-create-form" class="mt-5 space-y-4" novalidate>
            <div>
                <label class="section-label mb-1 block text-xs" for="credits-mention-create-name">Full name</label>
                <input
                    type="text"
                    id="credits-mention-create-name"
                    name="name"
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
                <select id="credits-mention-create-position" name="position_id" class="input-field text-sm">
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

            <div
                id="credits-mention-create-error"
                class="hidden rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800 whitespace-pre-wrap"
                role="alert"
            ></div>
            <p id="credits-mention-create-success" class="hidden text-sm text-green-700"></p>

            <div class="flex justify-end gap-2 border-t border-neutral-100 pt-4">
                <button type="button" data-credits-mention-modal-close class="rounded border border-archive-border px-4 py-2 text-sm hover:bg-neutral-50">
                    Cancel
                </button>
                <button
                    type="button"
                    id="credits-mention-create-save"
                    data-create-person-save
                    class="btn-primary text-xs"
                >
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    if (!window.__mentionCreateProfileInlineBound) {
        window.__mentionCreateProfileInlineBound = true;

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-mention-create-profile]');
            if (!btn) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            console.log('[mentions] create profile clicked', btn.dataset.name);

            if (typeof window.openCreatePersonModal === 'function') {
                window.openCreatePersonModal(btn.dataset.name || '');
                return;
            }

            var modal = document.getElementById('credits-mention-create-modal');
            var nameInput = document.getElementById('credits-mention-create-name');
            if (modal && nameInput) {
                nameInput.value = btn.dataset.name || '';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');
            }
        }, true);
    }

    if (window.__creditsMentionInlineSaveBound) {
        return;
    }
    window.__creditsMentionInlineSaveBound = true;

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function showModalError(message) {
        const box = document.getElementById('credits-mention-create-error');
        if (!box) {
            alert(message);
            return;
        }
        box.textContent = message;
        box.classList.remove('hidden');
        box.scrollIntoView({ block: 'nearest' });
    }

    function clearModalMessages() {
        const error = document.getElementById('credits-mention-create-error');
        const success = document.getElementById('credits-mention-create-success');
        if (error) {
            error.textContent = '';
            error.classList.add('hidden');
        }
        if (success) {
            success.textContent = '';
            success.classList.add('hidden');
        }
    }

    function closeModal() {
        const modal = document.getElementById('credits-mention-create-modal');
        if (!modal) {
            return;
        }
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
    }

    function showToast(message) {
        const existing = document.getElementById('credits-mention-inline-toast');
        if (existing) {
            existing.remove();
        }
        const toast = document.createElement('div');
        toast.id = 'credits-mention-inline-toast';
        toast.setAttribute('role', 'status');
        toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:100002;padding:10px 18px;background:#171717;color:#fff;font-size:13px;border-radius:8px;';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(function () { toast.remove(); }, 3200);
    }

    function insertPersonIntoCredits(person) {
        const textarea = document.getElementById('credits') || document.querySelector('textarea[name="credits"]');
        const hidden = document.getElementById('credits_mentions_json') || document.querySelector('input[name="credits_mentions_json"]');
        if (!textarea || !person?.name) {
            return;
        }

        const draft = window.__creditsMentionDraft || {};
        let start = typeof draft.mentionStart === 'number' ? draft.mentionStart : null;
        let end = typeof draft.selectionEnd === 'number' ? draft.selectionEnd : textarea.selectionStart;

        if (start === null) {
            const cursor = textarea.selectionStart ?? textarea.value.length;
            const before = textarea.value.slice(0, cursor);
            const match = before.match(/@([^\n@]*)$/);
            if (match) {
                start = cursor - match[0].length;
            } else {
                start = cursor;
            }
            end = cursor;
        }

        const token = '@' + person.name;
        const beforeText = textarea.value.slice(0, start);
        const afterText = textarea.value.slice(end);
        textarea.value = beforeText + token + ' ' + afterText;

        let mentions = [];
        if (hidden?.value) {
            try {
                mentions = JSON.parse(hidden.value);
            } catch (e) {
                mentions = [];
            }
        }
        if (!Array.isArray(mentions)) {
            mentions = [];
        }
        if (!mentions.some(function (m) { return m.person_id === person.id; })) {
            mentions.push({
                person_id: person.id,
                name: person.name,
                role: person.position || 'Credit',
            });
        }
        if (hidden) {
            hidden.value = JSON.stringify(mentions);
        }

        const pos = beforeText.length + token.length + 1;
        textarea.focus();
        textarea.setSelectionRange(pos, pos);
        textarea.dispatchEvent(new Event('input', { bubbles: true }));

        window.__creditsMentionDraft = null;
    }

    async function saveCreatePerson() {
        const modal = document.getElementById('credits-mention-create-modal');
        const nameInput = document.getElementById('credits-mention-create-name');
        const positionSelect = document.getElementById('credits-mention-create-position');
        const photoInput = document.getElementById('credits-mention-create-photo');
        const saveBtn = document.querySelector('[data-create-person-save]');

        const endpoint = modal?.dataset.peopleStoreUrl || '{{ $peopleStoreUrl }}';
        const isAdmin = modal?.dataset.isAdmin === '1';
        const token = csrfToken();

        const name = nameInput?.value?.trim();
        const positionId = positionSelect?.value;

        if (!endpoint) {
            showModalError('Missing create profile URL.');
            return;
        }
        if (!token) {
            showModalError('CSRF token missing. Reload the page.');
            return;
        }
        if (!name) {
            showModalError('Full name is required.');
            return;
        }
        if (!positionId) {
            showModalError('Please select a position.');
            return;
        }

        clearModalMessages();
        if (saveBtn) {
            saveBtn.disabled = true;
        }

        const formData = new FormData();
        formData.append('name', name);
        formData.append('position_id', positionId);
        if (isAdmin) {
            formData.append('approve', '1');
        }
        if (photoInput?.files?.[0]) {
            formData.append('photo', photoInput.files[0]);
        }

        const payloadPreview = {
            name: name,
            position_id: positionId,
            approve: isAdmin ? '1' : null,
            photo: photoInput?.files?.[0]?.name || null,
        };

        console.log('[create-person-inline] save clicked');
        console.log('[create-person-inline] payload', payloadPreview);
        console.log('[create-person-inline] endpoint', endpoint);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token,
                },
                credentials: 'same-origin',
                body: formData,
            });

            const responseText = await response.text();
            console.log('[create-person-inline] response status', response.status);
            console.log('[create-person-inline] response body', responseText);

            let json = {};
            if (responseText) {
                try {
                    json = JSON.parse(responseText);
                } catch (e) {
                    throw new Error('Server returned non-JSON (' + response.status + '). ' + responseText.slice(0, 300));
                }
            }

            if (!response.ok) {
                let message = json.message || ('Request failed (' + response.status + ')');
                if (json.errors && typeof json.errors === 'object') {
                    message = Object.entries(json.errors)
                        .map(function (entry) {
                            return entry[0] + ': ' + [].concat(entry[1]).join(', ');
                        })
                        .join('\n') || message;
                }
                throw new Error(message);
            }

            const person = json.person || json.data;
            if (!person?.id) {
                throw new Error('Server response missing person id.');
            }

            const success = document.getElementById('credits-mention-create-success');
            if (success) {
                success.textContent = 'Profile saved.';
                success.classList.remove('hidden');
            }

            insertPersonIntoCredits(person);
            closeModal();
            showToast('Profile created and added to credits.');
        } catch (error) {
            const message = error?.message || 'Could not create profile';
            showModalError(message);
            console.log('[create-person-inline] error', message);
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
            }
        }
    }

    document.addEventListener('click', function (e) {
        const button = e.target.closest('[data-create-person-save]');
        if (!button) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        saveCreatePerson();
    }, true);
})();
</script>
@endpush
