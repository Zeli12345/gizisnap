@php
    $macroTotal = max(1, $stats['avg_protein'] * 4 + $stats['avg_carbs'] * 4 + $stats['avg_fat'] * 9);
    $split = [
        ['Protein', 'protein', $stats['avg_protein'], (int) round($stats['avg_protein'] * 4 / $macroTotal * 100), 20],
        ['Karbohidrat', 'carbs', $stats['avg_carbs'], (int) round($stats['avg_carbs'] * 4 / $macroTotal * 100), 55],
        ['Lemak', 'fat', $stats['avg_fat'], (int) round($stats['avg_fat'] * 9 / $macroTotal * 100), 25],
    ];
@endphp
<x-layouts.app title="Laporan" :wide="true">
    <x-page-header title="Laporan">
        <x-slot:subtitle>Polamu {{ $period }} hari terakhir, dibandingkan targetmu sendiri.</x-slot:subtitle>
        <x-slot:actions>
            <nav class="seg" aria-label="Periode">
                <a href="{{ route('reports.index', ['period' => 7]) }}" @if ($period === 7) aria-current="page" @endif>7 hari</a>
                <a href="{{ route('reports.index', ['period' => 30]) }}" @if ($period === 30) aria-current="page" @endif>30 hari</a>
            </nav>
        </x-slot:actions>
    </x-page-header>

    @if ($stats['logged_days'] === 0)
        <x-empty-state icon="chart" title="Belum ada data untuk periode ini">
            Laporan terisi setelah kamu mencatat makan beberapa hari.
            <x-slot:action><a href="{{ route('scan.create') }}" class="btn btn-primary">Scan makanan</a></x-slot:action>
        </x-empty-state>
    @else
        {{-- Angka kunci --}}
        <div class="grid grid-cols-2 gap-x-6 gap-y-5 border-b border-ink/8 pb-6 sm:grid-cols-4">
            <x-stat label="Rata-rata kalori" :value="gizi_num($stats['avg_calories'])" unit="kkal" :note="$target ? 'Target '.gizi_num($target->calories_kcal).' kkal' : null" />
            <x-stat label="Hari tercatat" :value="$stats['logged_days']" :unit="'dari '.$stats['days']" :note="$stats['within_target_days'].' hari dalam rentang'" />
            <x-stat label="Piring seimbang" :value="$balance['balanced']" unit="hari" :note="$balance['rate'].'% dari '.$balance['days'].' hari'" />
            <x-stat label="Rata-rata protein" :value="gizi_num($stats['avg_protein'])" unit="g" color="protein" :note="$target ? 'Target '.gizi_num($target->protein_g).' g' : null" />
        </div>

        {{-- Laporan AI: otomatis dibuat saat data berubah, lalu disimpan. --}}
        <section class="mt-6 overflow-hidden rounded-surface border border-sun/20 bg-paper/90"
            aria-labelledby="judul-laporan-ai"
            x-data="reportInsight({ url: '{{ route('reports.ai') }}', period: {{ $period }}, needs: {{ $needs_report ? 'true' : 'false' }} })">
            <div class="border-b border-ink/8 bg-sun-tint/40 px-5 py-4 sm:px-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-sun text-canvas">
                            <x-icon name="sparkle" size="19" />
                        </span>
                        <div class="min-w-0">
                            <p class="eyebrow">Analisis AI</p>
                            <h2 id="judul-laporan-ai" class="title text-[19px]">Laporan {{ $period === 7 ? 'mingguan' : 'bulanan' }}</h2>
                            <p class="mt-0.5 text-[12.5px] leading-relaxed text-ink-2">Ringkasan otomatis dari catatan {{ $period }} hari terakhir.</p>
                        </div>
                    </div>
                    @if ($report?->generated_at)
                        <p class="num shrink-0 text-[11.5px] text-ink-3">Diperbarui {{ $report->generated_at->diffForHumans() }}</p>
                    @endif
                </div>
            </div>

            {{-- Sedang menganalisis --}}
            <div x-show="loading" x-cloak class="px-5 py-6 sm:px-6" role="status">
                <div class="flex items-center gap-3">
                    <span class="size-4 shrink-0 animate-spin rounded-full border-2 border-ink/20 border-t-sun" aria-hidden="true"></span>
                    <div>
                        <p class="text-[13.5px] font-semibold">AI sedang membaca catatanmu…</p>
                        <p class="mt-0.5 text-[12px] text-ink-3">Biasanya beberapa detik. Halaman akan diperbarui otomatis.</p>
                    </div>
                </div>
                <div class="mt-5 space-y-2.5" aria-hidden="true">
                    <div class="h-3 w-2/3 animate-pulse rounded-full bg-sunk"></div>
                    <div class="h-3 w-full animate-pulse rounded-full bg-sunk"></div>
                    <div class="h-3 w-5/6 animate-pulse rounded-full bg-sunk"></div>
                </div>
            </div>

            {{-- Gagal --}}
            <div x-show="error" x-cloak class="px-5 py-6 sm:px-6">
                <div class="flex items-start gap-2.5 text-danger">
                    <x-icon name="alert" size="17" class="mt-0.5 shrink-0" />
                    <div>
                        <p class="text-[13.5px] leading-relaxed" x-text="error"></p>
                        <button type="button" class="btn btn-quiet btn-sm mt-3" @click="run()" :disabled="loading">
                            <x-icon name="refresh" size="14" /> Coba lagi
                        </button>
                    </div>
                </div>
            </div>

            @if ($report)
                @php $content = $report->content ?? []; @endphp
                <div class="p-5 sm:p-6">
                    <h3 class="font-display text-[24px] leading-tight font-semibold tracking-[-0.02em]">{{ $content['headline'] ?? 'Laporan pola makanmu' }}</h3>
                    <p class="mt-2 text-[14px] leading-relaxed text-ink-2">{{ $content['summary'] ?? '' }}</p>

                    @if (! empty($content['changes']))
                        <div class="mt-5 grid gap-2.5 sm:grid-cols-2">
                            @foreach ($content['changes'] as $change)
                                <div class="rounded-control border border-ink/10 bg-sunk/40 p-3.5">
                                    <p class="text-[12.5px] font-semibold text-sun-ink">{{ $change['title'] }}</p>
                                    <p class="mt-1 text-[12.5px] leading-relaxed text-ink-2">{{ $change['detail'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (! empty($content['body']))
                        <div class="mt-4 rounded-control border border-ink/10 bg-paper px-4 py-3.5">
                            <p class="text-[11px] font-semibold tracking-[0.05em] text-ink-3 uppercase">Yang mungkin terjadi pada tubuh</p>
                            <ul class="mt-2 space-y-1.5 text-[13px] leading-relaxed text-ink-2">
                                @foreach ($content['body'] as $item)
                                    <li class="flex gap-2">
                                        <span class="mt-[7px] size-1 shrink-0 rounded-full bg-sun" aria-hidden="true"></span>
                                        {{ $item }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-4 grid gap-2.5 sm:grid-cols-2">
                        @if (! empty($content['reduce']))
                            <div class="rounded-control border border-danger/15 bg-danger-tint/25 p-3.5">
                                <p class="text-[11px] font-semibold tracking-[0.04em] text-danger uppercase">Kurangi / hindari</p>
                                <ul class="mt-2 space-y-1.5 text-[12.5px] leading-relaxed text-ink-2">
                                    @foreach ($content['reduce'] as $item)
                                        <li>• {{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if (! empty($content['increase']))
                            <div class="rounded-control border border-pine/15 bg-pine-tint/25 p-3.5">
                                <p class="text-[11px] font-semibold tracking-[0.04em] text-pine uppercase">Perbanyak / pilih</p>
                                <ul class="mt-2 space-y-1.5 text-[12.5px] leading-relaxed text-ink-2">
                                    @foreach ($content['increase'] as $item)
                                        <li>• {{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    @if (! empty($content['encouragement']))
                        <p class="mt-4 flex items-start gap-2 rounded-control bg-sun-tint/40 px-4 py-3 text-[13px] leading-relaxed text-sun-ink">
                            <x-icon name="sparkle" size="15" class="mt-0.5 shrink-0" />
                            {{ $content['encouragement'] }}
                        </p>
                    @endif

                    <p class="mt-4 border-t border-ink/8 pt-3 text-[11px] leading-relaxed text-ink-3">
                        Dibuat otomatis oleh AI dari catatanmu. Bukan diagnosis atau pengganti saran dokter/ahli gizi.
                    </p>
                </div>
            @endif
        </section>

        {{-- Grafik kalori: batang CSS. Garis target tepat di angkanya, tidak ada yang bisa salah ukur. --}}
        <section class="panel panel-pad mt-6" aria-labelledby="judul-kalori" data-reveal>
            @php
                $calories = collect($chart['calories'])->map(fn ($v) => (float) $v);
                $targets = collect($chart['targets'])->map(fn ($v) => (float) $v);
                $targetLine = (float) ($targets->first(fn ($v) => $v > 0) ?? 0);
                $peak = max($calories->max() ?? 0, $targetLine * 1.06, 1);
                $dense = count($chart['labels']) > 10;
                $n = count($chart['labels']);
            @endphp

            <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-2">
                <div>
                    <h2 id="judul-kalori" class="title text-[18px]">Kalori per hari</h2>
                    <p class="mt-1 text-[12.5px] text-ink-2">Garis putus-putus adalah targetmu. Batang merah lebih dari 10% di atasnya.</p>
                </div>
                @if ($targetLine > 0)
                    <p class="num flex items-center gap-2 text-[12px] text-ink-3"><span class="h-px w-5 border-t border-dashed border-ink-3"></span>Target {{ gizi_num($targetLine) }} kkal</p>
                @endif
            </div>

            <figure class="mt-7" x-data="{ at: null }">
                <div class="relative flex h-[210px] items-end justify-between gap-1 border-b border-ink/12" role="img"
                    aria-label="Grafik kalori per hari dibandingkan target {{ gizi_num($targetLine) }} kkal. Angka lengkap ada di tabel di bawah.">
                    @foreach ([0.25, 0.5, 0.75, 1] as $step)
                        <div class="pointer-events-none absolute inset-x-0 border-t border-ink/6" style="bottom: {{ $step * 100 }}%"></div>
                    @endforeach

                    @if ($targetLine > 0)
                        <div class="pointer-events-none absolute inset-x-0 z-10 border-t border-dashed border-ink-3/60" style="bottom: {{ min(100, $targetLine / $peak * 100) }}%"></div>
                    @endif

                    @foreach ($chart['labels'] as $i => $label)
                        @php
                            $value = $calories[$i] ?? 0;
                            $goal = $targets[$i] ?? 0;
                            $height = $value > 0 ? max(2, $value / $peak * 100) : 0;
                            $over = $goal > 0 && $value > $goal * 1.1;
                        @endphp
                        <div class="relative flex h-full flex-1 items-end justify-center" @mouseenter="at = {{ $i }}" @mouseleave="at = null" @focus="at = {{ $i }}" @blur="at = null" tabindex="0">
                            <div @class([
                                    'col-grow w-full max-w-[40px] rounded-t-[5px] bg-gradient-to-t transition-[filter,opacity] duration-140',
                                    'from-danger/40 to-danger' => $over,
                                    'from-sun/35 to-sun' => ! $over,
                                ]) style="height: {{ $height }}%; --i: {{ $i }}"
                                :class="at === {{ $i }} ? 'brightness-110' : (at !== null ? 'opacity-40' : '')"></div>

                            <div x-show="at === {{ $i }}" x-cloak class="float pointer-events-none absolute bottom-full z-20 mb-2 px-2.5 py-1.5 text-center">
                                <p class="num text-[13px] leading-none font-semibold whitespace-nowrap">{{ gizi_num($value) }} <span class="text-[10.5px] font-medium text-ink-3">kkal</span></p>
                                <p class="mt-1 text-[10.5px] leading-none whitespace-nowrap text-ink-3">{{ $label }}@if ($goal > 0) · {{ $value > $goal ? '+' : '−' }}{{ gizi_num(abs($value - $goal)) }}@endif</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <figcaption class="mt-2 flex justify-between gap-1">
                    @foreach ($chart['labels'] as $i => $label)
                        <span @class([
                                'num flex-1 text-center text-[10px] text-ink-3',
                                'invisible' => $dense && $i % 5 !== 0 && $i !== $n - 1,
                            ])>{{ $label }}</span>
                    @endforeach
                </figcaption>
            </figure>
        </section>

        {{-- Tabel pendamping: angka yang sama, untuk yang tidak membaca grafik --}}
        <section class="panel mt-6 overflow-hidden" aria-labelledby="judul-tabel-kalori" data-reveal>
            <details class="group">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 transition-colors duration-140 hover:bg-raise/40 sm:px-6 [&::-webkit-details-marker]:hidden">
                    <h2 id="judul-tabel-kalori" class="text-[13.5px] font-semibold">Angka di balik grafik</h2>
                    <span class="num flex items-center gap-2 text-[12px] text-ink-3">{{ $stats['days'] }} hari <x-icon name="chevron-down" size="15" class="transition-transform duration-[420ms] ease-spring group-open:rotate-180" /></span>
                </summary>
                <div class="table-scroll border-t border-ink/8">
                    <table class="table min-w-[360px]">
                        <thead><tr><th>Hari</th><th class="text-right">Masuk</th><th class="text-right">Target</th><th class="text-right">Selisih</th></tr></thead>
                        <tbody>
                            @foreach ($chart['labels'] as $i => $label)
                                @php $diff = $chart['calories'][$i] - $chart['targets'][$i]; @endphp
                                <tr>
                                    <td class="text-ink-2">{{ $label }}</td>
                                    <td class="num text-right font-semibold">{{ gizi_num($chart['calories'][$i]) }}</td>
                                    <td class="num text-right text-ink-3">{{ gizi_num($chart['targets'][$i]) }}</td>
                                    <td @class(['num text-right', 'text-danger' => $diff > 0, 'text-ink-2' => $diff <= 0])>{{ $diff > 0 ? '+' : '' }}{{ gizi_num($diff) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        </section>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
            <section class="panel panel-pad" aria-labelledby="judul-makro" data-reveal>
                <h2 id="judul-makro" class="title text-[18px]">Makro per hari</h2>
                <p class="mt-1 text-[13px] text-ink-2">Protein, karbohidrat, dan lemak dalam gram, ditumpuk per hari.</p>
                <x-stack-chart class="mt-6" :labels="$chart['labels']" unit=" g" :height="200" :series="[
                    ['name' => 'Protein', 'color' => 'protein', 'values' => $chart['protein']],
                    ['name' => 'Karbohidrat', 'color' => 'carbs', 'values' => $chart['carbs']],
                    ['name' => 'Lemak', 'color' => 'fat', 'values' => $chart['fat']],
                ]" />
            </section>

            <section class="panel panel-pad" aria-labelledby="judul-komposisi" data-reveal style="--d: 80">
                <h2 id="judul-komposisi" class="title text-[18px]">Komposisi rata-rata</h2>
                <p class="mt-1 text-[13px] leading-relaxed text-ink-2">Bagian kalori dari tiap zat gizi, dibanding anjuran.</p>

                <div class="mt-5 flex h-3 gap-[3px] overflow-hidden rounded-full" aria-hidden="true">
                    @foreach ($split as [$label, $color, $grams, $pct])
                        <div class="bar-fill first:rounded-l-full last:rounded-r-full" style="width: {{ $pct }}%; background: var(--color-{{ $color }})"></div>
                    @endforeach
                </div>

                <dl class="mt-5 space-y-2.5">
                    @foreach ($split as [$label, $color, $grams, $pct, $advice])
                        <div class="flex items-baseline justify-between gap-3 border-b border-ink/6 pb-2.5 text-[13.5px] last:border-0 last:pb-0">
                            <dt class="flex items-center gap-2 font-medium">
                                <span class="size-2 rounded-full" style="background: var(--color-{{ $color }})"></span>{{ $label }}
                            </dt>
                            <dd class="num text-right text-[12.5px] text-ink-3"><span class="text-[14px] font-semibold text-ink">{{ $pct }}%</span> · {{ gizi_num($grams) }} g · anjuran {{ $advice }}%</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($topFoods->isNotEmpty())
                    <h3 class="mt-6 border-t border-ink/8 pt-4 text-[13.5px] font-semibold">Paling sering dimakan</h3>
                    <ol class="mt-3 space-y-2">
                        @foreach ($topFoods as $i => $food)
                            <li class="flex items-center gap-3 text-[13.5px]">
                                <span class="num w-3 shrink-0 text-[12px] font-semibold text-sun">{{ $i + 1 }}</span>
                                <span class="min-w-0 flex-1 truncate">{{ $food->name }}</span>
                                <span class="num shrink-0 text-[12px] text-ink-3">{{ $food->times }}×</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>

        {{-- Berat badan, digabung ke sini supaya tidak jadi halaman sendiri --}}
        <section class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.35fr)]" aria-labelledby="judul-berat" data-reveal>
            <div>
                <h2 id="judul-berat" class="title text-[18px]">Berat badan</h2>
                <p class="mt-1.5 text-[13.5px] leading-relaxed text-ink-2">Timbang di waktu yang sama, misalnya pagi setelah bangun, supaya angkanya sebanding.</p>

                <div class="mt-5 grid grid-cols-3 gap-4">
                    <x-stat label="Sekarang" :value="gizi_num($latestWeight?->weight_kg, 1)" unit="kg" />
                    <x-stat label="Perubahan" :value="$weightChange === null ? '–' : ($weightChange > 0 ? '+' : '').gizi_num($weightChange, 1)" :unit="$weightChange === null ? null : 'kg'" :note="$firstWeight ? 'sejak '.$firstWeight->logged_on->translatedFormat('j M') : null" />
                    <x-stat label="IMT" :value="gizi_num($bmi, 1)" :note="$bmiCategory ?? 'Tanpa label di bawah 18 tahun'" />
                </div>

                <a href="{{ route('weight.index') }}" class="group mt-5 inline-flex items-center gap-1.5 text-[13px] font-semibold text-sun underline decoration-sun/30 underline-offset-[3px] transition-colors duration-140 hover:decoration-sun">
                    Catat berat dan lihat riwayat
                    <x-icon name="chevron-right" size="13" class="transition-transform duration-[420ms] ease-spring group-hover:translate-x-1" />
                </a>
            </div>

            <div class="panel p-5">
                @if ($weightLogs->count() > 1)
                    <x-line-chart :labels="$weightChart['labels']->all()" :values="$weightChart['values']->all()" unit="kg" :height="220" color="sun" />
                @else
                    <p class="grid h-[220px] place-items-center rounded-control border border-dashed border-ink/12 text-center text-[13.5px] text-ink-2">Grafik muncul setelah ada dua catatan atau lebih.</p>
                @endif
            </div>
        </section>
    @endif
</x-layouts.app>
