export default function campaignGallery(stills, placeholderUrl) {
    return {
        stills: Array.isArray(stills) ? stills : [],
        placeholder: placeholderUrl,
        active: 0,
        lightboxOpen: false,

        init() {
            if (this.active >= this.stills.length) {
                this.active = 0;
            }
        },

        get current() {
            return this.stills[this.active] ?? null;
        },

        get hasMultiple() {
            return this.stills.length > 1;
        },

        select(index) {
            if (index >= 0 && index < this.stills.length) {
                this.active = index;
            }
        },

        openLightbox() {
            if (! this.current) {
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
            if (! this.hasMultiple) {
                return;
            }

            this.active = (this.active + 1) % this.stills.length;
        },

        prev() {
            if (! this.hasMultiple) {
                return;
            }

            this.active = (this.active - 1 + this.stills.length) % this.stills.length;
        },

        onImageError(event) {
            if (event?.target) {
                event.target.src = this.placeholder;
            }
        },
    };
}
