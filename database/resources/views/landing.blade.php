@php
    $chatQa = [
        ['q' => 'Protein aku hari ini cukup belum?', 'a' => 'Baru <strong>58 g dari 80 g</strong>. Tinggal satu lauk: ikan kembung 100 g menambah sekitar 22 g protein.', 'bar' => 72, 'color' => 'protein'],
        ['q' => 'Kenapa aku cepat lapar siang ini?', 'a' => 'Sarapanmu <strong>312 kkal</strong> dan hampir semuanya karbo. Besok coba tambah telur atau tempe supaya kenyang lebih lama.', 'bar' => 39, 'color' => 'carbs'],
        ['q' => 'Sisa kalori malam ini boleh apa?', 'a' => 'Sisa <strong>640 kkal</strong>. Muat nasi, ayam bakar, dan lalapan. Sambalnya tetap aman, tapi hati-hati garamnya.', 'bar' => 68, 'color' => 'sun'],
    ];
    $trust = [
        ['Kamu yang memutuskan', 'Hasil scan tidak tersimpan sebelum kamu cek dan setujui. Porsi bisa diubah per lauk.', 'check'],
        ['Target dari tubuhmu', 'Dihitung dari tinggi, berat, usia, dan aktivitas. Remaja tidak diberi target turun berat.', 'user'],
        ['Data tetap milikmu', 'Admin hanya melihat angka statistik, bukan foto atau isi chat. Akun bisa dihapus sendiri.', 'lock'],
    ];
