@php
    $target = $summary['target'];
    $totals = $summary['totals'];
    $remaining = $summary['remaining_kcal'] ?? 0;
    $over = $remaining < 0;
    $eaten = $totals['calories_kcal'];
    $goal = $target?->calories_kcal ?? 0;
    $pct = $goal > 0 ? min(100, (int) round($eaten / $goal * 100)) : 0;
    $overPct = $goal > 0 ? (int) round($eaten / $goal * 100) : 0;

    // Penggaris 50 goresan: tiap goresan 2% dari target.
    $ticks = 50;
    $filled = (int) round($pct / 100 * $ticks);

    $macros = [
        ['label' => 'Protein', 'value' => $totals['protein_g'], 'target' => $target?->protein_g ?? 0, 'color' => 'protein'],
        ['label' => 'Karbohidrat', 'value' => $totals['carbs_g'], 'target' => $target?->carbs_g ?? 0, 'color' => 'carbs'],
        ['label' => 'Lemak', 'value' => $totals['fat_g'], 'target' => $target?->fat_g ?? 0, 'color' => 'fat'],
    ];

    $hour = (int) now()->format('G');
    $greeting = match (true) { $hour < 11 => 'Pagi', $hour < 15 => 'Siang', $hour < 18 => 'Sore', default => 'Malam' };
    $nowPct = $isToday ? min(100, ($hour + (int) now()->format('i') / 60) / 24 * 100) : null;
