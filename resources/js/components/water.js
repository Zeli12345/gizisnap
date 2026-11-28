import { sendJson, formatNumber } from '../http';

const VESSELS = [
    { id: 'gelas', label: 'Gelas', ml: 250 },
    { id: 'mug', label: 'Mug', ml: 350 },
    { id: 'tumbler', label: 'Tumbler', ml: 500 },
    { id: 'botol', label: 'Botol', ml: 600 },
    { id: 'botol-besar', label: 'Botol besar', ml: 1000 },
];

// Laju minum wajar untuk memperkirakan waktu habis.
const PACE_ML_PER_HOUR = 250;
const STORAGE_KEY = 'gizisnap.water.vessel';

export default (config) => ({
    water: config.water,
    target: config.target,
    busy: false,
    error: '',
    vessels: VESSELS,
    vesselId: VESSELS[0].id,
    amountMl: VESSELS[0].ml,
    lastEntry: '',
    now: Date.now(),
    clock: null,

    init() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (VESSELS.some((vessel) => vessel.id === saved)) this.vesselId = saved;
        } catch {
            // localStorage bisa diblokir; pilihan tetap jalan tanpa disimpan.
        }

        this.amountMl = this.vessel.ml;

        this.clock = setInterval(() => {
            this.now = Date.now();
        }, 60000);
    },

    destroy() {
        if (this.clock) clearInterval(this.clock);
    },

    get vessel() {
        return this.vessels.find((item) => item.id === this.vesselId) ?? this.vessels[0];
    },

    get targetGlasses() {
        return Math.max(1, Math.round(this.target / this.vessel.ml));
    },

    get percent() {
        return this.target > 0 ? Math.min(100, Math.round((this.water / this.target) * 100)) : 0;
    },

    get remaining() {
        return Math.max(0, this.target - this.water);
    },

    get reached() {
        return this.target > 0 && this.water >= this.target;
    },

    get remainingLabel() {
        return this.reached ? 'Target tercapai' : `Sisa ${this.format(this.remaining)} ml`;
    },

    get minutesToFinish() {
        if (this.reached) return 0;

        const raw = (this.remaining / PACE_ML_PER_HOUR) * 60;

        return Math.max(15, Math.ceil(raw / 15) * 15);
    },

    get durationLabel() {
        const minutes = this.minutesToFinish;
        if (minutes <= 0) return '';

        if (minutes < 60) return `${minutes} menit`;

        const hours = Math.floor(minutes / 60);
        const rest = minutes % 60;

        return rest > 0 ? `${hours} jam ${rest} menit` : `${hours} jam`;
    },

    get finishTime() {
        if (this.reached) return '';

        const finish = new Date(this.now + this.minutesToFinish * 60000);

        return new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }).format(finish);
    },

    format(value) {
        return formatNumber(value);
    },

    formatLiters(ml) {
        return `${formatNumber(ml / 1000, 2)} L`;
    },

    setVessel(id) {
        if (!this.vessels.some((item) => item.id === id)) return;

        this.vesselId = id;
        this.amountMl = this.vessel.ml;
        this.lastEntry = '';

        try {
            localStorage.setItem(STORAGE_KEY, id);
        } catch {
            // Abaikan kalau penyimpanan diblokir.
        }
    },

    async add(amount = this.amountMl) {
        const ml = Math.round(Number(amount));

        if (!Number.isFinite(ml) || ml < 50 || ml > 2000) {
            this.error = 'Jumlah air harus antara 50 dan 2.000 ml.';
            return;
        }

        this.error = '';
        await this.call(config.addUrl, 'POST', {
            amount_ml: ml,
            vessel: this.vesselId,
        });

        if (!this.error) {
            this.lastEntry = `${this.vessel.label} ${this.format(ml)} ml`;
        }
    },

    async undo() {
        if (this.water <= 0) return;
        this.lastEntry = '';
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
