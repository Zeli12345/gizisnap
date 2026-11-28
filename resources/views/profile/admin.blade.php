<x-layouts.app title="Akun admin">
    <x-page-header title="Akun admin" :back="route('admin.dashboard')">
        <x-slot:subtitle>Akun ini hanya mengelola panel admin. Data kesehatan pengguna tidak diperlukan di sini.</x-slot:subtitle>
    </x-page-header>

    <header class="flex items-center gap-4 border-b border-line pb-6">
        <x-avatar :user="$user" size="lg" />
        <div class="min-w-0 flex-1">
            <h2 class="display truncate text-[26px] sm:text-[30px]">{{ $user->name }}</h2>
            <p class="truncate text-[13.5px] text-ink-2">{{ $user->email }}</p>
        </div>
        <span class="rounded-full border border-sun/25 bg-sun-tint px-3 py-1 text-[11.5px] font-semibold text-sun-ink">Admin</span>
    </header>

    <section class="mt-8" aria-labelledby="judul-akun-admin">
        <h2 id="judul-akun-admin" class="title mb-4 border-b border-line pb-3 text-[19px]">Akun</h2>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-[13.5px] text-ink-2">Masuk sebagai {{ $user->email }}.</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-quiet btn-sm"><x-icon name="logout" size="14" /> Keluar</button>
            </form>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="panel-tap mt-6 flex items-center justify-between gap-3 rounded-surface border border-line bg-paper px-4 py-3.5">
            <span class="flex items-center gap-2.5 text-[13.5px] font-semibold"><x-icon name="settings" size="17" class="text-sun" /> Kembali ke panel admin</span>
            <x-icon name="chevron-right" size="15" class="text-ink-3" />
        </a>
    </section>
</x-layouts.app>
