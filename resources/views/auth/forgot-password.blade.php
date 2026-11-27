<x-auth-shell
    heading="Lupa kata sandi"
    subtitle="Masukkan emailmu. Kami kirim tautan untuk membuat kata sandi baru.">

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                autocomplete="email" inputmode="email" placeholder="nama@email.com"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror class="field">
            @error('email')<p id="email-error" class="error-text">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn btn-primary w-full py-3 text-[15px]">Kirim tautan</button>
    </form>

    <x-slot:after>
        <p class="text-[14px] text-ink-2">
            Ingat kata sandimu? <a href="{{ route('login') }}" class="link">Masuk</a>
        </p>
    </x-slot:after>
</x-auth-shell>
