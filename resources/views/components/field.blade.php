@props(['label' => null, 'name', 'hint' => null, 'suffix' => null, 'id' => null])
@php $id = $id ?? 'f-'.str_replace(['[', ']', '.'], '-', $name); @endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}</label>
    @endif
    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}"
            {{ $attributes->except('class')->merge(['class' => 'field'.($suffix ? ' pr-14' : '')]) }}
            @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror>
        @if ($suffix)
            <span class="pointer-events-none absolute inset-y-0 right-3.5 flex items-center text-[13px] font-medium text-ink-3">{{ $suffix }}</span>
        @endif
    </div>
    @error($name)
        <p id="{{ $id }}-error" class="error-text">{{ $message }}</p>
    @else
        @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @enderror
</div>
