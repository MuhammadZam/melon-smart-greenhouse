<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Dashboard - Azolla Monitoring System</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans antialiased selection:bg-emerald-500 selection:text-white flex flex-col justify-between">
    <!-- Ambient Background Glow -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-emerald-600/15 rounded-full blur-[120px]"></div>
        <div class="absolute top-1/2 -right-32 w-96 h-96 bg-teal-500/10 rounded-full blur-[120px]"></div>
    </div>

    <!-- Navigation Header -->
    <nav class="border-b border-slate-800 bg-slate-900/60 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Brand -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div>
                    <span class="text-base font-bold bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent">Azolla Monitor</span>
                    <span class="hidden sm:inline-block text-xs text-slate-400 ml-2">&bull; Dashboard</span>
                </div>
            </div>

            <!-- User Menu & Logout -->
            <div class="flex items-center gap-4">
                <!-- User Profile Pill -->
                <div class="hidden sm:flex items-center gap-3 px-3 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700/60">
                    <div class="w-7 h-7 rounded-lg {{ $user->isAdmin() ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-teal-500/20 text-teal-300 border border-teal-500/30' }} flex items-center justify-center text-xs font-bold uppercase">
                        {{ substr($user->name, 0, 2) }}
                    </div>
                    <div class="text-left">
                        <div class="text-xs font-semibold text-white leading-tight">{{ $user->name }}</div>
                        <div class="text-[10px] text-slate-400 leading-tight">{{ $user->email }}</div>
                    </div>
                    <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $user->isAdmin() ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-teal-500/20 text-teal-400 border border-teal-500/30' }}">
                        {{ $user->role }}
                    </span>
                </div>

                <!-- Form Logout -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="px-3.5 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 border border-rose-500/25 text-xs font-medium flex items-center gap-1.5 transition-all shadow-sm"
                        title="Keluar dari akun"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Keluar (Logout)</span>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">

        <!-- Flash Message: Success -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center gap-3 text-emerald-300 text-sm shadow-sm">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Welcome Banner -->
        <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-slate-900 via-slate-900/90 to-slate-800/80 border border-slate-800 relative overflow-hidden">
            <div class="relative z-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold {{ $user->isAdmin() ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-teal-500/10 text-teal-400 border border-teal-500/20' }} mb-3">
                    <span class="w-1.5 h-1.5 rounded-full {{ $user->isAdmin() ? 'bg-emerald-400' : 'bg-teal-400' }} animate-pulse"></span>
                    Autentikasi Terverifikasi &bull; Role {{ ucfirst($user->role) }}
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                    Halo, <span class="bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent">{{ $user->name }}</span>!
                </h1>
                <p class="text-slate-400 text-sm sm:text-base mt-2 max-w-2xl">
                    Anda berhasil masuk ke Sistem Monitoring Tanaman Azolla. Sesi login Anda tersimpan aman dan divalidasi oleh Role Middleware.
                </p>
            </div>
        </div>

        <!-- Role Authorization Demonstration Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- User Status Card -->
            <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800">
                <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Informasi Akun Anda
                </h2>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between py-2 border-b border-slate-800">
                        <span class="text-slate-400 text-xs">Nama Lengkap</span>
                        <span class="font-semibold text-white">{{ $user->name }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-slate-800">
                        <span class="text-slate-400 text-xs">Alamat Email</span>
                        <span class="font-mono text-emerald-400 text-xs">{{ $user->email }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-slate-800">
                        <span class="text-slate-400 text-xs">Tingkat Hak Akses</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $user->isAdmin() ? 'bg-emerald-500/20 text-emerald-300' : 'bg-teal-500/20 text-teal-300' }}">
                            {{ $user->role }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-slate-400 text-xs">Status Autentikasi</span>
                        <span class="text-emerald-400 text-xs font-medium flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            Session Aktif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Role Access Test Card -->
            <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800">
                <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    Uji Otorisasi Middleware
                </h2>
                
                @if ($user->isAdmin())
                    <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/25 mb-4">
                        <div class="flex items-center gap-2 text-emerald-400 font-semibold text-xs mb-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Hak Akses Administrator Aktif
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Anda memiliki izin untuk mengelola seluruh data tanaman, mengubah parameter threshold, input data, serta manajemen akun user.
                        </p>
                    </div>
                    <a
                        href="{{ route('admin.check') }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/30 text-xs font-semibold transition-all"
                    >
                        <span>Uji Endpoint Proteksi Role (Admin Only)</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </a>
                @else
                    <div class="p-4 rounded-xl bg-teal-500/10 border border-teal-500/25 mb-4">
                        <div class="flex items-center gap-2 text-teal-400 font-semibold text-xs mb-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Hak Akses Pengamat Lapangan (Read-Only)
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Sebagai Pengamat (User), Anda dapat melihat data dashboard, histori monitoring, dan grafik kondisi tanaman. Akses manajemen Admin diproteksi oleh <code class="bg-slate-800 px-1 py-0.5 rounded text-amber-300">RoleMiddleware</code>.
                        </p>
                    </div>
                    <a
                        href="{{ route('admin.check') }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-rose-300 border border-slate-700 text-xs font-semibold transition-all"
                    >
                        <span>Uji Endpoint Admin (Akan Muncul 403 Forbidden)</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </a>
                @endif
            </div>
        </div>

        <!-- Data Summary Cards (Database Integration Check) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                <div class="text-xs text-slate-400 font-medium">Total Kolam Terdaftar</div>
                <div class="text-2xl font-bold text-white mt-1">{{ $totalPlants }} <span class="text-xs font-normal text-slate-400">Kolam</span></div>
                <p class="text-[11px] text-emerald-400 mt-1">Data aktif di MySQL</p>
            </div>
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                <div class="text-xs text-slate-400 font-medium">Total Rekaman Monitoring</div>
                <div class="text-2xl font-bold text-white mt-1">{{ $totalMonitorings }} <span class="text-xs font-normal text-slate-400">Entri</span></div>
                <p class="text-[11px] text-teal-400 mt-1">Siap untuk visualisasi grafik Phase 4</p>
            </div>
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                <div class="text-xs text-slate-400 font-medium">Parameter Ambang Batas</div>
                <div class="text-2xl font-bold text-white mt-1">{{ $totalParameters }} <span class="text-xs font-normal text-slate-400">Parameter</span></div>
                <p class="text-[11px] text-emerald-400 mt-1">Suhu, pH, Kelembapan, dll.</p>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 py-5 text-center text-xs text-slate-500">
        <p>Sistem Web Monitoring Tanaman Azolla &copy; {{ date('Y') }} &bull; Phase 3 Selesai</p>
    </footer>
</body>
</html>
