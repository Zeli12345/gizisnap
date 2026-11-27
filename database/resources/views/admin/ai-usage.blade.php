@php $features = ['scan' => 'Scan foto', 'chat' => 'Chat', 'insight' => 'Ringkasan']; @endphp
<x-layouts.app title="Pemakaian AI" :wide="true">
    <x-page-header title="Pemakaian AI">
        <x-slot:subtitle>Jumlah panggilan dan token. Biaya sebenarnya dilihat di dashboard OpenAI Platform.</x-slot:subtitle>
        <x-slot:actions>
            <nav class="seg" aria-label="Periode">
                <a href="{{ route('admin.ai-usage', ['days' => 7]) }}" @if ($days === 7) aria-current="page" @endif>7 hari</a>
                <a href="{{ route('admin.ai-usage', ['days' => 30]) }}" @if ($days === 30) aria-current="page" @endif>30 hari</a>
            </nav>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-x-6 gap-y-5 pb-2 sm:grid-cols-3">
        <x-stat label="Total panggilan" :value="gizi_num($totals['calls'])" />
        <x-stat label="Token masuk" :value="gizi_num($totals['input_tokens'])" />
        <x-stat label="Token keluar" :value="gizi_num($totals['output_tokens'])" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <section class="panel table-scroll" aria-labelledby="judul-fitur">
            <h2 id="judul-fitur" class="px-5 pt-4 pb-2 text-[13.5px] font-semibold">Per fitur</h2>
            <table class="table min-w-[520px]">
                <thead>
                    <tr>
                        <th>Fitur</th>
                        <th class="text-right">Panggilan</th>
                        <th class="text-right">Error</th>
                        <th class="text-right">Token</th>
                        <th class="text-right">Rata-rata</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byFeature as $feature => $row)
                        <tr>
                            <td class="font-semibold">{{ $features[$feature] ?? $feature }}</td>
                            <td class="num text-right">{{ gizi_num($row['calls']) }}</td>
                            <td @class(['num text-right', 'font-semibold text-danger' => $row['errors'] > 0])>{{ gizi_num($row['errors']) }}</td>
                            <td class="num text-right">{{ gizi_num($row['input_tokens'] + $row['output_tokens']) }}</td>
                            <td class="num text-right">{{ gizi_num($row['avg_ms'] / 1000, 1) }} dtk</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-ink-2">Belum ada panggilan di periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="panel panel-pad" aria-labelledby="judul-jenis">
            <h2 id="judul-jenis" class="text-[13.5px] font-semibold">Jenis error</h2>
            <dl class="mt-3">
                @forelse ($errorCodes as $code => $count)
                    <div class="flex items-baseline justify-between gap-3 border-t border-line py-2 text-[13.5px]">
                        <dt class="font-medium">{{ $code ?: 'lainnya' }}</dt>
                        <dd class="num font-semibold text-danger">{{ gizi_num($count) }}</dd>
                    </div>
                @empty
                    <p class="text-[13.5px] text-ink-2">Tidak ada error di periode ini.</p>
                @endforelse
            </dl>
        </section>
    </div>

    <section class="panel table-scroll mt-6" aria-labelledby="judul-hari">
        <h2 id="judul-hari" class="px-5 pt-4 pb-2 text-[13.5px] font-semibold">Per hari</h2>
        <table class="table min-w-[520px]">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="text-right">Scan</th>
                    <th class="text-right">Chat</th>
                    <th class="text-right">Error</th>
                    <th class="text-right">Token</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($byDay->reverse() as $row)
                    <tr>
                        <td class="text-ink-2">{{ $row['date']->translatedFormat('l, j M') }}</td>
                        <td class="num text-right">{{ $row['scan'] }}</td>
                        <td class="num text-right">{{ $row['chat'] }}</td>
                        <td @class(['num text-right', 'font-semibold text-danger' => $row['errors'] > 0])>{{ $row['errors'] }}</td>
                        <td class="num text-right">{{ gizi_num($row['tokens']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</x-layouts.app>
