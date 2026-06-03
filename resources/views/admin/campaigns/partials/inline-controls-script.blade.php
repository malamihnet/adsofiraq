@push('scripts')
<script>
(function () {
    const table = document.getElementById('admin-campaigns-table');
    if (!table) {
        console.warn('[campaign-inline] Table not found.');
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrf) {
        console.error('[campaign-inline] Missing CSRF meta tag.');
        alert('CSRF token missing. Refresh the page.');
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
        console.error('[campaign-inline]', message);
        errorBanner.textContent = message;
        errorBanner.classList.remove('hidden');
        alert(message);
    };

    const clearError = () => {
        errorBanner.textContent = '';
        errorBanner.classList.add('hidden');
    };

    const showSaved = (campaignId) => {
        const el = document.querySelector(`[data-inline-feedback="${campaignId}"]`);
        if (!el) {
            return;
        }
        el.classList.remove('hidden');
        window.setTimeout(() => el.classList.add('hidden'), 2500);
    };

    const setRowLoading = (row, loading) => {
        row?.classList.toggle('opacity-50', loading);
        row?.classList.toggle('pointer-events-none', loading);
    };

    const saveInline = async (control) => {
        const url = control.dataset.inlineUrl;
        const field = control.dataset.inlineField;
        const campaignId = control.dataset.campaignId;
        const row = control.closest('[data-campaign-id]');

        let value;
        if (field === 'status') {
            value = control.value;
        } else {
            value = control.checked;
        }

        console.log('[campaign-inline] request', {
            campaignId,
            field,
            value,
            url,
        });

        if (!url) {
            showError('Missing inline save URL. Hard refresh the page (Ctrl+F5).');
            return false;
        }

        setRowLoading(row, true);
        clearError();

        let responseText = '';

        try {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ field, value }),
            });

            responseText = await response.text();

            console.log('[campaign-inline] response', {
                status: response.status,
                body: responseText,
            });

            let data = {};
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                throw new Error(`Non-JSON response (${response.status}). Body starts with: ${responseText.slice(0, 120)}`);
            }

            if (!response.ok || data.ok !== true) {
                throw new Error(data.message || `Save failed with HTTP ${response.status}`);
            }

            if (data.campaign) {
                if (field === 'status' && control.tagName === 'SELECT') {
                    control.value = data.campaign.status;
                    control.dataset.previousValue = data.campaign.status;
                }

                if (field === 'is_hero' || field === 'is_featured' || field === 'is_verified') {
                    const checked = Boolean(data.campaign[field]);
                    control.checked = checked;
                    control.dataset.previousChecked = checked ? '1' : '0';
                    const label = control.closest('label')?.querySelector('span');
                    if (label) {
                        label.textContent = checked ? 'On' : 'Off';
                    }
                }

                if (field === 'is_verified') {
                    const badge = row?.querySelector('[data-verified-badge]');
                    if (badge) {
                        badge.classList.toggle('hidden', !data.campaign.is_verified);
                    }
                }
            }

            showSaved(campaignId);
            return true;
        } catch (error) {
            showError(error.message || 'Could not save.');
            return false;
        } finally {
            setRowLoading(row, false);
        }
    };

    table.addEventListener('change', async (event) => {
        const control = event.target.closest('[data-inline-field]');
        if (!control) {
            return;
        }

        if (control.dataset.inlineField === 'status') {
            const previous = control.dataset.previousValue || control.value;
            const ok = await saveInline(control);
            if (!ok) {
                control.value = previous;
            }
            return;
        }

        const previousChecked = control.dataset.previousChecked === '1';
        const ok = await saveInline(control);
        if (!ok) {
            control.checked = previousChecked;
        }
    });

    console.log('[campaign-inline] Ready — controls:', table.querySelectorAll('[data-inline-field]').length);
})();
</script>
@endpush
