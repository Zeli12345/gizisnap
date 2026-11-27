@php
    $mode = $mode ?? 'login';
    $isLogin = $mode === 'login';
@endphp
<x-auth-shell
    :heading="$isLogin ? 'Masuk ke GiziSnap' : 'Buat akun GiziSnap'"
    :subtitle="$isLogin ? 'Lanjutkan catatan makanmu.' : 'Gratis, dan target harianmu dihitung dari tubuhmu sendiri.'"
    :switcher="$isLogin ? ['url' => route('register'), 'label' => 'Buat akun'] : ['url' => route('login'), 'label' => 'Masuk']">

    <a href="{{ route('auth.google.redirect') }}" class="btn btn-quiet w-full py-3">
        <x-icon name="google" size="17" /> {{ $isLogin ? 'Masuk' : 'Daftar' }} dengan Google
    </a>

    <div class="my-6 flex items-center gap-3" role="presentation">
        <hr class="h-px flex-1 border-0 bg-line">
        <span class="text-[12.5px] text-ink-3">atau pakai email</span>
        <hr class="h-px flex-1 border-0 bg-line">
    </div>

    <form method="POST" action="{{ $isLogin ? route('login.store') : route('register.store') }}" class="space-y-4">
        @csrf

        @if (! $isLogin)
            <div>
                <label for="name" class="label">Nama</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                    autocomplete="name" placeholder="Nama panggilanmu"
                    @error('name') aria-invalid="true" aria-describedby="name-error" @enderror class="field">
                @error('name')<p id="name-error" class="error-text">{{ $message }}</p>@enderror
            </div>
        @endif

        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                @if ($isLogin) autofocus @endif
                autocomplete="email" inputmode="email" placeholder="nama@email.com"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror class="field">
            @error('email')<p id="email-error" class="error-text">{{ $message }}</p>@enderror
        </div>

        <div>
            <div class="mb-1.5 flex items-baseline justify-between gap-2">
                <label for="password" class="label mb-0">Kata sandi</label>
                @if ($isLogin)
                    <a href="{{ route('password.request') }}" class="text-[13px] font-medium text-ink-2 underline decoration-line-strong underline-offset-[3px] transition-colors duration-140 hover:text-ink hover:decoration-ink">Lupa sandi?</a>
                @endif
            </div>
            <div x-data="{ show: false }" class="relative">
                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required
                    autocomplete="{{ $isLogin ? 'current-password' : 'new-password' }}"
                    @unless ($isLogin) aria-describedby="password-hint" @endunless
                    @error('password') aria-invalid="true" @enderror class="field pr-12">
                <button type="button" @click="show = !show"
                    class="absolute inset-y-1 right-1 grid w-10 place-items-center rounded-[6px] text-ink-3 transition-colors duration-140 hover:bg-sunk hover:text-ink"
                    :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" aria-label="Tampilkan kata sandi">
                    <x-icon name="eye" size="17" x-show="!show" />
                    <x-icon name="eye-off" size="17" x-show="show" x-cloak />
                </button>
            </div>
            @error('password')
                <p class="error-text">{{ $message }}</p>
            @else
                @unless ($isLogin)<p id="password-hint" class="hint">Minimal 8 karakter.</p>@endunless
            @enderror
        </div>

        @if (! $isLogin)
            <div>
                <label for="password_confirmation" class="label">Ulangi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    autocomplete="new-password" class="field">
            </div>
        @else
            <label class="flex w-fit cursor-pointer items-center gap-2.5">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                    class="size-[16px] rounded-[3px] accent-[#f0b04e]">
                <span class="text-[13.5px] text-ink-2">Ingat saya di perangkat ini</span>
            </label>
        @endif

        <button type="submit" class="btn btn-primary w-full py-3 text-[15px]">
            {{ $isLogin ? 'Masuk' : 'Buat akun' }}
        </button>
    </form>

    <x-slot:after>
        <p class="text-[14px] text-ink-2">
            {{ $isLogin ? 'Belum punya akun?' : 'Sudah punya akun?' }}
            <a href="{{ $isLogin ? route('register') : route('login') }}" class="link">{{ $isLogin ? 'Buat akun' : 'Masuk' }}</a>
        </p>
    </x-slot:after>
</x-auth-shell>
