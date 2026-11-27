import { sendJson, formatNumber } from '../http';

export default (config) => ({
    water: config.water,
    target: config.target,
    glass: 250,
    busy: false,
    error: '',

    get glasses() {
        return Math.round(this.water / this.glass);
    },

    get targetGlasses() {
        return Math.max(1, Math.round(this.target / this.glass));
    },

    get percent() {
        return Math.min(100, Math.round((this.water / this.target) * 100));
    },

    format(value) {
        return formatNumber(value);
    },

    async add() {
        await this.call(config.addUrl, 'POST', { amount_ml: this.glass });
    },

    async undo() {
        if (this.water <= 0) return;
        await this.call(config.undoUrl, 'DELETE');
    },

    async call(url, method, body = null) {
        this.busy = true;
        this.error = '';
        try {
            const data = await sendJson(url, { method, body });
            this.water = data.water_ml;
            this.target = data.water_target_ml;
        } catch (e) {
            this.error = e.message;
        } finally {
            this.busy = false;
        }
    },
});
