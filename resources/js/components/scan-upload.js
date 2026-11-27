import { formatNumber } from '../http';

const STAGES = ['Mengenali tiap lauk', 'Menaksir porsi', 'Menghitung kalori dan makro', 'Menyusun hasilnya'];

/**
 * Pilih atau ambil foto, tampilkan pratinjau, lalu kirim formulir.
 * Saat menganalisis, pesan tahap berganti supaya menunggu terasa berjalan.
 */
export default (config) => ({
    preview: null,
    fileName: '',
    fileSize: '',
    error: '',
    dragging: false,
    submitting: false,
    stageIndex: 0,
    timer: null,
    maxBytes: config.maxKb * 1024,

    get stage() {
        return STAGES[this.stageIndex];
    },

    pick(event) {
        const file = event.target.files?.[0];
        this.use(file);
    },

    drop(event) {
        this.dragging = false;
        const file = event.dataTransfer.files?.[0];
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        this.$refs.input.files = transfer.files;
        this.use(file);
    },

    use(file) {
        this.error = '';
        if (!file) return;
        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
            this.error = 'Format foto harus JPG, PNG, atau WebP.';
            this.clear();
            return;
        }
        if (file.size > this.maxBytes) {
            this.error = 'Ukuran foto paling besar 5 MB.';
            this.clear();
            return;
        }
        if (this.preview) URL.revokeObjectURL(this.preview);
        this.preview = URL.createObjectURL(file);
        this.fileName = file.name;
        this.fileSize = `${formatNumber(file.size / 1024 / 1024, 1)} MB`;
    },

    clear() {
        if (this.preview) URL.revokeObjectURL(this.preview);
        this.preview = null;
        this.fileName = '';
        this.fileSize = '';
        this.$refs.input.value = '';
        this.$refs.camera && (this.$refs.camera.value = '');
    },

    fromCamera(event) {
        const file = event.target.files?.[0];
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        this.$refs.input.files = transfer.files;
        this.use(file);
    },

    submit(event) {
        if (!this.preview) {
            event.preventDefault();
            this.error = 'Pilih atau ambil foto makanan dulu.';
            return;
        }
        this.submitting = true;
        this.stageIndex = 0;
        this.timer = setInterval(() => {
            if (this.stageIndex < STAGES.length - 1) this.stageIndex++;
        }, 2800);
    },
});
