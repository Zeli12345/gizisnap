<x-auth-shell
    heading="Kata sandi baru"
    subtitle="Buat kata sandi baru untuk akunmu.">

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required
                autocomplete="email" inputmode="email"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror class="field">
            @error('email')<p id="email-error" class="error-text">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="label">Kata sandi baru</label>
            <input id="password" name="password" type="password" required autofocus
                autocomplete="new-password" aria-describedby="password-hint"
                @error('password') aria-invalid="true" @enderror class="field">
            @error('password')
                <p class="error-text">{{ $message }}</p>
            @else
                <p id="password-hint" class="hint">Minimal 8 karakter.</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="label">Ulangi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                autocomplete="new-password" class="field">
        </div>

        <button type="submit" class="btn btn-primary w-full py-3 text-[15px]">Simpan kata sandi</button>
    </form>
</x-auth-shell>
