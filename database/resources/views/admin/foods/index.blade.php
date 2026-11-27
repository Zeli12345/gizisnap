<x-layouts.app title="Daftar makanan" :wide="true">
    <x-page-header title="Daftar makanan">
        <x-slot:subtitle>Dipakai saat pengguna mencatat tanpa foto. Angka berlaku untuk satu porsi.</x-slot:subtitle>
        <x-slot:actions>
            <form method="GET" class="relative" role="search">
                <x-icon name="search" size="15" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-ink-3" />
                <input type="search" name="q" value="{{ $term }}" placeholder="Cari makanan" class="field field-sm w-48 pl-9" aria-label="Cari makanan">
            </form>
            <a href="{{ route('admin.foods.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" size="15" stroke="2" /> Tambah</a>
        </x-slot:actions>
    </x-page-header>

    <div class="panel table-scroll">
        <table class="table min-w-[820px]">
            <thead>
                <tr>
                    <th>Makanan</th>
                    <th>Kategori</th>
                    <th class="text-right">Kalori</th>
                    <th class="text-right"><span class="inline-flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-protein"></span>Protein</span></th>
                    <th class="text-right"><span class="inline-flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-carbs"></span>Karbo</span></th>
                    <th class="text-right"><span class="inline-flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-fat"></span>Lemak</span></th>
                    <th><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($foods as $food)
                    <tr @class(['text-ink-3' => ! $food->is_active])>
                        <td>
                            <p class="font-semibold">{{ $food->name }}@unless ($food->is_active) <span class="ml-1 bg-sunk px-1.5 py-0.5 text-[10.5px] font-semibold">Disembunyikan</span>@endunless</p>
                            <p class="text-[12.5px] text-ink-3">{{ $food->serving_desc }}</p>
                        </td>
                        <td class="text-ink-2">{{ $food->category }}</td>
                        <td class="num text-right font-semibold">{{ gizi_num($food->calories_kcal) }}</td>
                        <td class="num text-right">{{ gizi_num($food->protein_g, 1) }} g</td>
                        <td class="num text-right">{{ gizi_num($food->carbs_g, 1) }} g</td>
                        <td class="num text-right">{{ gizi_num($food->fat_g, 1) }} g</td>
                        <td class="text-right"><a href="{{ route('admin.foods.edit', $food) }}" class="font-semibold text-sun underline decoration-sun/30 underline-offset-[3px] hover:decoration-sun">Ubah</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-ink-2">Tidak ada makanan yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $foods->links('partials.pagination') }}
</x-layouts.app>
