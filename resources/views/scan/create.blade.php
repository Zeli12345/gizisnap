{{--
    Halaman scan. Seluruhnya satu penampil bidik: sudut kunyit, garis pindai, dan tahap
    analisis yang berganti. Tidak ada yang bersaing dengan tombol analisis.
--}}
<x-layouts.app title="Scan makanan" :wide="true">
    <x-page-header title="Scan makanan">
        <x-slot:subtitle>Foto dari atas dengan cahaya cukup. AI mengenali tiap makanan, lalu kamu yang memastikan porsinya.</x-slot:subtitle>
    </x-page-header>

    <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1.45fr)_minmax(0,1fr)] lg:gap-12">
        <div>
            <form method="POST" action="{{ route('scan.store') }}" enctype="multipart/form-data"
                x-data="scanUpload({ maxKb: {{ config('gizisnap.scan.max_upload_kb') }} })" @submit="submit($event)">
                @csrf
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="sr-only" x-ref="input" @change="pick($event)" id="foto-galeri">
                <input type="file" accept="image/*" capture="environment" class="sr-only" x-ref="camera" @change="fromCamera($event)" id="foto-kamera">

                <div class="relative aspect-[4/3] overflow-hidden rounded-stage border border-ink/10 bg-paper"
                    :class="dragging && 'border-sun/60'"
                    @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)">

                    {{-- Kosong: bidik menunggu --}}
                    <label for="foto-galeri" x-show="!preview"
                        class="absolute inset-0 flex cursor-pointer flex-col items-center justify-center gap-5 px-6 text-center transition-colors duration-[240ms] ease-smooth"
                        :class="dragging ? 'bg-sun-tint' : 'hover:bg-sunk/50'">
                        {{-- Bingkai menyempit saat foto diseret ke atasnya --}}
                        <span class="pointer-events-none absolute transition-[inset] duration-[420ms] ease-spring" :class="dragging ? 'inset-[38px]' : 'inset-[22px]'" aria-hidden="true">
                            <span class="corner corner-tl left-0 top-0"></span>
                            <span class="corner corner-tr right-0 top-0"></span>
                            <span class="corner corner-bl bottom-0 left-0"></span>
                            <span class="corner corner-br right-0 bottom-0"></span>
                        </span>

                        <span class="relative grid size-[72px] place-items-center rounded-full bg-sun text-canvas shadow-[0_12px_32px_-8px_rgb(240_176_78/0.45)]">
                            <span class="ping-ring absolute inset-0 rounded-full border border-sun/50" aria-hidden="true"></span>
                            <x-icon name="camera" size="28" stroke="1.8" />
                        </span>
                        <span class="max-w-[20rem]">
                            <span class="font-display block text-[24px] leading-tight font-semibold tracking-[-0.015em]" x-text="dragging ? 'Lepaskan di sini' : 'Pilih atau ambil foto'">Pilih atau ambil foto</span>
                            <span class="mt-2 block text-[13.5px] leading-relaxed text-ink-2">Seluruh piring masuk bingkai. JPG, PNG, atau WebP, paling besar 5 MB.</span>
                        </span>
                    </label>

                    {{-- Foto terpilih --}}
                    <template x-if="preview">
                        <img :src="preview" alt="Pratinjau foto makanan" class="absolute inset-0 size-full object-cover">
                    </template>
                    <div x-show="preview" x-cloak class="pointer-events-none absolute inset-4" aria-hidden="true">
                        <span class="corner corner-tl left-0 top-0"></span>
                        <span class="corner corner-tr right-0 top-0"></span>
                        <span class="corner corner-bl bottom-0 left-0"></span>
                        <span class="corner corner-br right-0 bottom-0"></span>
                    </div>
                    <div x-show="preview && !submitting" x-cloak class="absolute inset-x-12 bottom-5 flex items-center gap-3 rounded-[10px] border border-ink/12 bg-canvas/85 py-2 pr-2 pl-3.5 backdrop-blur-md">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-semibold" x-text="fileName"></span>
                            <span class="num block text-[11.5px] text-ink-3"><span x-text="fileSize"></span> · <span class="text-pine">siap dianalisis</span></span>
                        </span>
                        <button type="button" @click="clear()" class="btn btn-ghost grid size-8 shrink-0 place-items-center rounded-full p-0" aria-label="Hapus foto">
                            <x-icon name="x" size="16" />
                        </button>
                    </div>

                    {{-- Menganalisis --}}
                    <div x-show="submitting" x-cloak class="absolute inset-0 bg-canvas/55">
                        <div class="scan-loop absolute inset-0"><div class="scan-band" style="height: 26%"></div></div>
                        <div class="absolute inset-x-12 bottom-5 flex items-center gap-3.5 rounded-[12px] border border-ink/12 bg-canvas/90 px-4 py-3 backdrop-blur-md" role="status">
                            <span class="relative flex size-2.5 shrink-0">
                                <span class="ping-ring absolute inset-0 rounded-full bg-sun/60"></span>
                                <span class="relative size-2.5 rounded-full bg-sun"></span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[13.5px] font-semibold" x-text="stage + '…'">Mengenali tiap lauk…</span>
                                <span class="mt-0.5 block text-[11.5px] text-ink-3">Biasanya 5 sampai 15 detik.</span>
                            </span>
                            <span class="num text-[11.5px] font-semibold text-ink-3"><span x-text="stageIndex + 1"></span>/4</span>
                        </div>
                    </div>
                </div>

                <p x-show="error" x-text="error" x-cloak class="error-text" role="alert"></p>
                @error('photo')<p class="error-text">{{ $message }}</p>@enderror

                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-[1fr_1fr_1.5fr]">
                    <label for="foto-kamera" class="btn btn-quiet cursor-pointer py-3" :class="submitting && 'pointer-events-none opacity-40'">
                        <x-icon name="camera" size="16" /> Kamera
                    </label>
                    <label for="foto-galeri" class="btn btn-quiet cursor-pointer py-3" :class="submitting && 'pointer-events-none opacity-40'">
                        <x-icon name="image" size="16" /> Galeri
                    </label>
                    <button type="submit" class="btn btn-primary col-span-2 py-3 sm:col-span-1" :disabled="submitting">
                        <span x-show="!submitting">Analisis foto</span>
                        <span x-show="submitting" x-cloak>Menganalisis…</span>
                    </button>
                </div>
            </form>

            @if ($recentScans->isNotEmpty())
                <section class="mt-9" aria-labelledby="judul-terakhir">
                    <div class="flex items-baseline justify-between gap-3 border-b border-ink/8 pb-2.5">
                        <h2 id="judul-terakhir" class="text-[13.5px] font-semibold">Scan terakhir</h2>
                        <a href="{{ route('meals.index') }}" class="text-[12.5px] font-medium text-ink-2 underline decoration-line-strong underline-offset-[3px] hover:text-ink">Semua catatan</a>
                    </div>
                    <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                        @foreach ($recentScans as $recent)
                            <a href="{{ route('scan.show', $recent) }}" class="group relative size-[88px] shrink-0 overflow-hidden rounded-[10px] border border-ink/10 bg-sunk transition-[transform,border-color] duration-[420ms] ease-spring hover:-translate-y-0.5 hover:border-sun/50" title="{{ $recent->dish_name ?? $recent->status->label() }}">
                                <img src="{{ route('scan.photo', $recent) }}" alt="{{ $recent->dish_name ?? 'Foto scan' }}" class="size-full object-cover transition-transform duration-[600ms] ease-expo group-hover:scale-[1.08]" loading="lazy">
                                @if ($recent->status->value !== 'completed')
                                    <span class="absolute inset-x-1 bottom-1 rounded-[3px] bg-canvas/90 px-1 py-0.5 text-center text-[10.5px] font-semibold">{{ $recent->status->label() }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-8 lg:pt-1">
            <section aria-labelledby="judul-tips">
                <h2 id="judul-tips" class="text-[13.5px] font-semibold">Supaya hasilnya akurat</h2>
                <ol class="mt-3.5 space-y-3.5">
                    @foreach (['Cahaya terang, hindari bayangan di atas piring.', 'Foto dari atas, seluruh piring masuk bingkai.', 'Satu piring per foto. Minuman boleh ikut.', 'Cek porsinya sebelum menyimpan, terutama nasi dan minyak.'] as $i => $tip)
                        <li class="flex gap-3.5 text-[14px] leading-relaxed text-ink-2">
                            <span class="num mt-0.5 grid size-5 shrink-0 place-items-center rounded-full border border-ink/12 text-[10.5px] font-semibold text-sun">{{ $i + 1 }}</span>
                            {{ $tip }}
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="border-t border-ink/8 pt-6" aria-labelledby="judul-tanpa-foto">
                <h2 id="judul-tanpa-foto" class="text-[13.5px] font-semibold">Lebih suka tanpa foto?</h2>
                <p class="mt-1.5 text-[14px] leading-relaxed text-ink-2">Pilih langsung dari daftar makanan Indonesia, lengkap dengan porsinya.</p>
                <a href="{{ route('meals.create') }}" class="btn btn-quiet btn-sm mt-3">
                    <x-icon name="list" size="14" /> Catat dari daftar
                </a>
            </section>

            <x-disclaimer class="border-t border-ink/8 pt-6" />
        </aside>
    </div>
</x-layouts.app>
