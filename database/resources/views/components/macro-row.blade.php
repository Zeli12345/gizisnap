@props(['label', 'value' => 0, 'target' => null, 'unit' => 'g', 'color' => 'pine', 'decimals' => 0])
@php
    $pct = $target ? (int) round($value / $target * 100) : null;
    $width = $pct !== null ? min(100, $pct) : 0;
    $over = $pct !== null && $pct > 105;
@endphp
{{-- Satu zat gizi: nama, angka, batang tipis. Angka dulu, grafik kedua. --}}
<div {{ $attributes }}>
    <div class="flex items-baseline justify-between gap-3">
        <span class="flex items-center gap-1.5 text-[12.5px] font-semibold text-ink-2">
            <span class="size-1.5 shrink-0 rounded-full" style="background: var(--color-{{ $color }})"></span>
            {{ $label }}
        </span>
        <span class="num text-[12px] whitespace-nowrap text-ink-3">
            <span @class(['text-[13.5px] font-semibold', 'text-danger' => $over, 'text-ink' => ! $over])>{{ gizi_num($value, $decimals) }}</span>@if ($target) / {{ gizi_num($target) }}@endif {{ $unit }}
        </span>
    </div>

    <div class="mt-2 h-[4px] overflow-hidden rounded-full bg-ink/10"
        @if ($target) role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $label }} {{ $pct }} persen dari target" @endif>
        <div class="bar-fill h-full rounded-full" style="width: {{ $width }}%; background: var(--color-{{ $over ? 'danger' : $color }})"></div>
    </div>
    @if ($over)
        <p class="mt-1 text-[11px] font-semibold text-danger">Lewat {{ $pct - 100 }}% dari target</p>
    @endif
</div>
