@php
    $editing = $meal !== null;
    $oldItems = old('items');
    $editorConfig = [
        'items' => $oldItems ? array_values($oldItems) : $defaults['items'],
        'searchUrl' => route('foods.search'),
        'startWithBlank' => false,
    ];
@endphp
<x-layouts.app :title="$editing ? 'Ubah catatan' : 'Catat makanan'">
    <x-page-header :title="$editing ? 'Ubah catatan' : 'Catat makanan'" :back="url()->previous() !== url()->current() ? url()->previous() : route('dashboard')">
        <x-slot:subtitle>{{ $editing ? 'Ubah porsi, angka, atau jamnya. Total dihitung ulang saat disimpan.' : 'Cari dari daftar makanan Indonesia, atau tambahkan sendiri.' }}</x-slot:subtitle>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('meals.update', $meal) : route('meals.store') }}" x-data="mealEditor(@js($editorConfig))" class="space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section class="panel panel-pad" aria-labelledby="judul-makanan">
            <h2 id="judul-makanan" class="mb-4 text-[13.5px] font-semibold">Makanan</h2>
            @include('partials.meal-items-editor', ['withSearch' => true])
        </section>

        <section class="panel panel-pad" aria-labelledby="judul-detail">
            <h2 id="judul-detail" class="mb-4 text-[13.5px] font-semibold">Kapan dan apa namanya</h2>
            @include('partials.meal-meta-fields', ['defaults' => $defaults])
        </section>

        <div @class([
            'flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:items-center',
            'sm:justify-between' => $editing,
            'sm:justify-end' => ! $editing,
        ])>
            @if ($editing)
                <div class="flex flex-wrap gap-2" x-data="{ confirming: false }">
                    <button type="button" class="btn btn-danger" x-show="!confirming" @click="confirming = true">
                        <x-icon name="trash" size="16" /> Hapus
                    </button>
                    <span x-show="confirming" x-cloak class="flex items-center gap-2">
                        <button type="submit" form="hapus-catatan" class="btn btn-danger-solid">Ya, hapus</button>
                        <button type="button" class="btn btn-ghost" @click="confirming = false">Batal</button>
                    </span>
                    <button type="submit" form="ulangi-catatan" class="btn btn-ghost">
                        <x-icon name="repeat" size="16" /> Catat lagi hari ini
                    </button>
                </div>
            @endif
            <button type="submit" class="btn btn-primary px-6 py-3 text-[15px]" :disabled="items.length === 0">
                <x-icon name="check" size="17" stroke="2.2" /> {{ $editing ? 'Simpan perubahan' : 'Simpan catatan' }}
            </button>
        </div>
    </form>

    @if ($editing)
        <form id="hapus-catatan" method="POST" action="{{ route('meals.destroy', $meal) }}">
            @csrf @method('DELETE')
        </form>
        <form id="ulangi-catatan" method="POST" action="{{ route('meals.repeat', $meal) }}">
            @csrf
        </form>
    @endif
</x-layouts.app>
