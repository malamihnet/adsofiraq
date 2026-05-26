export default function homeHeroSlider({ slides }) {
    return {
        slides,
        index: 0,
        progress: 0,
        playing: true,
        instant: false,
        touchX: null,
        autoplayMs: 5000,
        progressTimer: null,
        autoplayTimer: null,
        resizeHandler: null,

        init() {
            this.resizeHandler = () => this.clampIndex();
            window.addEventListener('resize', this.resizeHandler);
            this.startAutoplay();
        },

        visibleCount() {
            return window.matchMedia('(min-width: 768px)').matches
                ? Math.min(3, this.slides.length)
                : 1;
        },

        maxIndex() {
            return Math.max(0, this.slides.length - this.visibleCount());
        },

        canNavigate() {
            return this.slides.length > this.visibleCount();
        },

        slideWidthPercent() {
            return 100 / this.visibleCount();
        },

        slideStyle() {
            return { width: `${this.slideWidthPercent()}%` };
        },

        trackStyle() {
            return { transform: `translateX(-${this.index * this.slideWidthPercent()}%)` };
        },

        dotIndex() {
            return this.index;
        },

        clampIndex() {
            if (this.index > this.maxIndex()) {
                this.index = 0;
            }
        },

        goTo(target) {
            if (! this.canNavigate()) {
                return;
            }
            this.index = Math.min(target, this.maxIndex());
            this.resetAutoplay();
        },

        next() {
            if (! this.canNavigate()) {
                return;
            }
            this.index = this.index >= this.maxIndex() ? 0 : this.index + 1;
            this.resetAutoplay();
        },

        prev() {
            if (! this.canNavigate()) {
                return;
            }
            this.index = this.index <= 0 ? this.maxIndex() : this.index - 1;
            this.resetAutoplay();
        },

        touchStart(event) {
            this.touchX = event.changedTouches[0].screenX;
        },

        touchEnd(event) {
            if (this.touchX === null) {
                return;
            }
            const delta = event.changedTouches[0].screenX - this.touchX;
            if (Math.abs(delta) > 50) {
                delta < 0 ? this.next() : this.prev();
            }
            this.touchX = null;
        },

        pause() {
            this.playing = false;
            this.stopTimers();
        },

        resume() {
            this.playing = true;
            this.startAutoplay();
        },

        stopTimers() {
            clearInterval(this.autoplayTimer);
            clearInterval(this.progressTimer);
        },

        startAutoplay() {
            this.stopTimers();
            if (! this.canNavigate()) {
                this.progress = 100;

                return;
            }
            this.progress = 0;
            const step = 100 / (this.autoplayMs / 100);
            this.progressTimer = setInterval(() => {
                if (! this.playing) {
                    return;
                }
                this.progress = Math.min(100, this.progress + step);
            }, 100);
            this.autoplayTimer = setInterval(() => {
                if (! this.playing) {
                    return;
                }
                this.next();
            }, this.autoplayMs);
        },

        resetAutoplay() {
            if (this.playing) {
                this.startAutoplay();
            }
        },
    };
}
