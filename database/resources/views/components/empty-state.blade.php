@props(['icon' => 'leaf', 'title'])
<div {{ $attributes->merge(['class' => 'rounded-surface border border-dashed border-ink/14 px-6 py-12 text-center']) }}>
    <span class="relative mx-auto mb-5 grid size-12 place-items-center rounded-full border border-ink/10 bg-sunk text-ink-2">
        <span class="ping-ring absolute inset-0 rounded-full border border-sun/40" aria-hidden="true"></span>
        <x-icon :name="$icon" size="21" />
    </span>
    <h3 class="title text-[19px]">{{ $title }}</h3>
    @if (trim($slot))
        <div class="mx-auto mt-2 max-w-sm text-[14.5px] leading-relaxed text-ink-2">{{ $slot }}</div>
    @endif
    @isset($action)
        <div class="mt-6 flex justify-center">{{ $action }}</div>
    @endisset
</div>
