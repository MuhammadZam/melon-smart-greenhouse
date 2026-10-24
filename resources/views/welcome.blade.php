<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Monitoring Tanaman Melon</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas text-ink min-h-screen font-sans selection:bg-accent selection:text-white flex flex-col justify-between">
    

    <!-- Navigation -->
    <header class="border-b border-line  sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-[2px]    flex items-center justify-center  ">
                    <svg class="w-6 h-6 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-base font-semibold     text-ink">Melon Monitor</h1>
                    <p class="text-xs text-muted">Smart Agriculture System</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center px-2.5 py-0.5 rounded-[2px] text-xs font-medium bg-accent-soft text-accent border border-line">
                    <span class="w-1.5 h-1.5 rounded-[2px] bg-accent-soft mr-1.5 "></span>
                    Phase 3 Active &bull; Docker + Auth
                </span>

                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-[2px] bg-accent hover:bg-accent-soft text-current font-semibold text-xs transition-all">
                        <span>Dashboard ({{ auth()->user()->name }})</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-[2px] text-current font-semibold text-xs transition-all ui-button-primary">
                        <span>Masuk (Login)</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-1 flex flex-col justify-center">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-[2px] bg-surface border border-line text-xs text-ink mb-6">
                <span class="px-1.5 py-0.5 rounded-[2px] bg-accent text-ink font-semibold text-xs">SUCCESS</span>
                <span>Setup Lingkungan & Framework Berhasil Berjalan</span>
            </div>
            <h2 class="text-3xl sm:text-5xl font-semibold tracking-tight text-current mb-4">
                Sistem Web Monitoring <span class="    text-ink">Tanaman Melon</span>
            </h2>
            <p class="text-muted text-base sm:text-lg">
                Platform pencatatan, pemantauan kondisi mikro-lingkungan, dan analisis perkembangan tanaman melon secara real-time dan terstruktur.
            </p>
        </div>

        <!-- System Status Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 max-w-5xl mx-auto w-full mb-12">
            <div class="p-5 rounded-[2px] bg-surface border border-line ">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-muted">Framework</span>
                    <span class="text-xs text-accent tabular-nums">v11.57.0</span>
                </div>
                <div class="text-lg font-semibold text-current">Laravel 11</div>
                <p class="text-xs text-muted mt-1">PHP 8.2.12 Aktif</p>
            </div>

            <div class="p-5 rounded-[2px] bg-surface border border-line ">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-muted">Styling</span>
                    <span class="text-xs text-accent tabular-nums">v3.4.17</span>
                </div>
                <div class="text-lg font-semibold text-current">Tailwind CSS</div>
                <p class="text-xs text-muted mt-1">Vite Asset Bundler</p>
            </div>

            <div class="p-5 rounded-[2px] bg-surface border border-line ">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-muted">Visualisasi</span>
                    <span class="text-xs text-accent tabular-nums">Ready</span>
                </div>
                <div class="text-lg font-semibold text-current">Chart.js</div>
                <p class="text-xs text-muted mt-1">Siap untuk Tren Grafik</p>
            </div>

            <div class="p-5 rounded-[2px] bg-surface border border-line ">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-muted">Database</span>
                    <span class="text-xs text-warning tabular-nums">Port 3306</span>
                </div>
                <div class="text-lg font-semibold text-current">MySQL 8.0</div>
                <p class="text-xs text-muted mt-1">Menunggu Kredensial DB</p>
            </div>
        </div>

        <!-- Next Steps Banner -->
        <div class="max-w-3xl mx-auto w-full p-6 rounded-[2px]     border border-line">
            <h3 class="text-sm font-semibold text-accent   mb-2">Status Saat Ini: Phase 1 Selesai</h3>
            <p class="text-sm text-ink leading-relaxed mb-4">
                Seluruh fondasi aplikasi web (PHP, Composer, Laravel 11, Tailwind CSS, Vite, Chart.js, Alpine.js, dan Git) telah terkonfigurasi dan berjalan lancar. Tahap berikutnya adalah pembuatan database dan migrasi tabel (Phase 2).
            </p>
            <div class="flex items-center gap-2 text-xs text-muted">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Server berjalan di <span class="tabular-nums text-accent">http://127.0.0.1:8000</span>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-line py-6 text-center text-xs text-muted">
        <p>Sistem Web Monitoring Tanaman Melon &copy; {{ date('Y') }}. Dibangun dengan Laravel & Tailwind CSS.</p>
    </footer>
</body>
</html>
