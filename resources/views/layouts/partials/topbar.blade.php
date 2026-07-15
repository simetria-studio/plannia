<header class="sticky top-0 z-20 flex h-16 items-center justify-end gap-4 border-b border-plannia-border bg-white px-8">
    <button type="button" class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100 transition">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        @if(($pendingCount ?? 0) > 0)
            <span class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">{{ $pendingCount }}</span>
        @endif
    </button>

    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open" class="flex items-center gap-3 rounded-lg py-1.5 pl-1.5 pr-3 hover:bg-gray-50 transition">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-plannia-blue text-sm font-semibold text-white">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div class="text-left hidden sm:block">
                <div class="text-sm font-semibold text-gray-800">{{ Auth::user()->name }}</div>
                <div class="text-xs text-gray-500">{{ Auth::user()->school?->name ?? 'Escola' }}</div>
            </div>
            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Perfil</a>
            @if(Auth::user()->isDirecao())
                <a href="{{ route('turmas.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Turmas</a>
                <a href="{{ route('users.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Usuários</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">Sair</button>
            </form>
        </div>
    </div>
</header>
