<header class="topbar">
    <button @click="sidebarOpen = true" class="topbar-menu lg:hidden" aria-label="Menu">
        <svg class="h-6 w-6" viewBox="0 0 24 24"><path fill="currentColor" d="M4 6h16v2H4zm0 5h16v2H4zm0 5h10v2H4z"/></svg>
    </button>

  

    <div class="ml-auto hidden items-center gap-3 xl:flex">
        <div class="visitx-search">
            <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M9.5 3a6.5 6.5 0 0 1 5.176 10.435l4.445 4.444l-1.414 1.414l-4.444-4.445A6.5 6.5 0 1 1 9.5 3m0 2a4.5 4.5 0 1 0 0 9a4.5 4.5 0 0 0 0-9"/></svg>
            <input type="text" placeholder="Rechercher un visiteur, badge..." />
        </div>

        <div class="hidden items-center gap-2 rounded-full border border-[var(--visitx-border)] bg-[var(--visitx-panel)] px-3 py-2 text-sm font-medium text-[var(--visitx-muted)] shadow-sm 2xl:flex">
            <svg class="h-4 w-4 text-[var(--visitx-primary)]" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7m0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5"/></svg>
            Siege ANAM
        </div>

        <div x-data="{ dropdownOpen: false }" class="relative">
            <button @click="dropdownOpen = ! dropdownOpen" class="visitx-avatar-circle">
                {{ strtoupper(substr(Auth::guard('web')->user()->lastname ?? 'U', 0, 1)) }}
            </button>
            <div x-cloak x-show="dropdownOpen" @click="dropdownOpen = false" class="fixed inset-0 z-10"></div>
            <div x-cloak x-show="dropdownOpen" class="absolute right-0 z-20 mt-3 w-64 overflow-hidden rounded-2xl border border-[var(--visitx-border)] bg-white shadow-xl">
                <div class="border-b border-[var(--visitx-border)] px-4 py-3">
                    <p class="text-sm font-semibold text-[var(--visitx-secondary)]">{{ Auth::guard('web')->user()->name }}</p>
                    <p class="text-xs text-[var(--visitx-muted)]">{{ Auth::guard('web')->user()->email }}</p>
                </div>
                <form action="{{ route('p_logout') }}" method="post">
                    @csrf
                    <button class="block w-full px-4 py-3 text-left text-sm font-medium text-rose-600 hover:bg-rose-50" type="submit">Se deconnecter</button>
                </form>
            </div>
        </div>
    </div>
</header>
