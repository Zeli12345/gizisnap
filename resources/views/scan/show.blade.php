@php
    $status = $scan->status->value;
    $result = $scan->ai_result ?? [];
    $saved = $scan->meal;
    $health = $result['health_assessment'] ?? [];
    $healthConditions = $scan->user->healthProfile?->health_conditions;
    $showHealth = $status === 'completed'
        && filled($healthConditions)
        && filled($health['summary'] ?? null);
    $healthSeverity = $health['severity'] ?? 'safe';
    $healthTone = match ($healthSeverity) {
        'danger' => [
            'panel' => 'border-danger/30 bg-danger-tint/40',
            'icon' => 'bg-danger/15 text-danger',
            'badge' => 'bg-danger/15 text-danger',
            'label' => 'Hindari atau batasi',
        ],
        'caution' => [
            'panel' => 'border-sun/30 bg-sun-tint/60',
            'icon' => 'bg-sun/15 text-sun-ink',
            'badge' => 'bg-sun/15 text-sun-ink',
            'label' => 'Perhatikan porsinya',
        ],
        default => [
            'panel' => 'border-pine/25 bg-pine-tint/60',
            'icon' => 'bg-pine/15 text-pine',
            'badge' => 'bg-pine/15 text-pine-deep',
            'label' => 'Tidak ada sinyal bahaya',
        ],
    };
    $flagged = collect($health['flagged_items'] ?? [])
        ->filter(fn ($item) => filled($item['name'] ?? null))
        ->keyBy(fn ($item) => mb_strtolower(trim($item['name'])));
    $editorConfig = [
        'items' => collect($scan->items())->map(fn ($item) => [
            ...$item,
            'food_id' => null,
            'health_reason' => $flagged->get(mb_strtolower(trim($item['name'] ?? '')))['reason'] ?? null,
        ])->all(),
        'searchUrl' => route('foods.search'),
    ];
    $defaults = [
        'title' => $scan->dish_name ?? '',
        'eaten_date' => $scan->created_at->toDateString(),
        'eaten_time' => $scan->created_at->format('H:i'),
        'notes' => '',
        'items' => [],
    ];
    $confidence = (int) round(($scan->confidence ?? 0) * 100);
