<div x-cloak :class="sidebarOpen ? 'block' : 'hidden'" @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-violet-400/30 backdrop-blur-sm transition-opacity lg:hidden"></div>

<aside x-cloak :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'" class="app-sidebar fixed inset-y-0 left-0 z-30 w-72 overflow-y-auto transition duration-300 transform lg:translate-x-0 lg:static lg:inset-0">
    <div class="sidebar-brand">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-500/15 text-violet-200">
            <svg class="h-7 w-7" viewBox="0 0 32 32"><path fill="currentColor" d="M5 6.5A3.5 3.5 0 0 1 8.5 3h15A3.5 3.5 0 0 1 27 6.5V24a2 2 0 0 1-2 2H7.085A1.5 1.5 0 0 0 8.5 27H26a1 1 0 1 1 0 2H8.5A3.5 3.5 0 0 1 5 25.5z"/></svg>
        </div>
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-white">VisitX</p>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a class="sidebar-link @if($url == 'home') sidebar-link-active @endif" href="/">
            <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M10 20v-6h4v6h5v-8h3L12 3L2 12h3v8z"/></svg>
            Accueil
        </a>
        @php $profile = (int) Auth::guard('web')->user()->profile; @endphp
        @if(in_array($profile, [1, 2, 3], true))
            <a class="sidebar-link @if($url == 'guest') sidebar-link-active @endif" href="{{ route('i_visitors') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3s-3 1.34-3 3s1.34 3 3 3M8 11c1.66 0 3-1.34 3-3S9.66 5 8 5S5 6.34 5 8s1.34 3 3 3m0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13"/></svg>
                Visiteurs siege
            </a>
            <a class="sidebar-link @if($url == 'guest_ant') sidebar-link-active @endif" href="{{ route('i_visitors_ant') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M15 11V5.83c0-.53-.21-1.04-.59-1.41L12.7 2.71a.996.996 0 0 0-1.41 0l-1.7 1.7C9.21 4.79 9 5.3 9 5.83V7H5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-6c0-1.1-.9-2-2-2z"/></svg>
                Visiteurs antennes
            </a>
            <a class="sidebar-link @if($url == 'history') sidebar-link-active @endif" href="{{ route('i_history') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12h2a8 8 0 1 0 1.865-5.135L8 9H2V3l2.447 2.446A9.98 9.98 0 0 1 12 2zm1 5v4.585l3.243 3.243l-1.415 1.415L11 12.413V7h2z"/></svg>
                Historique
            </a>
            <a class="sidebar-link @if(request()->routeIs('users.index')) sidebar-link-active @endif" href="{{ route('users.index') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M12 12q-1.65 0-2.825-1.175T8 8t1.175-2.825T12 4t2.825 1.175T16 8t-1.175 2.825T12 12m-8 8v-2.8q0-.85.438-1.562T5.6 14.55q1.55-.775 3.15-1.162T12 13t3.25.388t3.15 1.162q.725.375 1.163 1.088T20 17.2V20z"/></svg>
                Utilisateurs
            </a>
            <a class="sidebar-link @if($url == 'trash') sidebar-link-active @endif" href="{{ route('i_trash') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M6 7h12v13H6zm2 2v9h8V9zm1-5h6l1 2H8zM4 7h16v2H4z"/></svg>
                Corbeille
            </a>
        @elseif(in_array($profile, [4, 9, 10], true))
            <a class="sidebar-link @if($url == 'guest') sidebar-link-active @endif" href="{{ route('i_visitors') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3s-3 1.34-3 3s1.34 3 3 3M8 11c1.66 0 3-1.34 3-3S9.66 5 8 5S5 6.34 5 8s1.34 3 3 3m0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13"/></svg>
                Visiteurs{{ $profile === 10 ? ' DFC' : '' }}
            </a>
        @else
            <a class="sidebar-link @if($url == 'guest_ant') sidebar-link-active @endif" href="{{ route('i_visitors_ant') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M15 11V5.83c0-.53-.21-1.04-.59-1.41L12.7 2.71a.996.996 0 0 0-1.41 0l-1.7 1.7C9.21 4.79 9 5.3 9 5.83V7H5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-6c0-1.1-.9-2-2-2z"/></svg>
                Visiteurs antennes
            </a>
            <a class="sidebar-link @if($url == 'history') sidebar-link-active @endif" href="{{ route('i_history') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12h2a8 8 0 1 0 1.865-5.135L8 9H2V3l2.447 2.446A9.98 9.98 0 0 1 12 2zm1 5v4.585l3.243 3.243l-1.415 1.415L11 12.413V7h2z"/></svg>
                Historique
            </a>
            <a class="sidebar-link @if($url == 'history_ant') sidebar-link-active @endif" href="{{ route('i_ant_history') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M13 3a9 9 0 1 1-9 9H1l4-4l4 4H6a7 7 0 1 0 7-7zm-1 4h2v5l4 2l-1 1.73l-5-3z"/></svg>
                Historique antennes
            </a>
        @endif
    </nav>

 
</aside>

