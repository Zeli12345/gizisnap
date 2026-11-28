import { formatNumber } from '../http';

const STAGES = ['Mengenali tiap lauk', 'Menaksir porsi', 'Menghitung kalori dan makro', 'Menyusun hasilnya'];

/**
 * Pilih atau ambil foto, tampilkan pratinjau, lalu kirim formulir.
 * Kamera langsung memakai getUserMedia: pratinjau video, tombol rana, dan
 * hasilnya ditempel ke input yang sama supaya formulir tetap satu jalur.
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

    cameraOpen: false,
    cameraReady: false,
    cameraBusy: false,
    cameraError: '',
    cameraCount: 1,
    facingMode: 'environment',
    hasTorch: false,
    torchOn: false,
    capturing: false,
    stream: null,

    get stage() {
        return STAGES[this.stageIndex];
    },

    get mirrored() {
        return this.facingMode === 'user';
    },

    pick(event) {
        const file = event.target.files?.[0];
        this.use(file);
    },

    drop(event) {
        this.dragging = false;
        const file = event.dataTransfer.files?.[0];
        if (!file) return;
        this.attach(file);
        this.use(file);
    },

    /** Salin file ke input formulir utama. */
    attach(file) {
        const transfer = new DataTransfer();
        transfer.items.add(file);
        this.$refs.input.files = transfer.files;
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
    },

    /* ---------- Kamera langsung ---------- */

    async openCamera() {
        this.cameraOpen = true;
        document.body.style.overflow = 'hidden';
        this.cameraError = '';
        await this.startStream();
    },

    async startStream() {
        this.stopStream();
        this.cameraReady = false;
        this.cameraBusy = true;
        this.torchOn = false;
        this.hasTorch = false;

        try {
            if (!window.isSecureContext) {
                throw new Error('insecure');
            }
            if (!navigator.mediaDevices?.getUserMedia) {
                throw new Error('unsupported');
            }

            const stream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: {
                    facingMode: { ideal: this.facingMode },
                    width: { ideal: 1920 },
                    height: { ideal: 1440 },
                },
            });

            this.stream = stream;

            const video = this.$refs.video;
            video.srcObject = stream;
            video.muted = true;
            await video.play().catch(() => {});

            this.cameraReady = true;

            const track = stream.getVideoTracks()[0];
            this.hasTorch = Boolean(track?.getCapabilities?.().torch);

            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                this.cameraCount = devices.filter((device) => device.kind === 'videoinput').length;
            } catch {
                this.cameraCount = 1;
            }
        } catch (problem) {
            this.cameraError = this.cameraMessage(problem);
            this.cameraReady = false;
        } finally {
            this.cameraBusy = false;
        }
    },

    cameraMessage(problem) {
        const name = problem?.name || problem?.message;

        if (name === 'insecure') {
            return 'Kamera langsung hanya bisa dipakai lewat HTTPS atau localhost. Pakai tombol Galeri, atau buka situs ini lewat alamat aman.';
        }
        if (name === 'unsupported') {
            return 'Peramban ini belum mendukung kamera langsung. Pakai tombol Galeri untuk memilih foto.';
        }
        if (name === 'NotAllowedError' || name === 'SecurityError') {
            return 'Izin kamera ditolak. Izinkan akses kamera di pengaturan peramban, lalu coba lagi.';
        }
        if (name === 'NotFoundError' || name === 'OverconstrainedError') {
            return 'Tidak ada kamera yang bisa dipakai. Pakai tombol Galeri untuk memilih foto.';
        }
        if (name === 'NotReadableError' || name === 'AbortError') {
            return 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi itu, lalu coba lagi.';
        }

        return 'Kamera tidak bisa dibuka. Pakai tombol Galeri untuk memilih foto.';
    },

    stopStream() {
        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }
        if (this.$refs.video) {
            this.$refs.video.srcObject = null;
        }
        this.cameraReady = false;
        this.torchOn = false;
    },

    closeCamera() {
        this.stopStream();
        this.cameraOpen = false;
        this.cameraError = '';
        document.body.style.overflow = '';
    },

    async switchCamera() {
        this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
        await this.startStream();
    },

    async toggleTorch() {
        const track = this.stream?.getVideoTracks?.()[0];
        if (!track || !this.hasTorch) return;

        try {
            this.torchOn = !this.torchOn;
            await track.applyConstraints({ advanced: [{ torch: this.torchOn }] });
        } catch {
            this.torchOn = false;
            this.hasTorch = false;
        }
    },

    async capture() {
        const video = this.$refs.video;
        if (!video || !this.cameraReady || video.readyState < 2) return;

        this.capturing = true;

        try {
            // Batasi sisi terpanjang supaya hasil tetap ringan untuk diunggah.
            const maxSide = 1600;
            const sourceWidth = video.videoWidth;
            const sourceHeight = video.videoHeight;
            const scale = Math.min(1, maxSide / Math.max(sourceWidth, sourceHeight));
            const width = Math.max(1, Math.round(sourceWidth * scale));
            const height = Math.max(1, Math.round(sourceHeight * scale));

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d').drawImage(video, 0, 0, width, height);

            const blob = await new Promise((resolve, reject) => {
                canvas.toBlob(
                    (result) => (result ? resolve(result) : reject(new Error('capture'))),
                    'image/jpeg',
                    0.9,
                );
            });

            const file = new File([blob], `scan-${Date.now()}.jpg`, { type: 'image/jpeg' });
            this.attach(file);
            this.use(file);
            this.closeCamera();
        } catch {
            this.error = 'Gagal mengambil gambar dari kamera. Coba lagi.';
        } finally {
            this.capturing = false;
        }
    },

    submit(event) {
        if (!this.preview) {
            event.preventDefault();
            this.error = 'Pilih atau ambil foto makanan dulu.';
            return;
        }
        this.stopStream();
        this.submitting = true;
        this.stageIndex = 0;
        this.timer = setInterval(() => {
            if (this.stageIndex < STAGES.length - 1) this.stageIndex++;
        }, 2800);
    },

    destroy() {
        this.stopStream();
        if (this.timer) clearInterval(this.timer);
        if (this.cameraOpen) document.body.style.overflow = '';
    },
});