@endphp
<x-layouts.app title="Hasil scan" :wide="true">
    <x-page-header :title="$status === 'completed' ? 'Hasil scan' : $scan->status->label()" :back="route('scan.create')" />

    <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] lg:gap-12">
        {{-- Foto --}}
        <div class="space-y-5 lg:sticky lg:top-10">
            <figure>
                <img src="{{ route('scan.photo', $scan) }}" alt="Foto makanan yang discan" class="aspect-[4/3] w-full rounded-surface border border-line bg-sunk object-cover">
                @if ($status === 'completed')
                    <figcaption class="mt-4 flex items-start justify-between gap-3">
                        <span class="min-w-0">
                            <span class="font-display block text-[21px] leading-tight font-semibold">{{ $scan->dish_name }}</span>
                            <span class="num mt-1 block text-[12.5px] text-ink-3">{{ count($scan->items()) }} makanan · {{ $scan->created_at->translatedFormat('j M, H.i') }}</span>
                        </span>
                        <span @class([
                            'shrink-0 rounded-[4px] px-1.5 py-0.5 text-[11.5px] font-semibold',
                            'bg-sun-tint text-sun-ink' => $scan->isLowConfidence(),
                            'bg-pine-tint text-pine-deep' => ! $scan->isLowConfidence(),
                        ])>
                            Yakin {{ $confidence }}%
                        </span>
                    </figcaption>
                @endif
            </figure>

            @if ($status === 'completed' && ! empty($result['notes']))
                <div class="rounded-control bg-sunk/60 px-4 py-3">
                    <p class="text-[11.5px] font-semibold text-ink-3">Asumsi AI</p>
                    <p class="mt-1 text-[13.5px] leading-relaxed">{{ $result['notes'] }}</p>
                </div>
            @endif
            <x-disclaimer />
        </div>

        {{-- Isi sesuai status --}}
        <div>
            @if ($showHealth)
                <section class="mb-5 overflow-hidden rounded-surface border {{ $healthTone['panel'] }}" aria-labelledby="judul-kesehatan">
                    <div class="p-5">
                        <div class="flex items-start gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full {{ $healthTone['icon'] }}">
                                <x-icon :name="$healthSeverity === 'safe' ? 'check' : 'alert'" size="19" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-[10.5px] font-semibold tracking-[0.05em] text-ink-3 uppercase">Untuk kondisi: {{ \Illuminate\Support\Str::limit($healthConditions, 90) }}</p>
                                <h2 id="judul-kesehatan" class="title mt-1 text-[19px]">{{ $healthTone['label'] }}</h2>
                                <p class="mt-1 text-[13.5px] leading-relaxed text-ink-2">{{ $health['summary'] }}</p>
                            </div>
                        </div>

                        @if (! empty($health['warnings']))
                            <ul class="mt-4 space-y-1.5">
                                @foreach ($health['warnings'] as $warning)
                                    <li class="flex gap-2 text-[12.5px] leading-relaxed text-ink-2">
                                        <span class="mt-[7px] size-1 shrink-0 rounded-full bg-current opacity-60" aria-hidden="true"></span>
                                        {{ $warning }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if (! empty($health['avoid']) || ! empty($health['prefer']))
                            <div class="mt-4 grid gap-2.5 sm:grid-cols-2">
                                @if (! empty($health['avoid']))
                                    <div class="rounded-control border border-danger/15 bg-canvas/30 p-3.5">
                                        <p class="text-[11px] font-semibold tracking-[0.04em] text-danger uppercase">Batasi / hindari</p>
                                        <ul class="mt-1.5 space-y-1 text-[12.5px] leading-relaxed text-ink-2">
                                            @foreach ($health['avoid'] as $item)
                                                <li>• {{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                @if (! empty($health['prefer']))
                                    <div class="rounded-control border border-pine/15 bg-canvas/30 p-3.5">
                                        <p class="text-[11px] font-semibold tracking-[0.04em] text-pine uppercase">Pilih yang lebih baik</p>
                                        <ul class="mt-1.5 space-y-1 text-[12.5px] leading-relaxed text-ink-2">
                                            @foreach ($health['prefer'] as $item)
                                                <li>• {{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($flagged->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($flagged as $flag)
                                    <span class="rounded-full px-2 py-0.5 text-[10.5px] font-semibold {{ $healthTone['badge'] }}">
                                        {{ $flag['name'] }}@if (! empty($flag['reason'])) · {{ $flag['reason'] }}@endif
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <p class="mt-4 border-t border-ink/10 pt-3 text-[11px] leading-relaxed text-ink-3">
                            Peringatan ini bukan diagnosis atau pengganti saran dokter/ahli gizi.
                        </p>
                    </div>
                </section>
            @endif

            @if ($status === 'completed' && $saved)
                <div class="panel panel-pad">
                    <span class="grid size-11 place-items-center rounded-full bg-pine-tint text-pine"><x-icon name="check" size="22" stroke="2.2" /></span>
                    <h2 class="title mt-4 text-[21px]">Sudah tersimpan</h2>
                    <p class="mt-1.5 text-[14px] leading-relaxed text-ink-2">
                        {{ $saved->title }}, {{ $saved->eaten_at->translatedFormat('j F, H.i') }}, {{ gizi_num($saved->total_calories_kcal) }} kkal.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <a href="{{ route('meals.edit', $saved) }}" class="btn btn-quiet">Ubah catatan</a>
                        <a href="{{ route('dashboard', ['date' => $saved->eaten_at->toDateString()]) }}" class="btn btn-primary">Lihat hari itu</a>
                    </div>
                </div>
            @elseif ($status === 'completed')
                <form method="POST" action="{{ route('scan.meal', $scan) }}" class="panel panel-pad" x-data="mealEditor(@js($editorConfig))">
                    @csrf
                    @if ($scan->isLowConfidence())
                        <div class="mb-5 flex gap-2.5 rounded-control bg-sun-tint px-4 py-3 text-[13px] leading-relaxed text-sun-ink">
                            <x-icon name="alert" size="16" class="mt-0.5 shrink-0" />
                            <p>AI kurang yakin dengan foto ini. Cek nama makanan dan porsinya sebelum menyimpan.</p>
                        </div>
                    @endif

                    <h2 class="title text-[21px]">Makanan di foto</h2>
                    <p class="mt-1 mb-5 text-[13.5px] leading-relaxed text-ink-2">Ketuk makanan untuk mengubah porsi atau angkanya.</p>
                    @include('partials.meal-items-editor', ['withSearch' => false])

                    <hr class="my-6 h-px border-0 bg-line">

                    @include('partials.meal-meta-fields', ['defaults' => $defaults])

                    <div class="mt-7 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <a href="{{ route('scan.create') }}" class="btn btn-ghost">Scan ulang</a>
                        <button type="submit" class="btn btn-primary px-6 py-3 text-[15px]" :disabled="items.length === 0">
                            <x-icon name="check" size="17" stroke="2.2" /> Simpan ke catatan
                        </button>
                    </div>
                </form>
            @elseif ($status === 'not_food')
                <div class="panel panel-pad">
                    <span class="grid size-11 place-items-center rounded-full bg-sunk text-ink-2"><x-icon name="image" size="21" /></span>
                    <h2 class="title mt-4 text-[21px]">Foto tidak dikenali sebagai makanan</h2>
                    <p class="mt-1.5 text-[14px] leading-relaxed text-ink-2">Coba foto ulang dari atas dengan seluruh piring terlihat, atau catat makanannya tanpa foto.</p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <a href="{{ route('meals.create') }}" class="btn btn-quiet">Catat tanpa foto</a>
                        <a href="{{ route('scan.create') }}" class="btn btn-primary"><x-icon name="camera" size="16" /> Foto ulang</a>
                    </div>
                </div>
            @else
                <div class="panel panel-pad">
                    <span class="grid size-11 place-items-center rounded-full bg-danger-tint text-danger"><x-icon name="alert" size="21" /></span>
                    <h2 class="title mt-4 text-[21px]">Analisis belum berhasil</h2>
                    <p class="mt-1.5 text-[14px] leading-relaxed text-ink-2">{{ $scan->error_message ?? 'Terjadi kesalahan saat menghubungi AI.' }}</p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <a href="{{ route('meals.create') }}" class="btn btn-quiet">Catat tanpa foto</a>
                        <form method="POST" action="{{ route('scan.retry', $scan) }}" x-data="{ busy: false }" @submit="busy = true">
                            @csrf
                            <button class="btn btn-primary" :disabled="busy">
                                <x-icon name="refresh" size="16" /> <span x-text="busy ? 'Menganalisis…' : 'Coba lagi'">Coba lagi</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
