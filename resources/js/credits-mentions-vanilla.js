/**
 * Vanilla JS credits @mention autocomplete (bundled in app.js).
 */

const MENTIONS_DEBUG = document.querySelector('[data-mentions-debug="1"]') !== null;

function log(...args) {
    if (MENTIONS_DEBUG) {
        console.log('[mentions]', ...args);
    }
}

function createPersonLog(...args) {
    console.log('[create-person]', ...args);
}

export function initCreditsMentions() {
    window.__creditsMentionsScriptLoaded = true;

    document.querySelectorAll('.credits-mentions-field').forEach((root) => {
        bindCreditsMentionsRoot(root);
    });

    document.querySelectorAll('#credits, textarea[name="credits"]').forEach((textarea) => {
        if (textarea.dataset.mentionsBound === 'true') {
            return;
        }

        const root = textarea.closest('.credits-mentions-field');

        if (root) {
            bindCreditsMentionsRoot(root);

            return;
        }

        setupCreditsMentionsField(textarea, null);
    });
}

export const initCreditsMentionsVanilla = initCreditsMentions;

function bindCreditsMentionsRoot(root) {
    const textarea = findCreditsTextarea(root);

    if (! textarea || textarea.dataset.mentionsBound === 'true') {
        return;
    }

    setupCreditsMentionsField(textarea, root);
}

function findCreditsTextarea(root) {
    if (root instanceof HTMLTextAreaElement) {
        return root.matches('#credits, textarea[name="credits"]') ? root : null;
    }

    return root.querySelector('#credits') || root.querySelector('textarea[name="credits"]');
}

