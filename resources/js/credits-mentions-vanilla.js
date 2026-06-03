/**
 * Vanilla JS credits @mention autocomplete (does not depend on Alpine).
 */
export function initCreditsMentionsVanilla() {
    const fields = document.querySelectorAll('.credits-mentions-field');

    fields.forEach((root) => {
        if (root.dataset.mentionsBound === 'true') {
            return;
        }

        root.dataset.mentionsBound = 'true';
        setupCreditsMentionsField(root);
    });

    const orphan = document.querySelector('#credits, textarea[name="credits"]');

    if (orphan && !orphan.closest('.credits-mentions-field[data-mentions-bound="true"]')) {
        const wrapper = orphan.closest('.credits-mentions-field') || orphan.parentElement;

        if (wrapper && wrapper.dataset.mentionsBound !== 'true') {
            wrapper.dataset.mentionsBound = 'true';
            setupCreditsMentionsField(wrapper, orphan);
        }
    }
}

function log(...args) {
    console.log('[mentions]', ...args);
}

function setupCreditsMentionsField(root, textareaOverride = null) {
    const textarea = textareaOverride
        || root.querySelector('#credits')
        || root.querySelector('textarea[name="credits"]');

    if (! textarea) {
        log('no textarea found in', root);

        return;
    }

    log('bound to textarea', textarea.id || textarea.name);

    const searchUrl = root.dataset.peopleSearchUrl || '/api/people/search';
    const hiddenInput = root.querySelector('input[name="credits_mentions_json"]')
        || root.querySelector('#credits_mentions_json');

    let mentions = parseMentionsJson(hiddenInput?.value || root.dataset.initialMentions || '[]');
    let mentionStart = null;
    let query = '';
    let results = [];
    let loading = false;
    let debounceTimer = null;
    let activeIndex = -1;

    const wrapper = textarea.closest('.relative') || root.querySelector('.relative') || root;
    wrapper.classList.add('relative');

    let dropdown = root.querySelector('[data-credits-mentions-dropdown]');

    if (! dropdown) {
        dropdown = document.createElement('div');
        dropdown.setAttribute('data-credits-mentions-dropdown', 'true');
        dropdown.className = 'credits-mentions-dropdown hidden absolute left-0 right-0 top-full z-[9999] mt-1 max-h-64 overflow-y-auto border border-archive-border bg-white shadow-lg';
        wrapper.appendChild(dropdown);
    }

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

    const hideDropdown = () => {
        dropdown.classList.add('hidden');
        activeIndex = -1;
    };

    const showDropdown = () => {
        dropdown.classList.remove('hidden');
    };

    const roleBeforeCursor = () => {
        const cursor = textarea.selectionStart;
        const lineStart = textarea.value.lastIndexOf('\n', cursor - 1) + 1;
        const line = textarea.value.slice(lineStart, cursor);
        const match = line.match(/^\s*([^:@\n]{2,60})\s*:\s*@?[^@]*$/);

        return match ? match[1].trim() : '';
    };

    const detectMention = () => {
        const cursor = textarea.selectionStart;
        const before = textarea.value.slice(0, cursor);
        const match = before.match(/@([^\n@]*)$/);

        if (! match) {
            mentionStart = null;
            query = '';
            hideDropdown();

            return false;
        }

        mentionStart = cursor - match[0].length;
        query = match[1];
        log('input detected');
        log('query:', query);

        return true;
    };

    const renderDropdown = () => {
        dropdown.innerHTML = '';

        if (loading) {
            const loadingEl = document.createElement('p');
            loadingEl.className = 'px-4 py-3 text-xs text-archive-gray';
            loadingEl.textContent = 'Searching...';
            dropdown.appendChild(loadingEl);
            showDropdown();

            return;
        }

        if (query.trim() === '') {
            const hint = document.createElement('p');
            hint.className = 'px-4 py-2 text-xs text-archive-gray';
            hint.textContent = 'Type a name after @ to search';
            dropdown.appendChild(hint);
        }

        results.forEach((person, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm hover:bg-archive-light ${index === activeIndex ? 'bg-archive-light' : ''}`;
            btn.innerHTML = `
                <img src="${escapeAttr(person.photo_url || '')}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover bg-archive-light" onerror="this.style.visibility='hidden'">
                <span class="min-w-0">
                    <span class="block font-medium">${escapeHtml(person.name)}</span>
                    <span class="block truncate text-xs text-archive-gray">${escapeHtml(person.position || '')}</span>
                </span>
            `;

            btn.addEventListener('mousedown', (event) => {
                event.preventDefault();
                selectPerson(person);
            });

            dropdown.appendChild(btn);
        });

        if (query.trim() !== '') {
            const createBtn = document.createElement('button');
            createBtn.type = 'button';
            createBtn.className = 'block w-full border-t border-archive-border px-4 py-3 text-left text-sm hover:bg-archive-light';
            createBtn.textContent = `Create profile: ${query.trim()}`;
            createBtn.addEventListener('mousedown', (event) => {
                event.preventDefault();
                hideDropdown();
                const name = query.trim();

                if (name) {
                    window.dispatchEvent(new CustomEvent('credits-mentions:create-person', {
                        detail: { name, textarea, root },
                    }));
                }
            });
            dropdown.appendChild(createBtn);
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
        hideDropdown();
        mentionStart = null;
        query = '';

        const pos = before.length + token.length + 1;
        textarea.focus();
        textarea.setSelectionRange(pos, pos);

        log('selected', person.name);
    };

    const fetchPeople = async () => {
        const q = query;
        const url = `${searchUrl}${searchUrl.includes('?') ? '&' : '?'}q=${encodeURIComponent(q)}`;

        log('fetching:', url);
        loading = true;
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
                const text = await response.text();
                console.error('[mentions] API failed', response.status, text);
                results = [];
                loading = false;
                renderDropdown();

                return;
            }

            const payload = await response.json();
            results = Array.isArray(payload.data) ? payload.data : (Array.isArray(payload) ? payload : []);
            log('results:', results.length);
        } catch (error) {
            console.error('[mentions] fetch error', error);
            results = [];
        } finally {
            loading = false;
            renderDropdown();
        }
    };

    const scheduleSearch = () => {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        debounceTimer = setTimeout(fetchPeople, 200);
    };

    textarea.addEventListener('input', () => {
        if (detectMention()) {
            scheduleSearch();
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
    });

    textarea.addEventListener('keyup', () => {
        if (detectMention()) {
            scheduleSearch();
        }
    });

    textarea.addEventListener('click', () => {
        if (detectMention()) {
            scheduleSearch();
        }
    });

    textarea.addEventListener('keydown', (event) => {
        if (dropdown.classList.contains('hidden')) {
            return;
        }

        const itemCount = results.length + (query.trim() !== '' ? 1 : 0);

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            activeIndex = Math.min(activeIndex + 1, Math.max(itemCount - 1, 0));
            renderDropdown();

            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            renderDropdown();

            return;
        }

        if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();

            if (activeIndex < results.length) {
                selectPerson(results[activeIndex]);
            }

            return;
        }

        if (event.key === 'Escape') {
            hideDropdown();
        }
    });

    document.addEventListener('click', (event) => {
        if (! root.contains(event.target)) {
            hideDropdown();
        }
    });

    const form = textarea.closest('form');

    if (form) {
        form.addEventListener('submit', () => {
            syncHidden();
        });
    }

    syncHidden();
    log('ready', searchUrl);
}

function parseMentionsJson(raw) {
    if (! raw || raw === '') {
        return [];
    }

    try {
        const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;

        return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        log('invalid mentions json', error);

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
