@php
    $links = $paginator->linkCollection()->toArray();
    $current = $paginator->currentPage();
@endphp
@if ($paginator->hasPages())
    <nav class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-ink/8 pt-4" aria-label="Halaman">
        <p class="num text-[12px] text-ink-3">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}
        </p>
        <div class="flex items-center gap-1">
            @foreach ($links as $link)
                @continue($link['label'] === '&laquo; Previous' || $link['label'] === 'Next &raquo;')
                @if ($link['url'] === null)
                    <span class="num grid size-8 place-items-center rounded-[4px] text-[12px] text-ink-3">{{ $link['label'] }}</span>
                @else
                    <a href="{{ $link['url'] }}" @if ($link['label'] == $current) aria-current="page" @endif
                        @class([
                            'num grid size-8 place-items-center rounded-[4px] text-[12px] font-semibold transition-colors duration-140',
                            'bg-sun text-canvas' => $link['label'] == $current,
                            'text-ink-2 hover:bg-sunk hover:text-ink' => $link['label'] != $current,
                        ])>{{ $link['label'] }}</a>
                @endif
            @endforeach
        </div>
    </nav>
@endif
