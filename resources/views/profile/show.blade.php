@php
    $manual = $target?->source === 'manual';
@endphp
<x-layouts.app title="Profil">
    {{-- Kepala profil --}}
    <header class="flex items-center gap-4 border-b border-line pb-6">
        <x-avatar :user="$user" size="lg" />
        <div class="min-w-0 flex-1">
            <h1 class="display truncate text-[26px] sm:text-[30px]">{{ $user->name }}</h1>
            <p class="truncate text-[13.5px] text-ink-2">{{ $user->email }}</p>
        </div>
        @if ($user->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="btn btn-quiet btn-sm shrink-0">
                <x-icon name="settings" size="14" /> Admin
            </a>
        @endif
    </header>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-3 gap-4 border-b border-line py-5">
        <x-stat label="Target" :value="gizi_num($target?->calories_kcal)" unit="kkal" />
        <x-stat label="IMT" :value="gizi_num($bmi, 1)" :note="$bmiCategory" />
        <x-stat label="Usia" :value="$profile->age()" unit="tahun" />
    </div>

    <div class="mt-8 space-y-9">

        {{-- Data diri --}}
        <form method="POST" action="{{ route('profile.update') }}" aria-labelledby="judul-data">
            @csrf @method('PUT')
            <div class="mb-5 flex items-start justify-between gap-4 border-b border-ink/8 pb-3">
                <div>
                    <h2 id="judul-data" class="title text-[19px]">Data diri</h2>
                    <p class="mt-1 text-[12.5px] leading-relaxed text-ink-2">{{ $manual ? 'Targetmu diatur manual, jadi tidak berubah saat data diri diubah.' : 'Target harian dihitung ulang otomatis saat data ini berubah.' }}</p>
                </div>
                <button class="btn btn-primary btn-sm mt-0.5 shrink-0">Simpan</button>
            </div>

            <div class="space-y-4">
                <x-field label="Nama" name="name" :value="old('name', $user->name)" maxlength="100" required />

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="f-gender" class="label">Jenis kelamin</label>
                        <select id="f-gender" name="gender" class="field">
                            @foreach ($genders as $gender)
                                <option value="{{ $gender->value }}" @selected(old('gender', $profile->gender->value) === $gender->value)>{{ $gender->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-field label="Tanggal lahir" name="birth_date" type="date" :value="old('birth_date', $profile->birth_date->toDateString())" required />
                    <x-field label="Tinggi badan" name="height_cm" type="number" step="0.1" inputmode="decimal" suffix="cm" :value="old('height_cm', $profile->height_cm)" required />
                    <x-field label="Berat badan" name="weight_kg" type="number" step="0.1" inputmode="decimal" suffix="kg" :value="old('weight_kg', $profile->weight_kg)" required />
                    <div>
                        <label for="f-activity" class="label">Tingkat aktivitas</label>
                        <select id="f-activity" name="activity_level" class="field">
                            @foreach ($activityLevels as $level)
                                <option value="{{ $level->value }}" @selected(old('activity_level', $profile->activity_level->value) === $level->value)>{{ $level->label() }}, {{ mb_strtolower($level->description()) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="f-goal" class="label">Tujuan</label>
                        <select id="f-goal" name="goal" class="field">
                            @foreach ($goals as $goal)
                                <option value="{{ $goal->value }}" @selected(old('goal', $profile->goal->value) === $goal->value) @disabled($goal->value === 'lose' && $profile->isMinor())>{{ $goal->label() }}</option>
                            @endforeach
                        </select>
                        @error('goal')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <x-field label="Alergi" name="allergies" :value="old('allergies', implode(', ', $profile->allergies ?? []))" placeholder="kacang, udang" hint="Pisahkan dengan koma." />
                    <x-field label="Pantangan" name="dietary_preference" :value="old('dietary_preference', $profile->dietary_preference)" placeholder="vegetarian" />
                </div>
            </div>
        </form>

        {{-- Target harian --}}
        <section aria-labelledby="judul-target" x-data="{ editing: {{ $errors->hasAny(['calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'water_ml']) ? 'true' : 'false' }} }">
            <div class="mb-5 flex items-start justify-between gap-4 border-b border-ink/8 pb-3">
                <div class="min-w-0">
                    <h2 id="judul-target" class="title text-[19px]">Target harian</h2>
                    <p class="mt-1 max-w-lg text-[12.5px] leading-relaxed text-ink-2">
                        {{ $manual ? 'Diatur manual.' : 'Dihitung otomatis dari data diri.' }}
                        Saran rumus: {{ gizi_num($suggested['calories_kcal']) }} kkal (energi istirahat {{ gizi_num($suggested['resting_kcal']) }} kkal).
                    </p>
                </div>
                <button type="button" class="btn btn-quiet btn-sm mt-0.5 shrink-0" x-show="!editing" @click="editing = true"><x-icon name="edit" size="14" /> Ubah</button>
            </div>

            <dl class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-5" x-show="!editing">
                @foreach ([['Kalori', $target?->calories_kcal, 'kkal', 'sun'], ['Protein', $target?->protein_g, 'g', 'protein'], ['Karbohidrat', $target?->carbs_g, 'g', 'carbs'], ['Lemak', $target?->fat_g, 'g', 'fat'], ['Air', $target?->water_ml, 'ml', 'water']] as [$label, $value, $unit, $color])
                    <div class="border-t border-line pt-2.5">
                        <dt class="flex items-center gap-1.5 text-[12px] text-ink-3">
                            <span class="size-1.5 rounded-full" style="background: var(--color-{{ $color }})"></span>{{ $label }}
                        </dt>
                        <dd class="num mt-1 text-[16px] font-semibold">{{ gizi_num($value) }} <span class="text-[11.5px] font-medium text-ink-3">{{ $unit }}</span></dd>
                    </div>
                @endforeach
            </dl>

            <form method="POST" action="{{ route('profile.targets') }}" x-show="editing" x-cloak x-collapse class="space-y-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <x-field label="Kalori" name="calories_kcal" type="number" suffix="kkal" :value="old('calories_kcal', $target?->calories_kcal)" />
                    <x-field label="Protein" name="protein_g" type="number" step="0.1" suffix="g" :value="old('protein_g', $target?->protein_g)" />
                    <x-field label="Karbohidrat" name="carbs_g" type="number" step="0.1" suffix="g" :value="old('carbs_g', $target?->carbs_g)" />
                    <x-field label="Lemak" name="fat_g" type="number" step="0.1" suffix="g" :value="old('fat_g', $target?->fat_g)" />
                    <x-field label="Air" name="water_ml" type="number" suffix="ml" :value="old('water_ml', $target?->water_ml)" />
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" class="btn btn-ghost" @click="editing = false">Batal</button>
                    <button class="btn btn-primary">Simpan target</button>
                </div>
            </form>

            @if ($manual)
                <form method="POST" action="{{ route('profile.targets.reset') }}" class="mt-3" x-show="!editing">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost btn-sm -ml-2"><x-icon name="refresh" size="14" /> Kembalikan ke hitungan otomatis</button>
                </form>
            @endif
        </section>

        {{-- Halaman lain --}}
        <nav class="grid gap-2 sm:grid-cols-3" aria-label="Halaman lain">
            <a href="{{ route('reports.index') }}" class="panel-tap flex items-center justify-between gap-3 rounded-surface border border-line bg-paper px-4 py-3.5">
                <span class="flex items-center gap-2.5 text-[13.5px] font-semibold"><x-icon name="chart" size="17" class="text-sun" /> Laporan</span>
                <x-icon name="chevron-right" size="15" class="text-ink-3" />
            </a>
            <a href="{{ route('weight.index') }}" class="panel-tap flex items-center justify-between gap-3 rounded-surface border border-line bg-paper px-4 py-3.5">
                <span class="flex items-center gap-2.5 text-[13.5px] font-semibold"><x-icon name="scale" size="17" class="text-sun" /> Berat badan</span>
                <x-icon name="chevron-right" size="15" class="text-ink-3" />
            </a>
            <a href="{{ route('privacy') }}" class="panel-tap flex items-center justify-between gap-3 rounded-surface border border-line bg-paper px-4 py-3.5">
                <span class="flex items-center gap-2.5 text-[13.5px] font-semibold"><x-icon name="shield" size="17" class="text-sun" /> Privasi</span>
                <x-icon name="chevron-right" size="15" class="text-ink-3" />
            </a>
        </nav>

        {{-- Akun --}}
        <section aria-labelledby="judul-akun">
            <h2 id="judul-akun" class="title mb-4 border-b border-line pb-3 text-[19px]">Akun</h2>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-[13.5px] text-ink-2">Masuk sebagai {{ $user->email }}.</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-quiet btn-sm"><x-icon name="logout" size="14" /> Keluar</button>
                </form>
            </div>

            <div class="mt-6 rounded-surface border border-danger/20 bg-danger-tint/40 p-4" x-data="{ open: {{ $errors->has('confirmation') ? 'true' : 'false' }} }">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[13.5px] font-semibold text-danger">Hapus akun</p>
                        <p class="mt-0.5 text-[13px] text-ink-2">Semua catatan, foto, dan percakapan ikut terhapus permanen.</p>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm shrink-0" x-show="!open" @click="open = true">Hapus akun</button>
                </div>
                <form method="POST" action="{{ route('profile.destroy') }}" x-show="open" x-cloak x-collapse class="mt-4 space-y-3">
                    @csrf @method('DELETE')
                    <x-field label="Ketik HAPUS untuk mengonfirmasi" name="confirmation" autocomplete="off" />
                    <div class="flex gap-2">
                        <button class="btn btn-danger-solid">Hapus permanen</button>
                        <button type="button" class="btn btn-ghost" @click="open = false">Batal</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-layouts.app>
