<x-layouts.app title="Admin" :wide="true">
    <x-page-header title="Ringkasan admin">
        <x-slot:subtitle>Angka 7 hari terakhir. Admin tidak bisa melihat isi chat atau foto pengguna.</x-slot:subtitle>
    </x-page-header>

    @if ($aiUnavailable)
        <div class="mb-6 flex gap-2.5 rounded-control border border-danger/20 bg-danger-tint px-4 py-3 text-danger" role="alert">
            <x-icon name="alert" size="17" class="mt-0.5 shrink-0" />
            <p class="text-[13.5px] leading-relaxed">Panggilan AI terakhir gagal karena kunci API salah atau saldo habis. Periksa <span class="font-semibold">OPENAI_API_KEY</span> dan saldo di OpenAI Platform.</p>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-x-6 gap-y-5 pb-2 sm:grid-cols-3">
        <x-stat label="Total pengguna" :value="gizi_num($stats['users'])" />
        <x-stat label="Aktif minggu ini" :value="gizi_num($stats['active_week'])" />
        <x-stat label="Catatan makan" :value="gizi_num($stats['meals_week'])" note="7 hari terakhir" />
        <x-stat label="Scan foto" :value="gizi_num($stats['scans_week'])" note="7 hari terakhir" color="pine" />
        <x-stat label="Error AI" :value="gizi_num($stats['ai_errors_week'])" note="7 hari terakhir" color="danger" />
        <x-stat label="Daftar makanan" :value="gizi_num($stats['foods'])" note="item" />
    </div>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <section class="panel panel-pad" aria-labelledby="judul-panggilan">
            <div class="flex items-baseline justify-between gap-3">
                <h2 id="judul-panggilan" class="text-[13.5px] font-semibold">Panggilan AI per hari</h2>
                <a href="{{ route('admin.ai-usage') }}" class="text-[12.5px] font-semibold text-sun underline decoration-sun/30 underline-offset-[3px] hover:decoration-sun">Rincian</a>
            </div>
            <div class="mt-5">
                @if ($stats['scans_week'] + $stats['ai_errors_week'] > 0 || collect($chart['chat'])->sum() > 0)
                    <x-stack-chart :labels="$chart['labels']->all()" :height="200" :series="[
                        ['name' => 'Scan foto', 'color' => 'sun', 'values' => $chart['scan']->all()],
                        ['name' => 'Chat', 'color' => 'protein', 'values' => $chart['chat']->all()],
                    ]" />
                @else
                    <div class="grid h-[240px] place-items-center rounded-control border border-dashed border-ink/10 text-center">
                        <div>
                            <p class="text-[13px] font-medium text-ink-2">Belum ada panggilan AI</p>
                            <p class="mt-1 text-[12px] text-ink-3">Grafik muncul setelah ada scan atau chat.</p>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="panel overflow-hidden" aria-labelledby="judul-error">
            <h2 id="judul-error" class="px-5 pt-4 pb-2 text-[13.5px] font-semibold">Error terakhir</h2>
            <div class="px-5 pb-2">
                @forelse ($recentErrors as $log)
                    <div class="border-t border-line py-3">
                        <p class="flex items-baseline justify-between gap-2">
                            <span class="text-[13.5px] font-semibold">{{ $log->feature }}, {{ $log->error_code ?? 'error' }}</span>
                            <span class="num shrink-0 text-[11.5px] text-ink-3">{{ $log->created_at->diffForHumans() }}</span>
                        </p>
                        <p class="mt-0.5 text-[12.5px] text-ink-2">{{ $log->user?->name ?? 'Pengguna dihapus' }} · {{ $log->model }}</p>
                    </div>
                @empty
                    <p class="py-4 text-[13.5px] text-ink-2">Tidak ada error dalam 7 hari terakhir.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
