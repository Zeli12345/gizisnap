@props([])
<p {{ $attributes->merge(['class' => 'flex items-start gap-2 text-[12.5px] leading-relaxed text-ink-3']) }}>
    <x-icon name="info" size="14" class="mt-px shrink-0" />
    <span>{{ trim($slot) ?: 'Angka gizi adalah perkiraan, bukan hasil laboratorium. GiziSnap bukan pengganti saran dokter atau ahli gizi.' }}</span>
</p>
