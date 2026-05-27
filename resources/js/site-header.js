export default function siteHeader() {
    return {
        open: false,
        userOpen: false,
        menuTop: 0,
        menuRight: 0,

        init() {
            const reposition = () => {
                if (this.userOpen) {
                    this.positionUserMenu();
                }
            };

            window.addEventListener('resize', reposition);
            window.addEventListener('scroll', reposition, { passive: true });
        },

        toggleUserMenu() {
            this.userOpen = !this.userOpen;

            if (this.userOpen) {
                this.$nextTick(() => this.positionUserMenu());
            }
        },

        positionUserMenu() {
            const trigger = this.$refs.userTrigger;

            if (!trigger) {
                return;
            }

            const rect = trigger.getBoundingClientRect();

            this.menuTop = Math.round(rect.bottom + 8);
            this.menuRight = Math.round(window.innerWidth - rect.right);
        },
    };
}