function setupCreditsMentionsField(textarea, root) {
    if (textarea.dataset.mentionsBound === 'true') {
        return;
    }

    textarea.dataset.mentionsBound = 'true';

    if (root) {
        root.dataset.mentionsBound = 'true';
    }

    const searchUrl = root?.dataset.peopleSearchUrl
        || textarea.closest('[data-people-search-url]')?.dataset.peopleSearchUrl
        || '/api/people/search';

    const peopleStoreUrl = root?.dataset.peopleStoreUrl
        || textarea.closest('[data-people-store-url]')?.dataset.peopleStoreUrl
        || '/api/people';

    const positionsUrl = root?.dataset.positionsUrl
        || textarea.closest('[data-positions-url]')?.dataset.positionsUrl
        || '/api/positions';

    const isAdmin = (root?.dataset.isAdmin || textarea.closest('[data-is-admin]')?.dataset.isAdmin) === '1';

    const hiddenInput = (root || textarea.closest('.credits-mentions-field'))?.querySelector('input[name="credits_mentions_json"]')
        || document.querySelector('#credits_mentions_json')
        || document.querySelector('input[name="credits_mentions_json"]');

    let mentions = parseMentionsJson(hiddenInput?.value || '[]');
    let mentionStart = null;
    let query = '';
    let results = [];
    let loading = false;
    let debounceTimer = null;
    let activeIndex = -1;
    let dropdownOpen = false;
    let creatingPerson = false;

    const dropdown = document.createElement('div');
    dropdown.setAttribute('data-credits-mentions-dropdown', 'true');
    dropdown.setAttribute('data-mention-dropdown', 'true');
    dropdown.setAttribute('role', 'listbox');
    dropdown.className = 'credits-mentions-dropdown';
    applyDropdownBaseStyles(dropdown);
    document.body.appendChild(dropdown);

    const updateActiveContext = () => {
        window.__creditsMentionActive = {
            textarea,
            mentionStart,
            selectionEnd: textarea.selectionStart,
            query,
            peopleStoreUrl,
            isAdmin,
            hideDropdown,
            onPersonSelected: (person) => selectPerson(person),
        };
    };

    const syncHidden = () => {
        if (! hiddenInput) {
            return;
        }

        const seen = new Set();
        const payload = mentions
            .filter((m) => {
                if (seen.has(m.person_id)) {
                    return false;
                }

                seen.add(m.person_id);

                return textarea.value.includes(`@${m.name}`);
            })
            .map((m) => ({
                person_id: m.person_id,
                name: m.name,
                role: m.role || 'Credit',
            }));

        hiddenInput.value = JSON.stringify(payload);
    };

    const positionDropdown = () => {
        const rect = textarea.getBoundingClientRect();

        dropdown.style.left = `${rect.left}px`;
        dropdown.style.top = `${rect.bottom + 4}px`;
        dropdown.style.width = `${Math.max(rect.width, 280)}px`;
    };

    const dismissMention = () => {
        mentionStart = null;
        query = '';
        results = [];
        loading = false;
        activeIndex = -1;
        creatingPerson = false;

        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }

        hideDropdown();
    };

    const hideDropdown = () => {
        dropdown.style.setProperty('display', 'none', 'important');
        dropdownOpen = false;
        activeIndex = -1;
    };

    const showDropdown = () => {
        positionDropdown();
        dropdown.style.setProperty('display', 'block', 'important');
        dropdownOpen = true;
        updateActiveContext();
    };

    const showInlineCreateForm = () => query.trim() !== '' && results.length === 0 && ! loading;

    const navigableCount = () => results.length;

    const roleBeforeCursor = () => {
        const cursor = textarea.selectionStart;
        const lineStart = textarea.value.lastIndexOf('\n', cursor - 1) + 1;
        const line = textarea.value.slice(lineStart, cursor);
        const match = line.match(/^\s*([^:@\n]{2,60})\s*:\s*@?[^@]*$/);

        return match ? match[1].trim() : '';
    };

    const detectMention = () => {
        const cursor = textarea.selectionStart ?? textarea.value.length;
        const before = textarea.value.slice(0, cursor);
        const match = before.match(/@([^\n@]*)$/);

        if (! match) {
            return false;
        }

        mentionStart = cursor - match[0].length;
        query = match[1];
        log('input detected', query);

        return true;
    };

    const renderDropdown = () => {
        if (creatingPerson) {
            showDropdown();

            return;
        }

        dropdown.innerHTML = '';

        if (loading) {
            dropdown.appendChild(createMessage('Searching…'));
            showDropdown();

            return;
        }

        if (query.trim() === '' && results.length === 0) {
            dropdown.appendChild(createMessage('Type a name after @'));
            showDropdown();

            return;
        }

        results.forEach((person, index) => {
            dropdown.appendChild(createResultRow(
                person,
                index === activeIndex,
                () => selectPerson(person),
                () => {
                    activeIndex = index;
                    renderDropdown();
                },
            ));
        });

        if (showInlineCreateForm()) {
            dropdown.style.maxHeight = '420px';
            dropdown.appendChild(createInlineCreateForm({
                name: query.trim(),
                positionsUrl,
                peopleStoreUrl,
                isAdmin,
                onSuccess: (person) => {
                    selectPerson(person);
                    showToast('Profile created and added to credits.');
                },
                onCreatingChange: (isCreating) => {
                    creatingPerson = isCreating;
                },
            }));
        } else {
            dropdown.style.maxHeight = '280px';
        }

        if (results.length === 0 && ! showInlineCreateForm()) {
            dropdown.appendChild(createMessage('No people found'));
        }

        showDropdown();
    };

    const selectPerson = (person) => {
        const role = roleBeforeCursor();
        const token = `@${person.name}`;
        const start = mentionStart ?? textarea.selectionStart;
        const end = textarea.selectionStart;
        const before = textarea.value.slice(0, start);
        const after = textarea.value.slice(end);

        textarea.value = `${before}${token} ${after}`;

        if (! mentions.some((m) => m.person_id === person.id)) {
            mentions.push({
                person_id: person.id,
                name: person.name,
                role: role || person.position || 'Credit',
            });
        }

        syncHidden();
        dismissMention();

        const pos = before.length + token.length + 1;
        textarea.focus();
        textarea.setSelectionRange(pos, pos);

        log('selected', person.name);
    };

    const buildSearchUrl = (q) => {
        try {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', q);

            return url.toString();
        } catch {
            const separator = searchUrl.includes('?') ? '&' : '?';

            return `${searchUrl}${separator}q=${encodeURIComponent(q)}`;
        }
    };

    const fetchPeople = async (searchQuery = query) => {
        const q = searchQuery;
        const url = buildSearchUrl(q);

        log('fetching', url);
        loading = true;
        query = q;
        renderDropdown();

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (! response.ok) {
                results = [];

                return;
            }

            const json = await response.json();
            results = json.data || [];
            log('results', results.length);
        } catch (error) {
            if (MENTIONS_DEBUG) {
                console.error('[mentions] fetch error', error);
            }

            results = [];
        } finally {
            loading = false;
            activeIndex = results.length > 0 ? 0 : -1;
            renderDropdown();
        }
    };

    const scheduleSearch = () => {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        debounceTimer = setTimeout(() => fetchPeople(), 200);
    };

    const handleMentionInput = () => {
        if (detectMention()) {
            scheduleSearch();
        } else {
            dismissMention();
        }

        const seen = new Set();
        mentions = mentions.filter((m) => {
            if (! textarea.value.includes(`@${m.name}`)) {
                return false;
            }

            if (seen.has(m.person_id)) {
                return false;
            }

            seen.add(m.person_id);

            return true;
        });
        syncHidden();
    };

    textarea.addEventListener('input', handleMentionInput);
    textarea.addEventListener('keyup', handleMentionInput);
    textarea.addEventListener('click', handleMentionInput);

    textarea.addEventListener('keydown', (event) => {
        if (! dropdownOpen) {
            return;
        }

        const total = navigableCount();

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            activeIndex = activeIndex < 0 ? 0 : Math.min(activeIndex + 1, total - 1);
            renderDropdown();

            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            activeIndex = activeIndex <= 0 ? total - 1 : activeIndex - 1;
            renderDropdown();

            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();

            if (activeIndex >= 0 && activeIndex < results.length) {
                selectPerson(results[activeIndex]);
            }

            return;
        }

        if (event.key === 'Escape') {
            dismissMention();
        }
    });

    window.addEventListener('scroll', () => {
        if (dropdownOpen) {
            positionDropdown();
        }
    }, true);

    window.addEventListener('resize', () => {
        if (dropdownOpen) {
            positionDropdown();
        }
    });

    document.addEventListener('click', (event) => {
        if (! dropdownOpen) {
            return;
        }

        if (event.target.closest('[data-mention-dropdown]')) {
            return;
        }

        if (textarea.contains(event.target)) {
            return;
        }

        dismissMention();
    });

    const form = textarea.closest('form');

    if (form) {
        form.addEventListener('submit', syncHidden);
    }

    syncHidden();
    log('ready', searchUrl);
}

