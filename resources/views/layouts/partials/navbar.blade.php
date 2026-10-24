<header class="sticky top-0 z-30 h-16 bg-canvas border-b border-line">
    <div class="h-full px-4 sm:px-6 lg:px-8 flex items-center justify-between">

        <!-- Sisi Kiri: Hamburger Mobile & Nama Aplikasi -->
        <div class="flex items-center gap-2 sm:gap-3 min-w-0">
            <!-- Mobile Menu Toggle Button -->
            <button
                type="button"
                @click="sidebarOpen = !sidebarOpen"
                class="lg:hidden p-2 rounded-[2px] text-muted hover:text-ink hover:bg-surface focus:outline-none transition-colors"
                title="Buka Menu Navigasi"
                id="navigation-toggle"
                aria-label="Buka menu navigasi"
                aria-controls="main-navigation"
                :aria-expanded="sidebarOpen.toString()"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>

            <!-- App Title & Breadcrumb -->
            <div class="min-w-0">
                <div class="text-xs sm:text-base font-semibold text-current flex items-center gap-2">
                    <span class="truncate">Sistem Monitoring Melon</span>
                    <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-accent-soft text-accent border border-line">
                        Monitoring IoT
                    </span>
                </div>
                <p class="hidden sm:block text-xs text-muted">Dashboard Pemantauan Tanaman &amp; Mikroklimat</p>
            </div>
        </div>

        <!-- Sisi Kanan: Profile & Logout -->
        <div class="flex items-center gap-1 sm:gap-4 shrink-0">
            <!-- Notification Bell with Lightweight Polling (Phase 12) -->
            <div
                x-data="{
                    open: false,
                    activeCount: 0,
                    criticalCount: 0,
                    latestAlarms: [],
                    loading: false,
                    failed: false,
                    async fetchStatus() {
                        this.loading = true;
                        this.failed = false;
                        try {
                            const res = await fetch('{{ route('notifications.status') }}', {
                                headers: { 'Accept': 'application/json' }
                            });
                            if (res.ok) {
                                const data = await res.json();
                                this.activeCount = data.active_count || 0;
                                this.criticalCount = data.critical_count || 0;
                                this.latestAlarms = data.latest || [];
                            } else {
                                throw new Error('Status notifikasi tidak tersedia');
                            }
                        } catch (e) {
                            this.failed = true;
                        } finally {
                            this.loading = false;
                        }
                    },
                    init() {
                        this.fetchStatus();
                        // Polling ringan setiap 30 detik
                        setInterval(() => this.fetchStatus(), 30000);
                    }
                }"
                class="relative"
                @click.outside="open = false"
                @keydown.escape.window="if(open) { open = false; $refs.notificationButton.focus() }"
            >
                <button
                    type="button"
                    @click="open = !open"
                    class="relative p-2 rounded-[2px] text-muted hover:text-ink hover:bg-surface transition-colors focus:outline-none"
                    title="Notifikasi Alarm Sensor"
                    id="alarm-notification-btn"
                    x-ref="notificationButton"
                    aria-label="Notifikasi alarm sensor"
                    :aria-busy="loading.toString()"
                    aria-controls="alarm-notifications"
                    :aria-expanded="open.toString()"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>

                    {{-- Badge Jumlah Alarm Aktif --}}
                    <template x-if="activeCount > 0">
                        <span
                            class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-[2px] px-1 text-xs font-semibold text-current"
                            :class="criticalCount > 0 ? 'bg-danger ' : 'bg-surface'"
                            x-text="activeCount"
                        ></span>
                    </template>
                </button>

                {{-- Dropdown Notifikasi --}}
                <div
                    id="alarm-notifications"
                    role="region"
                    aria-label="Notifikasi alarm sensor"
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="fixed top-16 right-4 w-[calc(100vw-2rem)] sm:absolute sm:top-auto sm:right-0 sm:mt-2 sm:w-96 rounded-[2px] bg-canvas border border-line py-3 z-50 text-xs text-ink"
                    style="display: none;"
                >
                    {{-- Dropdown Header --}}
                    <div class="px-4 pb-2 border-b border-line flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-current">Notifikasi Alarm Sensor</span>
                            <span
                                class="px-2 py-0.5 rounded-[2px] text-xs font-semibold"
                                :class="criticalCount > 0 ? 'bg-danger-soft text-danger border border-danger/25' : (activeCount > 0 ? 'bg-warning-soft text-warning border border-warning/25' : 'bg-accent-soft text-accent border border-line')"
                                x-text="activeCount > 0 ? (activeCount + ' Aktif') : 'Aman'"
                            ></span>
                        </div>
                        <a href="{{ route('alarms.index') }}" class="text-xs font-semibold text-accent hover:text-accent">
                            Semua &rarr;
                        </a>
                    </div>

                    {{-- Alarm Items --}}
                    <div class="max-h-72 overflow-y-auto divide-y divide-line">
                        <div x-show="loading" role="status" class="p-4 text-ink">Memuat notifikasi...</div>
                        <div x-show="failed" role="alert" class="p-4 text-warning">
                            <p>Status alarm belum dapat dimuat. Periksa koneksi lalu coba lagi.</p>
                            <button type="button" @click="fetchStatus()" class="mt-2 rounded-[2px] px-3 py-2 bg-surface text-current">Coba lagi</button>
                        </div>
                        <template x-if="latestAlarms.length === 0 && !failed && !loading">
                            <div class="py-8 px-4 text-center">
                                <div class="w-8 h-8 rounded-[2px] bg-accent-soft text-accent flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <p class="text-xs font-semibold text-current">Semua Parameter Normal</p>
                                <p class="text-xs text-muted mt-0.5">Tidak ada pembacaan sensor di luar ambang batas.</p>
                            </div>
                        </template>

                        <template x-for="item in latestAlarms" :key="item.id">
                            <a :href="item.alarm_url" class="block p-3 hover:bg-surface transition-colors">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span
                                                class="w-2 h-2 rounded-[2px] shrink-0"
                                                :class="item.severity === 'critical' ? 'bg-danger ' : 'bg-surface'"
                                            ></span>
                                            <span class="font-semibold text-current truncate" x-text="item.plant_name"></span>
                                        </div>
                                        <div class="text-xs text-ink mt-0.5">
                                            <span x-text="item.parameter"></span>:
                                            <strong
                                                :class="item.severity === 'critical' ? 'text-danger' : 'text-warning'"
                                                x-text="item.sensor_value + ' ' + (item.unit || '')"
                                            ></strong>
                                            <span class="text-muted" x-text="'(' + item.violation + ')'"></span>
                                        </div>
                                    </div>
                                    <div class="text-xs text-muted shrink-0" x-text="item.first_detected"></div>
                                </div>
                            </a>
                        </template>
                    </div>

                    {{-- Dropdown Footer --}}
                    <div class="px-4 pt-2 border-t border-line flex items-center justify-between text-xs">
                        <span class="text-muted">Status diperbarui via polling</span>
                        <a href="{{ route('alarms.index') }}" class="font-semibold text-accent hover:text-accent">
                            Buka Riwayat Alarm
                        </a>
                    </div>
                </div>
            </div>

            <!-- User Status Chip -->
            <div class="hidden sm:flex items-center gap-2.5 pl-2 sm:pl-3 pr-2 py-1 rounded-[2px] bg-surface border border-line">
                <div class="w-8 h-8 rounded-[2px] {{ auth()->user()->isAdmin() ? 'bg-accent-soft text-accent border border-line' : 'bg-accent-soft text-accent border border-line' }} flex items-center justify-center text-xs font-semibold">
                    {{ substr(auth()->user()->name, 0, 2) }}
                </div>
                <div class="hidden sm:block text-left">
                    <div class="text-xs font-semibold text-current leading-tight">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-muted leading-tight flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-[2px] {{ auth()->user()->isAdmin() ? 'bg-accent-soft' : 'bg-accent-soft' }}"></span>
                        <span class="capitalize">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Pengamat' }}</span>
                    </div>
                </div>
            </div>

            <!-- Tombol Logout (Menggunakan Route Phase 3) -->
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button
                    type="submit"
                    class="px-3 py-1.5 rounded-[2px] bg-danger-soft hover:bg-danger-soft text-danger hover:text-danger border border-danger/25 text-xs font-semibold flex items-center gap-1.5 transition-all"
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
