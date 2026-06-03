console.log('CREDITS MENTIONS FILE LOADED');
console.log('[mentions] script loaded');

insertMentionsLoadMarker();

/**
 * Vanilla JS credits @mention autocomplete (does not depend on Alpine).
 */
export function initCreditsMentions() {
    window.__creditsMentionsScriptLoaded = true;

    const fields = document.querySelectorAll('.credits-mentions-field');

    fields.forEach((root) => {
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

    document.querySelectorAll('[data-mentions-debug]').forEach((panel) => {
        setDebug(panel, {
            jsLoaded: true,
            markerFound: Boolean(document.getElementById('mentions-script-loaded')),
        });
    });
}

/** @deprecated use initCreditsMentions */
export const initCreditsMentionsVanilla = initCreditsMentions;

function bindCreditsMentionsRoot(root) {
    const textarea = findCreditsTextarea(root);

    if (! textarea || textarea.dataset.mentionsBound === 'true') {
        if (textarea) {
            setDebug(root.querySelector('[data-mentions-debug]'), {
                textareaFound: true,
                jsLoaded: true,
            });
        } else {
            setDebug(root.querySelector('[data-mentions-debug]'), {
                textareaFound: false,
                jsLoaded: true,
            });
        }

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

function log(...args) {
    console.log('[mentions]', ...args);
}

function setDebug(panel, state) {
    if (! panel) {
        return;
    }

    if (state.jsLoaded !== undefined) {
        const el = panel.querySelector('[data-debug-js]');

        if (el) {
            el.textContent = state.jsLoaded ? 'yes' : 'no';
        }
    }

    if (state.textareaFound !== undefined) {
        const el = panel.querySelector('[data-debug-textarea]');

        if (el) {
            el.textContent = state.textareaFound ? 'yes' : 'no';
        }
    }

    if (state.lastQuery !== undefined) {
        const el = panel.querySelector('[data-debug-query]');

        if (el) {
            el.textContent = state.lastQuery === '' ? '—' : state.lastQuery;
        }
    }

    if (state.resultsCount !== undefined) {
        const el = panel.querySelector('[data-debug-results]');

        if (el) {
            el.textContent = String(state.resultsCount);
        }
    }

    if (state.markerFound !== undefined) {
        const el = panel.querySelector('[data-debug-marker]');

        if (el) {
            el.textContent = state.markerFound ? 'yes' : 'no';
        }
    }
}

function setupCreditsMentionsField(textarea, root) {
    if (textarea.dataset.mentionsBound === 'true') {
        return;
    }

    textarea.dataset.mentionsBound = 'true';

    if (root) {
        root.dataset.mentionsBound = 'true';
    }

    const debugPanel = root?.querySelector('[data-mentions-debug]') ?? textarea
        .closest('.credits-mentions-field')
        ?.querySelector('[data-mentions-debug]');

    const searchUrl = root?.dataset.peopleSearchUrl
        || textarea.closest('[data-people-search-url]')?.dataset.peopleSearchUrl
        || '/api/people/search';

    const hiddenInput = (root || textarea.closest('.credits-mentions-field'))?.querySelector('input[name="credits_mentions_json"]')
        || document.querySelector('#credits_mentions_json')
        || document.querySelector('input[name="credits_mentions_json"]');

    setDebug(debugPanel, {
        jsLoaded: true,
        textareaFound: true,
        lastQuery: '—',
        resultsCount: 0,
        markerFound: Boolean(document.getElementById('mentions-script-loaded')),
    });

    log('bound to textarea', textarea.id || textarea.name);

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
    dropdown.setAttribute('role', 'listbox');
    dropdown.className = 'credits-mentions-dropdown';
    applyDropdownBaseStyles(dropdown);
    document.body.appendChild(dropdown);

    const updateDebugState = (extra = {}) => {
        setDebug(debugPanel, {
            jsLoaded: true,
            textareaFound: true,
            lastQuery: extra.lastQuery ?? query,
            resultsCount: extra.resultsCount ?? results.length,
        });
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
        dropdown.style.top = `${rect.bottom}px`;
        dropdown.style.width = `${rect.width}px`;
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
        const cursor = textarea.selectionStart ?? textarea.value.length;
        const before = textarea.value.slice(0, cursor);
        const match = before.match(/@([^\n@]*)$/);

        if (! match) {
            mentionStart = null;
            query = '';
            updateDebugState({ lastQuery: '—' });

            return false;
        }

        mentionStart = cursor - match[0].length;
        query = match[1];
        log('input detected');
        log('query:', query);
        updateDebugState();

        return true;
    };

    const renderDropdown = (forceShow = false) => {
        dropdown.innerHTML = '';

        if (loading) {
            dropdown.appendChild(createMessage('Searching...'));
            showDropdown();

            return;
        }

        if (query.trim() === '' && ! forceShow) {
            dropdown.appendChild(createMessage('Type a name after @ to search'));
        }

        results.forEach((person, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.style.cssText = 'display:flex;width:100%;align-items:center;gap:12px;padding:10px 16px;text-align:left;font-size:14px;border:none;background:transparent;cursor:pointer';
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
            createBtn.style.cssText = 'display:block;width:100%;border-top:1px solid #e5e5e5;padding:12px 16px;text-align:left;font-size:14px;background:#fff;cursor:pointer';
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
        updateDebugState({ lastQuery: '—', resultsCount: 0 });

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

    const fetchPeople = async (searchQuery = query) => {
        const q = searchQuery;
        const url = buildSearchUrl(q);

        log('fetching:', url);
        loading = true;
        query = q;
        updateDebugState();
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
                updateDebugState({ resultsCount: 0 });

                return;
            }

            const json = await response.json();
            results = json.data || [];
            log('results:', results.length);
            updateDebugState({ resultsCount: results.length });
        } catch (error) {
            console.error('[mentions] fetch error', error);
            results = [];
            updateDebugState({ resultsCount: 0 });
        } finally {
            loading = false;
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

    document.addEventListener('mousedown', (event) => {
        if (dropdown.contains(event.target) || textarea.contains(event.target)) {
            return;
        }

        if (root?.contains(event.target)) {
            return;
        }

        if (document.activeElement === textarea) {
            return;
        }

        hideDropdown();
    });

    const testBtn = debugPanel?.querySelector('[data-mentions-test-btn]');

    if (testBtn) {
        testBtn.addEventListener('click', async (event) => {
            event.preventDefault();
            query = 'm';
            mentionStart = null;
            updateDebugState({ lastQuery: 'm (test)' });
            log('test button clicked');
            await fetchPeople('m');
        });
    }

    const form = textarea.closest('form');

    if (form) {
        form.addEventListener('submit', () => {
            syncHidden();
        });
    }

    syncHidden();
    log('ready', searchUrl);
}

function applyDropdownBaseStyles(dropdown) {
    dropdown.style.cssText = [
        'display:none',
        'position:fixed',
        'z-index:999999',
        'max-height:16rem',
        'overflow-y:auto',
        'background:#fff',
        'border:2px solid red',
        'box-shadow:0 8px 24px rgba(0,0,0,0.12)',
    ].join(';');
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

function insertMentionsLoadMarker() {
    const place = () => {
        if (document.getElementById('mentions-script-loaded')) {
            return;
        }

        document.body?.insertAdjacentHTML(
            'beforeend',
            '<div id="mentions-script-loaded" style="position:fixed;bottom:8px;right:8px;z-index:999999;padding:6px 10px;background:#dc2626;color:#fff;font:12px/1.2 monospace;border-radius:4px;pointer-events:none">mentions script loaded</div>',
        );
    };

    if (document.body) {
        place();
    } else {
        document.addEventListener('DOMContentLoaded', place);
    }
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