function applyDropdownBaseStyles(dropdown) {
    dropdown.style.cssText = [
        'display:none',
        'position:fixed',
        'z-index:999999',
        'pointer-events:auto',
        'max-height:280px',
        'overflow-y:auto',
        'overflow-x:hidden',
        'background:#fff',
        'border:1px solid #e5e5e5',
        'border-radius:12px',
        'box-shadow:0 4px 24px rgba(0,0,0,0.1), 0 2px 8px rgba(0,0,0,0.06)',
        'padding:4px 0',
        'font-family:inherit',
    ].join(';');
}

function createMessage(text) {
    const el = document.createElement('p');
    el.style.cssText = 'margin:0;padding:10px 14px;font-size:13px;color:#737373;line-height:1.4';
    el.textContent = text;

    return el;
}

function createInlineCreateForm({ name, positionsUrl, peopleStoreUrl, isAdmin, onSuccess, onCreatingChange }) {
    const wrap = document.createElement('div');
    wrap.setAttribute('data-mention-inline-create', 'true');
    wrap.style.cssText = 'padding:12px 14px;border-top:1px solid #f0f0f0;';

    const title = document.createElement('p');
    title.style.cssText = 'margin:0 0 10px;font-size:13px;font-weight:600;color:#171717';
    title.textContent = 'Create new profile';
    wrap.appendChild(title);

    const nameLabel = document.createElement('label');
    nameLabel.style.cssText = 'display:block;margin-bottom:4px;font-size:11px;font-weight:500;color:#525252';
    nameLabel.textContent = 'Full name';
    wrap.appendChild(nameLabel);

    const nameInput = document.createElement('input');
    nameInput.type = 'text';
    nameInput.value = name;
    nameInput.autocomplete = 'name';
    nameInput.style.cssText = inlineFieldStyle();
    wrap.appendChild(nameInput);

    const positionLabel = document.createElement('label');
    positionLabel.style.cssText = 'display:block;margin:10px 0 4px;font-size:11px;font-weight:500;color:#525252';
    positionLabel.textContent = 'Position';
    wrap.appendChild(positionLabel);

    const positionSelect = document.createElement('select');
    positionSelect.style.cssText = inlineFieldStyle();
    positionSelect.innerHTML = '<option value="">Loading…</option>';
    wrap.appendChild(positionSelect);

    const photoLabel = document.createElement('label');
    photoLabel.style.cssText = 'display:block;margin:10px 0 4px;font-size:11px;font-weight:500;color:#525252';
    photoLabel.textContent = 'Profile image (optional)';
    wrap.appendChild(photoLabel);

    const photoInput = document.createElement('input');
    photoInput.type = 'file';
    photoInput.accept = '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp';
    photoInput.style.cssText = inlineFieldStyle();
    wrap.appendChild(photoInput);

    const errorEl = document.createElement('div');
    errorEl.setAttribute('role', 'alert');
    errorEl.style.cssText = 'display:none;margin-top:10px;padding:8px 10px;border-radius:8px;border:1px solid #fca5a5;background:#fef2f2;font-size:12px;color:#991b1b;white-space:pre-wrap';
    wrap.appendChild(errorEl);

    const createBtn = document.createElement('button');
    createBtn.type = 'button';
    createBtn.textContent = 'Create';
    createBtn.style.cssText = 'margin-top:12px;width:100%;padding:8px 12px;border:none;border-radius:8px;background:#171717;color:#fff;font-size:13px;font-weight:500;cursor:pointer';
    wrap.appendChild(createBtn);

    const showError = (message) => {
        errorEl.textContent = message;
        errorEl.style.display = message ? 'block' : 'none';
    };

    loadPositionsForSelect(positionSelect, positionsUrl).catch(() => {
        positionSelect.innerHTML = '<option value="">Failed to load positions</option>';
    });

    createBtn.addEventListener('mousedown', async (event) => {
        event.preventDefault();
        event.stopPropagation();

        const personName = nameInput.value.trim();
        const positionId = positionSelect.value;
        const endpoint = peopleStoreUrl;
        const token = csrfToken();

        showError('');

        if (! endpoint) {
            showError('Create profile URL is missing. Reload the page and try again.');

            return;
        }

        if (! token) {
            showError('CSRF token missing. Reload the page and try again.');

            return;
        }

        if (! personName) {
            showError('Full name is required.');

            return;
        }

        if (! positionId) {
            showError('Please select a position from the list.');

            return;
        }

        createBtn.disabled = true;
        createBtn.textContent = 'Creating…';
        onCreatingChange?.(true);

        const formData = new FormData();
        formData.append('name', personName);
        formData.append('position_id', positionId);

        if (isAdmin) {
            formData.append('approve', '1');
        }

        if (photoInput.files?.[0]) {
            formData.append('photo', photoInput.files[0]);
        }

        createPersonLog('inline create', { name: personName, position_id: positionId, endpoint });

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
            let json = {};

            if (responseText !== '') {
                try {
                    json = JSON.parse(responseText);
                } catch {
                    throw new Error(`Server returned non-JSON (${response.status}). ${responseText.slice(0, 300)}`);
                }
            }

            if (! response.ok) {
                let message = json.message || `Request failed (${response.status})`;

                if (json.errors && typeof json.errors === 'object') {
                    const fieldMessages = Object.entries(json.errors)
                        .map(([field, messages]) => `${field}: ${[].concat(messages).join(', ')}`)
                        .join('\n');

                    message = fieldMessages || message;
                }

                throw new Error(message);
            }

            const person = json.person || json.data;

            if (! person?.id) {
                throw new Error('Server response missing person id.');
            }

            createPersonLog('inline person created', person);
            onCreatingChange?.(false);
            onSuccess?.(person);
        } catch (error) {
            const message = error?.message || 'Could not create profile';
            showError(message);
            createPersonLog('inline create failed', message);
            createBtn.disabled = false;
            createBtn.textContent = 'Create';
            onCreatingChange?.(false);
        }
    });

    return wrap;
}

