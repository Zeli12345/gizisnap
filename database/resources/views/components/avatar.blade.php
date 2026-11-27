@props(['user', 'size' => 'md'])
@php $cls = $size === 'sm' ? 'size-8 text-[11.5px]' : ($size === 'lg' ? 'size-16 text-[20px]' : 'size-9 text-[12.5px]'); @endphp
@if ($user->avatar_url)
    <img src="{{ $user->avatar_url }}" alt="" referrerpolicy="no-referrer" {{ $attributes->merge(['class' => "$cls shrink-0 rounded-full object-cover"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$cls grid shrink-0 place-items-center rounded-full border border-ink/10 bg-raise font-semibold text-ink-2"]) }} aria-hidden="true">{{ $user->initials() }}</span>
@endif
