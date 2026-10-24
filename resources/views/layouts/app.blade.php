<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - Melon Monitoring System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="bg-canvas text-ink min-h-screen font-sans antialiased selection:bg-accent selection:text-white"
    x-data="{ sidebarOpen: false, desktopNav: window.innerWidth >= 1024 }"
    @resize.window="desktopNav = window.innerWidth >= 1024; if (desktopNav) sidebarOpen = false"
    @keydown.escape.window="sidebarOpen = false"
>
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] focus:bg-canvas focus:p-3 focus:rounded-[2px]">Lewati navigasi</a>


    <!-- Include Sidebar -->
    @include('layouts.partials.sidebar')

    <!-- Main Content Wrapper (offset by sidebar width on lg screens) -->
    <div class="app-shell lg:pl-64 flex flex-col min-h-screen" :inert="sidebarOpen && !desktopNav">
        <!-- Include Navbar -->
        @include('layouts.partials.navbar')

        <!-- Main Body Content -->
        <main id="main-content" tabindex="-1" class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl w-full mx-auto">
            <!-- Flash Message: Success -->
            @if (session('success'))
                <div role="status" class="p-4 rounded-[2px] bg-accent-soft border border-line flex items-center gap-3 text-accent text-sm">
                    <svg class="w-5 h-5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Flash Message: Error -->
            @if (session('error'))
                <div role="alert" class="p-4 rounded-[2px] bg-danger-soft border border-danger/25 flex items-center gap-3 text-danger text-sm">
                    <svg class="w-5 h-5 text-danger shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="border-t border-line py-4 px-6 text-center text-xs text-muted">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                <span>Sistem Web Monitoring Tanaman Melon &copy; {{ date('Y') }}</span>
                <span class="text-muted">Pemantauan greenhouse</span>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
