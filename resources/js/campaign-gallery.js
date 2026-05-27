function dedupeStills(stills) {
    const seen = new Set();

    return (Array.isArray(stills) ? stills : []).filter((still) => {
        const url = still?.url;

        if (! url || seen.has(url)) {
            return false;
        }

        seen.add(url);

        return true;
    });
}

export default function campaignGallery(stills, placeholderUrl) {
    const items = dedupeStills(stills);

    return {
        stills: items,
        placeholder: placeholderUrl,
        active: 0,
        lightboxOpen: false,

        init() {
            if (this.active >= this.stills.length) {
                this.active = 0;
            }
        },

        previewUrl() {
            return this.stills[this.active]?.url ?? this.placeholder;
        },

        previewAlt() {
            return this.stills[this.active]?.alt ?? 'Campaign still';
        },

        select(index) {
            if (index >= 0 && index < this.stills.length) {
                this.active = index;
            }
        },

        openLightbox() {
            if (this.stills.length === 0) {
                return;
            }

            this.lightboxOpen = true;
            document.body.classList.add('overflow-hidden');
        },

        closeLightbox() {
            this.lightboxOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        next() {
            if (this.stills.length < 2) {
                return;
            }

            this.active = (this.active + 1) % this.stills.length;
        },

        prev() {
            if (this.stills.length < 2) {
                return;
            }

            this.active = (this.active - 1 + this.stills.length) % this.stills.length;
        },

        onImageError(event) {
            if (event?.target) {
                event.target.onerror = null;
                event.target.src = this.placeholder;
            }
        },
    };
}
