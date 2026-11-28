import { sendJson } from '../http';

/**
 * Halaman laporan meminta analisis AI otomatis saat belum ada laporan
 * yang cocok dengan data terbaru. Setelah selesai, halaman dimuat ulang.
 */
export default ({ url, period, needs }) => ({
    loading: false,
    error: '',
    period,

    init() {
        if (needs) this.run();
    },

    async run() {
        this.loading = true;
        this.error = '';

        try {
            await sendJson(url, { method: 'POST', body: { period: this.period } });
            window.location.reload();
        } catch (e) {
            this.error = e.message;
        } finally {
            this.loading = false;
        }
    },
});
