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

function openCreatePersonModalGlobal(name) {
    const trimmed = (name || '').trim();

    if (! trimmed) {
        return;
    }

    const ctx = window.__creditsMentionActive;
    const modal = getCreatePersonModal();

    if (! modal) {
        showToast('Sign in to create a person profile.');
        log('create profile blocked: modal not found');

        return;
    }

    if (ctx) {
        window.__creditsMentionDraft = {
            mentionStart: ctx.mentionStart,
            selectionEnd: ctx.selectionEnd,
        };
    }

    modal.open({
        name: trimmed,
        storeUrl: ctx?.peopleStoreUrl || modal.defaultStoreUrl,
        isAdmin: ctx?.isAdmin ?? modal.isAdmin,
        onSaved: (person) => {
            ctx?.onPersonSelected?.(person);
            showToast('Profile created and added to credits.');
        },
    });

    ctx?.hideDropdown?.();
}

function registerMentionCreateProfileClick() {
    if (window.__mentionCreateProfileClickBound) {
        return;
    }

    window.__mentionCreateProfileClickBound = true;

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-mention-create-profile]');

        if (! btn) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        console.log('[mentions] create profile clicked', btn.dataset.name);
        openCreatePersonModalGlobal(btn.dataset.name || '');
    }, true);
}

window.openCreatePersonModal = openCreatePersonModalGlobal;
registerMentionCreateProfileClick();

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

    const hasCreateRow = () => query.trim() !== '';

    const navigableCount = () => results.length + (hasCreateRow() ? 1 : 0);

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

        if (hasCreateRow()) {
            const createIndex = results.length;
            dropdown.appendChild(createCreateRow(
                query.trim(),
                activeIndex === createIndex,
                () => {
                    activeIndex = createIndex;
                    renderDropdown();
                },
            ));
        }

        if (results.length === 0 && ! hasCreateRow()) {
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
            activeIndex = results.length > 0 ? 0 : (hasCreateRow() ? 0 : -1);
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

                return;
            }

            if (activeIndex === results.length && hasCreateRow()) {
                openCreatePersonModalGlobal(query.trim());
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

        const modalEl = document.getElementById('credits-mention-create-modal');

        if (modalEl?.contains(event.target)) {
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

function createCreateRow(name, isActive, onHover) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.setAttribute('role', 'option');
    btn.setAttribute('data-mention-create-profile', 'true');
    btn.dataset.name = name;
    btn.style.cssText = `${rowButtonStyle(isActive)}border-top:1px solid #f0f0f0;margin-top:2px;pointer-events:auto;cursor:pointer`;

    btn.innerHTML = `
        <span style="width:32px;height:32px;border-radius:50%;background:#f5f5f5;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:500;color:#525252;flex-shrink:0;line-height:1;pointer-events:none">+</span>
        <span style="min-width:0;flex:1;pointer-events:none">
            <span style="display:block;font-size:13px;color:#737373">Create profile</span>
            <span style="display:block;font-size:14px;font-weight:600;color:#171717;margin-top:1px">${escapeHtml(name)}</span>
        </span>
    `;

    btn.addEventListener('mouseenter', onHover);

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

        this.positionSelect.innerHTML = '<option value="">Loading…</option>';

        try {
            const response = await fetch(this.positionsUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (! response.ok) {
                throw new Error('Could not load positions');
            }

            const json = await response.json();
            this.allPositions = json.data || [];
            this.categoryLabels = json.categories || {};
            this.positionsLoaded = true;
            this.renderPositionOptions(this.positionSearch?.value?.trim() || '');
        } catch {
            this.positionSelect.innerHTML = '<option value="">Failed to load positions</option>';
        }
    }

    renderPositionOptions(filter = '') {
        if (! this.positionSelect) {
            return;
        }

        const previous = this.positionSelect.value;
        const needle = filter.toLowerCase();
        const filtered = this.allPositions.filter((position) => {
            if (needle === '') {
                return true;
            }

            return position.name.toLowerCase().includes(needle)
                || (position.category_label || '').toLowerCase().includes(needle);
        });

        this.positionSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = filtered.length === 0
            ? (needle ? 'No positions match your search' : 'Select position')
            : 'Select position';
        this.positionSelect.appendChild(placeholder);

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
            optgroup.label = this.categoryLabels[categoryKey]
                || items[0].category_label
                || categoryKey;

            items.forEach((position) => {
                const option = document.createElement('option');
                option.value = String(position.id);
                option.textContent = position.name;
                optgroup.appendChild(option);
            });

            this.positionSelect.appendChild(optgroup);
        });

        if (previous && [...this.positionSelect.options].some((option) => option.value === previous)) {
            this.positionSelect.value = previous;
        }
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
