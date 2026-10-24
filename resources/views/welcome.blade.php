<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Melon Monitor — Pemantauan greenhouse</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col bg-canvas text-ink font-sans antialiased">
    <header class="border-b border-line">
        <div class="max-w-6xl mx-auto px-4 sm:px-8 h-20 flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="font-semibold text-base tracking-tight">Melon Monitor<span class="block text-xs font-normal text-muted">Pemantauan greenhouse</span></a>
            @auth
                <a href="{{ route('dashboard') }}" class="ui-button-primary rounded-[4px] px-4 py-2 text-sm font-medium inline-flex items-center">Buka dashboard</a>
            @else
                <a href="{{ route('login') }}" class="ui-button-primary rounded-[4px] px-4 py-2 text-sm font-medium inline-flex items-center">Masuk</a>
            @endauth
        </div>
    </header>
    <main class="w-full max-w-6xl mx-auto px-4 sm:px-8 py-12 sm:py-20 flex-1 space-y-12">
        <section class="max-w-2xl space-y-4">
            <p class="text-xs text-accent font-medium">Sistem monitoring tanaman melon</p>
            <h1 class="ui-page-title">Kondisi greenhouse, dalam satu tampilan.</h1>
            <p class="text-muted text-sm leading-relaxed">Tinjau pembacaan sensor, telusuri riwayat pengamatan, dan pahami perubahan lingkungan tanaman. Data dan waktu pengamatan ditampilkan agar setiap kondisi dapat ditelusuri.</p>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="inline-flex items-center gap-2 text-sm font-medium text-accent py-2">{{ auth()->check() ? 'Lihat pemantauan' : 'Masuk ke pemantauan' }} <span aria-hidden="true">&rarr;</span></a>
        </section>
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-10" aria-label="Fungsi utama">
            <div class="border-t border-line pt-5 space-y-2"><h2 class="text-base font-semibold">Pembacaan sensor</h2><p class="text-sm text-muted">Delapan parameter lingkungan, lengkap dengan waktu pengamatan dan keterangan data yang belum tersedia.</p></div>
            <div class="border-t border-line pt-5 space-y-2"><h2 class="text-base font-semibold">Riwayat dan analisis</h2><p class="text-sm text-muted">Grafik tren, alarm parameter, dan hasil analisis fuzzy untuk membantu meninjau kondisi greenhouse.</p></div>
            <div class="border-t border-line pt-5 space-y-2"><h2 class="text-base font-semibold">Pencatatan dan laporan</h2><p class="text-sm text-muted">Kelola tanaman dan pengamatan, filter data yang dibutuhkan, lalu ekspor laporan CSV atau PDF.</p></div>
        </section>
        <section class="border-t border-line pt-6" aria-labelledby="sensor-list-title">
            <h2 id="sensor-list-title" class="text-lg font-semibold mb-4">Parameter yang dipantau</h2>
            <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-3 text-sm text-muted">
                <li>Suhu udara <span class="text-ink">°C</span></li><li>Kelembapan udara <span class="text-ink">%</span></li><li>Kelembapan tanah <span class="text-ink">%</span></li><li>pH nutrisi/media</li>
                <li>Konduktivitas listrik <span class="text-ink">mS/cm</span></li><li>Intensitas cahaya <span class="text-ink">lux</span></li><li>Suhu nutrisi <span class="text-ink">°C</span></li><li>Level tandon <span class="text-ink">%</span></li>
            </ul>
        </section>
    </main>
    <footer class="border-t border-line px-4 sm:px-8 py-6 text-xs text-muted"><div class="max-w-6xl mx-auto">Melon Monitor &copy; {{ date('Y') }}</div></footer>
</body>
</html>
