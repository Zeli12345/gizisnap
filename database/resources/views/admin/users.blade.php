<x-layouts.app title="Pengguna" :wide="true">
    <x-page-header title="Pengguna">
        <x-slot:actions>
            <form method="GET" class="relative" role="search">
                <x-icon name="search" size="15" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-ink-3" />
                <input type="search" name="q" value="{{ $term }}" placeholder="Cari nama atau email" class="field field-sm w-56 pl-9" aria-label="Cari pengguna">
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="panel table-scroll">
        <table class="table min-w-[720px]">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Peran</th>
                    <th class="text-right">Catatan</th>
                    <th class="text-right">Scan</th>
                    <th class="text-right">Chat</th>
                    <th>Login terakhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <x-avatar :user="$u" size="sm" />
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ $u->name }}</p>
                                    <p class="truncate text-[12.5px] text-ink-3">{{ $u->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span @class([
                                    'inline-flex items-center rounded-[4px] px-1.5 py-0.5 text-[11.5px] font-semibold',
                                    'bg-pine-tint text-pine-deep' => $u->isAdmin(),
                                    'bg-sunk text-ink-2' => ! $u->isAdmin(),
                                ])>{{ $u->isAdmin() ? 'Admin' : 'Pengguna' }}</span>
                                @unless ($u->isOnboarded())
                                    <span class="inline-flex items-center rounded-[4px] bg-sun-tint px-1.5 py-0.5 text-[11.5px] font-semibold text-sun-ink">Belum isi profil</span>
                                @endunless
                            </div>
                        </td>
                        <td class="num text-right">{{ $u->meals_count }}</td>
                        <td class="num text-right">{{ $u->scans_count }}</td>
                        <td class="num text-right">{{ $u->chat_conversations_count }}</td>
                        <td class="text-[12.5px] text-ink-2">{{ $u->last_login_at?->diffForHumans() ?? 'Belum pernah' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-ink-2">Tidak ada pengguna yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links('partials.pagination') }}
</x-layouts.app>
