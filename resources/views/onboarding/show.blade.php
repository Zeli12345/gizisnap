@php
    $old = [
        'gender' => old('gender', ''),
        'birth_date' => old('birth_date', ''),
        'height_cm' => old('height_cm') ? (float) old('height_cm') : null,
        'weight_kg' => old('weight_kg') ? (float) old('weight_kg') : null,
        'activity_level' => old('activity_level', 'light'),
        'goal' => old('goal', 'maintain'),
        'allergies' => old('allergies', ''),
        'dietary_preference' => old('dietary_preference', ''),
    ];
    $errorStep = $errors->hasAny(['gender', 'birth_date', 'height_cm', 'weight_kg']) ? 1 : ($errors->has('activity_level') ? 2 : ($errors->any() ? 3 : 1));
    $config = [
        'old' => $old,
        'step' => $errorStep,
        'factors' => config('gizisnap.targets.activity_factors'),
        'adjustments' => config('gizisnap.targets.goal_adjustments'),
        'adultAge' => (int) config('gizisnap.targets.adult_age'),
    ];
    $choice = 'flex cursor-pointer rounded-control border border-line bg-paper transition-[border-color,background-color] duration-140 ease-smooth hover:border-line-strong has-checked:border-sun has-checked:bg-sun-tint has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-sun';
