<!-- Sidebar Container -->
<aside
    x-cloak
    id="main-navigation"
    aria-label="Navigasi utama"
    :inert="!desktopNav && !sidebarOpen"
    x-focus-scope="sidebarOpen && !desktopNav"
    class="fixed inset-y-0 left-0 z-50 w-64 bg-canvas border-r border-line flex flex-col justify-between transition-transform duration-150 ease-in-out lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
>
    <div>
        <!-- Brand Header -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-line bg-canvas">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-[2px]    flex items-center justify-center  ">
                    <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div>
                    <span class="text-sm font-semibold     text-ink">Melon Monitor</span>
                    <span class="block text-xs text-muted   tabular-nums">Smart Agri</span>
                </div>
            </a>

            <!-- Close button for Mobile -->
            <button
                type="button"
                @click="sidebarOpen = false"
                class="lg:hidden p-1.5 rounded-[2px] text-muted hover:text-ink hover:bg-surface transition-colors"
                title="Tutup Menu"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation Menus -->
        <nav aria-label="Menu aplikasi" class="p-4 space-y-1 overflow-y-auto max-h-[calc(100dvh-10rem)]">
            <!-- Section: Menu Utama -->
            <div class="px-3 py-2 text-xs font-semibold   text-muted">
                Menu Utama
            </div>

            <!-- Dashboard -->
            <a
                href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[2px] text-xs font-semibold transition-all {{ request()->routeIs('dashboard') ? 'bg-accent-soft text-accent border border-line ' : 'text-muted hover:text-ink hover:bg-surface' }}"
            >
                <svg class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-accent' : 'text-muted' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
                <span>Dashboard</span>
            </a>

            <!-- Monitoring -->
            <div class="pt-2">
                <div class="px-3 py-1.5 text-xs font-semibold   text-muted">
                    Monitoring
                </div>
                <a
                    href="{{ route('monitorings.index') }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-[2px] text-xs font-semibold transition-all {{ request()->routeIs('monitorings.*') ? 'bg-accent-soft text-accent border border-line ' : 'text-muted hover:text-ink hover:bg-surface' }}"
                >
                    <svg class="w-4 h-4 {{ request()->routeIs('monitorings.*') ? 'text-accent' : 'text-muted' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span>Data Monitoring</span>
                </a>

                <a
                    href="{{ route('reports.monitoring.index') }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-[2px] text-xs font-semibold transition-all {{ request()->routeIs('reports.monitoring.*') ? 'bg-accent-soft text-accent border border-line ' : 'text-muted hover:text-ink hover:bg-surface' }}"
                >
                    <svg class="w-4 h-4 {{ request()->routeIs('reports.monitoring.*') ? 'text-accent' : 'text-muted' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Laporan Monitoring</span>
                </a>

                <a
                    href="{{ route('alarms.index') }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-[2px] text-xs font-semibold transition-all {{ request()->routeIs('alarms.index') || request()->routeIs('alarms.show') ? 'bg-accent-soft text-accent border border-line ' : 'text-muted hover:text-ink hover:bg-surface' }}"
                >
                    <svg class="w-4 h-4 {{ request()->routeIs('alarms.index') || request()->routeIs('alarms.show') ? 'text-accent' : 'text-muted' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span>Alarm &amp; Notifikasi</span>
                </a>

                <a
                    href="{{ route('fuzzy.history') }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-[2px] text-xs font-semibold transition-all {{ request()->routeIs('fuzzy.*') ? 'bg-accent-soft text-accent border border-line ' : 'text-muted hover:text-ink hover:bg-surface' }}"
                >
                    <svg class="w-4 h-4 {{ request()->routeIs('fuzzy.*') ? 'text-accent' : 'text-muted' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Analisis Fuzzy</span>
                </a>
            </div>

            <!-- Tanaman -->
            <div class="pt-2">
                <div class="px-3 py-1.5 text-xs font-semibold   text-muted">
                    Tanaman
                </div>
                <a
                    href="{{ route('plants.index') }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-[2px] text-xs font-semibold transition-all {{ request()->routeIs('plants.*') ? 'bg-accent-soft text-accent border border-line ' : 'text-muted hover:text-ink hover:bg-surface' }}"
                >
                    <svg class="w-4 h-4 {{ request()->routeIs('plants.*') ? 'text-accent' : 'text-muted' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Data Tanaman</span>
                </a>
            </div>

            <!-- Perangkat (Simulasi Phase 13) -->
            <div class="pt-2">
                <div class="px-3 py-1.5 text-xs font-semibold   text-muted">
                    Perangkat (Simulasi)
                </div>
                <a
                    href="{{ route('actuators.index') }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-[2px] text-xs font-semibold transition-all {{ request()->routeIs('actuators.index') ? 'bg-accent-soft text-accent border border-line ' : 'text-muted hover:text-ink hover:bg-surface' }}"
                >
                    <svg class="w-4 h-4 {{ request()->routeIs('actuators.index') ? 'text-accent' : 'text-muted' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span>Kontrol Perangkat</span>
                    <span class="ml-auto px-1.5 py-0.5 rounded-[2px] text-xs tabular-nums font-semibold bg-warning-soft text-warning border border-warning/25">SIM</span>
                </a>

                <a
                    href="{{ route('actuators.history') }}"
                    class="flex items-center gap-3 px-3.5 py-2 rounded-[2px] text-xs font-medium transition-all {{ request()->routeIs('actuators.history') ? 'bg-accent-soft text-accent border border-line ' : 'text-muted hover:text-ink hover:bg-surface' }}"
                >
                    <svg class="w-4 h-4 {{ request()->routeIs('actuators.history') ? 'text-accent' : 'text-muted' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <span>Riwayat Kontrol</span>
                </a>
            </div>

            <!-- Grafik & Histori -->
            <div class="pt-2 space-y-1">
                <a
                    href="{{ route('dashboard') }}#charts"
                    class="flex items-center gap-3 px-3.5 py-2 rounded-[2px] text-xs font-medium text-muted hover:text-ink hover:bg-surface transition-colors"
                >
                    <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                    <span>Grafik</span>
                </a>

                <a
                    href="{{ route('dashboard') }}#recent-table"
                    class="flex items-center gap-3 px-3.5 py-2 rounded-[2px] text-xs font-medium text-muted hover:text-ink hover:bg-surface transition-colors"
                >
                    <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Histori</span>
                </a>
            </div>

            <!-- Menu Administrasi (HANYA UNTUK ROLE ADMIN) -->
            @if (auth()->check() && auth()->user()->isAdmin())
                <div class="pt-4 border-t border-line mt-3">
                    <div class="flex items-center justify-between px-3 py-1.5">
                        <span class="text-xs font-semibold   text-accent flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-[2px] bg-accent-soft"></span>
                            Administrasi
                        </span>
                        <span class="px-1.5 py-0.2 rounded-[2px] text-xs tabular-nums font-semibold bg-accent-soft text-accent">ADMIN</span>
                    </div>

                    <a
                        href="{{ route('dashboard') }}#admin-section"
                        class="flex items-center gap-3 px-3.5 py-2 rounded-[2px] text-xs font-medium text-muted hover:text-accent hover:bg-surface transition-colors"
                    >
                        <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span>Pengguna</span>
                    </a>

                    <a
                        href="{{ route('dashboard') }}#parameters-list"
                        class="flex items-center gap-3 px-3.5 py-2 rounded-[2px] text-xs font-medium text-muted hover:text-accent hover:bg-surface transition-colors"
                    >
                        <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>Parameter</span>
                    </a>

                    <a
                        href="{{ route('alarms.thresholds') }}"
                        class="flex items-center gap-3 px-3.5 py-2 rounded-[2px] text-xs font-medium transition-colors {{ request()->routeIs('alarms.thresholds') ? 'bg-accent-soft text-accent border border-line ' : 'text-muted hover:text-accent hover:bg-surface' }}"
                    >
                        <svg class="w-4 h-4 {{ request()->routeIs('alarms.thresholds') ? 'text-accent' : 'text-accent' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                        <span>Threshold Alarm</span>
                    </a>

                    <a
                        href="{{ route('admin.integrations.google-sheets.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2 rounded-[2px] text-xs font-medium transition-colors {{ request()->routeIs('admin.integrations.google-sheets.*') ? 'bg-accent-soft text-accent border border-line ' : 'text-muted hover:text-accent hover:bg-surface' }}"
                    >
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.integrations.google-sheets.*') ? 'text-accent' : 'text-accent' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Integrasi Google Sheets</span>
                    </a>

                    <a
                        href="{{ route('admin.fuzzy.config') }}"
                        class="flex items-center gap-3 px-3.5 py-2 rounded-[2px] text-xs font-medium transition-colors {{ request()->routeIs('admin.fuzzy.*') ? 'bg-accent-soft text-accent border border-line ' : 'text-muted hover:text-accent hover:bg-surface' }}"
                    >
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.fuzzy.*') ? 'text-accent' : 'text-accent' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>Konfigurasi Fuzzy</span>
                    </a>
                </div>
            @endif
        </nav>
    </div>

    <!-- Sidebar Bottom / User Status Widget -->
    <div class="p-4 border-t border-line bg-canvas">
        <div class="flex items-center gap-3 p-2.5 rounded-[2px] bg-canvas border border-line">
            <div class="w-8 h-8 rounded-[2px] {{ auth()->user()->isAdmin() ? 'bg-accent-soft text-accent border border-line' : 'bg-accent-soft text-accent border border-line' }} flex items-center justify-center font-semibold text-xs ">
                {{ substr(auth()->user()->name, 0, 2) }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold text-current truncate">{{ auth()->user()->name }}</div>
                <div class="text-xs text-muted capitalize">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Pengamat' }}</div>
            </div>
        </div>
    </div>
</aside>

<!-- Backdrop overlay for mobile -->
<div
    x-show="sidebarOpen"
    x-transition:enter="transition-opacity ease-linear duration-150"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="sidebarOpen = false"
    aria-hidden="true"
    data-sidebar-backdrop
    class="fixed inset-0 z-40 bg-black/60  lg:hidden"
    style="display: none;"
></div>