function inlineFieldStyle() {
    return [
        'display:block',
        'width:100%',
        'box-sizing:border-box',
        'padding:7px 10px',
        'border:1px solid #e5e5e5',
        'border-radius:8px',
        'font-size:13px',
        'font-family:inherit',
        'background:#fff',
        'color:#171717',
    ].join(';');
}

function createResultRow(person, isActive, onSelect, onHover) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.setAttribute('role', 'option');
    btn.style.cssText = `${rowButtonStyle(isActive)}pointer-events:auto;cursor:pointer`;

    const avatar = person.photo_url
        ? `<img src="${escapeAttr(person.photo_url)}" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;background:#f5f5f5;flex-shrink:0" onerror="this.style.visibility='hidden'">`
        : `<span style="width:32px;height:32px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:13px;color:#a3a3a3;flex-shrink:0">${escapeHtml((person.name || '?').charAt(0))}</span>`;

    btn.innerHTML = `
        ${avatar}
        <span style="min-width:0;flex:1">
            <span style="display:block;font-size:14px;font-weight:500;color:#171717;line-height:1.3">${escapeHtml(person.name)}</span>
            <span style="display:block;font-size:12px;color:#737373;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:1px">${escapeHtml(person.position || '')}</span>
        </span>
    `;

    btn.addEventListener('mouseenter', onHover);
    btn.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        onSelect();
    });

    return btn;
}