@endphp
<x-layouts.guest title="Foto makanan, tahu gizinya">
    <div class="min-h-dvh overflow-x-clip">

        <header class="relative z-20 mx-auto flex max-w-[1180px] items-center justify-between px-5 py-5 sm:px-8 sm:py-6">
            <x-logo />
            <nav class="flex items-center gap-1" aria-label="Akun">
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Masuk</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Buat akun</a>
            </nav>
        </header>

        <main>
            {{-- Pembuka --}}
            <section class="relative mx-auto max-w-[1180px] px-5 pt-8 pb-20 sm:px-8 lg:pt-16 lg:pb-28">
                <div class="grid items-center gap-16 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)] lg:gap-8">

                    <div class="rise max-w-[40rem]">
                        <div>
                            <x-flash class="mb-6" />
                            <p class="inline-flex items-center gap-2.5 rounded-full border border-ink/10 bg-paper/70 py-1.5 pr-4 pl-3 text-[12px] font-semibold text-ink-2">
                                <span class="relative flex size-2">
                                    <span class="ping-ring absolute inset-0 rounded-full bg-sun/60"></span>
                                    <span class="relative size-2 rounded-full bg-sun"></span>
                                </span>
                                Pencatat gizi dari foto
                            </p>
                        </div>

                        <h1 class="display-xl mt-7">
                            Foto piringmu.<br>
                            Gizinya <span class="em text-sun">langsung</span> ketahuan.
                        </h1>

                        <p class="mt-7 max-w-[31rem] text-[16px] leading-relaxed text-ink-2 sm:text-[17.5px]">
                            GiziSnap mengenali tiap lauk di foto, dari nasi sampai sambal, lalu menghitung kalori dan
                            makronya. Kamu tinggal memastikan porsinya.
                        </p>

                        <div class="mt-9 flex flex-col gap-2.5 sm:flex-row sm:items-center">
                            <a href="{{ route('register') }}" class="group btn btn-primary px-7 py-4 text-[15.5px]">
                                Buat akun gratis
                                <x-icon name="chevron-right" size="16" stroke="2.2" class="transition-transform duration-[420ms] ease-spring group-hover:translate-x-1" />
                            </a>
                            <a href="{{ route('login') }}" class="btn btn-quiet px-5 py-4 text-[15px]">
                                <x-icon name="google" size="16" /> Masuk dengan Google
                            </a>
                        </div>

                        <ul class="mt-10 flex flex-wrap gap-x-6 gap-y-2 text-[13px] text-ink-2">
                            @foreach (['Tanpa kartu kredit', $foodCount.' makanan Indonesia bawaan', 'Bisa dikoreksi sebelum disimpan'] as $point)
                                <li class="flex items-center gap-2"><x-icon name="check" size="14" stroke="2.4" class="text-pine" /> {{ $point }}</li>
                            @endforeach
                        </ul>
                    </div>

                    @include('partials.app-preview')
                </div>
            </section>

            {{-- Dua pita makanan sungguhan, berlawanan arah --}}
            @if ($foods->isNotEmpty())
                @php $rows = $foods->chunk((int) ceil($foods->count() / 2)); @endphp
                <section class="space-y-px border-y border-ink/8 bg-paper/50 py-5" aria-label="Contoh makanan di daftar bawaan">
                    <div class="space-y-3 overflow-hidden [mask-image:linear-gradient(to_right,transparent,#000_8%,#000_92%,transparent)]" aria-hidden="true">
                        @foreach ($rows as $r => $row)
                            <div @class(['marquee', 'marquee-rev' => $r === 1]) style="--dur: {{ 70 + $r * 14 }}s">
                                @foreach ([1, 2] as $copy)
                                    <ul class="flex shrink-0 items-center gap-3 pr-3">
                                        @foreach ($row as $food)
                                            <li class="flex shrink-0 items-baseline gap-2.5 rounded-full border border-ink/8 bg-canvas/60 py-2 pr-4 pl-4">
                                                <span class="text-[14px] font-semibold">{{ $food->name }}</span>
                                                <span class="num text-[12px] text-sun">{{ gizi_num($food->calories_kcal) }} <span class="text-ink-3">kkal</span></span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Cara kerja: tiap langkah punya gambarnya sendiri --}}
            <section class="mx-auto max-w-[1180px] px-5 py-24 sm:px-8 lg:py-32">
                <div data-reveal class="max-w-[40rem]">
                    <p class="eyebrow">Cara kerjanya</p>
                    <h2 class="font-display mt-4 text-[34px] leading-[1.03] font-semibold tracking-[-0.028em] sm:text-[52px]">Dari foto ke catatan, <span class="em text-sun">di bawah semenit.</span></h2>
                </div>

                <div class="mt-14 grid gap-5 lg:grid-cols-3">

                    {{-- 1. Foto --}}
                    <article data-reveal class="panel relative flex flex-col overflow-hidden p-6 sm:p-7">
                        <p class="num text-[11px] font-semibold tracking-[0.07em] text-ink-3">LANGKAH 1</p>
                        <h3 class="font-display mt-2 text-[26px] leading-tight font-semibold tracking-[-0.02em]">Foto makananmu</h3>
                        <p class="mt-2 text-[14.5px] leading-relaxed text-ink-2">Dari kamera atau galeri. Satu piring dengan beberapa lauk tetap terbaca terpisah.</p>

                        <div class="relative mt-7 aspect-[4/3] overflow-hidden rounded-[14px] border border-ink/10 bg-sunk" aria-hidden="true">
                            <svg viewBox="0 0 200 150" class="absolute inset-0 size-full">
                                <rect width="200" height="150" fill="#1b1f19" />
                                <circle cx="100" cy="75" r="56" fill="#272c24" stroke="#f3f0e6" stroke-opacity=".1" />
                                <circle cx="82" cy="68" r="21" fill="#ebe5d0" />
                                <ellipse cx="122" cy="62" rx="22" ry="13" fill="#a9622b" transform="rotate(-16 122 62)" />
                                <circle cx="100" cy="102" r="13" fill="#3d6b46" />
                                <circle cx="70" cy="98" r="7" fill="#a22a1c" />
                            </svg>
                            <span class="corner corner-tl" style="top: 10px; left: 10px"></span>
                            <span class="corner corner-tr" style="top: 10px; right: 10px"></span>
                            <span class="corner corner-bl" style="bottom: 10px; left: 10px"></span>
                            <span class="corner corner-br" style="bottom: 10px; right: 10px"></span>
                            <div class="scan-pass absolute inset-0"><div class="scan-band"></div></div>
                            <span class="absolute right-3 bottom-3 grid size-9 place-items-center rounded-full bg-sun text-canvas"><x-icon name="camera" size="17" stroke="2" /></span>
                        </div>
                    </article>

                    {{-- 2. Periksa: bisa dicoba --}}
                    <article data-reveal style="--d: 90" class="panel relative flex flex-col overflow-hidden p-6 sm:p-7" x-data="plateDemo(@js($demo))">
                        <p class="num text-[11px] font-semibold tracking-[0.07em] text-ink-3">LANGKAH 2 · COBA</p>
                        <h3 class="font-display mt-2 text-[26px] leading-tight font-semibold tracking-[-0.02em]">Periksa perkiraannya</h3>
                        <p class="mt-2 text-[14.5px] leading-relaxed text-ink-2">Nasi kebanyakan? Ketuk tanda minus dan totalnya ikut turun.</p>

                        <ul class="mt-6 divide-y divide-ink/8 rounded-[14px] border border-ink/10 bg-sunk/60">
                            <template x-for="item in items" :key="item.name">
                                <li class="flex items-center gap-2 px-3.5 py-2.5">
                                    <span class="min-w-0 flex-1 truncate text-[13.5px] font-medium" x-text="item.name"></span>
                                    <button type="button" class="grid size-7 shrink-0 place-items-center rounded-full border border-ink/12 text-ink-2 transition-[transform,background-color] duration-[420ms] ease-spring hover:bg-raise active:scale-90 disabled:opacity-25" :disabled="!canStep(item, -1)" @click="step(item, -1)" :aria-label="'Kurangi porsi ' + item.name">
                                        <x-icon name="minus" size="13" stroke="2.2" />
                                    </button>
                                    <span class="num w-6 text-center text-[13px] font-semibold" x-text="label(item)"></span>
                                    <button type="button" class="grid size-7 shrink-0 place-items-center rounded-full border border-ink/12 text-ink-2 transition-[transform,background-color] duration-[420ms] ease-spring hover:bg-raise active:scale-90 disabled:opacity-25" :disabled="!canStep(item, 1)" @click="step(item, 1)" :aria-label="'Tambah porsi ' + item.name">
                                        <x-icon name="plus" size="13" stroke="2.2" />
                                    </button>
                                    <span class="num w-10 shrink-0 text-right text-[13px] text-ink-2" x-text="kcal(item)"></span>
                                </li>
                            </template>
                        </ul>

                        <div class="mt-4 flex items-baseline justify-between px-1">
                            <span class="text-[12px] font-semibold text-ink-3">Total piring</span>
                            <span class="num"><span class="font-display text-[30px] leading-none font-semibold tracking-[-0.02em]" x-text="fmt(total)">614</span> <span class="text-[11.5px] font-semibold text-ink-3">kkal</span></span>
                        </div>
                    </article>

                    {{-- 3. Simpan --}}
                    @php
                        // Cincin sisa kalori di langkah 3. Dihitung di server, bukan di Alpine.
                        $ringLength = 2 * M_PI * 15;
                        $ringLeft = 1386;
                        $ringGoal = 2000;
                    @endphp
                    <article data-reveal style="--d: 180" class="panel relative flex flex-col overflow-hidden p-6 sm:p-7">
                        <p class="num text-[11px] font-semibold tracking-[0.07em] text-ink-3">LANGKAH 3</p>
                        <h3 class="font-display mt-2 text-[26px] leading-tight font-semibold tracking-[-0.02em]">Simpan</h3>
                        <p class="mt-2 text-[14.5px] leading-relaxed text-ink-2">Jam makan tercatat otomatis. Sisa kalori hari itu langsung berkurang.</p>

                        <div class="mt-7 flex flex-1 items-center justify-center rounded-[14px] border border-ink/10 bg-sunk/60 px-5 py-7" aria-hidden="true">
                            <div class="relative size-[168px]">
                                <svg viewBox="0 0 36 36" class="size-full -rotate-90">
                                    <circle cx="18" cy="18" r="15" fill="none" stroke="var(--color-ink)" stroke-opacity=".1" stroke-width="2.6" />
                                    <circle cx="18" cy="18" r="15" fill="none" stroke="var(--color-sun)" stroke-width="2.6" stroke-linecap="round" stroke-dasharray="{{ round($ringLength, 2) }}" stroke-dashoffset="{{ round($ringLength * (1 - $ringLeft / $ringGoal), 2) }}" />
                                </svg>
                                <div class="absolute inset-0 grid place-items-center text-center">
                                    <div>
                                        <p class="text-[10.5px] font-semibold tracking-[0.06em] text-ink-3 uppercase">Sisa hari ini</p>
                                        <p class="num font-display mt-1 text-[38px] leading-none font-semibold tracking-[-0.03em]">{{ gizi_num($ringLeft) }}</p>
                                        <p class="mt-1 text-[11px] font-semibold text-ink-3">kkal</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            {{-- Penghitung target --}}
            <section class="border-y border-ink/8 bg-paper/50">
                <div class="mx-auto max-w-[1180px] px-5 py-24 sm:px-8 lg:py-32" x-data="calculator(@js($calculator))">
                    <div class="grid items-start gap-12 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:gap-20">
                        <div data-reveal>
                            <p class="eyebrow">Coba sekarang</p>
                            <h2 class="font-display mt-4 text-[34px] leading-[1.03] font-semibold tracking-[-0.028em] sm:text-[48px]">Bukan angka 2.000 <span class="em text-sun">untuk semua orang.</span></h2>
                            <p class="mt-5 max-w-[28rem] text-[15px] leading-relaxed text-ink-2">
                                Geser angkanya dan lihat targetmu berubah. Rumusnya Mifflin-St Jeor, sama dengan yang dipakai setelah kamu mendaftar.
                            </p>

                            <div class="mt-9 space-y-6">
                                <div class="flex items-center gap-2" role="group" aria-label="Jenis kelamin">
                                    <button type="button" class="chip" :aria-pressed="gender === 'female'" @click="gender = 'female'">Perempuan</button>
                                    <button type="button" class="chip" :aria-pressed="gender === 'male'" @click="gender = 'male'">Laki-laki</button>
                                </div>

                                @foreach ([['age', 'Usia', 'tahun', 13, 60], ['height', 'Tinggi', 'cm', 140, 200], ['weight', 'Berat', 'kg', 35, 120]] as [$model, $label, $unit, $min, $max])
                                    <div>
                                        <div class="flex items-baseline justify-between">
                                            <label for="calc-{{ $model }}" class="text-[13px] font-semibold text-ink-2">{{ $label }}</label>
                                            <span class="num text-[14px] font-semibold"><span x-text="{{ $model }}"></span> <span class="text-[12px] font-medium text-ink-3">{{ $unit }}</span></span>
                                        </div>
                                        <input id="calc-{{ $model }}" type="range" class="range mt-1.5" min="{{ $min }}" max="{{ $max }}" step="1" x-model.number="{{ $model }}"
                                            :style="`--p: ${(({{ $model }} - {{ $min }}) / ({{ $max - $min }})) * 100}%`">
                                    </div>
                                @endforeach

                                <div>
                                    <p class="mb-2 text-[13px] font-semibold text-ink-2">Seberapa aktif</p>
                                    <div class="flex flex-wrap gap-1.5" role="group" aria-label="Tingkat aktivitas">
                                        @foreach ($levels as $level)
                                            <button type="button" class="chip" :aria-pressed="level === '{{ $level->value }}'" @click="level = '{{ $level->value }}'">{{ $level->label() }}</button>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <p class="mb-2 text-[13px] font-semibold text-ink-2">Tujuanmu</p>
                                    <div class="flex flex-wrap gap-1.5" role="group" aria-label="Tujuan">
                                        @foreach ($goals as $goal)
                                            <button type="button" class="chip" :aria-pressed="goal === '{{ $goal->value }}'" @click="goal = '{{ $goal->value }}'">{{ $goal->label() }}</button>
                                        @endforeach
                                    </div>
                                    <p x-show="lockedLose" x-cloak x-transition.opacity class="mt-2.5 flex items-start gap-2 text-[12.5px] leading-relaxed text-sun-ink">
                                        <x-icon name="info" size="14" class="mt-0.5 shrink-0" />
                                        Di bawah 18 tahun, target turun berat tidak ditawarkan karena tubuhmu masih tumbuh.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div data-reveal style="--d: 120" class="panel relative overflow-hidden p-6 sm:p-10 lg:sticky lg:top-10" aria-live="polite">
                            <div class="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full bg-sun/12 blur-3xl" aria-hidden="true"></div>

                            <p class="relative text-[12.5px] font-semibold text-ink-3">Targetmu per hari</p>
                            <p class="num relative mt-3 flex items-baseline gap-3">
                                <span class="figure text-[84px] sm:text-[120px]" x-text="display">2.150</span>
                                <span class="text-[14px] font-semibold text-ink-3">kkal</span>
                            </p>
                            <p class="relative mt-2 text-[12.5px] text-ink-3">Energi istirahat <span class="num font-semibold text-ink-2" x-text="fmt(resting)"></span> kkal · aktivitas × <span class="num font-semibold text-ink-2" x-text="factor"></span> · <span x-text="adjustmentLabel"></span></p>

                            <div class="relative mt-9 flex h-3 gap-[3px] overflow-hidden rounded-full" aria-hidden="true">
                                <div class="rounded-l-full transition-[flex-grow] duration-[600ms] ease-expo" style="background: var(--color-protein)" :style="`flex-grow: ${protein * 4}`"></div>
                                <div class="transition-[flex-grow] duration-[600ms] ease-expo" style="background: var(--color-carbs)" :style="`flex-grow: ${carbs * 4}`"></div>
                                <div class="rounded-r-full transition-[flex-grow] duration-[600ms] ease-expo" style="background: var(--color-fat)" :style="`flex-grow: ${fat * 9}`"></div>
                            </div>

                            <div class="relative mt-6 grid grid-cols-3 gap-5">
                                @foreach ([['Protein', 'protein', 20], ['Karbohidrat', 'carbs', 55], ['Lemak', 'fat', 25]] as [$label, $key, $pct])
                                    <div>
                                        <p class="flex items-center gap-1.5 text-[11.5px] text-ink-3"><span class="size-1.5 rounded-full" style="background: var(--color-{{ $key }})"></span>{{ $label }}</p>
                                        <p class="num mt-1.5 text-[26px] leading-none font-semibold tracking-[-0.02em]"><span x-text="fmt({{ $key }})">0</span><span class="ml-0.5 text-[12px] font-medium text-ink-3">g</span></p>
                                        <p class="num mt-1 text-[11.5px] text-ink-3">{{ $pct }}% kalori</p>
                                    </div>
                                @endforeach
                            </div>

                            <p class="relative mt-8 border-t border-ink/8 pt-5 text-[12px] leading-relaxed text-ink-3">Perkiraan, bukan resep dokter. Alergi dan preferensi makan diatur saat mendaftar.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Tanya gizi: contoh, bisa diganti pertanyaannya --}}
            <section class="mx-auto max-w-[1180px] px-5 py-24 sm:px-8 lg:py-32">
                <div class="grid items-center gap-14 lg:grid-cols-2 lg:gap-24" x-data="chatDemo(@js(['qa' => $chatQa]))">
                    <div data-reveal>
                        <p class="eyebrow">Tanya gizi</p>
                        <h2 class="font-display mt-4 text-[34px] leading-[1.03] font-semibold tracking-[-0.028em] sm:text-[48px]">Jawaban yang tahu <span class="em text-sun">apa yang kamu makan</span> hari ini.</h2>
                        <p class="mt-5 max-w-[28rem] text-[15px] leading-relaxed text-ink-2">
                            Bukan saran umum dari internet. Pertanyaannya dijawab memakai target dan catatan makanmu sendiri.
                        </p>
                        <ul class="mt-8 space-y-2" aria-label="Contoh pertanyaan">
                            @foreach ($chatQa as $i => $qa)
                                <li>
                                    <button type="button" @click="ask({{ $i }})" :aria-pressed="current === {{ $i }}"
                                        class="group flex w-full items-center justify-between gap-4 rounded-[12px] border px-4 py-3 text-left text-[14px] font-medium transition-[border-color,background-color,transform] duration-[420ms] ease-spring active:scale-[0.985]"
                                        :class="current === {{ $i }} ? 'border-sun/60 bg-sun-tint text-ink' : 'border-ink/10 text-ink-2 hover:border-ink/24 hover:text-ink'">
                                        {{ $qa['q'] }}
                                        <x-icon name="chevron-right" size="15" class="shrink-0 transition-transform duration-[420ms] ease-spring group-hover:translate-x-1" />
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div data-reveal style="--d: 100" class="panel relative min-h-[300px] overflow-hidden p-5 sm:p-7" aria-live="polite">
                        <div class="pointer-events-none absolute -right-20 -bottom-24 size-64 rounded-full bg-protein/10 blur-3xl" aria-hidden="true"></div>
                        <div class="relative space-y-4">
                            @foreach ($chatQa as $i => $qa)
                                <div x-show="current === {{ $i }}" @if ($i > 0) x-cloak @endif class="space-y-4">
                                    <p class="toast-in ml-auto w-fit max-w-[88%] rounded-[16px] rounded-br-[4px] bg-sun px-4 py-2.5 text-[14px] font-medium text-canvas">{{ $qa['q'] }}</p>

                                    <div x-show="typing" x-cloak class="flex gap-2.5">
                                        <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-full border border-ink/12 bg-sunk text-sun"><x-icon name="sparkle" size="13" /></span>
                                        <div class="flex items-center gap-1.5 rounded-[16px] rounded-tl-[4px] border border-ink/8 bg-canvas px-4 py-4" role="status" aria-label="Sedang menjawab">
                                            <span class="pulse-dot size-1.5 rounded-full bg-sun"></span>
                                            <span class="pulse-dot size-1.5 rounded-full bg-sun [animation-delay:150ms]"></span>
                                            <span class="pulse-dot size-1.5 rounded-full bg-sun [animation-delay:300ms]"></span>
                                        </div>
                                    </div>

                                    <div x-show="shown && !typing" class="toast-in flex gap-2.5">
                                        <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-full border border-ink/12 bg-sunk text-sun"><x-icon name="sparkle" size="13" /></span>
                                        <div class="max-w-[92%] rounded-[16px] rounded-tl-[4px] border border-ink/8 bg-canvas px-4.5 py-3.5 text-[14px] leading-relaxed text-ink-2">
                                            {!! $qa['a'] !!}
                                            <span class="mt-3.5 flex h-1.5 overflow-hidden rounded-full bg-ink/10"><span class="bar-fill block h-full rounded-full" style="width: {{ $qa['bar'] }}%; background: var(--color-{{ $qa['color'] }})"></span></span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="relative mt-6 text-[11.5px] text-ink-3">Contoh percakapan. Jawaban asli memakai catatanmu.</p>
                    </div>
                </div>
            </section>

            {{-- Janji --}}
            <section class="border-y border-ink/8 bg-paper/50">
                <div class="mx-auto grid max-w-[1180px] gap-px px-5 py-20 sm:px-8 md:grid-cols-3 md:gap-12">
                    @foreach ($trust as $i => [$title, $text, $icon])
                        <div data-reveal style="--d: {{ $i * 90 }}" class="py-6 md:py-0">
                            <span class="grid size-10 place-items-center rounded-full border border-ink/12 bg-canvas text-sun"><x-icon :name="$icon" size="17" stroke="1.9" /></span>
                            <h3 class="font-display mt-5 text-[22px] leading-tight font-semibold tracking-[-0.015em]">{{ $title }}</h3>
                            <p class="mt-2 max-w-[22rem] text-[14.5px] leading-relaxed text-ink-2">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Penutup --}}
            <section class="mx-auto max-w-[1180px] px-5 py-24 sm:px-8">
                <div data-reveal class="panel relative grid gap-8 overflow-hidden rounded-stage px-6 py-14 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-14 sm:py-16">
                    <div class="pointer-events-none absolute -top-32 -right-20 size-96 rounded-full bg-sun/14 blur-3xl" aria-hidden="true"></div>
                    <div class="pointer-events-none absolute -bottom-32 -left-16 size-80 rounded-full bg-pine/10 blur-3xl" aria-hidden="true"></div>
                    <div class="relative max-w-[36rem]">
                        <h2 class="font-display text-[34px] leading-[1.03] font-semibold tracking-[-0.028em] sm:text-[48px]">Mulai dari <span class="em text-sun">piring berikutnya.</span></h2>
                        <p class="mt-4 text-[15px] leading-relaxed text-ink-2">
                            Angka dari foto bisa meleset, terutama untuk porsi dan minyak. Karena itu kamu selalu bisa mengoreksinya sebelum menyimpan.
                        </p>
                    </div>
                    <a href="{{ route('register') }}" class="group btn btn-primary relative shrink-0 px-8 py-4 text-[15.5px]">
                        Buat akun gratis
                        <x-icon name="chevron-right" size="16" stroke="2.2" class="transition-transform duration-[420ms] ease-spring group-hover:translate-x-1" />
                    </a>
                </div>
            </section>
        </main>

        <footer class="border-t border-ink/8">
            <div class="mx-auto flex max-w-[1180px] flex-col gap-3 px-5 py-7 text-[13px] text-ink-3 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <x-logo :size="26" />
                <p class="flex flex-wrap items-center gap-2">
                    Proyek tim SMK jurusan PPLG
                    <span aria-hidden="true" class="text-ink/20">·</span>
                    <a href="{{ route('privacy') }}" class="rounded-[3px] underline decoration-ink/25 underline-offset-[3px] transition-colors duration-140 hover:text-ink hover:decoration-ink">Kebijakan privasi</a>
                </p>
            </div>
        </footer>
    </div>
</x-layouts.guest>
