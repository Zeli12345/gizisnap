{{--
    Editor daftar makanan (Alpine: mealEditor). Dipakai di hasil scan dan formulir catatan.
    Satu baris per makanan; mengembang di tempat untuk mengubah angka.
    Variabel: $withSearch (bool)
--}}
<div class="space-y-4">
    @if ($withSearch ?? true)
        <div class="relative" @click.outside="searchOpen = false">
            <label for="cari-makanan" class="sr-only">Cari makanan</label>
            <div class="relative">
                <x-icon name="search" size="17" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-ink-3" />
                <input id="cari-makanan" type="search" x-model="query" @input="search()" @focus="search()" autocomplete="off"
                    placeholder="Cari makanan: nasi, tempe, bakso…" class="field py-3 pl-10">
            </div>
            <div x-show="searchOpen && (results.length || searching || searchError || query)" x-cloak
                x-transition:enter="transition duration-140 ease-smooth" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                class="float absolute inset-x-0 top-full z-20 mt-1.5 max-h-80 overflow-y-auto p-1.5">
                <p x-show="searching" class="px-3 py-2.5 text-[13.5px] text-ink-2">Mencari…</p>
                <p x-show="searchError" x-text="searchError" class="px-3 py-2.5 text-[13.5px] text-danger"></p>
                <template x-for="food in results" :key="food.id">
                    <div class="flex items-center gap-1 rounded-[5px] px-1.5 py-1 transition-colors duration-140 hover:bg-canvas">
                        <button type="button" class="min-w-0 flex-1 rounded-[4px] px-1.5 py-1.5 text-left" @click="addFood(food)">
                            <span class="block truncate text-[14px] font-semibold" x-text="food.name"></span>
                            <span class="num block truncate text-[12px] text-ink-3" x-text="food.serving_desc + ' · ' + format(food.calories_kcal) + ' kkal'"></span>
                        </button>
                        <button type="button" class="btn btn-quiet btn-sm min-w-9 px-2" @click="addFood(food, 0.5)" aria-label="Tambah setengah porsi">½</button>
                        <button type="button" class="btn btn-quiet btn-sm min-w-9 px-2" @click="addFood(food, 2)" aria-label="Tambah dua porsi">2×</button>
                    </div>
                </template>
                <p x-show="!searching && !searchError && query && results.length === 0" class="px-3 py-2.5 text-[13.5px] text-ink-2">
                    Tidak ketemu. Tambahkan sendiri dengan tombol di bawah.
                </p>
            </div>
        </div>
    @endif

    @error('items')<p class="error-text">{{ $message }}</p>@enderror

    <ul class="divide-y divide-line border-y border-line">
        <template x-for="(item, index) in items" :key="item.key">
            <li>
                <button type="button" class="flex w-full items-center gap-3 py-3 text-left" @click="item.open = !item.open" :aria-expanded="item.open">
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="text-[14.5px] font-semibold" x-text="item.name || 'Makanan baru'"></span>
                            <span x-show="item.needs_check" class="shrink-0 rounded-[3px] bg-sun-tint px-1.5 py-0.5 text-[10.5px] font-semibold text-sun-ink">Perlu dicek</span>
                        </span>
                        <span class="num mt-0.5 flex flex-wrap items-center gap-x-2.5 text-[12px] text-ink-3">
                            <span x-text="item.portion_desc"></span>
                            <span x-text="'P ' + format(item.protein_g) + ' g'"></span>
                            <span x-text="'K ' + format(item.carbs_g) + ' g'"></span>
                            <span x-text="'L ' + format(item.fat_g) + ' g'"></span>
                        </span>
                    </span>
                    <span class="num shrink-0 text-right text-[14.5px] font-semibold">
                        <span x-text="format(item.calories_kcal)"></span>
                        <span class="block text-[10.5px] font-medium text-ink-3">kkal</span>
                    </span>
                    <x-icon name="chevron-down" size="16" class="shrink-0 text-ink-3 transition-transform duration-[220ms] ease-smooth" ::class="item.open && 'rotate-180'" />
                </button>

                <div x-show="item.open" x-collapse.duration.220ms class="pb-4">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="col-span-2">
                            <label class="label text-[12.5px]" :for="'nama-' + item.key">Nama makanan</label>
                            <input class="field field-sm" data-item-name :id="'nama-' + item.key" x-model="item.name" maxlength="120" required>
                        </div>
                        <div class="col-span-2">
                            <label class="label text-[12.5px]" :for="'porsi-' + item.key">Porsi</label>
                            <input class="field field-sm" :id="'porsi-' + item.key" x-model="item.portion_desc" maxlength="80">
                        </div>
                        @foreach ([['calories_kcal', 'Kalori', 'kkal'], ['protein_g', 'Protein', 'g'], ['carbs_g', 'Karbohidrat', 'g'], ['fat_g', 'Lemak', 'g'], ['weight_g', 'Berat', 'g'], ['fiber_g', 'Serat', 'g'], ['sugar_g', 'Gula', 'g'], ['sodium_mg', 'Natrium', 'mg']] as [$key, $label, $unit])
                            <div>
                                <label class="label text-[12.5px]" :for="'{{ $key }}-' + item.key">{{ $label }} <span class="font-normal text-ink-3">({{ $unit }})</span></label>
                                <input type="number" inputmode="decimal" step="0.1" min="0" class="field field-sm num" :id="'{{ $key }}-' + item.key" x-model="item.{{ $key }}">
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <span class="mr-1 text-[12px] text-ink-3">Ubah porsi</span>
                            <button type="button" class="btn btn-quiet btn-sm min-w-10" @click="scale(index, 0.5)">½×</button>
                            <button type="button" class="btn btn-quiet btn-sm min-w-10" @click="scale(index, 1.5)">1½×</button>
                            <button type="button" class="btn btn-quiet btn-sm min-w-10" @click="scale(index, 2)">2×</button>
                        </div>
                        <button type="button" class="btn btn-danger btn-sm" @click="remove(index)">
                            <x-icon name="trash" size="14" /> Hapus
                        </button>
                    </div>
                </div>

                {{-- Nilai yang dikirim ke server --}}
                <template x-for="field in ['food_id','name','portion_desc','weight_g','calories_kcal','protein_g','carbs_g','fat_g','fiber_g','sugar_g','sodium_mg']">
                    <input type="hidden" :name="`items[${index}][${field}]`" :value="item[field] ?? ''">
                </template>
            </li>
        </template>
        <li x-show="items.length === 0" class="py-5 text-center text-[13.5px] text-ink-3">Belum ada makanan. Cari di atas, atau tambahkan sendiri.</li>
    </ul>

    <button type="button" class="btn btn-ghost w-full border border-dashed border-line-strong/50 py-3" @click="addBlank()">
        <x-icon name="plus" size="15" stroke="2" /> Tambah makanan sendiri
    </button>

    {{-- Total --}}
    <div class="rounded-control bg-paper px-4 py-4">
        <div class="flex items-baseline justify-between gap-3 border-b border-ink/12 pb-3">
            <span class="text-[13px] font-semibold">
                Total <span class="font-normal text-ink-3" x-text="'· ' + items.length + ' makanan'"></span>
            </span>
            <span class="num font-display text-[27px] leading-none font-semibold tracking-[-0.02em]">
                <span x-text="format(totals.calories_kcal)"></span>
                <span class="font-sans text-[12px] font-semibold text-ink-3">kkal</span>
            </span>
        </div>
        <dl class="num mt-3 grid grid-cols-3 gap-2">
            @foreach ([['Protein', 'protein', 'protein_g'], ['Karbohidrat', 'carbs', 'carbs_g'], ['Lemak', 'fat', 'fat_g']] as [$label, $color, $key])
                <div>
                    <dt class="flex items-center gap-1.5 text-[11.5px] text-ink-3">
                        <span class="size-1.5 rounded-full bg-{{ $color }}"></span>{{ $label }}
                    </dt>
                    <dd class="mt-0.5 text-[14px] font-semibold" x-text="format(totals.{{ $key }}) + ' g'"></dd>
                </div>
            @endforeach
        </dl>
    </div>
</div>
