@php
    $change = $latest && $first && $latest->id !== $first->id ? round($latest->weight_kg - $first->weight_kg, 1) : null;
@endphp
<x-layouts.app title="Berat badan" :wide="true">
    <x-page-header title="Berat badan" :back="route('reports.index')">
        <x-slot:subtitle>Timbang di waktu yang sama, misalnya pagi setelah bangun, supaya angkanya sebanding.</x-slot:subtitle>
    </x-page-header>

    <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] lg:gap-10">
        <div class="space-y-7">
            <div class="grid grid-cols-3 gap-4 pb-2">
                <x-stat label="Sekarang" :value="gizi_num($latest?->weight_kg, 1)" unit="kg" />
                <x-stat label="Perubahan" :value="$change === null ? '–' : ($change > 0 ? '+' : '').gizi_num($change, 1)" :unit="$change === null ? null : 'kg'" :note="$first ? 'sejak '.$first->logged_on->translatedFormat('j M') : null" />
                <x-stat label="IMT" :value="gizi_num($bmi, 1)" :note="$bmiCategory ?? 'Tanpa label di bawah 18 tahun'" />
            </div>

            <section class="panel panel-pad" aria-labelledby="judul-grafik">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 id="judul-grafik" class="text-[13.5px] font-semibold">30 catatan terakhir</h2>
                    @if ($logs->count() > 1)
                        <span class="num text-[12.5px] text-ink-3">{{ gizi_num($logs->min('weight_kg'), 1) }}–{{ gizi_num($logs->max('weight_kg'), 1) }} kg</span>
                    @endif
                </div>
                <div class="mt-4">
                    @if ($logs->count() > 1)
                        <x-line-chart :labels="$chart['labels']->all()" :values="$chart['values']->all()" unit="kg" :height="248" color="sun" />
                    @else
                        <p class="grid h-[248px] place-items-center rounded-control border border-dashed border-ink/12 text-center text-[13.5px] text-ink-2">Grafik muncul setelah ada dua catatan atau lebih.</p>
                    @endif
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <form method="POST" action="{{ route('weight.store') }}" class="panel panel-pad space-y-4">
                @csrf
                <h2 class="text-[13.5px] font-semibold">Catat berat</h2>
                <div class="grid grid-cols-2 gap-3">
                    <x-field label="Berat" name="weight_kg" type="number" step="0.1" min="20" max="300" inputmode="decimal" suffix="kg" :value="old('weight_kg', $latest?->weight_kg)" required />
                    <x-field label="Tanggal" name="logged_on" type="date" :value="old('logged_on', today()->toDateString())" max="{{ today()->toDateString() }}" required />
                </div>
                <x-field label="Catatan (opsional)" name="note" :value="old('note')" maxlength="255" placeholder="Misalnya: setelah olahraga" />
                <button class="btn btn-primary w-full py-3">Simpan</button>
                <p class="text-[12px] leading-relaxed text-ink-3">Tanggal yang sama menimpa catatan lama di tanggal itu.</p>
            </form>

            <section class="panel overflow-hidden" aria-labelledby="judul-riwayat">
                <h2 id="judul-riwayat" class="px-5 pt-4 pb-2 text-[13.5px] font-semibold">Riwayat</h2>
                <ul class="max-h-96 overflow-y-auto px-5 pb-2">
                    @foreach ($logs as $log)
                        <li class="flex items-center gap-3 border-t border-line py-2">
                            <span class="flex-1 truncate text-[13px] text-ink-2">{{ $log->logged_on->translatedFormat('l, j M Y') }}</span>
                            <span class="num text-[13.5px] font-semibold">{{ gizi_num($log->weight_kg, 1) }} <span class="text-[11.5px] font-medium text-ink-3">kg</span></span>
                            <form method="POST" action="{{ route('weight.destroy', $log) }}">
                                @csrf @method('DELETE')
                                <button class="grid size-8 place-items-center rounded-[5px] text-ink-3 transition-colors duration-140 hover:bg-danger-tint hover:text-danger" aria-label="Hapus catatan {{ $log->logged_on->translatedFormat('j M') }}">
                                    <x-icon name="trash" size="15" />
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</x-layouts.app>
