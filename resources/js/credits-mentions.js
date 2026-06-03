export default function creditsMentions({
    initialMentions = [],
    creditsText = '',
    peopleSearchUrl,
    positionsUrl,
    createPersonUrl,
    createPositionUrl,
    csrfToken,
}) {
    const hydrateMentions = () => (initialMentions || []).map((item) => ({
        person_id: item.person_id,
        name: item.name || '',
        role: item.role || '',
        slug: item.slug || '',
        photo_url: item.photo_url || '',
        key: `person:${item.person_id}`,
    }));

    return {
        text: creditsText || '',
        mentions: hydrateMentions(),
        peopleSearchUrl,
        positionsUrl,
        createPersonUrl,
        createPositionUrl,
        csrfToken,
        query: '',
        results: [],
        open: false,
        loading: false,
        activeIndex: -1,
        mentionStart: null,
        modalOpen: false,
        modalName: '',
        modalPositionId: '',
        modalPhoto: null,
        modalError: '',
        modalSaving: false,
        positions: [],
        positionsLoading: false,
        positionModalOpen: false,
        newPositionName: '',
        positionModalSaving: false,
        positionModalError: '',

        get mentionsJson() {
            const seen = new Set();

            return JSON.stringify(
                this.mentions
                    .filter((m) => {
                        if (seen.has(m.person_id)) {
                            return false;
                        }

                        seen.add(m.person_id);

                        return true;
                    })
                    .map((m) => ({
                        person_id: m.person_id,
                        name: m.name,
                        role: m.role,
                    })),
            );
        },

        get canSearch() {
            return this.query.trim().length >= 1;
        },

        init() {
            this.loadPositions();
        },

        async loadPositions() {
            this.positionsLoading = true;

            try {
                const response = await fetch(this.positionsUrl, {
                    headers: { Accept: 'application/json' },
                });

                if (response.ok) {
                    const payload = await response.json();
                    this.positions = payload.data || [];
                }
            } finally {
                this.positionsLoading = false;
            }
        },

        onCreditsInput(event) {
            this.text = event.target.value;
            this.detectMentionQuery(event.target);
            this.pruneMentions();
        },

        onCreditsKeydown(event) {
            if (!this.open) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.activeIndex = Math.min(this.activeIndex + 1, this.dropdownItems.length - 1);

                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.activeIndex = Math.max(this.activeIndex - 1, 0);

                return;
            }

            if (event.key === 'Enter' && this.activeIndex >= 0) {
                event.preventDefault();
                const item = this.dropdownItems[this.activeIndex];

                if (item?.type === 'person') {
                    this.selectPerson(item.person);
                } else if (item?.type === 'create') {
                    this.openCreateModal();
                }

                return;
            }

            if (event.key === 'Escape') {
                this.open = false;
                this.activeIndex = -1;
            }
        },

        get dropdownItems() {
            const items = this.results.map((person) => ({
                type: 'person',
                person,
            }));

            if (this.canSearch && !this.loading) {
                items.push({ type: 'create' });
            }

            return items;
        },

        detectMentionQuery(textarea) {
            const cursor = textarea.selectionStart;
            const before = this.text.slice(0, cursor);
            const match = before.match(/@([^\n@]*)$/);

            if (!match) {
                this.open = false;
                this.query = '';
                this.mentionStart = null;

                return;
            }

            this.mentionStart = cursor - match[0].length;
            this.query = match[1];
            this.searchPeople();
        },

        async searchPeople() {
            const q = this.query.trim();

            if (!q) {
                this.results = [];
                this.open = false;

                return;
            }

            this.loading = true;
            this.activeIndex = 0;

            try {
                const response = await fetch(`${this.peopleSearchUrl}?q=${encodeURIComponent(q)}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    throw new Error('Search failed');
                }

                const payload = await response.json();
                this.results = payload.data || [];
                this.open = true;
            } catch (error) {
                this.results = [];
                this.open = true;
            } finally {
                this.loading = false;
            }
        },

        selectPerson(person) {
            const textarea = this.$refs.creditsTextarea;
            const role = this.roleBeforeCursor(textarea);
            const token = `@${person.name}`;
            const start = this.mentionStart ?? textarea.selectionStart;
            const end = textarea.selectionStart;
            const before = this.text.slice(0, start);
            const after = this.text.slice(end);

            this.text = `${before}${token} ${after}`;

            const existing = this.mentions.find((m) => m.person_id === person.id);

            if (!existing) {
                this.mentions.push({
                    person_id: person.id,
                    name: person.name,
                    role: role || person.position || 'Credit',
                    slug: person.slug,
                    photo_url: person.photo_url || '',
                    key: `person:${person.id}`,
                });
            }

            this.query = '';
            this.results = [];
            this.open = false;
            this.activeIndex = -1;
            this.mentionStart = null;

            this.$nextTick(() => {
                const pos = before.length + token.length + 1;
                textarea.focus();
                textarea.setSelectionRange(pos, pos);
            });
        },

        roleBeforeCursor(textarea) {
            const cursor = textarea.selectionStart;
            const lineStart = this.text.lastIndexOf('\n', cursor - 1) + 1;
            const line = this.text.slice(lineStart, cursor);
            const match = line.match(/^\s*([^:@\n]{2,40})\s*:\s*@?[^@]*$/);

            return match ? match[1].trim() : '';
        },

        pruneMentions() {
            const seen = new Set();

            this.mentions = this.mentions.filter((mention) => {
                if (!this.text.includes(`@${mention.name}`)) {
                    return false;
                }

                if (seen.has(mention.person_id)) {
                    return false;
                }

                seen.add(mention.person_id);

                return true;
            });
        },

        openCreateModal() {
            this.modalName = this.query.trim() || this.query;
            this.modalPositionId = this.positions[0]?.id ? String(this.positions[0].id) : '';
            this.modalPhoto = null;
            this.modalError = '';
            this.modalOpen = true;
            this.open = false;
        },

        closeCreateModal() {
            this.modalOpen = false;
            this.modalSaving = false;
            this.modalError = '';
        },

        onModalPhotoChange(event) {
            this.modalPhoto = event.target.files?.[0] || null;
        },

        openPositionModal() {
            this.newPositionName = '';
            this.positionModalError = '';
            this.positionModalOpen = true;
        },

        closePositionModal() {
            this.positionModalOpen = false;
            this.positionModalSaving = false;
        },

        async createPosition() {
            const name = this.newPositionName.trim();

            if (!name) {
                this.positionModalError = 'Position name is required.';

                return;
            }

            this.positionModalSaving = true;
            this.positionModalError = '';

            try {
                const body = new FormData();
                body.append('name', name);

                const response = await fetch(this.createPositionUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body,
                });

                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Could not create position.');
                }

                this.positions.push(payload.data);
                this.modalPositionId = String(payload.data.id);
                this.closePositionModal();
            } catch (error) {
                this.positionModalError = error.message || 'Could not create position.';
            } finally {
                this.positionModalSaving = false;
            }
        },

        async createPerson() {
            const name = this.modalName.trim();

            if (!name) {
                this.modalError = 'Full name is required.';

                return;
            }

            if (!this.modalPositionId) {
                this.modalError = 'Position is required.';

                return;
            }

            this.modalSaving = true;
            this.modalError = '';

            try {
                const body = new FormData();
                body.append('name', name);
                body.append('position_id', this.modalPositionId);

                if (this.modalPhoto) {
                    body.append('photo', this.modalPhoto);
                }

                const response = await fetch(this.createPersonUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body,
                });

                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Could not create profile.');
                }

                this.selectPerson(payload.data);
                this.closeCreateModal();
            } catch (error) {
                this.modalError = error.message || 'Could not create profile.';
            } finally {
                this.modalSaving = false;
            }
        },
    };
}
