export default function campaignVideosManager({ initialRows = [], maxVideos = 5 } = {}) {
    const emptyRow = () => ({
        key: `${Date.now()}-${Math.random()}`,
        id: null,
        type: '',
        title: '',
        url: '',
        hasExistingFile: false,
        existingFileUrl: null,
    });

    return {
        rows: initialRows.length ? initialRows : [emptyRow()],
        maxVideos,

        addRow() {
            if (this.rows.length >= this.maxVideos) {
                return;
            }

            this.rows.push(emptyRow());
        },

        removeRow(index) {
            if (this.rows.length <= 1) {
                this.rows.splice(0, 1, emptyRow());

                return;
            }

            this.rows.splice(index, 1);
        },

        canAdd() {
            return this.rows.length < this.maxVideos;
        },
    };
}
