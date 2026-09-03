<div x-cloak :class="sidebarOpen ? 'block' : 'hidden'" @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-slate-400/40 backdrop-blur-sm transition-opacity lg:hidden"></div>
    
<aside x-cloak :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'" class="app-sidebar fixed inset-y-0 left-0 z-30 w-72 overflow-y-auto transition duration-300 transform lg:translate-x-0 lg:static lg:inset-0">
    <div class="sidebar-brand">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-500/15 text-indigo-200">
            <svg class="h-7 w-7" viewBox="0 0 32 32"><path fill="currentColor" d="M5 6.5A3.5 3.5 0 0 1 8.5 3h15A3.5 3.5 0 0 1 27 6.5V24a2 2 0 0 1-2 2H7.085A1.5 1.5 0 0 0 8.5 27H26a1 1 0 1 1 0 2H8.5A3.5 3.5 0 0 1 5 25.5v-19Zm16 10a1.5 1.5 0 0 0-1.5-1.5h-7a1.5 1.5 0 0 0-1.5 1.5v.5c0 1.971 1.86 4 5 4c3.14 0 5-2.029 5-4v-.5Zm-2.25-5.25a2.75 2.75 0 1 0-5.5 0a2.75 2.75 0 0 0 5.5 0Z"/></svg>
        </div>
        <div><p class="text-xs font-black uppercase tracking-widest text-white">Supervisor Console</p><p class="text-[9px] font-extrabold uppercase tracking-wider text-indigo-400">Supervision</p></div>
    </div>
    <div class="console-status">
        <div class="console-status-title">Superviseur</div>
        <p class="console-status-text">Lecture et suivi operationnel du registre.</p>
    </div>

    <nav class="sidebar-nav">
        <a class="sidebar-link @if($url == 'home') sidebar-link-active @endif" href="/">
            <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M6 21q-.825 0-1.412-.587Q4 19.825 4 19v-9q0-.475.213-.9q.212-.425.587-.7l6-4.5q.275-.2.575-.3q.3-.1.625-.1t.625.1q.3.1.575.3l6 4.5q.375.275.588.7q.212.425.212.9v9q0 .825-.587 1.413Q18.825 21 18 21h-4v-7h-4v7Z"/></svg>
            Accueil
        </a>


    </nav>
</aside>
