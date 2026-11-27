@php
    $chatConfig = [
        'messages' => $messages->map(fn ($m) => [
            'id' => $m->id,
            'role' => $m->role,
            'content' => $m->content,
            'html' => \Illuminate\Support\Str::markdown($m->content, ['html_input' => 'escape', 'allow_unsafe_links' => false]),
            'time' => $m->created_at->format('H.i'),
        ])->values(),
        'conversation' => $conversation ? [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'url' => route('chat.show', $conversation),
            'send_url' => route('chat.send', $conversation),
            'retry_url' => route('chat.retry', $conversation),
        ] : null,
        'storeUrl' => route('chat.store'),
        'pending' => (bool) $pending,
    ];
@endphp
<x-layouts.app title="Tanya gizi" :wide="true">
    <div class="grid gap-5 lg:h-[calc(100dvh-104px)] lg:grid-cols-[236px_minmax(0,1fr)]">

        {{-- Riwayat percakapan --}}
        <aside @class(['flex-col overflow-hidden lg:flex', 'hidden' => $conversation, 'flex' => ! $conversation])
            x-data="{ items: @js($conversations->map(fn ($c) => ['id' => $c->id, 'title' => $c->title, 'url' => route('chat.show', $c)])) }"
            @conversation-started.window="if (!items.find(i => i.id === $event.detail.id)) items.unshift({ id: $event.detail.id, title: $event.detail.title, url: $event.detail.url })">
            <a href="{{ route('chat.index') }}" class="btn btn-quiet w-full justify-start">
                <x-icon name="plus" size="15" stroke="2" /> Percakapan baru
            </a>
            <h2 class="mt-6 mb-1 px-1 text-[11.5px] font-semibold tracking-[0.05em] text-ink-3 uppercase">Riwayat</h2>
            <ul class="flex-1 space-y-px overflow-y-auto">
                <template x-for="item in items" :key="item.id">
                    <li>
                        <a :href="item.url" class="block truncate rounded-[5px] px-2.5 py-2 text-[13.5px] transition-colors duration-140"
                            :class="item.id === {{ $conversation?->id ?? 'null' }} ? 'bg-paper font-semibold text-ink' : 'text-ink-2 hover:bg-sunk/60 hover:text-ink'"
                            x-text="item.title"></a>
                    </li>
                </template>
                <li x-show="items.length === 0" class="px-2.5 py-2 text-[13px] text-ink-3">Belum ada percakapan.</li>
            </ul>
        </aside>

        {{-- Percakapan --}}
        <section class="flex min-h-[calc(100dvh-200px)] flex-col overflow-hidden rounded-surface border border-line bg-paper lg:min-h-0" x-data="chat(@js($chatConfig))">
            <header class="flex items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
                <div class="flex min-w-0 items-center gap-2">
                    @if ($conversation)
                        <a href="{{ route('chat.index') }}" class="-ml-1 grid size-8 place-items-center rounded-[5px] text-ink-2 transition-colors duration-140 hover:bg-sunk hover:text-ink lg:hidden" aria-label="Kembali ke daftar">
                            <x-icon name="chevron-left" size="17" />
                        </a>
                    @endif
                    <div class="min-w-0">
                        <h1 class="truncate text-[14px] leading-tight font-semibold" x-text="conversation ? conversation.title : 'Tanya gizi'">{{ $conversation?->title ?? 'Tanya gizi' }}</h1>
                        <p class="mt-0.5 text-[12px] text-ink-3">Dijawab dengan target dan catatan makanmu</p>
                    </div>
                </div>
                @if ($conversation)
                    <form method="POST" action="{{ route('chat.destroy', $conversation) }}" x-data="{ confirming: false }" class="flex shrink-0 items-center gap-1">
                        @csrf @method('DELETE')
                        <button type="button" x-show="!confirming" @click="confirming = true" class="grid size-8 place-items-center rounded-[5px] text-ink-3 transition-colors duration-140 hover:bg-danger-tint hover:text-danger" aria-label="Hapus percakapan">
                            <x-icon name="trash" size="16" />
                        </button>
                        <span x-show="confirming" x-cloak class="flex items-center gap-1">
                            <button class="btn btn-danger-solid btn-sm">Hapus</button>
                            <button type="button" class="btn btn-ghost btn-sm" @click="confirming = false">Batal</button>
                        </span>
                    </form>
                @endif
            </header>

            <div x-ref="thread" class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
                {{-- Kosong --}}
                <div x-show="messages.length === 0" class="mx-auto flex min-h-full max-w-md flex-col justify-center">
                    <h2 class="text-[19px] leading-snug font-semibold tracking-[-0.01em]">Tanya apa saja soal makananmu.</h2>
                    <p class="mt-1.5 text-[13px] leading-relaxed text-ink-2">Jawabannya memakai profil, target, dan catatan makanmu hari ini.</p>
                    <div class="mt-5 flex flex-col gap-1.5">
                        @foreach ($suggestions as $suggestion)
                            <button type="button"
                                class="group flex items-center gap-3 rounded-control border border-ink/8 bg-sunk/60 px-3.5 py-2.5 text-left text-[13px] font-medium transition-colors duration-140 ease-smooth hover:border-ink/16 hover:bg-raise"
                                @click="use(@js($suggestion))" :disabled="sending">
                                <span class="min-w-0 flex-1">{{ $suggestion }}</span>
                                <x-icon name="arrow-up" size="14" class="shrink-0 rotate-45 text-ink-3 transition-colors duration-140 group-hover:text-sun" />
                            </button>
                        @endforeach
                    </div>
                </div>

                <ul class="mx-auto max-w-[40rem] space-y-5" aria-live="polite">
                    <template x-for="message in messages" :key="message.id">
                        <li class="toast-in flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start gap-2.5'">
                            <span x-show="message.role === 'assistant'" class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-full border border-ink/12 bg-sunk text-sun"><x-icon name="sparkle" size="13" /></span>
                            <div class="max-w-[88%]">
                                <div x-show="message.role === 'user'" class="rounded-[16px] rounded-br-[4px] bg-sun px-4 py-2.5 text-[14px] leading-[1.55] font-medium whitespace-pre-line text-canvas" x-text="message.content"></div>
                                <div x-show="message.role === 'assistant'" class="prose-chat rounded-[16px] rounded-tl-[4px] border border-ink/8 bg-canvas px-4 py-3 text-[14px] leading-[1.6]" x-html="message.html"></div>
                                <p class="num mt-1 px-1 text-[11px] text-ink-3" :class="message.role === 'user' && 'text-right'" x-text="message.time"></p>
                            </div>
                        </li>
                    </template>
                    <li x-show="sending" x-cloak class="toast-in flex gap-2.5">
                        <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-full border border-ink/12 bg-sunk text-sun"><x-icon name="sparkle" size="13" /></span>
                        <div class="flex items-center gap-2.5 rounded-[16px] rounded-tl-[4px] border border-ink/8 bg-canvas px-4 py-3.5" role="status" aria-label="Sedang menjawab">
                            <span class="flex items-center gap-1.5">
                                <span class="pulse-dot size-1.5 rounded-full bg-sun"></span>
                                <span class="pulse-dot size-1.5 rounded-full bg-sun [animation-delay:150ms]"></span>
                                <span class="pulse-dot size-1.5 rounded-full bg-sun [animation-delay:300ms]"></span>
                            </span>
                            <span class="shimmer-text text-[12.5px] font-medium">Membaca catatanmu…</span>
                        </div>
                    </li>
                </ul>

                <div x-show="error" x-cloak class="mx-auto mt-4 flex max-w-[40rem] flex-wrap items-center justify-between gap-3 rounded-control border border-danger/20 bg-danger-tint px-4 py-3 text-[13px] text-danger" role="alert">
                    <span x-text="error"></span>
                    <button type="button" x-show="canRetry" class="btn btn-quiet btn-sm" @click="retry()"><x-icon name="refresh" size="14" /> Kirim ulang</button>
                </div>
            </div>

            <form class="border-t border-line p-3" @submit.prevent="send()">
                <div class="mx-auto flex max-w-[40rem] items-end gap-2 rounded-[10px] border border-line-strong/60 bg-paper p-1.5 pl-4 transition-[border-color,box-shadow] duration-140 focus-within:border-sun/70 focus-within:ring-3 focus-within:ring-sun/12">
                    <label for="pesan" class="sr-only">Pesan</label>
                    <textarea id="pesan" x-model="text" @keydown="keydown($event)" rows="1" maxlength="1000"
                        x-effect="text; $el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 152) + 'px'"
                        placeholder="Tulis pertanyaan soal makanan atau gizi" class="max-h-40 flex-1 resize-none bg-transparent py-2 text-[14px] placeholder:text-ink-3/75 focus:outline-none"></textarea>
                    <button type="submit" class="grid size-9 shrink-0 place-items-center rounded-[7px] bg-sun text-canvas transition-[background-color,transform] duration-140 ease-smooth hover:bg-sun-deep active:scale-95 disabled:opacity-25" :disabled="sending || !text.trim()" aria-label="Kirim">
                        <x-icon name="send" size="17" stroke="1.9" />
                    </button>
                </div>
                <div class="mx-auto mt-2 flex max-w-[40rem] flex-wrap items-baseline justify-between gap-x-3 gap-y-1 px-1 text-[11.5px] text-ink-3">
                    <span>Chatbot bukan dokter. Untuk keluhan kesehatan, tanyakan ke tenaga kesehatan.</span>
                    <span x-show="remainingChars < 100" x-cloak class="num" x-text="remainingChars + ' karakter tersisa'"></span>
                </div>
            </form>
        </section>
    </div>
</x-layouts.app>
