@props(['labels', 'series', 'height' => 200, 'unit' => ''])
{{--
    Batang bertumpuk dari CSS murni.
    $series: [['name' => 'Protein', 'color' => 'protein', 'values' => [...]], ...]
--}}
@php
    $n = count($labels);
    $totals = collect(range(0, $n - 1))->map(fn ($i) => collect($series)->sum(fn ($s) => (float) ($s['values'][$i] ?? 0)));
    $peak = max($totals->max() ?? 0, 1);
    $dense = $n > 10;
@endphp
<figure {{ $attributes }} x-data="{ at: null }">
    <div class="relative flex items-end justify-between gap-1 border-b border-ink/12" style="height: {{ $height }}px" role="img"
        aria-label="Grafik batang bertumpuk: {{ collect($series)->pluck('name')->implode(', ') }} per hari.">
        @foreach ([0.5, 1] as $g)
            <div class="pointer-events-none absolute inset-x-0 border-t border-dashed border-ink/6" style="bottom: {{ $g * 100 }}%"></div>
        @endforeach

        @foreach ($labels as $i => $label)
            <div class="relative flex h-full flex-1 items-end justify-center" @mouseenter="at = {{ $i }}" @mouseleave="at = null" @focus="at = {{ $i }}" @blur="at = null" tabindex="0">
                <div class="col-grow flex w-full max-w-[34px] flex-col-reverse gap-px overflow-hidden rounded-t-[3px] transition-opacity duration-140" style="--i: {{ $i }}; height: {{ $totals[$i] / $peak * 100 }}%"
                    :class="at !== null && at !== {{ $i }} && 'opacity-40'">
                    @foreach ($series as $s)
                        @php $v = (float) ($s['values'][$i] ?? 0); @endphp
                        @if ($v > 0)
                            <span class="block min-h-px" style="flex: {{ $v }} 1 0; background: var(--color-{{ $s['color'] }})"></span>
                        @endif
                    @endforeach
                </div>

                <div x-show="at === {{ $i }}" x-cloak class="float pointer-events-none absolute bottom-full z-10 mb-2 min-w-[7rem] px-3 py-2">
                    <p class="text-[10.5px] leading-none text-ink-3">{{ $label }}</p>
                    @foreach ($series as $s)
                        <p class="num mt-1.5 flex items-center justify-between gap-3 text-[12px] leading-none">
                            <span class="flex items-center gap-1.5 text-ink-2"><span class="size-1.5 rounded-full" style="background: var(--color-{{ $s['color'] }})"></span>{{ $s['name'] }}</span>
                            <span class="font-semibold">{{ gizi_num($s['values'][$i] ?? 0) }}{{ $unit }}</span>
                        </p>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <figcaption class="mt-2 flex justify-between gap-1">
        @foreach ($labels as $i => $label)
            <span @class(['num flex-1 text-center text-[10px] text-ink-3', 'invisible' => $dense && $i % 5 !== 0 && $i !== $n - 1])>{{ $label }}</span>
        @endforeach
    </figcaption>

    <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[11.5px] text-ink-2">
        @foreach ($series as $s)
            <li class="flex items-center gap-1.5"><span class="size-2 rounded-[2px]" style="background: var(--color-{{ $s['color'] }})"></span>{{ $s['name'] }}</li>
        @endforeach
    </ul>
</figure>
