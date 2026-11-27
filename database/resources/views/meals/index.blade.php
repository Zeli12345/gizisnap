{{--
    Catatan makan: satu garis waktu menerus. Tanggal besar di kiri menempel saat digulir,
    sehingga setiap hari terbaca sebagai satu bab.
--}}
<x-layouts.app title="Catatan makan">
    <x-page-header title="Catatan makan">
        <x-slot:subtitle>Semua yang kamu catat dalam 14 hari terakhir.</x-slot:subtitle>
        <x-slot:actions>
            <a href="{{ route('scan.create') }}" class="btn btn-quiet"><x-icon name="camera" size="16" /> Scan</a>
            <a href="{{ route('meals.create') }}" class="btn btn-primary"><x-icon name="plus" size="16" stroke="2.2" /> Catat</a>
        </x-slot:actions>
    </x-page-header>

    @if ($days->isEmpty())
        <x-empty-state icon="utensils" title="Belum ada catatan">
            Catatan makanmu akan muncul di sini, dikelompokkan per hari.
            <x-slot:action>
                <a href="{{ route('scan.create') }}" class="btn btn-primary"><x-icon name="camera" size="16" /> Scan makanan pertama</a>
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="space-y-12">
            @foreach ($days as $dateKey => $meals)
                @php
                    $day = \Carbon\CarbonImmutable::parse($dateKey);
                    $info = $targets->get($dateKey);
                    $total = $meals->sum('total_calories_kcal');
                    $target = $info['target_kcal'] ?? null;
                    $pct = $target ? min(100, $total / $target * 100) : 0;
                    $over = $target && $total > $target * 1.1;
                    $name = $day->isToday() ? 'Hari ini' : ($day->isYesterday() ? 'Kemarin' : $day->translatedFormat('l'));
                @endphp
                <section aria-labelledby="hari-{{ $dateKey }}" class="grid gap-x-8 gap-y-3 md:grid-cols-[104px_minmax(0,1fr)]" data-reveal>
                    <div class="flex items-baseline gap-3 md:sticky md:top-10 md:block md:self-start">
                        <p class="figure text-[34px] md:text-[52px]" aria-hidden="true">{{ $day->format('j') }}</p>
                        <div>
                            <h2 id="hari-{{ $dateKey }}" class="text-[13.5px] leading-tight font-semibold">{{ $name }}</h2>
                            <p class="mt-0.5 text-[12px] text-ink-3">{{ $day->translatedFormat('F Y') }}</p>
                        </div>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b border-ink/12 pb-3">
                            <p class="num text-[13px] text-ink-3">
                                <span @class(['font-display text-[22px] font-semibold tracking-[-0.02em]', 'text-danger' => $over, 'text-ink' => ! $over])>{{ gizi_num($total) }}</span>
                                @if ($target)<span> / {{ gizi_num($target) }}</span>@endif kkal
                            </p>
                            <a href="{{ route('dashboard', ['date' => $dateKey]) }}" class="text-[12.5px] font-semibold text-sun underline decoration-sun/30 underline-offset-[3px] transition-colors duration-140 hover:decoration-sun">Buka hari itu</a>
                        </div>
                        @if ($target)
                            <div class="-mt-px mb-1 h-[2px] overflow-hidden bg-ink/6">
                                <div @class(['bar-fill h-full', 'bg-danger' => $over, 'bg-sun' => ! $over]) style="width: {{ $pct }}%"></div>
                            </div>
                        @endif

                        <ul class="divide-y divide-ink/6">
                            @foreach ($meals->sortBy('eaten_at') as $meal)
                                <li>
                                    <a href="{{ route('meals.edit', $meal) }}" class="group -mx-2.5 flex items-center gap-3.5 rounded-[9px] px-2.5 py-2.5 transition-colors duration-140 ease-smooth hover:bg-paper">
                                        <span class="num w-11 shrink-0 text-[13px] font-medium text-ink-3">{{ $meal->eaten_at->format('H.i') }}</span>
                                        @if ($meal->scan_id)
                                            <img src="{{ route('scan.photo', $meal->scan_id) }}" alt="" width="38" height="38" class="size-[38px] shrink-0 rounded-[7px] border border-ink/10 object-cover" loading="lazy">
                                        @else
                                            <span class="grid size-[38px] shrink-0 place-items-center rounded-[7px] bg-sunk text-ink-3"><x-icon name="utensils" size="15" /></span>
                                        @endif
                                        <span class="min-w-0 flex-1 truncate text-[14.5px] font-medium">{{ $meal->title }}</span>
                                        <span class="num shrink-0 text-[13.5px] font-semibold">{{ gizi_num($meal->total_calories_kcal) }} <span class="text-[11.5px] font-medium text-ink-3">kkal</span></span>
                                        <x-icon name="chevron-right" size="15" class="shrink-0 text-line-strong transition-[transform,color] duration-[420ms] ease-spring group-hover:translate-x-1 group-hover:text-sun" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-layouts.app>