function rowButtonStyle(isActive) {
    return [
        'display:flex',
        'width:100%',
        'align-items:center',
        'gap:10px',
        'padding:8px 12px',
        'text-align:left',
        'border:none',
        'background:' + (isActive ? '#f5f5f5' : 'transparent'),
        'cursor:pointer',
        'transition:background 0.1s',
    ].join(';');
}

function showToast(message) {
    const existing = document.getElementById('credits-mention-toast');

    if (existing) {
        existing.remove();
    }

    const toast = document.createElement('div');
    toast.id = 'credits-mention-toast';
    toast.setAttribute('role', 'status');
    toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:100001;padding:10px 18px;background:#171717;color:#fff;font-size:13px;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,0.15)';
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => toast.remove(), 3200);
}

const POSITION_CATEGORY_ORDER = [
    'production',
    'camera_lighting',
    'art_styling',
    'post_production',
    'agency_creative',
    'brand_client',
    'other',
];

function renderPositionSelectOptions(select, allPositions, categoryLabels, filter = '') {
    if (! select) {
        return;
    }

    const previous = select.value;
    const needle = filter.toLowerCase();
    const filtered = allPositions.filter((position) => {
        if (needle === '') {
            return true;
        }

        return position.name.toLowerCase().includes(needle)
            || (position.category_label || '').toLowerCase().includes(needle);
    });

    select.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = filtered.length === 0
        ? (needle ? 'No positions match your search' : 'Select position')
        : 'Select position';
    select.appendChild(placeholder);

    const grouped = {};

    filtered.forEach((position) => {
        const key = position.category || 'other';

        if (! grouped[key]) {
            grouped[key] = [];
        }

        grouped[key].push(position);
    });

    POSITION_CATEGORY_ORDER.forEach((categoryKey) => {
        const items = grouped[categoryKey];

        if (! items?.length) {
            return;
        }

        const optgroup = document.createElement('optgroup');
        optgroup.label = categoryLabels[categoryKey]
            || items[0].category_label
            || categoryKey;

        items.forEach((position) => {
            const option = document.createElement('option');
            option.value = String(position.id);
            option.textContent = position.name;
            optgroup.appendChild(option);
        });

        select.appendChild(optgroup);
    });

    if (previous && [...select.options].some((option) => option.value === previous)) {
        select.value = previous;
    }
}

async function loadPositionsForSelect(select, positionsUrl) {
    if (! select) {
        return null;
    }

    select.innerHTML = '<option value="">Loading…</option>';

    const response = await fetch(positionsUrl, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });

    if (! response.ok) {
        throw new Error('Could not load positions');
    }

    const json = await response.json();
    const allPositions = json.data || [];
    const categoryLabels = json.categories || {};

    renderPositionSelectOptions(select, allPositions, categoryLabels);

    return { allPositions, categoryLabels };
}

let createPersonModalInstance = null;

function getCreatePersonModal() {
    if (createPersonModalInstance) {
        return createPersonModalInstance;
    }

    const el = document.getElementById('credits-mention-create-modal');

    if (! el) {
        return null;
    }

    createPersonModalInstance = new CreatePersonModal(el);

    return createPersonModalInstance;
}

