export default function peopleCreditsManager({ initial = [], searchUrl, createUrl, csrfToken }) {
    const hydrate = () => {
        return (initial || []).map((item) => ({
            person_id: item.person_id,
            role: item.role || '',
            name: item.name || '',
            slug: item.slug || '',
            photo_url: item.photo_url || '',
            key: `person:${item.person_id}`,
        }));
    };

    return {
        credits: hydrate(),
        query: '',
        results: [],
        open: false,
        loading: false,
        modalOpen: false,
        modalName: '',
        modalPosition: '',
        modalPhoto: null,
        modalError: '',
        modalSaving: false,
        searchUrl,
        createUrl,
        csrfToken,

        get canSearch() {
            return this.query.trim().length >= 1;
        },

        async searchPeople() {
            const q = this.query.trim();

            if (!q) {
                this.results = [];
                this.open = false;

                return;
            }

            this.loading = true;

            try {
                const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(q)}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    throw new Error('Search failed');
                }

                const payload = await response.json();
                const selectedIds = this.credits.map((c) => c.person_id);

                this.results = (payload.data || []).filter((person) => !selectedIds.includes(person.id));
                this.open = true;
            } catch (error) {
                this.results = [];
            } finally {
                this.loading = false;
            }
        },

        selectPerson(person) {
            if (this.credits.some((c) => c.person_id === person.id)) {
                return;
            }

            this.credits.push({
                person_id: person.id,
                role: person.position || '',
                name: person.name,
                slug: person.slug,
                photo_url: person.photo_url,
                key: `person:${person.id}`,
            });

            this.query = '';
            this.results = [];
            this.open = false;
        },

        removeCredit(credit) {
            this.credits = this.credits.filter((c) => c.key !== credit.key);
        },

        openCreateModal() {
            this.modalName = this.query.trim();
            this.modalPosition = '';
            this.modalPhoto = null;
            this.modalError = '';
            this.modalOpen = true;
        },

        closeCreateModal() {
            this.modalOpen = false;
            this.modalSaving = false;
            this.modalError = '';
        },

        onModalPhotoChange(event) {
            this.modalPhoto = event.target.files?.[0] || null;
        },

        async createPerson() {
            const name = this.modalName.trim();
            const position = this.modalPosition.trim();

            if (!name || !position) {
                this.modalError = 'Name and role are required.';

                return;
            }

            this.modalSaving = true;
            this.modalError = '';

            try {
                const body = new FormData();
                body.append('name', name);
                body.append('position', position);

                if (this.modalPhoto) {
                    body.append('photo', this.modalPhoto);
                }

                const response = await fetch(this.createUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body,
                });

                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Could not create person.');
                }

                this.selectPerson(payload.data);
                this.closeCreateModal();
            } catch (error) {
                this.modalError = error.message || 'Could not create person.';
            } finally {
                this.modalSaving = false;
            }
        },

        onKeydown(event) {
            if (event.key === 'Escape') {
                this.open = false;
            }
        },
    };
}
