export default function taxonomyMultiselect({ name, max, options, initial = [] }) {
    const normalize = (item) => {
        if (!item) {
            return null;
        }

        if (typeof item === 'string') {
            if (item.startsWith('new:')) {
                const label = item.slice(4).trim();

                return label ? { id: null, name: label, key: `new:${label.toLowerCase()}` } : null;
            }

            const id = parseInt(item, 10);

            if (!Number.isNaN(id)) {
                const match = options.find((o) => o.id === id);

                return match
                    ? { id: match.id, name: match.name, key: `id:${match.id}` }
                    : { id, name: `Item #${id}`, key: `id:${id}` };
            }
        }

        const id = item.id ?? null;
        const label = (item.name || '').trim();

        if (!label && !id) {
            return null;
        }

        return {
            id,
            name: label || options.find((o) => o.id === id)?.name || '',
            key: id ? `id:${id}` : `new:${label.toLowerCase()}`,
        };
    };

    const hydrateInitial = () => {
        const items = [];

        for (const raw of initial) {
            const item = normalize(raw);

            if (item && !items.some((s) => s.key === item.key)) {
                items.push(item);
            }
        }

        return items;
    };

    return {
        name,
        max,
        options,
        query: '',
        open: false,
        selected: hydrateInitial(),

        get filteredOptions() {
            const q = this.query.trim().toLowerCase();

            return this.options.filter((option) => {
                if (this.selected.some((s) => s.id === option.id)) {
                    return false;
                }

                if (!q) {
                    return true;
                }

                return option.name.toLowerCase().includes(q);
            });
        },

        get canAddMore() {
            return this.selected.length < this.max;
        },

        get counterLabel() {
            return `${this.selected.length} / ${this.max} selected`;
        },

        get showCreateOption() {
            const q = this.query.trim();

            if (!q || !this.canAddMore) {
                return false;
            }

            const exists = this.options.some(
                (o) => o.name.toLowerCase() === q.toLowerCase()
            );

            const alreadySelected = this.selected.some(
                (s) => s.name.toLowerCase() === q.toLowerCase()
            );

            return !exists && !alreadySelected;
        },

        toggleDropdown() {
            if (!this.canAddMore) {
                return;
            }

            this.open = !this.open;
        },

        closeDropdown() {
            this.open = false;
        },

        selectExisting(option) {
            if (!this.canAddMore) {
                return;
            }

            const item = { id: option.id, name: option.name, key: `id:${option.id}` };

            if (!this.selected.some((s) => s.key === item.key)) {
                this.selected.push(item);
            }

            this.query = '';
            this.open = false;
        },

        addNewFromQuery() {
            const label = this.query.trim();

            if (!label || !this.canAddMore) {
                return;
            }

            const key = `new:${label.toLowerCase()}`;

            if (this.selected.some((s) => s.key === key)) {
                return;
            }

            const existing = this.options.find(
                (o) => o.name.toLowerCase() === label.toLowerCase()
            );

            if (existing) {
                this.selectExisting(existing);

                return;
            }

            this.selected.push({ id: null, name: label, key });
            this.query = '';
            this.open = false;
        },

        remove(item) {
            this.selected = this.selected.filter((s) => s.key !== item.key);
        },

        hiddenValue(item) {
            return item.id ? String(item.id) : `new:${item.name}`;
        },

        onKeydown(event) {
            if (event.key === 'Enter') {
                event.preventDefault();

                if (this.showCreateOption) {
                    this.addNewFromQuery();
                } else if (this.filteredOptions.length === 1) {
                    this.selectExisting(this.filteredOptions[0]);
                }
            }

            if (event.key === 'Escape') {
                this.closeDropdown();
            }
        },
    };
}
