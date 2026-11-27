@props(['title', 'back' => null, 'eyebrow' => null])
<div {{ $attributes->merge(['class' => 'rise mb-8 flex flex-wrap items-end justify-between gap-x-5 gap-y-4']) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="group -ml-1.5 mb-3 inline-flex items-center gap-1.5 rounded-[4px] px-1.5 py-0.5 text-[12.5px] font-medium text-ink-3 transition-colors duration-140 ease-smooth hover:text-ink">
                <x-icon name="arrow-left" size="14" class="transition-transform duration-[420ms] ease-spring group-hover:-translate-x-1" /> Kembali
            </a>
        @endif
        @if ($eyebrow)
            <p class="eyebrow mb-2.5">{{ $eyebrow }}</p>
        @endif
        <h1 class="display">{{ $title }}</h1>
        @isset($subtitle)
            <p class="mt-2.5 max-w-[38rem] text-[14.5px] leading-relaxed text-ink-2">{{ $subtitle }}</p>
        @endisset
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
