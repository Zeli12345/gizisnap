import { formatNumber } from '../http';

/**
 * Formulir onboarding tiga langkah dengan perkiraan target langsung.
 * Rumus sama dengan App\Services\NutritionCalculator (Mifflin-St Jeor).
 */
export default (config) => ({
    step: config.step ?? 1,
    form: config.old,
    factors: config.factors,
    adjustments: config.adjustments,
    adultAge: config.adultAge,

    get age() {
        if (!this.form.birth_date) return null;
        const birth = new Date(this.form.birth_date);
        const now = new Date();
        let age = now.getFullYear() - birth.getFullYear();
        const m = now.getMonth() - birth.getMonth();
        if (m < 0 || (m === 0 && now.getDate() < birth.getDate())) age--;
        return age;
    },

    get isMinor() {
        return this.age !== null && this.age < this.adultAge;
    },

    get estimate() {
        const { gender, height_cm, weight_kg, activity_level, goal } = this.form;
        if (!gender || !height_cm || !weight_kg || this.age === null) return null;

        const base = 10 * weight_kg + 6.25 * height_cm - 5 * this.age;
        const resting = gender === 'male' ? base + 5 : base - 161;
        const factor = this.factors[activity_level] ?? 1.2;
        const adjust = this.isMinor && goal === 'lose' ? 0 : (this.adjustments[goal] ?? 0);
        return Math.round(Math.max(resting, resting * factor * (1 + adjust)));
    },

    formatted(value) {
        return formatNumber(value);
    },

    canContinue() {
        if (this.step === 1) {
            return this.form.gender && this.form.birth_date && this.form.height_cm && this.form.weight_kg;
        }
        if (this.step === 2) return !!this.form.activity_level;
        return !!this.form.goal;
    },

    next() {
        if (this.canContinue() && this.step < 3) {
            this.step++;
            this.$nextTick(() => this.$refs.panel?.focus());
        }
    },

    back() {
        if (this.step > 1) this.step--;
    },

    init() {
        this.$watch('isMinor', (minor) => {
            if (minor && this.form.goal === 'lose') this.form.goal = 'maintain';
        });
    },
});
