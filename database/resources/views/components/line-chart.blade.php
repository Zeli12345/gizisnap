@props(['labels', 'values', 'unit' => '', 'height' => 200, 'color' => 'sun', 'decimals' => 1])
@php
    // Grafik garis SVG murni: diukur sekali di server, tidak ada yang bisa salah ukur.
    $vals = collect($values)->map(fn ($v) => (float) $v)->values();
    $n = $vals->count();
    $min = $vals->min() ?? 0;
    $max = $vals->max() ?? 0;
    $pad = max(0.5, ($max - $min) * 0.25);
    $lo = floor(($min - $pad) * 2) / 2;
    $hi = ceil(($max + $pad) * 2) / 2;
    $span = max(0.5, $hi - $lo);

    $W = 600;
    $H = (int) $height;
    $L = 34;
    $R = 14;
    $T = 14;
    $B = 26;
    $iw = $W - $L - $R;
    $ih = $H - $T - $B;

    $x = fn ($i) => $L + ($n > 1 ? $i / ($n - 1) * $iw : $iw / 2);
    $y = fn ($v) => $T + (1 - ($v - $lo) / $span) * $ih;

    $pts = $vals->map(fn ($v, $i) => [round($x($i), 1), round($y($v), 1)]);

    // Kurva halus (Catmull-Rom ke Bezier), tanpa meluap di atas/bawah data.
    $path = '';
    foreach ($pts as $i => [$px, $py]) {
        if ($i === 0) {
            $path .= "M{$px},{$py}";
            continue;
        }
        [$p0x, $p0y] = $pts[max(0, $i - 2)];
        [$p1x, $p1y] = $pts[$i - 1];
        [$p3x, $p3y] = $pts[min($n - 1, $i + 1)];
        $c1x = round($p1x + ($px - $p0x) / 6, 1);
        $c1y = round($p1y + ($py - $p0y) / 6, 1);
        $c2x = round($px - ($p3x - $p1x) / 6, 1);
        $c2y = round($py - ($p3y - $p1y) / 6, 1);
        $c1y = max($T, min($T + $ih, $c1y));
        $c2y = max($T, min($T + $ih, $c2y));
        $path .= " C{$c1x},{$c1y} {$c2x},{$c2y} {$px},{$py}";
    }
    $area = $n > 1 ? $path." L{$pts->last()[0]},".($T + $ih).' L'.$pts->first()[0].','.($T + $ih).' Z' : '';

    $uid = 'lc'.substr(md5(json_encode($values).$height), 0, 6);
    $every = max(1, (int) ceil($n / 6));
@endphp
<figure {{ $attributes->merge(['class' => 'relative']) }} x-data="{ at: null }">
    <svg viewBox="0 0 {{ $W }} {{ $H }}" class="block w-full overflow-visible" role="img" aria-label="Grafik garis. Angka lengkap ada di daftar riwayat." @mouseleave="at = null">
        <defs>
            <linearGradient id="{{ $uid }}-fill" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0" stop-color="var(--color-{{ $color }})" stop-opacity=".22" />
                <stop offset="1" stop-color="var(--color-{{ $color }})" stop-opacity="0" />
            </linearGradient>
            <clipPath id="{{ $uid }}-reveal">
                <rect x="0" y="0" width="{{ $W }}" height="{{ $H }}">
                    <animate attributeName="width" from="0" to="{{ $W }}" dur="1.1s" begin="0s" fill="freeze" calcMode="spline" keyTimes="0;1" keySplines=".16 1 .3 1" />
                </rect>
            </clipPath>
        </defs>

        @foreach ([0, 0.5, 1] as $g)
            @php $gy = $T + $g * $ih; $gv = $hi - $g * $span; @endphp
            <line x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $gy }}" y2="{{ $gy }}" stroke="var(--color-ink)" stroke-opacity=".07" stroke-dasharray="{{ $g === 1 ? '0' : '2 5' }}" />
            <text x="{{ $L - 8 }}" y="{{ $gy + 3.5 }}" text-anchor="end" fill="var(--color-ink-3)" font-size="10" class="num">{{ gizi_num($gv, 0) }}</text>
        @endforeach

        <g clip-path="url(#{{ $uid }}-reveal)">
            @if ($area)
                <path d="{{ $area }}" fill="url(#{{ $uid }}-fill)" />
            @endif
            <path d="{{ $path }}" fill="none" stroke="var(--color-{{ $color }})" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" />
        </g>

        @foreach ($labels as $i => $label)
            @if ($i === $n - 1 || ($i % $every === 0 && ($n - 1 - $i) >= $every))
                <text x="{{ $x($i) }}" y="{{ $H - 6 }}" text-anchor="{{ $i === 0 ? 'start' : ($i === $n - 1 ? 'end' : 'middle') }}" fill="var(--color-ink-3)" font-size="10" class="num">{{ $label }}</text>
            @endif
        @endforeach

        @foreach ($vals as $i => $v)
            <g @mouseenter="at = {{ $i }}" @focus="at = {{ $i }}" tabindex="0">
                <rect x="{{ $x($i) - ($iw / max(1, $n - 1)) / 2 }}" y="0" width="{{ max(14, $iw / max(1, $n - 1)) }}" height="{{ $H }}" fill="transparent" />
                <line x1="{{ $x($i) }}" x2="{{ $x($i) }}" y1="{{ $T }}" y2="{{ $T + $ih }}" stroke="var(--color-ink)" stroke-opacity=".18" x-show="at === {{ $i }}" x-cloak />
                <circle cx="{{ $x($i) }}" cy="{{ $y($v) }}" r="{{ $i === $n - 1 ? 4.5 : 3 }}" fill="{{ $i === $n - 1 ? 'var(--color-'.$color.')' : 'var(--color-canvas)' }}" stroke="var(--color-{{ $color }})" stroke-width="2"
                    :r="at === {{ $i }} ? 5.5 : {{ $i === $n - 1 ? 4.5 : 3 }}" style="transition: r 160ms var(--ease-smooth)" />
            </g>
        @endforeach
    </svg>

    {{-- Keterangan: kotak HTML, bukan teks SVG, supaya huruf dan jaraknya sama dengan halaman --}}
    @foreach ($vals as $i => $v)
        <div x-show="at === {{ $i }}" x-cloak
            class="float pointer-events-none absolute z-10 -translate-x-1/2 px-2.5 py-1.5 text-center"
            style="left: {{ round($x($i) / $W * 100, 2) }}%; top: {{ max(0, round($y($v) / $H * 100 - 24, 2)) }}%">
            <p class="num text-[13px] leading-none font-semibold whitespace-nowrap">{{ gizi_num($v, $decimals) }} <span class="text-[10.5px] font-medium text-ink-3">{{ $unit }}</span></p>
            <p class="mt-1 text-[10.5px] leading-none whitespace-nowrap text-ink-3">{{ $labels[$i] }}</p>
        </div>
    @endforeach
</figure>
