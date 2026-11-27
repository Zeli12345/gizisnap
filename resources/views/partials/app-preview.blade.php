{{--
    Pratinjau di halaman depan: sebuah piring dari atas dengan bidik kamera, garis pindai,
    dan penanda yang muncul bergantian. Seluruhnya SVG dan CSS; dekoratif bagi pembaca layar.
    Satu putaran 7 detik: pindai turun, penanda muncul satu per satu, lalu semuanya reda.
--}}
@php
    $pins = [
        ['Nasi putih', 195, 'left: 3%; top: 24%'],
        ['Ayam bakar', 226, 'right: 2%; top: 12%'],
        ['Tempe goreng', 118, 'right: 3%; top: 64%'],
        ['Sayur asem', 54, 'left: 24%; top: 86%'],
        ['Sambal', 21, 'left: 1%; top: 66%'],
    ];
@endphp
<div class="relative mx-auto w-full max-w-[460px] lg:mx-0 lg:ml-auto" aria-hidden="true">
    {{-- Cahaya di belakang piring --}}
    <div class="absolute inset-[8%] rounded-full bg-sun/10 blur-3xl"></div>

    <div class="drift relative aspect-square">
        {{-- Bingkai bidik --}}
        <span class="corner corner-tl" style="top: 2%; left: 2%"></span>
        <span class="corner corner-tr" style="top: 2%; right: 2%"></span>
        <span class="corner corner-bl" style="bottom: 2%; left: 2%"></span>
        <span class="corner corner-br" style="bottom: 2%; right: 2%"></span>

        {{-- Meja dan piring --}}
        <svg viewBox="0 0 400 400" class="absolute inset-[5%] size-[90%]">
            <defs>
                <radialGradient id="plate" cx=".4" cy=".35" r=".8">
                    <stop offset="0" stop-color="#2d322a" />
                    <stop offset="1" stop-color="#1b1f19" />
                </radialGradient>
                <radialGradient id="rice" cx=".4" cy=".35" r=".8">
                    <stop offset="0" stop-color="#f6f1e1" />
                    <stop offset="1" stop-color="#d9d2bb" />
                </radialGradient>
                <radialGradient id="chicken" cx=".35" cy=".3" r=".9">
                    <stop offset="0" stop-color="#c9793a" />
                    <stop offset="1" stop-color="#7d3f18" />
                </radialGradient>
                <filter id="soft" x="-20%" y="-20%" width="140%" height="140%"><feGaussianBlur stdDeviation="6" /></filter>
            </defs>

            {{-- Bayangan piring --}}
            <ellipse cx="206" cy="214" rx="176" ry="170" fill="#000" opacity=".5" filter="url(#soft)" />
            {{-- Piring --}}
            <circle cx="200" cy="200" r="176" fill="url(#plate)" stroke="#f3f0e6" stroke-opacity=".1" stroke-width="2" />
            <circle cx="200" cy="200" r="140" fill="none" stroke="#f3f0e6" stroke-opacity=".07" stroke-width="1.5" />

            {{-- Nasi: gundukan dari butir --}}
            <g>
                <ellipse cx="128" cy="170" rx="62" ry="56" fill="url(#rice)" />
                @foreach ([[104, 150], [124, 140], [146, 152], [112, 176], [136, 178], [96, 168], [152, 172], [122, 158], [140, 138], [108, 190]] as [$gx, $gy])
                    <ellipse cx="{{ $gx }}" cy="{{ $gy }}" rx="5.5" ry="2.6" fill="#fff" opacity=".55" transform="rotate({{ ($gx * 7) % 70 - 35 }} {{ $gx }} {{ $gy }})" />
                @endforeach
            </g>

            {{-- Ayam bakar --}}
            <g transform="rotate(-18 262 150)">
                <ellipse cx="262" cy="150" rx="64" ry="38" fill="url(#chicken)" />
                <ellipse cx="248" cy="140" rx="30" ry="12" fill="#e29a55" opacity=".5" />
                <path d="M232 160c12 6 28 8 46 4" stroke="#4d2410" stroke-width="3" fill="none" opacity=".55" stroke-linecap="round" />
                <path d="M244 134c10-4 22-4 32 0" stroke="#4d2410" stroke-width="2.5" fill="none" opacity=".45" stroke-linecap="round" />
                <circle cx="324" cy="150" r="9" fill="#e7d9bd" />
            </g>

            {{-- Tempe goreng --}}
            <g transform="rotate(12 262 268)">
                <rect x="222" y="246" width="56" height="44" rx="9" fill="#b9832f" />
                <rect x="222" y="246" width="56" height="44" rx="9" fill="none" stroke="#e8c37a" stroke-width="2" opacity=".6" />
                <rect x="284" y="254" width="50" height="40" rx="9" fill="#a8742a" />
                @foreach ([[236, 262], [252, 276], [264, 258], [296, 270], [314, 282], [304, 262]] as [$tx, $ty])
                    <circle cx="{{ $tx }}" cy="{{ $ty }}" r="3" fill="#6b4413" opacity=".55" />
                @endforeach
            </g>

            {{-- Sayur dan sambal --}}
            <g>
                <circle cx="150" cy="268" r="34" fill="#3d6b46" />
                @foreach ([[138, 258, 12], [160, 270, 14], [146, 280, 11], [166, 252, 9]] as [$lx, $ly, $lr])
                    <circle cx="{{ $lx }}" cy="{{ $ly }}" r="{{ $lr }}" fill="#58a064" opacity=".85" />
                @endforeach
            </g>
            <circle cx="92" cy="262" r="17" fill="#a22a1c" />
            <circle cx="88" cy="258" r="8" fill="#d6492f" opacity=".8" />
            <circle cx="98" cy="268" r="4" fill="#f08a76" opacity=".6" />
        </svg>

        {{-- Garis pindai --}}
        <div class="absolute inset-x-[7%] inset-y-[7%] overflow-hidden rounded-[50%]">
            <div class="scan-pass absolute inset-0"><div class="scan-band"></div></div>
        </div>

        {{-- Penanda hasil --}}
        @foreach ($pins as $i => [$name, $kcal, $pos])
            @php preg_match('/top: (\d+)%/', $pos, $m); $t = (int) ($m[1] / 100 * 3000); @endphp
            <div class="box-cycle absolute z-10" style="{{ $pos }}; --t: {{ $t }}">
                <div class="flex items-center gap-2 rounded-[8px] border border-ink/14 bg-canvas/85 py-1.5 pr-3 pl-2 shadow-[0_10px_24px_-8px_rgb(0_0_0/0.8)] backdrop-blur-md">
                    <span class="size-2 rounded-full bg-sun shadow-[0_0_0_3px_rgb(240_176_78/0.2)]"></span>
                    <span class="text-[11.5px] leading-none font-semibold">{{ $name }}</span>
                    <span class="num text-[11px] leading-none text-ink-3">{{ $kcal }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Total, di bawah piring --}}
    <div class="relative mx-auto -mt-2 flex w-fit items-center gap-5 rounded-surface border border-ink/10 bg-paper/90 px-5 py-3 shadow-[0_18px_40px_-14px_rgb(0_0_0/0.8)] backdrop-blur-md">
        <div>
            <p class="text-[10.5px] font-semibold tracking-[0.06em] text-ink-3 uppercase">Total piring</p>
            <p class="num font-display mt-1 text-[30px] leading-none font-semibold tracking-[-0.03em]">614 <span class="font-sans text-[11.5px] font-semibold text-ink-3">kkal</span></p>
        </div>
        <span class="h-9 w-px bg-ink/10"></span>
        <p class="num grid gap-0.5 text-[11px] text-ink-2">
            <span class="flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-protein"></span>P 38 g</span>
            <span class="flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-carbs"></span>K 62 g</span>
            <span class="flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-fat"></span>L 22 g</span>
        </p>
    </div>
</div>
