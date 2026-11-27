import { formatNumber } from '../http';

/**
 * Penghitung target di halaman depan. Rumus, penyesuaian tujuan, dan pembagian
 * makro sama persis dengan App\Services\NutritionCalculator, supaya angka di
 * sini sama dengan yang didapat setelah mendaftar.
 */
export default (config) => ({
    gender: 'female',
    age: 16,
    height: 160,
    weight: 52,
    level: 'light',
    goal: 'maintain',
    factors: config.factors,
    adjustments: config.adjustments,
    adultAge: config.adultAge,
    split: config.split,
    shown: 0,
    frame: null,

    init() {
        this.shown = this.kcal;
        this.$watch('kcal', (next) => this.glide(next));
    },

    get resting() {
        const base = 10 * this.weight + 6.25 * this.height - 5 * this.age;
        return this.gender === 'male' ? base + 5 : base - 161;
    },

    get factor() {
        return this.factors[this.level] ?? 1.2;
    },

    /** Remaja tidak diberi pengurangan kalori. */
    get lockedLose() {
        return this.age < this.adultAge && this.goal === 'lose';
    },

    get adjustment() {
        return this.lockedLose ? 0 : (this.adjustments[this.goal] ?? 0);
    },

    get kcal() {
        return Math.round(Math.max(this.resting, this.resting * this.factor * (1 + this.adjustment)));
    },

    get protein() {
        return Math.round((this.kcal * this.split.protein) / 4);
    },

    get fat() {
        return Math.round((this.kcal * this.split.fat) / 9);
    },

    get carbs() {
        return Math.round((this.kcal * this.split.carbs) / 4);
    },

    get adjustmentLabel() {
        if (this.adjustment === 0) return 'tanpa penyesuaian';
        const pct = Math.round(Math.abs(this.adjustment) * 100);
        return `${this.adjustment > 0 ? '+' : '−'}${pct}%`;
    },

    get display() {
        return formatNumber(this.shown);
    },

    fmt(value, decimals = 0) {
        return formatNumber(value, decimals);
    },

    /** Angka meluncur ke nilai baru, bukan melompat. */
    glide(next) {
        cancelAnimationFrame(this.frame);
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.shown = next;
            return;
        }
        const from = this.shown;
        const start = performance.now();
        const duration = 520;
        const step = (now) => {
            const t = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - t, 4);
            this.shown = Math.round(from + (next - from) * eased);
            if (t < 1) this.frame = requestAnimationFrame(step);
        };
        this.frame = requestAnimationFrame(step);
    },
});
