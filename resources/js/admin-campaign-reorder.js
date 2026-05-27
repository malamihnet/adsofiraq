import Sortable from 'sortablejs';

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('campaign-reorder-list');
    const form = document.getElementById('campaign-reorder-form');

    if (!list || !form) {
        return;
    }

    const unsavedHint = document.getElementById('reorder-unsaved-hint');
    const savedHint = document.getElementById('reorder-saved-hint');

    const markDirty = () => {
        unsavedHint?.classList.remove('hidden');
        savedHint?.classList.add('hidden');
    };

    Sortable.create(list, {
        handle: '.drag-handle',
        animation: 180,
        ghostClass: 'bg-archive-light',
        onEnd: markDirty,
    });

    list.querySelectorAll('.campaign-pin-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', markDirty);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        form.querySelectorAll('input[name="order[]"], input[name="pinned[]"]').forEach((el) => el.remove());

        list.querySelectorAll('.campaign-reorder-item').forEach((item) => {
            const id = item.dataset.campaignId;

            const orderInput = document.createElement('input');
            orderInput.type = 'hidden';
            orderInput.name = 'order[]';
            orderInput.value = id;
            form.appendChild(orderInput);

            const pin = item.querySelector('.campaign-pin-checkbox');

            if (pin?.checked) {
                const pinnedInput = document.createElement('input');
                pinnedInput.type = 'hidden';
                pinnedInput.name = 'pinned[]';
                pinnedInput.value = id;
                form.appendChild(pinnedInput);
            }
        });

        form.submit();
    });
});
