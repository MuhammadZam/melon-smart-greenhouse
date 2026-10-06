<header class="sticky top-0 z-30 h-16 bg-slate-900/80 backdrop-blur-md border-b border-slate-800">
    <div class="h-full px-4 sm:px-6 lg:px-8 flex items-center justify-between">
        
        <!-- Sisi Kiri: Hamburger Mobile & Nama Aplikasi -->
        <div class="flex items-center gap-3">
            <!-- Mobile Menu Toggle Button -->
            <button
                type="button"
                @click="sidebarOpen = !sidebarOpen"
                class="lg:hidden p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none transition-colors"
                title="Buka Menu Navigasi"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>

            <!-- App Title & Breadcrumb -->
            <div>
                <h1 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                    <span>Sistem Monitoring Melon</span>
                    <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        Live IoT Ready
                    </span>
                </h1>
                <p class="hidden sm:block text-[11px] text-slate-400">Dashboard Pemantauan Tanaman &amp; Mikroklimat</p>
            </div>
        </div>

        <!-- Sisi Kanan: Profile & Logout -->
        <div class="flex items-center gap-3 sm:gap-4">
            <!-- User Status Chip -->
            <div class="flex items-center gap-2.5 pl-2 sm:pl-3 pr-2 py-1 rounded-xl bg-slate-800/60 border border-slate-700/60">
                <div class="w-8 h-8 rounded-lg {{ auth()->user()->isAdmin() ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-teal-500/20 text-teal-300 border border-teal-500/30' }} flex items-center justify-center text-xs font-bold uppercase shadow-inner">
                    {{ substr(auth()->user()->name, 0, 2) }}
                </div>
                <div class="hidden sm:block text-left">
                    <div class="text-xs font-semibold text-white leading-tight">{{ auth()->user()->name }}</div>
                    <div class="text-[10px] text-slate-400 leading-tight flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full {{ auth()->user()->isAdmin() ? 'bg-emerald-400' : 'bg-teal-400' }}"></span>
                        <span class="capitalize">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Pengamat' }}</span>
                    </div>
                </div>
            </div>

            <!-- Tombol Logout (Menggunakan Route Phase 3) -->
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button
                    type="submit"
                    class="px-3 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 border border-rose-500/25 text-xs font-semibold flex items-center gap-1.5 transition-all shadow-sm"
                    title="Keluar dari sistem"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="hidden sm:inline">Logout</span>
                </button>
            </form>
        </div>

    </div>
</header>