class CreatePersonModal {
    constructor(root) {
        this.root = root;
        this.form = root.querySelector('#credits-mention-create-form');
        this.nameInput = root.querySelector('#credits-mention-create-name');
        this.positionSelect = root.querySelector('#credits-mention-create-position');
        this.photoInput = root.querySelector('#credits-mention-create-photo');
        this.errorEl = root.querySelector('#credits-mention-create-error');
        this.successEl = root.querySelector('#credits-mention-create-success');
        this.saveBtn = root.querySelector('#credits-mention-create-save');
        this.newPositionWrap = root.querySelector('#credits-mention-new-position-wrap');
        this.newPositionInput = root.querySelector('#credits-mention-new-position-name');
        this.togglePositionBtn = root.querySelector('#credits-mention-toggle-position-btn');
        this.addPositionBtn = root.querySelector('#credits-mention-add-position-btn');
        this.positionSearch = root.querySelector('#credits-mention-position-search');
        this.positionsUrl = root.dataset.positionsUrl;
        this.positionsStoreUrl = root.dataset.positionsStoreUrl;
        this.defaultStoreUrl = root.dataset.peopleStoreUrl || '';
        this.onSaved = null;
        this.storeUrl = this.defaultStoreUrl;
        this.isAdmin = root.dataset.isAdmin === '1';
        this.positionsLoaded = false;
        this.allPositions = [];
        this.categoryLabels = {};

        this.positionSearch?.addEventListener('input', () => {
            this.renderPositionOptions(this.positionSearch?.value?.trim() || '');
        });

        root.querySelectorAll('[data-credits-mention-modal-close]').forEach((btn) => {
            btn.addEventListener('click', () => this.close());
        });

        this.togglePositionBtn?.addEventListener('click', () => {
            this.newPositionWrap?.classList.toggle('hidden');
        });

        this.addPositionBtn?.addEventListener('click', () => this.addPosition());

        this.form?.addEventListener('submit', (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.save();
        });

        this.saveBtn?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.save();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && ! this.root.classList.contains('hidden')) {
                this.close();
            }
        });
    }

    async open({ name, storeUrl, isAdmin, onSaved }) {
        this.storeUrl = storeUrl || this.defaultStoreUrl;
        this.isAdmin = isAdmin ?? this.isAdmin;
        this.onSaved = onSaved;
        createPersonLog('modal open', { storeUrl: this.storeUrl, isAdmin: this.isAdmin });
        this.clearMessages();

        if (this.nameInput) {
            this.nameInput.value = name;
        }

        if (this.photoInput) {
            this.photoInput.value = '';
        }

        this.newPositionWrap?.classList.add('hidden');

        if (this.newPositionInput) {
            this.newPositionInput.value = '';
        }

        if (this.positionSearch) {
            this.positionSearch.value = '';
        }

        this.root.classList.remove('hidden');
        this.root.classList.add('flex');
        this.root.setAttribute('aria-hidden', 'false');

        await this.loadPositions();
        this.nameInput?.focus();
    }

    close() {
        this.root.classList.add('hidden');
        this.root.classList.remove('flex');
        this.root.setAttribute('aria-hidden', 'true');
        this.onSaved = null;
    }

    clearMessages() {
        if (this.errorEl) {
            this.errorEl.classList.add('hidden');
            this.errorEl.textContent = '';
        }

        if (this.successEl) {
            this.successEl.classList.add('hidden');
            this.successEl.textContent = '';
        }
    }

    showError(message) {
        if (this.errorEl) {
            this.errorEl.textContent = message;
            this.errorEl.classList.remove('hidden');
            this.errorEl.scrollIntoView({ block: 'nearest' });
        }

        createPersonLog('error shown', message);
    }

    async loadPositions() {
        if (! this.positionSelect) {
            return;
        }

        try {
            const loaded = await loadPositionsForSelect(this.positionSelect, this.positionsUrl);

            if (loaded) {
                this.allPositions = loaded.allPositions;
                this.categoryLabels = loaded.categoryLabels;
                this.positionsLoaded = true;

                if (this.positionSearch?.value?.trim()) {
                    this.renderPositionOptions(this.positionSearch.value.trim());
                }
            }
        } catch {
            this.positionSelect.innerHTML = '<option value="">Failed to load positions</option>';
        }
    }

    renderPositionOptions(filter = '') {
        renderPositionSelectOptions(
            this.positionSelect,
            this.allPositions,
            this.categoryLabels,
            filter,
        );
    }

    async addPosition() {
        const name = this.newPositionInput?.value?.trim();

        if (! name) {
            this.showError('Enter a position name.');

            return;
        }

        this.addPositionBtn.disabled = true;

        try {
            const response = await fetch(this.positionsStoreUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ name, category: 'other' }),
            });

            if (! response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message || 'Could not add position');
            }

            const json = await response.json();
            const position = json.data;

            if (! this.positionsLoaded) {
                await this.loadPositions();
            } else {
                this.allPositions.push(position);
                this.renderPositionOptions(this.positionSearch?.value?.trim() || '');
            }

            this.positionSelect.value = String(position.id);
            this.newPositionWrap?.classList.add('hidden');

            if (this.newPositionInput) {
                this.newPositionInput.value = '';
            }
        } catch (error) {
            this.showError(error.message || 'Could not add position');
        } finally {
            this.addPositionBtn.disabled = false;
        }
    }

    async save() {
        createPersonLog('save clicked');

        const name = this.nameInput?.value?.trim();
        const positionId = this.positionSelect?.value;
        const endpoint = this.storeUrl || this.defaultStoreUrl;
        const token = csrfToken();

        if (! endpoint) {
            this.showError('Create profile URL is missing. Reload the page and try again.');

            return;
        }

        if (! token) {
            this.showError('CSRF token missing. Reload the page and try again.');

            return;
        }

        if (! name) {
            this.showError('Full name is required.');

            return;
        }

        if (! positionId) {
            this.showError('Please select a position from the list (or add a new one).');

            return;
        }

        this.clearMessages();

        if (this.saveBtn) {
            this.saveBtn.disabled = true;
        }

        const formData = new FormData();
        formData.append('name', name);
        formData.append('position_id', positionId);

        if (this.isAdmin) {
            formData.append('approve', '1');
        }

        if (this.photoInput?.files?.[0]) {
            formData.append('photo', this.photoInput.files[0]);
        }

        const payloadPreview = {
            name,
            position_id: positionId,
            approve: this.isAdmin ? '1' : null,
            photo: this.photoInput?.files?.[0]?.name || null,
        };

        createPersonLog('payload', payloadPreview);
        createPersonLog('endpoint', endpoint);

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
            createPersonLog('response status', response.status);
            createPersonLog('response body', responseText);

            let json = {};

            if (responseText !== '') {
                try {
                    json = JSON.parse(responseText);
                } catch (parseError) {
                    throw new Error(`Server returned non-JSON (${response.status}). ${responseText.slice(0, 300)}`);
                }
            }

            if (! response.ok) {
                let message = json.message || `Request failed (${response.status})`;

                if (json.errors && typeof json.errors === 'object') {
                    const fieldMessages = Object.entries(json.errors)
                        .map(([field, messages]) => `${field}: ${[].concat(messages).join(', ')}`)
                        .join('\n');

                    message = fieldMessages || message;
                }

                throw new Error(message);
            }

            const person = json.person || json.data;

            if (! person?.id) {
                throw new Error('Server response missing person id.');
            }

            createPersonLog('person created', person);

            if (this.successEl) {
                this.successEl.textContent = 'Profile saved.';
                this.successEl.classList.remove('hidden');
            }

            if (this.onSaved) {
                this.onSaved(person);
            }

            setTimeout(() => this.close(), 400);
        } catch (error) {
            const message = error?.message || 'Could not create profile';
            this.showError(message);
            createPersonLog('save failed', message);
        } finally {
            if (this.saveBtn) {
                this.saveBtn.disabled = false;
            }
        }
    }
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function parseMentionsJson(raw) {
    if (! raw || raw === '') {
        return [];
    }

    try {
        const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function escapeAttr(value) {
    return escapeHtml(value).replace(/'/g, '&#39;');
}

function bootCreditsMentions() {
    initCreditsMentions();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootCreditsMentions);
} else {
    bootCreditsMentions();
}

document.addEventListener('DOMContentLoaded', bootCreditsMentions);
