<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Masuk ke Sistem') - Melon Monitoring</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans antialiased selection:bg-emerald-500 selection:text-white flex flex-col justify-between relative overflow-x-hidden">
    <!-- Ambient Lighting Background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-emerald-600/15 rounded-full blur-[120px]"></div>
        <div class="absolute top-1/3 -right-32 w-96 h-96 bg-teal-500/15 rounded-full blur-[120px]"></div>
        <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-green-500/10 rounded-full blur-[120px]"></div>
    </div>

    <!-- Header Mini -->
    <header class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex items-center justify-between">
        <a href="{{ route('home') }}" class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/25 group-hover:scale-105 transition-transform duration-200">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <div>
                <span class="text-base font-bold bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent">Melon Monitor</span>
                <span class="block text-xs text-slate-400">Smart Agriculture</span>
            </div>
        </a>

        <a href="{{ route('home') }}" class="text-xs text-slate-400 hover:text-emerald-400 transition-colors flex items-center gap-1.5 py-1.5 px-3 rounded-lg border border-slate-800 bg-slate-900/60 hover:border-slate-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Beranda
        </a>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="w-full border-t border-slate-800/80 py-5 text-center text-xs text-slate-500">
        <p>Sistem Web Monitoring Tanaman Melon &copy; {{ date('Y') }}</p>
    </footer>
</body>
</html>
