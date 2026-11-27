@props(['label', 'value', 'unit' => null, 'note' => null, 'color' => null])
@php
    // Kalau nilai berupa angka biasa, hitung naik saat terlihat. Selain itu tampil apa adanya.
    $raw = is_string($value) ? str_replace(['.', ','], ['', '.'], $value) : $value;
    $countable = is_numeric($raw) && (float) $raw > 0 && ! str_starts_with((string) $value, '+');
@endphp
<dl {{ $attributes->merge(['class' => 'border-t border-ink/10 pt-3']) }}>
    <dt class="flex items-center gap-1.5 text-[11.5px] font-medium text-ink-3">
        @if ($color)<span class="size-1.5 shrink-0 rounded-full" style="background: var(--color-{{ $color }})"></span>@endif
        <span class="truncate">{{ $label }}</span>
    </dt>
    <dd class="num mt-1.5 flex items-baseline gap-1">
        <span class="figure text-[26px] sm:text-[31px]" @if ($countable) data-count="{{ $raw }}" data-decimals="{{ str_contains((string) $raw, '.') ? 1 : 0 }}" @endif>{{ $value }}</span>
        @if ($unit)<span class="text-[11.5px] font-semibold text-ink-3">{{ $unit }}</span>@endif
    </dd>
    @if ($note)<dd class="mt-1.5 text-[11.5px] leading-snug text-ink-3">{{ $note }}</dd>@endif
</dl>
