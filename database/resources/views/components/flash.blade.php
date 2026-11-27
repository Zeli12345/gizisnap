@props([])
@php
    $messages = array_filter([
        'success' => session('success'),
        'error' => session('error'),
    ]);
@endphp
@if ($messages !== [])
    <div {{ $attributes }} data-flash>
        @foreach ($messages as $type => $message)
            {{-- Pesan sukses hilang sendiri setelah 6 detik; pesan galat menetap sampai ditutup. --}}
            <div x-data="{ show: true }" x-show="show" x-collapse.duration.240ms
                @if ($type === 'success') x-init="setTimeout(() => show = false, 6000)" @endif
                role="{{ $type === 'error' ? 'alert' : 'status' }}"
                @class([
                    'toast-in relative flex items-start gap-3 overflow-hidden rounded-control border px-4 py-3 text-[13.5px]',
                    'border-pine/25 bg-pine-tint text-pine' => $type === 'success',
                    'border-danger/25 bg-danger-tint text-danger' => $type === 'error',
                ])>
                <x-icon :name="$type === 'error' ? 'alert' : 'check'" size="16" stroke="2.1" class="mt-0.5 shrink-0" />
                <p class="flex-1 leading-snug font-medium">{{ $message }}</p>
                <button type="button" @click="show = false" class="-m-1.5 grid size-7 shrink-0 place-items-center rounded-[5px] opacity-60 transition-opacity duration-140 hover:opacity-100" aria-label="Tutup pesan">
                    <x-icon name="x" size="14" />
                </button>
                @if ($type === 'success')
                    <span class="absolute inset-x-0 bottom-0 h-[2px] origin-left bg-pine/60" style="animation: toast-life 6s linear forwards" aria-hidden="true"></span>
                @endif
            </div>
        @endforeach
    </div>
@endif
