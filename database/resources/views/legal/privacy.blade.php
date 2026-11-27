<x-layouts.guest title="Kebijakan privasi">
    <div class="mx-auto max-w-[42rem] px-5 py-8 sm:px-8 sm:py-12">
        <header class="flex items-center justify-between gap-4">
            <a href="{{ url('/') }}" class="rounded-control"><x-logo /></a>
            <a href="{{ route('landing') }}" class="text-[13px] text-ink-2 underline decoration-line-strong underline-offset-[3px] transition-colors duration-140 hover:text-ink hover:decoration-ink">Halaman depan</a>
        </header>

        <main class="mt-14">
            <h1 class="display">Kebijakan privasi</h1>
            <p class="mt-3 max-w-[34rem] text-[15.5px] leading-relaxed text-ink-2">Ringkas dan apa adanya: data apa yang kami simpan dan untuk apa.</p>

            <div class="mt-10 space-y-8">
                @foreach ([
                    ['Yang kami simpan', 'Nama, email, dan foto profil dari akun Google (tanpa kata sandi atau token akses). Profil kesehatan: jenis kelamin, tanggal lahir, tinggi, berat, aktivitas, tujuan, alergi. Foto makanan, catatan makan, minum, berat badan, dan percakapan chatbot.'],
                    ['Yang dikirim ke pihak lain', 'Foto makanan dan pesan chat dikirim ke penyedia AI untuk dianalisis. Bersama pesan chat kami sertakan ringkasan profil dan catatan makan hari ini agar jawabannya sesuai. Email tidak ikut dikirim.'],
                    ['Yang tidak kami kumpulkan', 'Alamat, nomor telepon, dan lokasi. Admin hanya melihat angka statistik, bukan isi chat atau foto pengguna.'],
                    ['Menghapus data', 'Di halaman Profil ada tombol hapus akun. Semua catatan dan foto Anda ikut terhapus permanen.'],
                ] as $i => [$title, $text])
                    <section class="border-t border-line pt-5">
                        <div class="flex items-baseline gap-3">
                            <span class="num font-display text-[15px] font-semibold text-sun" aria-hidden="true">0{{ $i + 1 }}</span>
                            <h2 class="text-[18px] font-semibold">{{ $title }}</h2>
                        </div>
                        <p class="mt-2 max-w-[62ch] pl-7 text-[14.5px] leading-relaxed text-ink-2">{{ $text }}</p>
                    </section>
                @endforeach
            </div>
        </main>
    </div>
</x-layouts.guest>
