/**
 * Inline AJAX updates on admin campaign list (status, hero, verified, editor's pick).
 */
export default function initAdminCampaignInline() {
    const table = document.getElementById('admin-campaigns-table');
    if (!table) {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const baseUrl = table.dataset.inlineBaseUrl;

    if (!csrf || !baseUrl) {
        return;
    }

    const setRowState = (row, state) => {
        row?.classList.toggle('opacity-50', state === 'loading');
        row?.classList.toggle('pointer-events-none', state === 'loading');
    };

    const flashCell = (el, ok) => {
        if (!el) {
            return;
        }
        el.classList.remove('ring-2', 'ring-green-400', 'ring-red-400');
        el.classList.add(ok ? 'ring-2' : 'ring-2', ok ? 'ring-green-400' : 'ring-red-400');
        window.setTimeout(() => {
            el.classList.remove('ring-2', 'ring-green-400', 'ring-red-400');
        }, 1200);
    };

    const patch = async (campaignId, field, value, controlEl) => {
        const row = controlEl?.closest('[data-campaign-id]');
        setRowState(row, 'loading');

        try {
            const response = await fetch(`${baseUrl}/${campaignId}/inline`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ field, value }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = data.message
                    || Object.values(data.errors || {}).flat().join(' ')
                    || 'Could not save.';
                throw new Error(message);
            }

            flashCell(controlEl?.closest('td'), true);

            if (row) {
                if (field === 'is_verified') {
                    const badge = row.querySelector('[data-verified-badge]');
                    if (badge) {
                        badge.classList.toggle('hidden', !data.campaign?.is_verified);
                    }
                }

                if (field === 'is_hero' || field === 'is_verified' || field === 'is_featured') {
                    const label = controlEl?.closest('label')?.querySelector('span');
                    if (label) {
                        const on = data.campaign?.[field];
                        label.textContent = on ? 'On' : 'Off';
                    }
                }
            }

            return data;
        } catch (error) {
            flashCell(controlEl?.closest('td'), false);
            window.alert(error.message || 'Could not save.');
            throw error;
        } finally {
            setRowState(row, 'idle');
        }
    };

    table.addEventListener('change', async (event) => {
        const select = event.target.closest('[data-inline-field="status"]');
        if (select) {
            const previous = select.dataset.previousValue || select.value;
            try {
                await patch(select.dataset.campaignId, 'status', select.value, select);
                select.dataset.previousValue = select.value;
            } catch {
                select.value = previous;
            }
            return;
        }

        const checkbox = event.target.closest('[data-inline-field]');
        if (!checkbox || checkbox.matches('[data-inline-field="status"]')) {
            return;
        }

        const field = checkbox.dataset.inlineField;
        const previousChecked = checkbox.dataset.previousChecked === '1';

        try {
            await patch(checkbox.dataset.campaignId, field, checkbox.checked, checkbox);
            checkbox.dataset.previousChecked = checkbox.checked ? '1' : '0';
        } catch {
            checkbox.checked = previousChecked;
        }
    });
}