@endphp
<x-layouts.app title="Hari ini" :wide="true">
    <div>

        @if ($welcome)
            <div class="toast-in mb-7 flex items-start gap-3 rounded-surface border border-sun/20 bg-sun-tint px-5 py-4">
                <x-icon name="sparkle" size="17" class="mt-0.5 shrink-0 text-sun" />
                <div class="text-[14px] leading-relaxed">
                    <p class="font-semibold text-ink">Profilmu tersimpan. Target harianmu {{ gizi_num($goal) }} kkal.</p>
                    <p class="mt-0.5 text-ink-2">Mulai dengan memotret makanan pertama, atau catat manual kalau tidak sempat foto.</p>
                </div>
            </div>
        @endif

        {{-- Kepala --}}
        <header class="rise flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
            <div class="min-w-0">
                <p class="eyebrow">{{ $greeting }}, {{ auth()->user()->firstName() }}</p>
                <h1 class="display mt-2">{{ $isToday ? 'Hari ini' : $date->translatedFormat('l') }}</h1>
                <p class="num mt-1.5 text-[13.5px] text-ink-3">{{ $date->translatedFormat('j F Y') }}</p>
            </div>

            <nav class="flex items-center gap-1.5" aria-label="Pindah hari">
                <a href="{{ route('dashboard', ['date' => $date->subDay()->toDateString()]) }}"
                    class="group grid size-9 place-items-center rounded-full border border-ink/10 text-ink-2 transition-colors duration-140 ease-smooth hover:border-ink/20 hover:bg-sunk hover:text-ink" aria-label="Hari sebelumnya">
                    <x-icon name="chevron-left" size="16" class="transition-transform duration-[420ms] ease-spring group-hover:-translate-x-0.5" />
                </a>
                <label class="relative flex h-9 cursor-pointer items-center gap-2 rounded-full border border-ink/10 px-3.5 text-[13px] font-semibold transition-colors duration-140 hover:border-ink/20 hover:bg-sunk">
                    <x-icon name="calendar" size="14" class="text-ink-3" />
                    <span class="num whitespace-nowrap">{{ $date->translatedFormat('j M') }}</span>
                    <input type="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" aria-label="Pilih tanggal"
                        class="absolute inset-0 cursor-pointer opacity-0" @click="$el.showPicker?.()"
                        @change="window.location = '{{ route('dashboard') }}?date=' + $el.value">
                </label>
                @if ($isToday)
                    <span class="grid size-9 place-items-center rounded-full border border-ink/6 text-ink-3/25" aria-hidden="true"><x-icon name="chevron-right" size="16" /></span>
                @else
                    <a href="{{ route('dashboard', ['date' => $date->addDay()->toDateString()]) }}"
                        class="group grid size-9 place-items-center rounded-full border border-ink/10 text-ink-2 transition-colors duration-140 ease-smooth hover:border-ink/20 hover:bg-sunk hover:text-ink" aria-label="Hari berikutnya">
                        <x-icon name="chevron-right" size="16" class="transition-transform duration-[420ms] ease-spring group-hover:translate-x-0.5" />
                    </a>
                @endif
            </nav>
        </header>

        {{-- Kalori: angka terbesar di halaman, dengan penggaris di bawahnya --}}
        <section class="mt-9 border-t border-ink/10 pt-7" aria-labelledby="judul-kalori">
            <div class="grid gap-x-10 gap-y-5 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-end">
                <div>
                    <h2 id="judul-kalori" class="flex items-center gap-2 text-[12.5px] font-semibold text-ink-3">
                        <span @class(['size-1.5 rounded-full', 'bg-danger' => $over, 'bg-sun' => ! $over]) aria-hidden="true"></span>
                        {{ $over ? 'Lewat dari target' : 'Sisa kalori hari ini' }}
                    </h2>
                    <p class="num mt-2 flex items-baseline gap-2">
                        <span @class(['figure text-[68px] sm:text-[92px]', 'text-danger' => $over, 'text-ink' => ! $over]) data-count="{{ abs($remaining) }}" data-duration="1500">{{ gizi_num(abs($remaining)) }}</span>
                        <span class="text-[13px] font-semibold text-ink-3">kkal</span>
                    </p>
                </div>

                <dl class="num grid grid-cols-3 gap-x-6 text-[12.5px] sm:justify-self-end sm:pb-2.5">
                    <div>
                        <dt class="text-ink-3">Masuk</dt>
                        <dd class="mt-1 font-display text-[22px] leading-none font-semibold tracking-[-0.02em]">{{ gizi_num($eaten) }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-3">Target</dt>
                        <dd class="mt-1 font-display text-[22px] leading-none font-semibold tracking-[-0.02em]">{{ gizi_num($goal) }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-3">Terpakai</dt>
                        <dd @class(['mt-1 font-display text-[22px] leading-none font-semibold tracking-[-0.02em]', 'text-danger' => $over])>{{ $overPct }}%</dd>
                    </div>
                </dl>
            </div>

            <div class="mt-7" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Kalori masuk {{ $overPct }} persen dari target">
                <div class="flex h-8 items-end justify-between" aria-hidden="true">
                    @for ($i = 0; $i <= $ticks; $i++)
                        @php
                            $h = $i % 10 === 0 ? 32 : ($i % 5 === 0 ? 23 : 15);
                            $on = $i < $filled;
                        @endphp
                        <span @class(['tick block w-[2px] shrink-0 rounded-full', 'on' => $on, ($over ? 'bg-danger' : 'bg-sun') => $on, 'bg-ink/15' => ! $on]) style="height: {{ $h }}px; --i: {{ $i }}"></span>
                    @endfor
                </div>
                <div class="num mt-2 flex justify-between text-[10.5px] text-ink-3" aria-hidden="true">
                    <span>0</span>
                    <span>{{ gizi_num($goal / 2) }}</span>
                    <span>{{ gizi_num($goal) }}</span>
                </div>
            </div>
        </section>

        {{-- Makro --}}
        <section class="mt-8 grid gap-x-8 gap-y-5 sm:grid-cols-3" aria-label="Zat gizi hari ini">
            @foreach ($macros as $macro)
                <x-macro-row :label="$macro['label']" :value="$macro['value']" :target="$macro['target']" :color="$macro['color']" />
            @endforeach
        </section>

        {{-- Rel waktu: seluruh hari dalam satu garis, makanan sebagai penanda --}}
        <section class="mt-12" aria-labelledby="judul-rel">
            <div class="flex items-end justify-between gap-3">
                <h2 id="judul-rel" class="title">Sepanjang hari</h2>
                <a href="{{ route('meals.index') }}" class="text-[12.5px] font-semibold text-sun underline decoration-sun/25 underline-offset-[3px] transition-colors duration-140 hover:decoration-sun">Semua catatan</a>
            </div>

            <div class="mt-5">
                <div class="relative h-[52px]">
                    <div class="absolute inset-x-0 top-[33px] h-px bg-ink/12" aria-hidden="true"></div>

                    @foreach ($timeline as $n => $entry)
                        @php
                            $h = (int) $entry['at']->format('G');
                            $m = (int) $entry['at']->format('i');
                            $left = ($h + $m / 60) / 24 * 100;
                            $share = $goal > 0 ? min(100, $entry['calories'] / $goal * 100) : 0;
                            $size = 8 + (int) round($share / 100 * 16);
                        @endphp
                        <div class="pop absolute top-0 h-full -translate-x-1/2" style="left: {{ $left }}%; --d: {{ 300 + $n * 120 }}">
                            <span class="num absolute top-0 left-1/2 block w-max -translate-x-1/2 rounded-[4px] bg-sunk px-1.5 py-0.5 text-[10.5px] leading-none font-semibold text-ink-2">{{ $entry['at']->format('H.i') }}</span>
                            <span class="absolute left-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-sun shadow-[0_0_0_4px_rgb(240_176_78/0.14)]" style="top: 33px; width: {{ $size }}px; height: {{ $size }}px" title="{{ $entry['label'] }}, {{ gizi_num($entry['calories']) }} kkal"></span>
                        </div>
                    @endforeach

                    @if ($nowPct !== null)
                        <div class="absolute top-1 bottom-0 -translate-x-1/2" style="left: {{ $nowPct }}%" aria-hidden="true">
                            <span class="block h-full w-px bg-gradient-to-b from-transparent via-ink-3/60 to-ink-3/20"></span>
                            <span class="absolute top-[33px] left-1/2 size-[9px] -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-ink-3 bg-canvas"></span>
                        </div>
                    @endif
                </div>
                <div class="num flex justify-between border-t border-ink/8 pt-1.5 text-[10.5px] text-ink-3/70">
                    @foreach (['00', '06', '12', '18', '24'] as $tick)
                        <span>{{ $tick }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Isi hari --}}
        <div class="mt-12 grid items-start gap-x-10 gap-y-9 lg:grid-cols-[minmax(0,1.65fr)_minmax(0,1fr)]">

            <section aria-labelledby="judul-alur">
                <div class="flex items-end justify-between gap-3 border-b border-ink/10 pb-2.5">
                    <h2 id="judul-alur" class="title">Yang dimakan</h2>
                    <p class="num text-[12px] text-ink-3">{{ count($timeline) }} sesi</p>
                </div>

                @if ($timeline === [])
                    <div class="py-14 text-center">
                        <span class="relative mx-auto grid size-12 place-items-center rounded-full border border-ink/10 bg-sunk text-ink-3">
                            <span class="ping-ring absolute inset-0 rounded-full border border-sun/40" aria-hidden="true"></span>
                            <x-icon name="utensils" size="21" />
                        </span>
                        <p class="title mt-5">Piring masih kosong</p>
                        <p class="mx-auto mt-2 max-w-[22rem] text-[14px] leading-relaxed text-ink-2">
                            Belum ada yang dicatat {{ $isToday ? 'hari ini' : 'di hari itu' }}. Yang kamu catat akan muncul di sini, urut jam.
                        </p>
                        <div class="mt-6 flex flex-wrap justify-center gap-2">
                            @if ($isToday)
                                <a href="{{ route('scan.create') }}" class="btn btn-quiet"><x-icon name="camera" size="16" /> Foto makanan</a>
                            @endif
                            <a href="{{ route('meals.create', ['date' => $date->toDateString()]) }}" class="btn btn-primary">
                                <x-icon name="plus" size="16" stroke="2.2" /> Catat makanan pertama
                            </a>
                        </div>
                    </div>
                @else
                    <ol class="divide-y divide-ink/8">
                        @foreach ($timeline as $entry)
                            @php
                                $singleMeal = count($entry['meals']) === 1;
                                $firstMeal = $entry['meals'][0];
                                $photoScan = collect($entry['meals'])->firstWhere('scan_id')?->scan_id;
                                $details = collect($entry['meals'])->map(function ($meal) use ($entry) {
                                    $label = trim($entry['label']);
                                    $items = $meal->items;
                                    $text = $items->count() > 1
                                        ? $items->take(4)->pluck('name')->implode(', ')
                                        : trim((string) ($items->first()?->portion_desc ?? ''));

                                    return ['meal' => $meal, 'text' => ($text === '' || $text === $label) ? null : $text];
                                });
                            @endphp
                            <li class="group relative -mx-3 flex gap-4 rounded-[10px] px-3 py-4 transition-colors duration-140 ease-smooth hover:bg-paper/70" data-reveal style="--d: {{ $loop->index * 60 }}">
                                <div class="w-[44px] shrink-0">
                                    <span class="num block text-[13px] font-semibold text-ink">{{ $entry['at']->format('H.i') }}</span>
                                    <span class="mt-0.5 block text-[10.5px] text-ink-3">{{ $entry['part_of_day'] }}</span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline justify-between gap-3">
                                        @if ($singleMeal)
                                            <a href="{{ route('meals.edit', $firstMeal) }}" class="min-w-0 text-[15px] leading-snug font-semibold transition-colors duration-140 after:absolute after:inset-0 group-hover:text-sun">{{ $entry['label'] }}</a>
                                        @else
                                            <p class="min-w-0 text-[15px] leading-snug font-semibold">{{ $entry['label'] }}</p>
                                        @endif
                                        <p class="num shrink-0 text-[14px] font-semibold">
                                            {{ gizi_num($entry['calories']) }} <span class="text-[11.5px] font-normal text-ink-3">kkal</span>
                                        </p>
                                    </div>

                                    @if ($singleMeal)
                                        @if ($details[0]['text'])
                                            <p class="mt-0.5 line-clamp-2 text-[13px] leading-snug text-ink-2">{{ $details[0]['text'] }}</p>
                                        @endif
                                    @else
                                        <ul class="mt-1.5 space-y-px">
                                            @foreach ($details as $detail)
                                                <li class="relative z-10">
                                                    <a href="{{ route('meals.edit', $detail['meal']) }}" class="-mx-1.5 flex items-baseline justify-between gap-3 rounded-[4px] px-1.5 py-0.5 text-[13px] transition-colors duration-140 hover:bg-sunk">
                                                        <span class="min-w-0 truncate text-ink-2">{{ $detail['meal']->title }}</span>
                                                        <span class="num shrink-0 text-ink-3">{{ gizi_num($detail['meal']->total_calories_kcal) }}</span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if ($firstMeal->notes ?? null)
                                        <p class="mt-1 truncate text-[12px] text-ink-3 italic">{{ $firstMeal->notes }}</p>
                                    @endif

                                    <p class="num mt-2 flex flex-wrap gap-x-4 gap-y-0.5 text-[11.5px] text-ink-3">
                                        <span class="flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-protein"></span>{{ gizi_num($entry['protein']) }} g</span>
                                        <span class="flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-carbs"></span>{{ gizi_num($entry['carbs']) }} g</span>
                                        <span class="flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-fat"></span>{{ gizi_num($entry['fat']) }} g</span>
                                    </p>
                                </div>

                                @if ($photoScan)
                                    <img src="{{ route('scan.photo', $photoScan) }}" alt="" width="52" height="52" class="hidden size-[52px] shrink-0 rounded-[8px] border border-ink/10 object-cover sm:block" loading="lazy">
                                @endif
                            </li>
                        @endforeach
                    </ol>

                    <div class="mt-5 flex flex-wrap gap-2">
                        @if ($isToday)
                            <a href="{{ route('scan.create') }}" class="btn btn-primary"><x-icon name="camera" size="16" /> Foto makanan</a>
                        @endif
                        <a href="{{ route('meals.create', ['date' => $date->toDateString()]) }}" class="btn btn-quiet"><x-icon name="plus" size="16" stroke="2.2" /> Catat makanan</a>
                    </div>
                @endif
            </section>

            {{-- Sisi kanan --}}
            <aside class="space-y-5">
                @if ($isToday)
                    <section class="panel p-5" aria-labelledby="judul-air"
                        x-data="water({ water: {{ $summary['water_ml'] }}, target: {{ $summary['water_target_ml'] }}, addUrl: '{{ route('water.store') }}', undoUrl: '{{ route('water.destroy-latest') }}' })">
                        <div class="flex items-center justify-between gap-3">
                            <h2 id="judul-air" class="text-[12.5px] font-semibold text-ink-3">Air minum</h2>
                            <div class="flex shrink-0 items-center gap-1">
                                <button type="button" class="btn grid size-8 place-items-center rounded-full p-0 text-ink-3 hover:bg-sunk hover:text-ink disabled:opacity-25" @click="undo()" :disabled="busy || water <= 0" aria-label="Batalkan satu gelas">
                                    <x-icon name="minus" size="15" />
                                </button>
                                <button type="button" class="btn grid size-8 place-items-center rounded-full bg-water/15 p-0 text-water hover:bg-water/25 disabled:opacity-30" @click="add()" :disabled="busy" aria-label="Tambah satu gelas">
                                    <x-icon name="plus" size="16" stroke="2.2" />
                                </button>
                            </div>
                        </div>
                        <p class="num mt-2.5">
                            <span class="font-display text-[30px] leading-none font-semibold tracking-[-0.02em] text-water" x-text="format(water)">{{ gizi_num($summary['water_ml']) }}</span>
                            <span class="text-[12px] font-medium text-ink-3"> / <span x-text="format(target)">{{ gizi_num($summary['water_target_ml']) }}</span> ml</span>
                        </p>
                        <div class="mt-3.5 flex gap-1" aria-hidden="true">
                            <template x-for="i in targetGlasses" :key="i">
                                <span class="relative h-7 flex-1 overflow-hidden rounded-[5px] border border-ink/10 bg-sunk">
                                    <span class="absolute inset-x-0 bottom-0 bg-water/75 transition-[height] duration-[520ms] ease-expo" :style="`height: ${i <= glasses ? 100 : 0}%`"></span>
                                </span>
                            </template>
                        </div>
                        <p x-show="error" x-text="error" class="error-text" x-cloak></p>
                    </section>
                @endif

                @if ($streak > 1)
                    <p class="flex items-start gap-2.5 text-[13px] leading-relaxed text-ink-2">
                        <x-icon name="flame" size="15" class="mt-0.5 shrink-0 text-sun" />
                        <span><span class="num font-semibold text-ink">{{ $streak }} hari</span> berturut-turut kamu mencatat makan.</span>
                    </p>
                @endif

                <section class="panel p-5" aria-labelledby="judul-serat">
                    <h2 id="judul-serat" class="text-[12.5px] font-semibold text-ink-3">Serat, gula, natrium</h2>
                    <dl class="mt-2.5 space-y-2 text-[13px]">
                        @foreach ([['Serat', $totals['fiber_g'], 1, 'g'], ['Gula', $totals['sugar_g'], 1, 'g'], ['Natrium', $totals['sodium_mg'], 0, 'mg']] as [$label, $value, $decimals, $unit])
                            @php $high = $label === 'Natrium' && $value > 2000; @endphp
                            <div class="flex items-baseline justify-between gap-3 border-b border-ink/6 pb-2 last:border-0 last:pb-0">
                                <dt class="text-ink-3">{{ $label }}</dt>
                                <dd @class(['num font-semibold', 'text-danger' => $high])>
                                    {{ gizi_num($value, $decimals) }} <span class="text-[11px] font-normal text-ink-3">{{ $unit }}</span>
                                    @if ($high)<span class="text-[11px]">· tinggi</span>@endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <a href="{{ route('reports.index') }}" class="panel panel-tap group flex items-center justify-between gap-3 px-5 py-3.5">
                    <span>
                        <span class="block text-[13px] font-semibold">Lihat polamu</span>
                        <span class="mt-0.5 block text-[11.5px] text-ink-3">7 hari terakhir</span>
                    </span>
                    <x-icon name="chevron-right" size="15" class="shrink-0 text-ink-3 transition-transform duration-[420ms] ease-spring group-hover:translate-x-1" />
                </a>
            </aside>
        </div>
    </div>
</x-layouts.app>
