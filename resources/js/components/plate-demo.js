import { formatNumber } from '../http';

const LABELS = { 0.5: '½', 1: '1', 1.5: '1½', 2: '2' };
const STEPS = [0.5, 1, 1.5, 2];

/**
 * Piring contoh di halaman depan. Mengubah porsi satu lauk langsung mengubah
 * total, supaya pengunjung merasakan alurnya sebelum mendaftar.
 */
export default (config) => ({
    items: config.items.map((item) => ({ ...item, mult: 1 })),

    get total() {
        return Math.round(this.items.reduce((sum, item) => sum + item.kcal * item.mult, 0));
    },

    kcal(item) {
        return Math.round(item.kcal * item.mult);
    },

    label(item) {
        return LABELS[item.mult];
    },

    canStep(item, dir) {
        const index = STEPS.indexOf(item.mult);
        return dir < 0 ? index > 0 : index < STEPS.length - 1;
    },

    step(item, dir) {
        if (!this.canStep(item, dir)) return;
        item.mult = STEPS[STEPS.indexOf(item.mult) + dir];
    },

    fmt(value) {
        return formatNumber(value);
    },
});
