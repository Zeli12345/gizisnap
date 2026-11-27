import { formatNumber } from '../http';

const NUTRIENTS = ['calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g', 'sugar_g', 'sodium_mg'];

const blankItem = () => ({
    key: crypto.randomUUID?.() ?? String(Math.random()),
    food_id: null,
    name: '',
    portion_desc: '1 porsi',
    weight_g: '',
    calories_kcal: '',
    protein_g: '',
    carbs_g: '',
    fat_g: '',
    fiber_g: 0,
    sugar_g: 0,
    sodium_mg: 0,
    needs_check: false,
    open: true,
});

/**
 * Editor daftar makanan: dipakai di hasil scan, catatan manual, dan ubah catatan.
 * Total di layar hanya pratinjau; server menghitung ulang saat menyimpan.
 */
export default (config) => ({
    items: (config.items ?? []).map((item) => ({ ...blankItem(), ...item, open: false })),
    searchUrl: config.searchUrl,
    query: '',
    results: [],
    searching: false,
    searchOpen: false,
    searchError: '',
    timer: null,

    init() {
        if (this.items.length === 0 && config.startWithBlank) {
            this.items.push(blankItem());
        }
    },

    get totals() {
        const totals = {};
        for (const key of NUTRIENTS) {
            totals[key] = this.items.reduce((sum, item) => sum + (parseFloat(item[key]) || 0), 0);
        }
        return totals;
    },

    format(value, decimals = 0) {
        return formatNumber(value, decimals);
    },

    addBlank() {
        this.items.forEach((item) => (item.open = false));
        this.items.push(blankItem());
        this.$nextTick(() => {
            const inputs = this.$root.querySelectorAll('[data-item-name]');
            inputs[inputs.length - 1]?.focus();
        });
    },

    remove(index) {
        this.items.splice(index, 1);
    },

    search() {
        clearTimeout(this.timer);
        this.searchOpen = true;
        this.timer = setTimeout(async () => {
            this.searching = true;
            this.searchError = '';
            try {
                const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(this.query)}`, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) throw new Error();
                this.results = (await response.json()).data;
            } catch {
                this.searchError = 'Pencarian gagal. Periksa koneksi lalu coba lagi.';
                this.results = [];
            } finally {
                this.searching = false;
            }
        }, 220);
    },

    addFood(food, servings = 1) {
        const item = blankItem();
        Object.assign(item, {
            food_id: food.id,
            name: food.name,
            portion_desc: servings === 1 ? food.serving_desc : `${formatNumber(servings, 1)} × ${food.serving_desc}`,
            weight_g: round(food.serving_weight_g * servings),
            open: false,
        });
        for (const key of NUTRIENTS) {
            item[key] = round((parseFloat(food[key]) || 0) * servings);
        }
        this.items.push(item);
        this.query = '';
        this.results = [];
        this.searchOpen = false;
    },

    /**
     * Ubah porsi: semua angka gizi ikut dikalikan.
     */
    scale(index, factor) {
        const item = this.items[index];
        for (const key of [...NUTRIENTS, 'weight_g']) {
            if (item[key] !== '' && item[key] !== null) item[key] = round((parseFloat(item[key]) || 0) * factor);
        }
        const match = String(item.portion_desc).match(/^([\d.,]+)\s*(×\s*)?(.*)$/);
        if (match && !match[2]) {
            const amount = parseFloat(match[1].replace(',', '.')) * factor;
            item.portion_desc = `${formatNumber(amount, 1)} ${match[3]}`.trim();
        } else {
            item.portion_desc = `${formatNumber(factor, 1)} × ${item.portion_desc}`;
        }
    },
});

function round(value) {
    return Math.round(value * 10) / 10;
}
