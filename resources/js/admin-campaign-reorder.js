import Sortable from 'sortablejs';

export default function initAdminCampaignReorder() {
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
        scroll: true,
        forceAutoScrollFallback: true,
        scrollSensitivity: 80,
        scrollSpeed: 15,
        bubbleScroll: true,
        onEnd: markDirty,
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        form.querySelectorAll('input[name="order[]"]').forEach((el) => el.remove());

        list.querySelectorAll('.campaign-reorder-item').forEach((item) => {
            const id = item.dataset.campaignId;
            if (!id) {
                return;
            }

            const orderInput = document.createElement('input');
            orderInput.type = 'hidden';
            orderInput.name = 'order[]';
            orderInput.value = id;
            form.appendChild(orderInput);
        });

        form.submit();
    });
}
