<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Monitoring Tanaman Melon</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen font-sans selection:bg-emerald-500 selection:text-white flex flex-col justify-between">
    <!-- Background Glow -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 -right-40 w-96 h-96 bg-teal-500/15 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-green-500/20 rounded-full blur-3xl"></div>
    </div>

    <!-- Navigation -->
    <header class="border-b border-slate-800/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-base font-bold bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent">Melon Monitor</h1>
                    <p class="text-xs text-slate-400">Smart Agriculture System</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5 animate-pulse"></span>
                    Phase 3 Active &bull; Docker + Auth
                </span>

                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-xs shadow-md shadow-emerald-500/20 transition-all">
                        <span>Dashboard ({{ auth()->user()->name }})</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-slate-950 font-bold text-xs shadow-md shadow-emerald-500/20 transition-all">
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
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-800/80 border border-slate-700 text-xs text-slate-300 mb-6">
                <span class="px-1.5 py-0.5 rounded bg-emerald-500 text-slate-900 font-semibold text-[10px]">SUCCESS</span>
                <span>Setup Lingkungan & Framework Berhasil Berjalan</span>
            </div>
            <h2 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white mb-4">
                Sistem Web Monitoring <span class="bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent">Tanaman Melon</span>
            </h2>
            <p class="text-slate-400 text-base sm:text-lg">
                Platform pencatatan, pemantauan kondisi mikro-lingkungan, dan analisis perkembangan tanaman melon secara real-time dan terstruktur.
            </p>
        </div>

        <!-- System Status Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 max-w-5xl mx-auto w-full mb-12">
            <div class="p-5 rounded-2xl bg-slate-800/50 border border-slate-700/60 backdrop-blur-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-slate-400">Framework</span>
                    <span class="text-xs text-emerald-400 font-mono">v11.57.0</span>
                </div>
                <div class="text-lg font-bold text-white">Laravel 11</div>
                <p class="text-xs text-slate-400 mt-1">PHP 8.2.12 Aktif</p>
            </div>

            <div class="p-5 rounded-2xl bg-slate-800/50 border border-slate-700/60 backdrop-blur-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-slate-400">Styling</span>
                    <span class="text-xs text-teal-400 font-mono">v3.4.17</span>
                </div>
                <div class="text-lg font-bold text-white">Tailwind CSS</div>
                <p class="text-xs text-slate-400 mt-1">Vite Asset Bundler</p>
            </div>

            <div class="p-5 rounded-2xl bg-slate-800/50 border border-slate-700/60 backdrop-blur-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-slate-400">Visualisasi</span>
                    <span class="text-xs text-emerald-400 font-mono">Ready</span>
                </div>
                <div class="text-lg font-bold text-white">Chart.js</div>
                <p class="text-xs text-slate-400 mt-1">Siap untuk Tren Grafik</p>
            </div>

            <div class="p-5 rounded-2xl bg-slate-800/50 border border-slate-700/60 backdrop-blur-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-slate-400">Database</span>
                    <span class="text-xs text-amber-400 font-mono">Port 3306</span>
                </div>
                <div class="text-lg font-bold text-white">MySQL 8.0</div>
                <p class="text-xs text-slate-400 mt-1">Menunggu Kredensial DB</p>
            </div>
        </div>

        <!-- Next Steps Banner -->
        <div class="max-w-3xl mx-auto w-full p-6 rounded-2xl bg-gradient-to-r from-emerald-950/40 via-slate-800/60 to-slate-900 border border-emerald-500/30">
            <h3 class="text-sm font-semibold text-emerald-400 uppercase tracking-wider mb-2">Status Saat Ini: Phase 1 Selesai</h3>
            <p class="text-sm text-slate-300 leading-relaxed mb-4">
                Seluruh fondasi aplikasi web (PHP, Composer, Laravel 11, Tailwind CSS, Vite, Chart.js, Alpine.js, dan Git) telah terkonfigurasi dan berjalan lancar. Tahap berikutnya adalah pembuatan database dan migrasi tabel (Phase 2).
            </p>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Server berjalan di <span class="font-mono text-emerald-400">http://127.0.0.1:8000</span>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 py-6 text-center text-xs text-slate-500">
        <p>Sistem Web Monitoring Tanaman Melon &copy; {{ date('Y') }}. Dibangun dengan Laravel & Tailwind CSS.</p>
    </footer>
</body>
</html>
