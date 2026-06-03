/**
 * Inline AJAX updates on admin campaign list (status, hero, verified, editor's pick).
 */
export default function initAdminCampaignInline() {
    const table = document.getElementById('admin-campaigns-table');
    if (!table) {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrf) {
        console.error('Admin campaign inline: missing CSRF meta tag.');
        return;
    }

    let errorBanner = document.getElementById('admin-campaign-inline-error');
    if (!errorBanner) {
        errorBanner = document.createElement('div');
        errorBanner.id = 'admin-campaign-inline-error';
        errorBanner.className = 'mb-4 hidden border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800';
        errorBanner.setAttribute('role', 'alert');
        table.parentElement?.insertBefore(errorBanner, table);
    }

    const showError = (message) => {
        errorBanner.textContent = message;
        errorBanner.classList.remove('hidden');
    };

    const clearError = () => {
        errorBanner.textContent = '';
        errorBanner.classList.add('hidden');
    };

    const setRowState = (row, state) => {
        row?.classList.toggle('opacity-50', state === 'loading');
        row?.classList.toggle('pointer-events-none', state === 'loading');
    };

    const flashCell = (el, ok) => {
        if (!el) {
            return;
        }
        el.classList.remove('ring-2', 'ring-green-400', 'ring-red-400');
        el.classList.add('ring-2', ok ? 'ring-green-400' : 'ring-red-400');
        window.setTimeout(() => {
            el.classList.remove('ring-2', 'ring-green-400', 'ring-red-400');
        }, 1200);
    };

    const parseResponse = async (response) => {
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            const text = await response.text();
            if (text.includes('login') || response.redirected) {
                throw new Error('Session expired. Please refresh the page and log in again.');
            }
            throw new Error(`Server returned an unexpected response (${response.status}).`);
        }

        return response.json();
    };

    const patch = async (url, field, value, controlEl) => {
        const row = controlEl?.closest('[data-campaign-id]');
        setRowState(row, 'loading');
        clearError();

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ field, value }),
            });

            const data = await parseResponse(response);

            if (!response.ok || data.success !== true) {
                const message = data.message
                    || Object.values(data.errors || {}).flat().join(' ')
                    || `Could not save (${response.status}).`;
                throw new Error(message);
            }

            flashCell(controlEl?.closest('td'), true);

            if (row && data.campaign) {
                if (field === 'is_verified') {
                    const badge = row.querySelector('[data-verified-badge]');
                    if (badge) {
                        badge.classList.toggle('hidden', !data.campaign.is_verified);
                    }
                }

                if (field === 'is_hero' || field === 'is_verified' || field === 'is_featured') {
                    const label = controlEl?.closest('label')?.querySelector('span');
                    if (label) {
                        label.textContent = data.campaign[field] ? 'On' : 'Off';
                    }
                    if (controlEl?.type === 'checkbox') {
                        controlEl.checked = Boolean(data.campaign[field]);
                        controlEl.dataset.previousChecked = controlEl.checked ? '1' : '0';
                    }
                }

                if (field === 'status' && controlEl?.tagName === 'SELECT') {
                    controlEl.value = data.campaign.status;
                    controlEl.dataset.previousValue = data.campaign.status;
                }
            }

            return data;
        } catch (error) {
            flashCell(controlEl?.closest('td'), false);
            showError(error.message || 'Could not save.');
            throw error;
        } finally {
            setRowState(row, 'idle');
        }
    };

    table.addEventListener('change', async (event) => {
        const control = event.target.closest('[data-inline-field]');
        if (!control) {
            return;
        }

        const url = control.dataset.inlineUrl;
        if (!url) {
            showError('Missing save URL for this control. Refresh the page.');
            return;
        }

        const field = control.dataset.inlineField;

        if (field === 'status') {
            const previous = control.dataset.previousValue || control.value;
            try {
                await patch(url, field, control.value, control);
            } catch {
                control.value = previous;
            }
            return;
        }

        const previousChecked = control.dataset.previousChecked === '1';

        try {
            await patch(url, field, control.checked, control);
        } catch {
            control.checked = previousChecked;
        }
    });
}
