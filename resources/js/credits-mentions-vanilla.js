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
}

function log(...args) {
    console.log('[mentions]', ...args);
}

function setupCreditsMentionsField(root) {
    const textarea = root.querySelector('#credits') || root.querySelector('textarea[name="credits"]');

    if (! textarea) {
        log('no textarea found in', root);

        return;
    }

    log('bound to textarea', textarea.id || textarea.name);

    const searchUrl = root.dataset.peopleSearchUrl || '/api/people/search';
    const hiddenInput = root.querySelector('input[name="credits_mentions_json"]')
        || root.querySelector('#credits_mentions_json');

    let mentions = parseMentionsJson(hiddenInput?.value || '[]');
    let mentionStart = null;
    let query = '';
    let results = [];
    let loading = false;
    let debounceTimer = null;
    let activeIndex = -1;
    let dropdownOpen = false;

    let dropdown = document.createElement('div');
    dropdown.setAttribute('data-credits-mentions-dropdown', 'true');
    dropdown.setAttribute('role', 'listbox');
    dropdown.className = 'credits-mentions-dropdown';
    dropdown.style.cssText = [
        'display:none',
        'position:fixed',
        'z-index:99999',
        'max-height:16rem',
        'overflow-y:auto',
        'background:#fff',
        'border:2px solid #dc2626',
        'box-shadow:0 8px 24px rgba(0,0,0,0.12)',
    ].join(';');

    document.body.appendChild(dropdown);

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

        dropdown.style.top = `${rect.bottom + 4}px`;
        dropdown.style.left = `${rect.left}px`;
        dropdown.style.width = `${Math.max(rect.width, 280)}px`;
    };

    const hideDropdown = () => {
        dropdown.style.display = 'none';
        dropdownOpen = false;
        activeIndex = -1;
    };

    const showDropdown = () => {
        positionDropdown();
        dropdown.style.display = 'block';
        dropdownOpen = true;
        log('dropdown visible');
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
            dropdown.appendChild(createMessage('Searching...'));
            showDropdown();

            return;
        }

        if (query.trim() === '') {
            dropdown.appendChild(createMessage('Type a name after @ to search'));
        }

        results.forEach((person, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm hover:bg-neutral-100';
            if (index === activeIndex) {
                btn.style.backgroundColor = '#f5f5f5';
            }

            btn.innerHTML = `
                <img src="${escapeAttr(person.photo_url || '')}" alt="" style="width:36px;height:36px;border-radius:9999px;object-fit:cover;background:#f5f5f5" onerror="this.style.visibility='hidden'">
                <span style="min-width:0">
                    <span style="display:block;font-weight:500">${escapeHtml(person.name)}</span>
                    <span style="display:block;font-size:12px;color:#666;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(person.position || '')}</span>
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
            createBtn.className = 'block w-full border-t border-neutral-200 px-4 py-3 text-left text-sm hover:bg-neutral-100';
            createBtn.textContent = `Create profile: ${query.trim()}`;
            createBtn.addEventListener('mousedown', (event) => {
                event.preventDefault();
                hideDropdown();
                log('create profile requested:', query.trim());
            });
            dropdown.appendChild(createBtn);
        }

        if (dropdown.innerHTML === '') {
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
        hideDropdown();
        mentionStart = null;
        query = '';

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
        } catch (error) {
            const separator = searchUrl.includes('?') ? '&' : '?';

            return `${searchUrl}${separator}q=${encodeURIComponent(q)}`;
        }
    };

    const fetchPeople = async () => {
        const q = query;
        const url = buildSearchUrl(q);

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
        if (! dropdownOpen) {
            return;
        }

        const itemCount = results.length;

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

        if (event.key === 'Enter' && activeIndex >= 0 && activeIndex < results.length) {
            event.preventDefault();
            selectPerson(results[activeIndex]);

            return;
        }

        if (event.key === 'Escape') {
            hideDropdown();
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
        if (root.contains(event.target) || dropdown.contains(event.target)) {
            return;
        }

        hideDropdown();
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

function createMessage(text) {
    const el = document.createElement('p');
    el.style.cssText = 'padding:12px 16px;font-size:12px;color:#666;margin:0';
    el.textContent = text;

    return el;
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
