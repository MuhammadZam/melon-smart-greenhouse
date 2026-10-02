<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - Azolla Monitoring System</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="bg-slate-950 text-slate-100 min-h-screen font-sans antialiased selection:bg-emerald-500 selection:text-white"
    x-data="{ sidebarOpen: false }"
>
    <!-- Background Ambient Glow -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-emerald-600/10 rounded-full blur-[120px]"></div>
        <div class="absolute top-1/2 -right-40 w-96 h-96 bg-teal-500/10 rounded-full blur-[120px]"></div>
        <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-green-500/10 rounded-full blur-[120px]"></div>
    </div>

    <!-- Include Sidebar -->
    @include('layouts.partials.sidebar')

    <!-- Main Content Wrapper (offset by sidebar width on lg screens) -->
    <div class="lg:pl-64 flex flex-col min-h-screen">
        <!-- Include Navbar -->
        @include('layouts.partials.navbar')

        <!-- Main Body Content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl w-full mx-auto">
            <!-- Flash Message: Success -->
            @if (session('success'))
                <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center gap-3 text-emerald-300 text-sm shadow-sm">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Flash Message: Error -->
            @if (session('error'))
                <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center gap-3 text-rose-300 text-sm shadow-sm">
                    <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-800/80 py-4 px-6 text-center text-xs text-slate-400">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                <span>Sistem Web Monitoring Tanaman Azolla &copy; {{ date('Y') }}</span>
                <span class="text-slate-400">Laravel 11 &bull; Tailwind CSS &bull; Chart.js &bull; MySQL</span>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
