@extends('layouts.app')

@section('title', 'Laporan Monitoring Greenhouse')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Bar -->
    <div class="ui-page-header flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-[2px] text-xs font-semibold   bg-accent-soft text-accent border border-line">
                    <span class="w-1.5 h-1.5 rounded-[2px] bg-accent-soft mr-1.5 "></span>
                    Dokumentasi &amp; Analisis
                </span>
                <span class="text-xs text-muted">Modul Pelaporan Resmi</span>
            </div>
            <h2 class="ui-page-title text-xl sm:text-2xl font-semibold text-current tracking-tight">
                Laporan Monitoring <span class="    text-ink">Greenhouse Melon</span>
            </h2>
            <p class="text-xs sm:text-sm text-muted mt-0.5">
                Penyajian statistik 8 parameter mikroklimat, ringkasan alarm, inferensi fuzzy, dan rekapitulasi data terverifikasi.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 self-start md:self-auto">
            <!-- Tombol Ekspor CSV -->
            <a
                href="{{ route('reports.monitoring.export.csv', request()->all()) }}"
                class="px-3.5 py-2 rounded-[2px] bg-surface hover:bg-surface text-ink border border-line text-xs font-semibold flex items-center gap-1.5 transition-colors hover:text-ink"
                id="btn-export-csv"
                title="Unduh seluruh data hasil filter ke format CSV terstandarisasi"
            >
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Ekspor CSV</span>
            </a>

            <!-- Tombol Ekspor PDF -->
            <a
                href="{{ route('reports.monitoring.export.pdf', request()->all()) }}"
                class="px-3.5 py-2 rounded-[2px] bg-accent hover:bg-accent text-current text-xs font-semibold flex items-center gap-1.5 transition-colors"
                id="btn-export-pdf"
                title="Cetak laporan akademik profesional format PDF A4 Landscape"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                <span>Ekspor PDF (A4)</span>
            </a>
        </div>
    </div>

    <!-- Filter Form & Quick Range -->
    <div class="p-5 sm:p-6 rounded-[2px] bg-canvas border border-line  space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-line pb-4">
            <div>
                <h3 class="text-sm sm:text-base font-semibold text-current flex items-center gap-2">
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Filter &amp; Parameter Laporan
                </h3>
                <p class="text-xs text-muted mt-0.5">Filter berlaku secara identik pada ringkasan statistik, tabel web, ekspor CSV, dan PDF.</p>
            </div>

            <!-- Quick Date Range -->
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-semibold text-muted mr-1 hidden sm:inline">Pintasan Waktu:</span>
                <a
                    href="{{ route('reports.monitoring.index', array_merge(request()->except(['date_from', 'date_to', 'page']), ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()])) }}"
                    class="px-2.5 py-1 rounded-[2px] text-xs font-semibold transition-colors {{ request('date_from') === now()->toDateString() && request('date_to') === now()->toDateString() ? 'bg-accent text-current' : 'bg-surface hover:bg-surface text-ink' }}"
                >
                    Hari Ini
                </a>
                <a
                    href="{{ route('reports.monitoring.index', array_merge(request()->except(['date_from', 'date_to', 'page']), ['date_from' => now()->subDays(6)->toDateString(), 'date_to' => now()->toDateString()])) }}"
                    class="px-2.5 py-1 rounded-[2px] text-xs font-semibold transition-colors {{ request('date_from') === now()->subDays(6)->toDateString() ? 'bg-accent text-current' : 'bg-surface hover:bg-surface text-ink' }}"
                >
                    7 Hari Terakhir
                </a>
                <a
                    href="{{ route('reports.monitoring.index', array_merge(request()->except(['date_from', 'date_to', 'page']), ['date_from' => now()->subDays(29)->toDateString(), 'date_to' => now()->toDateString()])) }}"
                    class="px-2.5 py-1 rounded-[2px] text-xs font-semibold transition-colors {{ request('date_from') === now()->subDays(29)->toDateString() ? 'bg-accent text-current' : 'bg-surface hover:bg-surface text-ink' }}"
                >
                    30 Hari Terakhir
                </a>
                <a
                    href="{{ route('reports.monitoring.index', request()->except(['date_from', 'date_to', 'page'])) }}"
                    class="px-2.5 py-1 rounded-[2px] text-xs font-semibold transition-colors {{ empty(request('date_from')) && empty(request('date_to')) ? 'bg-accent text-current' : 'bg-surface hover:bg-surface text-ink' }}"
                >
                    Semua Waktu
                </a>
            </div>
        </div>

        <!-- Filter Form Fields -->
        <form method="GET" action="{{ route('reports.monitoring.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3.5 items-end" id="report-filter-form">
            <!-- 1. Tanggal Mulai -->
            <div>
                <label for="date_from" class="block text-xs font-semibold   text-ink mb-1">Tanggal Mulai</label>
                <input
                    type="date"
                    name="date_from"
                    id="date_from"
                    value="{{ $filters['date_from'] ?? '' }}"
                    class="w-full px-3 py-2 rounded-[2px] bg-canvas border border-line hover:border-line text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
                >
            </div>

            <!-- 2. Tanggal Akhir -->
            <div>
                <label for="date_to" class="block text-xs font-semibold   text-ink mb-1">Tanggal Akhir</label>
                <input
                    type="date"
                    name="date_to"
                    id="date_to"
                    value="{{ $filters['date_to'] ?? '' }}"
                    class="w-full px-3 py-2 rounded-[2px] bg-canvas border border-line hover:border-line text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
                >
            </div>

            <!-- 3. Dropdown Tanaman -->
            <div>
                <label for="plant_id" class="block text-xs font-semibold   text-ink mb-1">Tanaman</label>
                <select
                    name="plant_id"
                    id="plant_id"
                    class="w-full px-3 py-2 rounded-[2px] bg-canvas border border-line hover:border-line text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
                >
                    <option value="all" {{ ($filters['plant_id'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua Tanaman</option>
                    @foreach($plants as $plant)
                        <option value="{{ $plant->id }}" {{ (string)($filters['plant_id'] ?? '') === (string)$plant->id ? 'selected' : '' }}>
                            {{ $plant->name }} ({{ $plant->location ?? 'Greenhouse' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 4. Sumber Data -->
            <div>
                <label for="source" class="block text-xs font-semibold   text-ink mb-1">Sumber Data</label>
                <select
                    name="source"
                    id="source"
                    class="w-full px-3 py-2 rounded-[2px] bg-canvas border border-line hover:border-line text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
                >
                    @foreach($sources as $srcKey => $srcLabel)
                        <option value="{{ $srcKey }}" {{ ($filters['source'] ?? 'all') === $srcKey ? 'selected' : '' }}>
                            {{ $srcLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 5. Kondisi Tanaman -->
            <div>
                <label for="condition" class="block text-xs font-semibold   text-ink mb-1">Kondisi Tanaman</label>
                <select
                    name="condition"
                    id="condition"
                    class="w-full px-3 py-2 rounded-[2px] bg-canvas border border-line hover:border-line text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
                >
                    <option value="all" {{ ($filters['condition'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua Kondisi</option>
                    @foreach($conditions as $cond)
                        <option value="{{ $cond }}" {{ ($filters['condition'] ?? '') === $cond ? 'selected' : '' }}>
                            {{ $cond }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 6. Tombol Aksi Form -->
            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="flex-1 py-2 px-3 rounded-[2px] bg-accent hover:bg-accent text-current text-xs font-semibold transition-colors flex items-center justify-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <span>Filter</span>
                </button>
                <a
                    href="{{ route('reports.monitoring.index') }}"
                    class="py-2 px-3 rounded-[2px] bg-surface hover:bg-surface text-ink hover:text-ink text-xs font-semibold transition-colors border border-line flex items-center justify-center"
                    title="Reset Filter"
                >
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Ringkasan Statistik Laporan (8 Sensor Parameters) -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-base font-semibold text-current flex items-center gap-2">
                    <span class="w-2 h-2 rounded-[2px] bg-accent-soft"></span>
                    Ringkasan Statistik Lingkungan Greenhouse
                </h3>
                <p class="text-xs text-muted mt-0.5">
                    {{ $statistics['aggregation_scope'] }} Total observasi: <strong class="text-accent tabular-nums">{{ number_format($statistics['total_count']) }}</strong> entri.
                    @if($statistics['first_monitored_at'] && $statistics['last_monitored_at'])
                        Rentang: <span class="tabular-nums text-ink">{{ $statistics['first_monitored_at']->format('d M Y') }}</span> s.d. <span class="tabular-nums text-ink">{{ $statistics['last_monitored_at']->format('d M Y') }}</span>.
                    @endif
                </p>
            </div>
        </div>

        <!-- 8 Sensor Parameters Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $sensorCards = [
                    'temperature' => [
                        'label' => 'Suhu Udara',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />',
                        'color' => 'emerald',
                    ],
                    'humidity' => [
                        'label' => 'Kelembapan Udara',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />',
                        'color' => 'teal',
                    ],
                    'soil_moisture' => [
                        'label' => 'Kelembapan Tanah',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />',
                        'color' => 'sky',
                    ],
                    'ph' => [
                        'label' => 'pH Nutrisi/Media',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />',
                        'color' => 'indigo',
                    ],
                    'ec' => [
                        'label' => 'Konduktivitas Listrik (EC)',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />',
                        'color' => 'amber',
                    ],
                    'light_intensity' => [
                        'label' => 'Intensitas Cahaya',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />',
                        'color' => 'yellow',
                    ],
                    'nutrient_temperature' => [
                        'label' => 'Suhu Nutrisi',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
                        'color' => 'cyan',
                    ],
                    'tank_level' => [
                        'label' => 'Level Tandon Nutrisi',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />',
                        'color' => 'blue',
                    ],
                ];
            @endphp

            @foreach($sensorCards as $paramKey => $card)
                @php
                    $pData = $statistics[$paramKey] ?? null;
                    $avg = $pData['avg'] ?? null;
                    $min = $pData['min'] ?? null;
                    $max = $pData['max'] ?? null;
                    $unit = $pData['unit'] ?? '';
                @endphp
                <div class="p-4 rounded-[2px] bg-canvas border border-line  space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-ink">{{ $card['label'] }}</span>
                        <span class="text-xs tabular-nums font-semibold text-muted bg-surface px-2 py-0.5 rounded-[2px]">
                            {{ $unit }}
                        </span>
                    </div>

                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-semibold tabular-nums text-current">
                            {{ $avg !== null ? $avg : 'N/A' }}
                        </span>
                        <span class="text-xs text-muted font-medium">Rata-rata</span>
                    </div>

                    <div class="pt-2 border-t border-line flex items-center justify-between text-xs tabular-nums text-muted">
                        <div>
                            <span>Min:</span>
                            <strong class="text-ink ml-1">{{ $min !== null ? $min : 'N/A' }}</strong>
                        </div>
                        <div>
                            <span>Max:</span>
                            <strong class="text-ink ml-1">{{ $max !== null ? $max : 'N/A' }}</strong>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Alarm & Fuzzy Summary Cards (Periode Terpilih) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Ringkasan Alarm 4 Parameter -->
        <div class="p-5 rounded-[2px] bg-canvas border border-line  space-y-3">
            <div class="flex items-center justify-between border-b border-line pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-[2px] bg-warning-soft text-warning border border-warning/25 flex items-center justify-center font-semibold text-sm">
                        !
                    </div>
                    <div>
                        <h4 class="text-xs sm:text-sm font-semibold text-current">Ringkasan Alarm 4 Parameter</h4>
                        <p class="text-xs text-muted">Insiden batas Suhu, Kelembapan Udara, Kelembapan Tanah, dan pH</p>
                    </div>
                </div>
                <span class="text-xs tabular-nums font-semibold px-2 py-0.5 rounded-[2px] bg-surface text-ink border border-line">
                    {{ $alarmSummary['total'] }} Insiden
                </span>
            </div>

            <div class="grid grid-cols-4 gap-2 text-center text-xs">
                <div class="p-2.5 rounded-[2px] bg-canvas border border-line">
                    <span class="text-xs text-muted block ">Critical</span>
                    <strong class="text-sm tabular-nums font-semibold {{ $alarmSummary['critical'] > 0 ? 'text-danger' : 'text-ink' }}">
                        {{ $alarmSummary['critical'] }}
                    </strong>
                </div>
                <div class="p-2.5 rounded-[2px] bg-canvas border border-line">
                    <span class="text-xs text-muted block ">Warning</span>
                    <strong class="text-sm tabular-nums font-semibold {{ $alarmSummary['warning'] > 0 ? 'text-warning' : 'text-ink' }}">
                        {{ $alarmSummary['warning'] }}
                    </strong>
                </div>
                <div class="p-2.5 rounded-[2px] bg-canvas border border-line">
                    <span class="text-xs text-muted block ">Aktif</span>
                    <strong class="text-sm tabular-nums font-semibold {{ $alarmSummary['active'] > 0 ? 'text-warning' : 'text-ink' }}">
                        {{ $alarmSummary['active'] }}
                    </strong>
                </div>
                <div class="p-2.5 rounded-[2px] bg-canvas border border-line">
                    <span class="text-xs text-muted block ">Resolved</span>
                    <strong class="text-sm tabular-nums font-semibold text-accent">
                        {{ $alarmSummary['resolved'] }}
                    </strong>
                </div>
            </div>
        </div>

        <!-- Ringkasan Logika Fuzzy Mamdani -->
        <div class="p-5 rounded-[2px] bg-canvas border border-line  space-y-3">
            <div class="flex items-center justify-between border-b border-line pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-[2px] bg-surface text-muted border border-line flex items-center justify-center font-semibold text-sm">
                        λ
                    </div>
                    <div>
                        <h4 class="text-xs sm:text-sm font-semibold text-current">Ringkasan Analisis Logika Fuzzy</h4>
                        <p class="text-xs text-muted">Evaluasi kenyamanan mikroklimat Mamdani (Aturan: {{ $fuzzySummary['rule_version'] }})</p>
                    </div>
                </div>
                <span class="text-xs tabular-nums font-semibold px-2 py-0.5 rounded-[2px] bg-surface text-muted border border-line">
                    Rata-rata: {{ $fuzzySummary['avg_score'] !== null ? $fuzzySummary['avg_score'] : 'N/A' }}
                </span>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                <div class="p-2.5 rounded-[2px] bg-canvas border border-line">
                    <span class="text-xs text-muted block ">Kondisi BAIK</span>
                    <strong class="text-sm tabular-nums font-semibold text-accent">{{ $fuzzySummary['by_category']['BAIK'] }}</strong>
                </div>
                <div class="p-2.5 rounded-[2px] bg-canvas border border-line">
                    <span class="text-xs text-muted block ">PERHATIAN</span>
                    <strong class="text-sm tabular-nums font-semibold text-warning">{{ $fuzzySummary['by_category']['PERLU_PERHATIAN'] }}</strong>
                </div>
                <div class="p-2.5 rounded-[2px] bg-canvas border border-line">
                    <span class="text-xs text-muted block ">Kondisi BURUK</span>
                    <strong class="text-sm tabular-nums font-semibold text-danger">{{ $fuzzySummary['by_category']['BURUK'] }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Detail Monitoring -->
    <div class="p-5 sm:p-6 rounded-[2px] bg-canvas border border-line  space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-line pb-4">
            <div>
                <h3 class="text-sm sm:text-base font-semibold text-current flex items-center gap-2">
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    Tabel Rekaman Observasi Terverifikasi
                </h3>
                <p class="text-xs text-muted mt-0.5">
                    Menampilkan <span class="text-accent font-semibold">{{ $monitorings->count() }}</span> dari total <span class="text-current font-semibold">{{ $monitorings->total() }}</span> rekaman hasil filter.
                </p>
            </div>
        </div>

        @if($monitorings->total() > 500)
            <div class="p-3.5 rounded-[2px] bg-surface border border-line flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-muted">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>
                        <strong class="text-muted">Catatan Ekspor PDF:</strong> PDF dibatasi maksimal 500 observasi terbaru untuk menjaga performa memori render. Gunakan <strong>Ekspor CSV</strong> untuk mengunduh seluruh data ({{ number_format($monitorings->total()) }} observasi).
                    </span>
                </div>
                <a href="{{ route('reports.monitoring.export.csv', request()->all()) }}" class="shrink-0 self-start sm:self-auto px-3 py-1 rounded-[2px] bg-surface hover:bg-surface text-muted border border-line font-semibold text-xs transition-colors flex items-center gap-1.5">
                    <span>Unduh CSV Lengkap</span>
                    <span>&rarr;</span>
                </a>
            </div>
        @endif

        @if($monitorings->isEmpty())
            <div class="p-8 rounded-[2px] bg-canvas border border-dashed border-line text-center space-y-2">
                <div class="w-12 h-12 rounded-[2px] bg-surface text-muted flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h4 class="text-sm font-semibold text-current">Tidak Ada Rekaman Monitoring Ditemukan</h4>
                <p class="text-xs text-muted max-w-md mx-auto">
                    Tidak ditemukan data observasi yang memenuhi kriteria filter tanggal, tanaman, atau sumber data yang dipilih. Silakan sesuaikan kriteria filter atau klik "Reset".
                </p>
                <div class="pt-2">
                    <a href="{{ route('reports.monitoring.index') }}" class="inline-flex items-center px-4 py-2 rounded-[2px] bg-surface hover:bg-surface text-ink text-xs font-semibold border border-line transition-colors">
                        Reset Semua Filter
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto rounded-[2px] border border-line bg-canvas">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-line text-xs   text-muted bg-canvas whitespace-nowrap">
                            <th class="py-3 px-3.5 font-semibold">Waktu</th>
                            <th class="py-3 px-3.5 font-semibold">Tanaman</th>
                            <th class="py-3 px-3 font-semibold">Sumber</th>
                            <th class="py-3 px-3 font-semibold">Suhu (°C)</th>
                            <th class="py-3 px-3 font-semibold">Lembap (%)</th>
                            <th class="py-3 px-3 font-semibold">Tanah (%)</th>
                            <th class="py-3 px-3 font-semibold">pH</th>
                            <th class="py-3 px-3 font-semibold">EC (mS)</th>
                            <th class="py-3 px-3 font-semibold">Cahaya (lux)</th>
                            <th class="py-3 px-3 font-semibold">Suhu Nutrisi</th>
                            <th class="py-3 px-3 font-semibold">Tandon (%)</th>
                            <th class="py-3 px-3.5 font-semibold">Kondisi</th>
                            <th class="py-3 px-3.5 font-semibold">Fuzzy</th>
                            <th class="py-3 px-3.5 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line tabular-nums">
                        @foreach($monitorings as $m)
                            @php
                                $fuzzy = $m->latestFuzzyInference;
                                $fuzzyScore = $fuzzy?->fuzzy_score ?? $m->fuzzy_score;
                                $fuzzyCat = $fuzzy?->fuzzy_category ?? $m->fuzzy_label;
                            @endphp
                            <tr class="hover:bg-canvas transition-colors">
                                <td class="py-2.5 px-3.5 text-ink whitespace-nowrap">
                                    {{ $m->monitored_at ? $m->monitored_at->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="py-2.5 px-3.5 font-sans font-medium text-current whitespace-nowrap">
                                    {{ $m->plant?->name ?? 'N/A' }}
                                    @if($m->plant?->location)
                                        <span class="block text-xs tabular-nums text-muted">{{ $m->plant->location }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 font-sans whitespace-nowrap">
                                    @php
                                        $sourceBadge = match($m->source) {
                                            'google_sheets' => 'bg-surface text-muted border-line',
                                            'api'           => 'bg-surface text-muted border-line',
                                            'simulator'     => 'bg-warning-soft text-warning border-warning/25',
                                            default         => 'bg-accent-soft text-accent border-line',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-[2px] text-xs font-semibold border {{ $sourceBadge }}">
                                        {{ $m->source ?? 'web' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-ink">
                                    {{ $m->temperature !== null ? number_format($m->temperature, 1) : '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-ink">
                                    {{ $m->humidity !== null ? number_format($m->humidity, 1) : '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-ink">
                                    {{ $m->soil_moisture !== null ? number_format($m->soil_moisture, 1) : '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-ink">
                                    {{ $m->ph !== null ? number_format($m->ph, 2) : '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-ink text-xs">
                                    {{ $m->ec !== null ? number_format($m->ec, 2) : '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-ink text-xs">
                                    {{ $m->light_intensity !== null ? number_format($m->light_intensity, 0) : '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-ink text-xs">
                                    {{ $m->nutrient_temperature !== null ? number_format($m->nutrient_temperature, 1) : '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-ink text-xs">
                                    {{ $m->tank_level !== null ? number_format($m->tank_level, 0) : '-' }}
                                </td>
                                <td class="py-2.5 px-3.5 font-sans whitespace-nowrap">
                                    <span class="text-xs text-ink">{{ $m->plant_condition ?? '-' }}</span>
                                </td>
                                <td class="py-2.5 px-3.5 font-sans whitespace-nowrap">
                                    @if($fuzzyScore !== null)
                                        <span class="tabular-nums font-semibold {{ $fuzzyScore >= 65 ? 'text-accent' : ($fuzzyScore <= 40 ? 'text-danger' : 'text-warning') }}">
                                            {{ number_format($fuzzyScore, 1) }}
                                        </span>
                                        <span class="text-xs text-muted block">{{ $fuzzyCat ?? '' }}</span>
                                    @else
                                        <span class="text-muted tabular-nums text-xs">-</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3.5 text-right font-sans whitespace-nowrap">
                                    <a
                                        href="{{ route('monitorings.show', $m->id) }}"
                                        class="inline-flex items-center px-2.5 py-1 rounded-[2px] text-xs font-semibold bg-surface hover:bg-surface text-ink hover:text-ink border border-line transition-colors"
                                    >
                                        Detail &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            <div class="pt-2">
                {{ $monitorings->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
