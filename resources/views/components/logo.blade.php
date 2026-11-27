@props(['size' => 30, 'withText' => true])
{{--
    Tanda GiziSnap: piring dari atas dengan satu irisan terpotong.
    Kunyit sebagai satu-satunya warna terang di tanda.
--}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 32 32" aria-hidden="true" class="shrink-0">
        <rect width="32" height="32" rx="8" fill="#212520" />
        <rect x="0.75" y="0.75" width="30.5" height="30.5" rx="7.25" fill="none" stroke="#f3f0e6" stroke-opacity=".12" stroke-width="1.5" />
        <circle cx="16" cy="16.5" r="8.4" fill="none" stroke="#f3f0e6" stroke-width="1.8" />
        <circle cx="16" cy="16.5" r="3.4" fill="#f0b04e" />
        <path d="M16 8.1v4.6M16 20.3v1" stroke="#f3f0e6" stroke-width="1.8" stroke-linecap="round" />
        <circle cx="24.9" cy="7.9" r="2.5" fill="#f0b04e" />
    </svg>
    @if ($withText)
        <span class="font-display text-[21px] leading-none font-semibold tracking-[-0.03em] text-ink">GiziSnap</span>
    @endif
</span>
