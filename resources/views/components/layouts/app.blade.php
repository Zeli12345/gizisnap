@props(['title' => null, 'wide' => false])
@php
    $user = auth()->user();
    $isAdmin = $user?->isAdmin() ?? false;
    $admin = [
        ['admin.dashboard', 'admin.dashboard', 'Ringkasan', 'settings'],
        ['admin.users.index', 'admin.users.*', 'Pengguna', 'users'],
        ['admin.ai-usage', 'admin.ai-usage', 'Pemakaian AI', 'cpu'],
        ['admin.foods.index', 'admin.foods.*', 'Daftar makanan', 'database'],
    ];
    $adminNav = collect($admin)
        ->map(fn ($item) => ['route' => $item[0], 'match' => $item[1], 'label' => $item[2], 'icon' => $item[3]])
        ->all();
    $nav = $isAdmin ? $adminNav : [
        ['route' => 'dashboard', 'match' => ['dashboard'], 'label' => 'Hari ini', 'icon' => 'home'],
        ['route' => 'meals.index', 'match' => ['meals.*'], 'label' => 'Catatan', 'icon' => 'utensils'],
        ['route' => 'chat.index', 'match' => ['chat.*'], 'label' => 'Tanya gizi', 'icon' => 'chat'],
        ['route' => 'reports.index', 'match' => ['reports.*', 'weight.*'], 'label' => 'Laporan', 'icon' => 'chart'],
    ];
    $mobile = $isAdmin ? $adminNav : [
        ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Hari ini', 'icon' => 'home'],
        ['route' => 'meals.index', 'match' => 'meals.*', 'label' => 'Catatan', 'icon' => 'utensils'],
        ['route' => 'chat.index', 'match' => 'chat.*', 'label' => 'Tanya', 'icon' => 'chat'],
        ['route' => 'reports.index', 'match' => ['reports.*', 'weight.*'], 'label' => 'Laporan', 'icon' => 'chart'],
    ];
    $home = route($isAdmin ? 'admin.dashboard' : 'dashboard');
    $ring = $isAdmin ? null : ($dayRing ?? null);
    $circ = 2 * M_PI * 15;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="min-h-dvh">
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-control focus:bg-sun focus:px-4 focus:py-2 focus:font-semibold focus:text-canvas">Langsung ke konten</a>

    {{-- Sisi kiri, laptop. Tetap diam saat berpindah halaman; hanya penanda aktif yang meluncur. --}}
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-[256px] flex-col border-r border-ink/6 bg-[#0c0e0b]/80 backdrop-blur-xl lg:flex" style="view-transition-name: sidebar">
        <div class="px-6 pt-7 pb-6">
            <a href="{{ $home }}" aria-label="GiziSnap, ke {{ $isAdmin ? 'ringkasan admin' : 'halaman hari ini' }}" class="inline-flex rounded-control">
                <x-logo />
            </a>
        </div>

        @unless ($isAdmin)
            <div class="px-4">
                <a href="{{ route('scan.create') }}" class="btn btn-primary w-full py-2.5 text-[14px]">
                    <x-icon name="camera" size="17" stroke="2" /> Scan makanan
                </a>
            </div>
        @endunless

        <nav class="mt-7 flex-1 overflow-y-auto px-3" aria-label="{{ $isAdmin ? 'Menu admin' : 'Menu utama' }}">
            @if ($isAdmin)
                <p class="mb-2 px-3.5 text-[10.5px] font-semibold tracking-[0.08em] text-ink-3/70 uppercase">Panel admin</p>
            @endif
            <ul class="space-y-px">
                @foreach ($nav as $item)
                    @php $active = request()->routeIs($item['match']); @endphp
                    <li class="relative">
                        @if ($active)
                            <span class="absolute inset-0 rounded-[7px] bg-sunk" style="view-transition-name: nav-pill" aria-hidden="true"></span>
                            <span class="absolute top-2.5 bottom-2.5 left-0 w-[3px] rounded-r-full bg-sun" aria-hidden="true"></span>
                        @endif
                        <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                            @class([
                                'group relative flex items-center gap-3 rounded-[7px] px-3.5 py-2.5 text-[14px] transition-colors duration-140 ease-smooth',
                                'font-semibold text-ink' => $active,
                                'font-medium text-ink-3 hover:bg-sunk/50 hover:text-ink' => ! $active,
                            ])>
                            <x-icon :name="$item['icon']" size="17" :stroke="$active ? 2 : 1.7" :class="$active ? 'text-sun' : 'text-ink-3 transition-colors duration-140 group-hover:text-ink-2'" class="shrink-0" />
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- Cincin hari ini: ada di setiap halaman, jadi sisa kalori selalu terlihat --}}
        @if ($ring && $ring['goal'] > 0)
            <a href="{{ route('dashboard') }}" class="mx-3 mb-3 flex items-center gap-3.5 rounded-surface border border-ink/8 bg-paper/80 p-3.5 transition-colors duration-140 hover:border-ink/16 hover:bg-raise/70" aria-label="Kalori hari ini: {{ $ring['eaten'] }} dari {{ $ring['goal'] }}">
                <svg viewBox="0 0 36 36" class="size-[46px] shrink-0 -rotate-90" aria-hidden="true">
                    <circle cx="18" cy="18" r="15" fill="none" stroke="var(--color-ink)" stroke-opacity=".1" stroke-width="3.5" />
                    <circle cx="18" cy="18" r="15" fill="none" stroke="var(--color-{{ $ring['over'] ? 'danger' : 'sun' }})" stroke-width="3.5" stroke-linecap="round"
                        stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $circ * (1 - $ring['pct'] / 100) }}" style="transition: stroke-dashoffset 900ms var(--ease-expo)" />
                </svg>
                <span class="min-w-0">
                    <span class="block text-[10.5px] font-semibold tracking-[0.05em] text-ink-3 uppercase">{{ $ring['left'] >= 0 ? 'Sisa hari ini' : 'Lewat' }}</span>
                    <span class="num mt-0.5 block font-display text-[21px] leading-none font-semibold tracking-[-0.02em] {{ $ring['over'] ? 'text-danger' : '' }}">{{ gizi_num(abs($ring['left'])) }} <span class="font-sans text-[10.5px] font-semibold text-ink-3">kkal</span></span>
                </span>
            </a>
        @endif

        <div class="border-t border-ink/6 p-3">
            <a href="{{ route('profile.show') }}" @class(['flex items-center gap-3 rounded-[7px] p-2 transition-colors duration-140 ease-smooth hover:bg-sunk/60', 'bg-sunk/60' => request()->routeIs('profile.*')])>
                <x-avatar :user="$user" />
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[13px] font-semibold">{{ $user->name }}</span>
                    <span class="block truncate text-[11.5px] text-ink-3">{{ $isAdmin ? 'Akun admin' : 'Profil dan pengaturan' }}</span>
                </span>
                <x-icon name="chevron-right" size="14" class="shrink-0 text-ink-3" />
            </a>
        </div>
    </aside>

    {{-- Bilah atas, ponsel --}}
    <header class="sticky top-0 z-20 flex items-center justify-between border-b border-ink/8 bg-canvas/80 px-4 py-2.5 backdrop-blur-xl lg:hidden" style="view-transition-name: topbar">
        <a href="{{ $home }}" aria-label="GiziSnap, ke {{ $isAdmin ? 'ringkasan admin' : 'halaman hari ini' }}" class="rounded-control"><x-logo :size="29" /></a>
        <div class="flex items-center gap-2">
            @if ($ring && $ring['goal'] > 0)
                <a href="{{ route('dashboard') }}" class="num flex items-center gap-1.5 rounded-full border border-ink/10 bg-paper py-1 pr-3 pl-1.5 text-[12px] font-semibold" aria-label="Sisa kalori hari ini">
                    <svg viewBox="0 0 36 36" class="size-[22px] -rotate-90" aria-hidden="true">
                        <circle cx="18" cy="18" r="14" fill="none" stroke="var(--color-ink)" stroke-opacity=".12" stroke-width="5" />
                        <circle cx="18" cy="18" r="14" fill="none" stroke="var(--color-{{ $ring['over'] ? 'danger' : 'sun' }})" stroke-width="5" stroke-linecap="round"
                            stroke-dasharray="{{ 2 * M_PI * 14 }}" stroke-dashoffset="{{ 2 * M_PI * 14 * (1 - $ring['pct'] / 100) }}" />
                    </svg>
                    {{ gizi_num(abs($ring['left'])) }}
                </a>
            @endif
            <a href="{{ route('profile.show') }}" class="rounded-full p-0.5">
                <x-avatar :user="$user" size="sm" />
                <span class="sr-only">Akun</span>
            </a>
        </div>
    </header>

    <main id="konten" class="lg:pl-[256px]">
        <div @class([
            'mx-auto px-4 pt-6 pb-32 sm:px-7 lg:px-10 lg:pt-10 lg:pb-20',
            'max-w-[1080px]' => $wide,
            'max-w-[700px]' => ! $wide,
        ])>
            <x-flash class="mb-5" />
            {{ $slot }}
        </div>
    </main>

    {{-- Navigasi bawah, ponsel. Untuk admin hanya menu admin, tanpa tombol scan. --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-ink/8 bg-paper/85 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl lg:hidden" aria-label="{{ $isAdmin ? 'Menu admin' : 'Menu utama' }}" style="view-transition-name: bottomnav">
        <ul @class(['mx-auto grid max-w-md items-end px-1', 'grid-cols-4' => $isAdmin, 'grid-cols-5' => ! $isAdmin])>
            @foreach ($mobile as $item)
                @php $active = request()->routeIs($item['match']); @endphp
                <li>
                    <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                        @class(['relative flex min-h-[58px] flex-col items-center justify-center gap-1 text-[10.5px] font-semibold transition-colors duration-140', 'text-sun' => $active, 'text-ink-3' => ! $active])>
                        @if ($active)<span class="absolute top-0 h-[2.5px] w-7 rounded-b-full bg-sun" aria-hidden="true"></span>@endif
                        <x-icon :name="$item['icon']" size="20" :stroke="$active ? 2.1 : 1.7" />
                        {{ $item['label'] }}
                    </a>
                </li>
                @unless ($isAdmin)
                    @if ($loop->index === 1)
                        <li>
                            <a href="{{ route('scan.create') }}" class="group flex flex-col items-center gap-1" @if (request()->routeIs('scan.*')) aria-current="page" @endif>
                                <span class="grid size-[52px] -translate-y-3.5 place-items-center rounded-full bg-sun text-canvas shadow-[0_8px_24px_-4px_rgb(240_176_78/0.35),0_4px_12px_-2px_rgb(0_0_0/0.7)] ring-[5px] ring-paper transition-transform duration-[420ms] ease-spring active:scale-90">
                                    <x-icon name="camera" size="22" stroke="2" />
                                </span>
                                <span @class(['-mt-4 text-[10.5px] font-semibold', 'text-sun' => request()->routeIs('scan.*'), 'text-ink-2' => ! request()->routeIs('scan.*')])>Scan</span>
                            </a>
                        </li>
                    @endif
                @endunless
            @endforeach
        </ul>
    </nav>

    @stack('scripts')
</body>
</html>