@endphp
<x-layouts.guest title="Isi profil">
    <div class="flex min-h-dvh flex-col">
        <header class="mx-auto flex w-full max-w-[42rem] items-center justify-between px-5 py-6">
            <x-logo />
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-ghost btn-sm">Keluar</button>
            </form>
        </header>

        <main class="mx-auto w-full max-w-[42rem] flex-1 px-5 pt-2 pb-16" x-data="onboarding(@js($config))">
            {{-- Kemajuan --}}
            <div class="flex items-center gap-4">
                <div class="flex flex-1 gap-1.5" aria-hidden="true">
                    <template x-for="i in 3" :key="i">
                        <span class="h-[3px] flex-1 rounded-full transition-colors duration-[220ms] ease-smooth" :class="i <= step ? 'bg-sun' : 'bg-ink/10'"></span>
                    </template>
                </div>
                <p class="num text-[12.5px] text-ink-3">Langkah <span x-text="step">1</span> / 3</p>
            </div>

            <form method="POST" action="{{ route('onboarding.store') }}" class="mt-12" novalidate @submit="if (step < 3) { $event.preventDefault(); next(); }">
                @csrf

                {{-- Langkah 1 --}}
                <section x-show="step === 1" x-transition:enter="transition duration-[420ms] ease-expo" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-ref="panel" tabindex="-1" class="outline-none">
                    <h1 class="font-display text-[34px] leading-[1.04] font-semibold tracking-[-0.025em] sm:text-[42px]">Halo, {{ auth()->user()->firstName() }}.</h1>
                    <p class="mt-3 text-[15px] leading-relaxed text-ink-2">Isi sedikit data tubuhmu. Dipakai untuk menghitung kebutuhan gizi harian, dan hanya kamu yang bisa melihatnya.</p>

                    <div class="mt-9 space-y-5">
                        <fieldset>
                            <legend class="label">Jenis kelamin</legend>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($genders as $gender)
                                    <label class="{{ $choice }} items-center justify-center px-4 py-3.5 text-[14.5px] font-semibold">
                                        <input type="radio" name="gender" value="{{ $gender->value }}" x-model="form.gender" class="sr-only">
                                        {{ $gender->label() }}
                                    </label>
                                @endforeach
                            </div>
                            @error('gender')<p class="error-text">{{ $message }}</p>@enderror
                        </fieldset>

                        <x-field label="Tanggal lahir" name="birth_date" type="date" x-model="form.birth_date" max="{{ now()->subYears(13)->toDateString() }}" required />

                        <div class="grid grid-cols-2 gap-3">
                            <x-field label="Tinggi badan" name="height_cm" type="number" inputmode="decimal" step="0.1" min="100" max="250" suffix="cm" x-model.number="form.height_cm" placeholder="165" required />
                            <x-field label="Berat badan" name="weight_kg" type="number" inputmode="decimal" step="0.1" min="20" max="300" suffix="kg" x-model.number="form.weight_kg" placeholder="55" required />
                        </div>
                    </div>
                </section>

                {{-- Langkah 2 --}}
                <section x-show="step === 2" x-cloak x-transition:enter="transition duration-[420ms] ease-expo" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-ref="panel" tabindex="-1" class="outline-none">
                    <h1 class="font-display text-[34px] leading-[1.04] font-semibold tracking-[-0.025em] sm:text-[42px]">Seberapa aktif kamu dalam seminggu?</h1>
                    <p class="mt-3 text-[15px] leading-relaxed text-ink-2">Pilih yang paling mendekati minggu biasa, bukan minggu terbaikmu.</p>

                    <fieldset class="mt-9 space-y-2">
                        <legend class="sr-only">Tingkat aktivitas</legend>
                        @foreach ($activityLevels as $level)
                            <label class="{{ $choice }} items-center justify-between gap-4 px-4 py-3.5">
                                <span>
                                    <span class="block text-[14.5px] font-semibold">{{ $level->label() }}</span>
                                    <span class="mt-0.5 block text-[13px] leading-relaxed text-ink-2">{{ $level->description() }}</span>
                                </span>
                                <input type="radio" name="activity_level" value="{{ $level->value }}" x-model="form.activity_level" class="size-[18px] shrink-0 accent-[#f0b04e]">
                            </label>
                        @endforeach
                        @error('activity_level')<p class="error-text">{{ $message }}</p>@enderror
                    </fieldset>
                </section>

                {{-- Langkah 3 --}}
                <section x-show="step === 3" x-cloak x-transition:enter="transition duration-[420ms] ease-expo" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-ref="panel" tabindex="-1" class="outline-none">
                    <h1 class="font-display text-[34px] leading-[1.04] font-semibold tracking-[-0.025em] sm:text-[42px]">Apa tujuanmu?</h1>
                    <p class="mt-3 text-[15px] leading-relaxed text-ink-2">Target kalori menyesuaikan pilihan ini. Kamu bisa mengubahnya kapan saja di profil.</p>

                    <fieldset class="mt-9 grid gap-2 sm:grid-cols-3">
                        <legend class="sr-only">Tujuan</legend>
                        @foreach ($goals as $goal)
                            <label class="{{ $choice }} items-center justify-center px-3 py-4 text-center text-[14.5px] font-semibold has-disabled:cursor-not-allowed has-disabled:opacity-40">
                                <input type="radio" name="goal" value="{{ $goal->value }}" x-model="form.goal" class="sr-only"
                                    @if ($goal->value === 'lose') :disabled="isMinor" @endif>
                                {{ $goal->label() }}
                            </label>
                        @endforeach
                    </fieldset>
                    @error('goal')<p class="error-text">{{ $message }}</p>@enderror
                    <p x-show="isMinor" x-cloak class="hint">Untuk usia di bawah 18 tahun, tubuh masih tumbuh, jadi target turun berat tidak ditawarkan.</p>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <x-field label="Alergi (opsional)" name="allergies" x-model="form.allergies" placeholder="kacang, udang" hint="Pisahkan dengan koma." />
                        <x-field label="Pantangan (opsional)" name="dietary_preference" x-model="form.dietary_preference" placeholder="vegetarian" />
                    </div>

                    <div class="relative mt-8 overflow-hidden panel rounded-stage px-6 py-6" x-show="estimate" x-transition:enter="transition duration-[600ms] ease-spring" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" aria-live="polite">
                        <div class="pointer-events-none absolute -top-16 -right-10 size-48 rounded-full bg-sun/14 blur-3xl" aria-hidden="true"></div>
                        <p class="relative text-[12.5px] font-semibold text-ink-3">Perkiraan target harianmu</p>
                        <p class="num relative mt-2 flex items-baseline gap-2">
                            <span class="figure text-[58px]" x-text="formatted(estimate)"></span>
                            <span class="text-[13px] font-semibold text-ink-3">kkal</span>
                        </p>
                    </div>
                    <x-disclaimer class="mt-5" />
                </section>

                <div class="mt-12 flex items-center justify-between gap-3">
                    <button type="button" class="btn btn-ghost" x-show="step > 1" x-cloak @click="back()">
                        <x-icon name="chevron-left" size="17" /> Kembali
                    </button>
                    <span x-show="step === 1" aria-hidden="true"></span>
                    <button type="button" class="btn btn-primary min-w-36 py-3" x-show="step < 3" :disabled="!canContinue()" @click="next()">
                        Lanjut <x-icon name="chevron-right" size="16" stroke="2" />
                    </button>
                    <button type="submit" class="btn btn-primary min-w-36 py-3" x-show="step === 3" x-cloak :disabled="!canContinue()">
                        Simpan dan hitung
                    </button>
                </div>
            </form>
        </main>
    </div>
</x-layouts.guest>
