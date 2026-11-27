{{--
    Bingkai halaman masuk, daftar, dan lupa sandi.
    Kolom formulir di kiri; panel kanan menjelaskan apa yang didapat setelah masuk,
    dengan satu kartu hasil scan yang hidup pelan.
--}}
@props([
    'heading',
    'subtitle' => null,
    'switcher' => null,
])
<x-layouts.guest :title="$heading">
    <div class="grid min-h-dvh lg:grid-cols-[minmax(0,1fr)_minmax(0,0.95fr)]">

        {{-- Kolom formulir --}}
        <div class="flex flex-col px-5 py-6 sm:px-10 lg:px-16 lg:py-8">
            <header class="flex items-center justify-between gap-4">
                <a href="{{ route('landing') }}" aria-label="GiziSnap, ke halaman depan" class="rounded-control"><x-logo /></a>
                @if ($switcher)
                    <a href="{{ $switcher['url'] }}" class="group text-[13px] text-ink-2 transition-colors duration-140 hover:text-ink">
                        {{ $switcher['label'] }}<span aria-hidden="true" class="ml-1.5 inline-block transition-transform duration-[420ms] ease-spring group-hover:translate-x-1">→</span>
                    </a>
                @endif
            </header>

            <main class="rise mx-auto flex w-full max-w-[396px] flex-1 flex-col justify-center py-12">
                <h1 class="display text-[32px] sm:text-[40px]">{{ $heading }}</h1>
                @if ($subtitle)
                    <p class="mt-3 text-[14.5px] leading-relaxed text-ink-2">{{ $subtitle }}</p>
                @endif

                <x-flash class="mt-6" />

                <div class="mt-7">{{ $slot }}</div>

                @isset($after)
                    <div class="mt-7">{{ $after }}</div>
                @endisset
            </main>

            <footer class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[12px] text-ink-3">
                <a href="{{ route('privacy') }}" class="rounded-[3px] transition-colors duration-140 hover:text-ink">Kebijakan privasi</a>
                <span aria-hidden="true">·</span>
                <a href="{{ route('landing') }}" class="rounded-[3px] transition-colors duration-140 hover:text-ink">Halaman depan</a>
            </footer>
        </div>

        {{-- Panel kanan: penjelas --}}
        <aside class="relative hidden overflow-hidden border-l border-ink/8 bg-paper/70 lg:flex lg:flex-col lg:justify-center lg:px-14">
            <div class="pointer-events-none absolute -top-32 -right-24 size-[26rem] rounded-full bg-sun/12 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-40 -left-20 size-[24rem] rounded-full bg-pine/10 blur-3xl" aria-hidden="true"></div>

            <div class="relative max-w-[24rem]">
                <p class="eyebrow">Yang kamu dapat</p>

                <p class="font-display mt-4 text-[32px] leading-[1.05] font-semibold tracking-[-0.025em]">
                    Satu foto, dan kamu tahu <span class="em text-sun">di mana posisimu</span> hari ini.
                </p>

                {{-- Kartu hasil scan, naik pelan --}}
                <div class="drift mt-9 rounded-surface border border-ink/10 bg-canvas/70 p-4 shadow-[0_24px_48px_-20px_rgb(0_0_0/0.8)] backdrop-blur-md" aria-hidden="true">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-[11px] font-semibold tracking-[0.05em] text-ink-3 uppercase"><span class="size-1.5 rounded-full bg-sun"></span>Hasil scan</span>
                        <span class="rounded-[4px] bg-pine-tint px-1.5 py-0.5 text-[10.5px] font-semibold text-pine">Yakin 94%</span>
                    </div>
                    <p class="font-display mt-2.5 text-[19px] leading-tight font-semibold">Nasi ayam bakar</p>
                    <div class="mt-3 space-y-1.5 border-t border-ink/8 pt-3">
                        @foreach ([['Nasi putih', 195], ['Ayam bakar', 226], ['Tempe goreng', 118]] as [$n, $k])
                            <p class="flex items-baseline justify-between text-[12.5px]"><span class="text-ink-2">{{ $n }}</span><span class="num font-semibold">{{ $k }}</span></p>
                        @endforeach
                    </div>
                    <div class="mt-3 flex items-baseline justify-between border-t border-ink/10 pt-3">
                        <span class="text-[11.5px] font-semibold text-ink-2">Total</span>
                        <span class="num font-display text-[26px] leading-none font-semibold">539 <span class="font-sans text-[11px] font-semibold text-ink-3">kkal</span></span>
                    </div>
                </div>

                <ol class="mt-9 space-y-4">
                    @foreach ([
                        ['Target dari tubuhmu', 'Dihitung dari tinggi, berat, usia, dan aktivitas, bukan angka umum.'],
                        ['Catatan yang rapi', 'Semua makanan tersusun per jam, lengkap dengan kalori dan makronya.'],
                        ['Bisa ditanya', 'Tanya gizi menjawab memakai catatanmu sendiri.'],
                    ] as $i => [$title, $text])
                        <li class="flex gap-3.5">
                            <span class="num mt-0.5 grid size-5 shrink-0 place-items-center rounded-full border border-ink/12 text-[10.5px] font-semibold text-sun">{{ $i + 1 }}</span>
                            <span>
                                <span class="block text-[13.5px] font-semibold">{{ $title }}</span>
                                <span class="mt-0.5 block text-[12.5px] leading-relaxed text-ink-2">{{ $text }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </aside>
    </div>
</x-layouts.guest>
