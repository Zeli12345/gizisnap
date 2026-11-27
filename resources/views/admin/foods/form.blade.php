@php $editing = $food->exists; @endphp
<x-layouts.app :title="$editing ? 'Ubah makanan' : 'Tambah makanan'">
    <x-page-header :title="$editing ? 'Ubah '.$food->name : 'Tambah makanan'" :back="route('admin.foods.index')">
        <x-slot:subtitle>Ambil angka dari Tabel Komposisi Pangan Indonesia (TKPI) atau label kemasan, lalu tulis sumbernya.</x-slot:subtitle>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('admin.foods.update', $food) : route('admin.foods.store') }}" class="space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section aria-labelledby="judul-identitas">
            <h2 id="judul-identitas" class="mb-4 border-b border-line pb-2.5 text-[13.5px] font-semibold">Identitas makanan</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Nama" name="name" :value="old('name', $food->name)" required class="sm:col-span-2" />
                <div>
                    <x-field label="Kategori" name="category" :value="old('category', $food->category)" list="kategori" required />
                    <datalist id="kategori">
                        @foreach ($categories as $category)<option value="{{ $category }}">@endforeach
                    </datalist>
                </div>
                <x-field label="Sumber data" name="source" :value="old('source', $food->source)" required />
                <x-field label="Porsi" name="serving_desc" :value="old('serving_desc', $food->serving_desc)" placeholder="1 centong (100 g)" required />
                <x-field label="Berat porsi" name="serving_weight_g" type="number" step="0.1" suffix="g" :value="old('serving_weight_g', $food->serving_weight_g)" required />
            </div>
        </section>

        <section aria-labelledby="judul-gizi">
            <h2 id="judul-gizi" class="mb-4 border-b border-line pb-2.5 text-[13.5px] font-semibold">Gizi per porsi</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach ([['calories_kcal', 'Kalori', 'kkal', true], ['protein_g', 'Protein', 'g', true], ['carbs_g', 'Karbohidrat', 'g', true], ['fat_g', 'Lemak', 'g', true], ['fiber_g', 'Serat', 'g', false], ['sugar_g', 'Gula', 'g', false], ['sodium_mg', 'Natrium', 'mg', false]] as [$key, $label, $unit, $required])
                    <x-field :label="$label" :name="$key" type="number" step="0.1" min="0" :suffix="$unit" :value="old($key, $food->$key)" :required="$required" />
                @endforeach
            </div>
            <label class="mt-5 flex w-fit cursor-pointer items-center gap-2.5 text-[13.5px] font-semibold">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="size-[16px] rounded-[3px] accent-[#f0b04e]" @checked(old('is_active', $food->is_active))>
                Tampilkan di pencarian pengguna
            </label>
        </section>

        <div class="flex flex-col-reverse gap-2 border-t border-line pt-5 sm:flex-row sm:justify-between">
            @if ($editing)
                <button type="submit" form="hapus-makanan" class="btn btn-danger">Hapus</button>
            @else
                <span></span>
            @endif
            <button class="btn btn-primary px-6">{{ $editing ? 'Simpan perubahan' : 'Tambah makanan' }}</button>
        </div>
    </form>

    @if ($editing)
        <form id="hapus-makanan" method="POST" action="{{ route('admin.foods.destroy', $food) }}">@csrf @method('DELETE')</form>
    @endif
</x-layouts.app>
