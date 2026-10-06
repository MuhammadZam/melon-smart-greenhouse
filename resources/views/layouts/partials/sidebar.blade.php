<!-- Sidebar Container -->
<aside
    class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 border-r border-slate-800 flex flex-col justify-between transition-transform duration-300 ease-in-out lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
>
    <div>
        <!-- Brand Header -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-slate-800/80 bg-slate-950/40">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center shadow-md shadow-emerald-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div>
                    <span class="text-sm font-bold bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent">Melon Monitor</span>
                    <span class="block text-[10px] text-slate-400 uppercase tracking-widest font-mono">Smart Agri</span>
                </div>
            </a>

            <!-- Close button for Mobile -->
            <button
                type="button"
                @click="sidebarOpen = false"
                class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                title="Tutup Menu"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation Menus -->
        <nav class="p-4 space-y-1 overflow-y-auto max-h-[calc(100vh-10rem)]">
            <!-- Section: Menu Utama -->
            <div class="px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                Menu Utama
            </div>

            <!-- Dashboard -->
            <a
                href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('dashboard') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}"
            >
                <svg class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-emerald-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
                <span>Dashboard</span>
            </a>

            <!-- Monitoring -->
            <div class="pt-2">
                <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Monitoring
                </div>
                <a
                    href="{{ route('dashboard') }}#recent-table"
                    class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800/60 transition-colors"
                >
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span>Data Monitoring</span>
                </a>
            </div>

            <!-- Tanaman -->
            <div class="pt-2">
                <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Tanaman
                </div>
                <a
                    href="{{ route('dashboard') }}#plant-summary"
                    class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800/60 transition-colors"
                >
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Data Tanaman</span>
                </a>
            </div>

            <!-- Grafik & Histori -->
            <div class="pt-2 space-y-1">
                <a
                    href="{{ route('dashboard') }}#charts"
                    class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800/60 transition-colors"
                >
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                    <span>Grafik</span>
                </a>

                <a
                    href="{{ route('dashboard') }}#recent-table"
                    class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800/60 transition-colors"
                >
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Histori</span>
                </a>
            </div>

            <!-- Menu Administrasi (HANYA UNTUK ROLE ADMIN) -->
            @if (auth()->check() && auth()->user()->isAdmin())
                <div class="pt-4 border-t border-slate-800/80 mt-3">
                    <div class="flex items-center justify-between px-3 py-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            Administrasi
                        </span>
                        <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold bg-emerald-500/20 text-emerald-300">ADMIN</span>
                    </div>

                    <a
                        href="{{ route('dashboard') }}#admin-section"
                        class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium text-slate-400 hover:text-emerald-300 hover:bg-slate-800/60 transition-colors"
                    >
                        <svg class="w-4 h-4 text-emerald-400/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span>Pengguna</span>
                    </a>

                    <a
                        href="{{ route('dashboard') }}#parameters-list"
                        class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium text-slate-400 hover:text-emerald-300 hover:bg-slate-800/60 transition-colors"
                    >
                        <svg class="w-4 h-4 text-emerald-400/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>Parameter</span>
                    </a>
                </div>
            @endif
        </nav>
    </div>

    <!-- Sidebar Bottom / User Status Widget -->
    <div class="p-4 border-t border-slate-800 bg-slate-950/60">
        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-slate-900 border border-slate-800">
            <div class="w-8 h-8 rounded-lg {{ auth()->user()->isAdmin() ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-teal-500/20 text-teal-300 border border-teal-500/30' }} flex items-center justify-center font-bold text-xs uppercase">
                {{ substr(auth()->user()->name, 0, 2) }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold text-white truncate">{{ auth()->user()->name }}</div>
                <div class="text-[10px] text-slate-400 capitalize">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Pengamat' }}</div>
            </div>
        </div>
    </div>
</aside>

<!-- Backdrop overlay for mobile -->
<div
    x-show="sidebarOpen"
    x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="sidebarOpen = false"
    class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
    style="display: none;"
></div>
