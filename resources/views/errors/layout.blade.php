{{--
    Halaman kesalahan: satu kalimat jelas, satu jalan keluar.
    Nomornya besar dan setengah tenggelam, karena itu satu-satunya hal yang perlu dilihat.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="grid min-h-dvh place-items-center overflow-hidden px-6 py-12">
    <main class="rise relative w-full max-w-[34rem]">
        <p class="num font-display pointer-events-none absolute top-16 -left-3 text-[190px] leading-none font-semibold tracking-[-0.05em] text-ink/[0.05] select-none sm:text-[250px]" aria-hidden="true">{{ $code }}</p>
        <div class="relative">
            <x-logo />
            <p class="num mt-28 text-[12px] font-semibold tracking-[0.08em] text-sun">KODE {{ $code }}</p>
            <h1 class="display mt-3 text-[34px] sm:text-[42px]">{{ $title }}</h1>
            <p class="mt-3 max-w-[28rem] text-[15px] leading-relaxed text-ink-2">{{ $message }}</p>
            <div class="mt-8 flex flex-wrap gap-2">
                <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="btn btn-primary">
                    {{ auth()->check() ? 'Kembali ke hari ini' : 'Kembali ke beranda' }}
                </a>
                @auth
                    <a href="{{ route('meals.index') }}" class="btn btn-quiet">Catatan makan</a>
                @endauth
            </div>
        </div>
    </main>
</body>
</html>
