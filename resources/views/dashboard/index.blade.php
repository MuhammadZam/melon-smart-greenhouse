@extends('layouts.app')

@section('title', 'Dashboard Monitoring & Analytics')

@section('content')
<div class="space-y-6">

    <!-- Top Action & Filter Bar Header -->
    <div class="ui-page-header flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-[2px] text-xs font-semibold   {{ $user->isAdmin() ? 'bg-accent-soft text-accent border border-line' : 'bg-accent-soft text-accent border border-line' }}">
                    <span class="w-1.5 h-1.5 rounded-[2px] {{ $user->isAdmin() ? 'bg-accent-soft' : 'bg-accent-soft' }} mr-1.5 "></span>
                    {{ $user->isAdmin() ? 'Mode Administrator' : 'Mode Pengamat (User)' }}
                </span>
                <span class="text-xs text-muted">Berdasarkan rekaman tersimpan</span>
            </div>
            <h2 class="ui-page-title text-xl sm:text-2xl font-semibold text-current tracking-tight">
                Ringkasan Pemantauan <span class="    text-ink">Tanaman Melon</span>
            </h2>
            <p class="text-xs sm:text-sm text-muted mt-0.5">
                Visualisasi komprehensif mikroklimat greenhouse, dinamika nutrisi, dan perkembangan vegetatif melon.
            </p>
        </div>

        <div class="flex items-center gap-2.5 self-start md:self-auto">
            <a
                href="{{ route('monitorings.index') }}"
                class="px-3.5 py-2 rounded-[2px] bg-surface hover:bg-surface text-ink border border-line text-xs font-semibold flex items-center gap-1.5 transition-colors hover:text-ink"
            >
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span>Kelola Monitoring</span>
            </a>
            <a
                href="{{ route('monitorings.create') }}"
                class="px-3.5 py-2 rounded-[2px] bg-accent hover:bg-accent text-current text-xs font-semibold flex items-center gap-1.5 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>+ Catat Data</span>
            </a>
        </div>
    </div>

    <!-- SECTION A — GREENHOUSE OVERVIEW -->
    <div class="space-y-2" id="greenhouse-overview-section">
        <div class="flex items-center justify-between px-1">
            <span class="text-xs font-semibold   text-muted flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-[2px] bg-accent-soft"></span>
                Ringkasan greenhouse
            </span>
            <span class="text-xs text-muted tabular-nums">
                Sinkronisasi: {{ $latestMonitoring ? $latestMonitoring->monitored_at->diffForHumans() : 'Belum Ada Data' }}
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Total & Aktif Tanaman -->
            <x-summary-card
                title="Total Tanaman"
                :value="$totalPlants"
                unit="Tanaman"
                color="emerald"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </x-slot:icon>
                <x-slot:subtitle>
                    <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Aktif: <strong class="text-accent tabular-nums">{{ $activePlantsCount }}</strong> &bull; Panen: <span class="tabular-nums text-ink">{{ $plantStatusCounts['harvested'] }}</span>
                </x-slot:subtitle>
            </x-summary-card>

            <!-- Card 2: Total Monitoring & Hari Ini -->
            <x-summary-card
                title="Total Monitoring"
                :value="$totalMonitorings"
                unit="Entri Rekaman"
                color="teal"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </x-slot:icon>
                <x-slot:subtitle>
                    <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Hari ini: <strong class="text-accent tabular-nums">{{ $todayMonitoringCount }}</strong> observasi
                </x-slot:subtitle>
            </x-summary-card>

            <!-- Card 3: Waktu Pembaruan Terakhir -->
            <x-summary-card
                title="Monitoring Terakhir"
                color="indigo"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </x-slot:icon>
                <div class="text-base sm:text-lg font-semibold text-current tracking-tight truncate">
                    @if ($latestMonitoring)
                        {{ $latestMonitoring->monitored_at->format('d M Y, H:i') }}
                    @else
                        Belum Ada Data
                    @endif
                </div>
                <div class="text-xs text-muted mt-2 flex items-center justify-between">
                    @if ($latestMonitoring)
                        <span class="text-ink tabular-nums">{{ $latestMonitoring->monitored_at->diffForHumans() }}</span>
                        <span class="px-1.5 py-0.2 rounded-[2px] tabular-nums text-xs  bg-surface text-ink border border-line">
                            {{ $latestMonitoring->source ?? 'manual' }}
                        </span>
                    @else
                        <span>Menunggu input pertama</span>
                    @endif
                </div>
            </x-summary-card>

            <!-- Card 4: Status Sumber Data -->
            <x-summary-card
                id="source-summary"
                title="Sumber Data Telemetri"
                color="sky"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6-8h6"></path>
                    </svg>
                </x-slot:icon>
                <div class="space-y-1 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-muted">Web Form:</span>
                        <strong class="tabular-nums text-accent">{{ $sourceDistribution['web'] ?? 0 }}</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">REST API:</span>
                        <strong class="tabular-nums text-muted">{{ $sourceDistribution['api'] ?? 0 }}</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">Google Sheets:</span>
                        <strong class="tabular-nums text-muted">{{ $sourceDistribution['google_sheets'] ?? 0 }}</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">Simulator:</span>
                        <strong class="tabular-nums text-warning">{{ $sourceDistribution['simulator'] ?? 0 }}</strong>
                    </div>
                </div>
            </x-summary-card>
        </div>
    </div>

    <!-- SECTION B — SENSOR OVERVIEW (SMART GREENHOUSE) -->
    <div class="ui-section space-y-5" id="sensor-overview-section">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-line pb-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-semibold   text-accent flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-[2px] bg-accent-soft "></span>
                        Pembacaan terbaru
                    </span>
                    <span class="px-2 py-0.5 rounded-[2px] text-xs tabular-nums font-semibold bg-surface text-ink border border-line">
                        8 parameter sensor
                    </span>
                </div>
                <h3 class="text-base sm:text-lg font-semibold text-current tracking-tight">
                    Sensor lingkungan
                </h3>
                <p class="text-xs text-muted mt-0.5">
                    @if($latestMonitoring)
                        Nilai telemetri aktual terakhir tercatat: <span class="text-ink tabular-nums font-semibold">{{ $latestMonitoring->monitored_at->format('d M Y, H:i:s') }} WIB</span>
                        &bull; Tanaman: <strong class="text-accent">{{ $latestMonitoring->plant?->name ?? 'Melon' }}</strong>
                        &bull; Sumber: <span class=" text-ink tabular-nums text-xs">{{ $latestMonitoring->source ?? 'manual' }}</span>
                    @else
                        Belum ada data sensor tercatat di database. Silakan catat data baru atau jalankan sinkronisasi telemetri.
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('reports.monitoring.index') }}"
                   class="px-3.5 py-1.5 rounded-[2px] bg-surface hover:bg-surface text-ink border border-line text-xs font-semibold flex items-center gap-1.5 transition-colors">
                    <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Laporan Lengkap &amp; Ekspor</span>
                </a>
            </div>
        </div>

        <!-- 8 Sensor Parameters Grid -->
        <div class="ui-sensor-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Suhu Udara -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line hover:border-line transition-colors">
                <div class="flex items-center gap-2 text-muted mb-2">
                    <svg class="w-4 h-4 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="text-xs font-semibold">Suhu Udara</span>
                </div>
                <div class="text-2xl font-semibold tabular-nums text-current tracking-tight">
                    {{ $latestMonitoring && $latestMonitoring->temperature !== null ? number_format($latestMonitoring->temperature, 1, ',', '.') . '°C' : 'N/A' }}
                </div>
                <div class="text-xs text-muted tabular-nums mt-1">
                    Ambang: {{ $thresholds['temperature']['min'] }}°C - {{ $thresholds['temperature']['max'] }}°C
                </div>
                @if($latestMonitoring)<time datetime="{{ $latestMonitoring->monitored_at->toIso8601String() }}">{{ $latestMonitoring->monitored_at->format('d M Y, H:i') }} WIB</time>@endif
            </div>

            <!-- 2. Kelembapan Udara -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line hover:border-line transition-colors">
                <div class="flex items-center gap-2 text-muted mb-2">
                    <svg class="w-4 h-4 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                    </svg>
                    <span class="text-xs font-semibold">Kelembapan Udara</span>
                </div>
                <div class="text-2xl font-semibold tabular-nums text-current tracking-tight">
                    {{ $latestMonitoring && $latestMonitoring->humidity !== null ? number_format($latestMonitoring->humidity, 0, ',', '.') . '%' : 'N/A' }}
                </div>
                <div class="text-xs text-muted tabular-nums mt-1">
                    Ambang: {{ $thresholds['humidity']['min'] }}% - {{ $thresholds['humidity']['max'] }}%
                </div>
                @if($latestMonitoring)<time datetime="{{ $latestMonitoring->monitored_at->toIso8601String() }}">{{ $latestMonitoring->monitored_at->format('d M Y, H:i') }} WIB</time>@endif
            </div>

            <!-- 3. Kelembapan Tanah -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line hover:border-line transition-colors">
                <div class="flex items-center gap-2 text-muted mb-2">
                    <svg class="w-4 h-4 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span class="text-xs font-semibold">Kelembapan Tanah</span>
                </div>
                <div class="text-2xl font-semibold tabular-nums text-current tracking-tight">
                    {{ $latestMonitoring && $latestMonitoring->soil_moisture !== null ? number_format($latestMonitoring->soil_moisture, 0, ',', '.') . '%' : 'N/A' }}
                </div>
                <div class="text-xs text-muted tabular-nums mt-1">
                    Ambang: {{ $thresholds['soil_moisture']['min'] }}% - {{ $thresholds['soil_moisture']['max'] }}%
                </div>
                @if($latestMonitoring)<time datetime="{{ $latestMonitoring->monitored_at->toIso8601String() }}">{{ $latestMonitoring->monitored_at->format('d M Y, H:i') }} WIB</time>@endif
            </div>

            <!-- 4. pH Nutrisi/Media -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line hover:border-line transition-colors">
                <div class="flex items-center gap-2 text-muted mb-2">
                    <svg class="w-4 h-4 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                    </svg>
                    <span class="text-xs font-semibold">pH Nutrisi/Media</span>
                </div>
                <div class="text-2xl font-semibold tabular-nums text-current tracking-tight">
                    {{ $latestMonitoring && $latestMonitoring->ph !== null ? number_format($latestMonitoring->ph, 1, ',', '.') : 'N/A' }}
                </div>
                <div class="text-xs text-muted tabular-nums mt-1">
                    Ambang: {{ $thresholds['ph']['min'] }} - {{ $thresholds['ph']['max'] }}
                </div>
                @if($latestMonitoring)<time datetime="{{ $latestMonitoring->monitored_at->toIso8601String() }}">{{ $latestMonitoring->monitored_at->format('d M Y, H:i') }} WIB</time>@endif
            </div>

            <!-- 5. Konduktivitas Listrik (EC) -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line hover:border-warning/25 transition-colors">
                <div class="flex items-center gap-2 text-muted mb-2">
                    <svg class="w-4 h-4 text-warning shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span class="text-xs font-semibold">Konduktivitas Listrik (EC)</span>
                </div>
                <div class="text-2xl font-semibold tabular-nums text-current tracking-tight">
                    {{ $latestMonitoring && $latestMonitoring->ec !== null ? number_format($latestMonitoring->ec, 2, ',', '.') . ' mS/cm' : 'N/A' }}
                </div>
                <div class="text-xs text-muted tabular-nums mt-1">
                    Ambang: {{ $thresholds['ec']['min'] }} - {{ $thresholds['ec']['max'] }} mS/cm
                </div>
                @if($latestMonitoring)<time datetime="{{ $latestMonitoring->monitored_at->toIso8601String() }}">{{ $latestMonitoring->monitored_at->format('d M Y, H:i') }} WIB</time>@endif
            </div>

            <!-- 6. Intensitas Cahaya -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line hover:border-warning/25 transition-colors">
                <div class="flex items-center gap-2 text-muted mb-2">
                    <svg class="w-4 h-4 text-warning shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span class="text-xs font-semibold">Intensitas Cahaya</span>
                </div>
                <div class="text-2xl font-semibold tabular-nums text-current tracking-tight">
                    {{ $latestMonitoring && $latestMonitoring->light_intensity !== null ? number_format($latestMonitoring->light_intensity, 0, ',', '.') . ' lux' : 'N/A' }}
                </div>
                <div class="text-xs text-muted tabular-nums mt-1">
                    Ambang: {{ number_format($thresholds['light_intensity']['min'], 0, ',', '.') }} - {{ number_format($thresholds['light_intensity']['max'], 0, ',', '.') }} lux
                </div>
                @if($latestMonitoring)<time datetime="{{ $latestMonitoring->monitored_at->toIso8601String() }}">{{ $latestMonitoring->monitored_at->format('d M Y, H:i') }} WIB</time>@endif
            </div>

            <!-- 7. Suhu Nutrisi -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line hover:border-line transition-colors">
                <div class="flex items-center gap-2 text-muted mb-2">
                    <svg class="w-4 h-4 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-xs font-semibold">Suhu Nutrisi</span>
                </div>
                <div class="text-2xl font-semibold tabular-nums text-current tracking-tight">
                    {{ $latestMonitoring && $latestMonitoring->nutrient_temperature !== null ? number_format($latestMonitoring->nutrient_temperature, 1, ',', '.') . '°C' : 'N/A' }}
                </div>
                <div class="text-xs text-muted tabular-nums mt-1">
                    Ambang: {{ $thresholds['nutrient_temperature']['min'] }}°C - {{ $thresholds['nutrient_temperature']['max'] }}°C
                </div>
                @if($latestMonitoring)<time datetime="{{ $latestMonitoring->monitored_at->toIso8601String() }}">{{ $latestMonitoring->monitored_at->format('d M Y, H:i') }} WIB</time>@endif
            </div>

            <!-- 8. Level Tandon Nutrisi -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line hover:border-line transition-colors">
                <div class="flex items-center gap-2 text-muted mb-2">
                    <svg class="w-4 h-4 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span class="text-xs font-semibold">Level Tandon Nutrisi</span>
                </div>
                <div class="text-2xl font-semibold tabular-nums text-current tracking-tight">
                    {{ $latestMonitoring && $latestMonitoring->tank_level !== null ? number_format($latestMonitoring->tank_level, 0, ',', '.') . '%' : 'N/A' }}
                </div>
                <div class="text-xs text-muted tabular-nums mt-1">
                    Ambang: {{ $thresholds['tank_level']['min'] }}% - {{ $thresholds['tank_level']['max'] }}%
                </div>
                @if($latestMonitoring)<time datetime="{{ $latestMonitoring->monitored_at->toIso8601String() }}">{{ $latestMonitoring->monitored_at->format('d M Y, H:i') }} WIB</time>@endif
            </div>
        </div>
    </div>

    <!-- ALARM & NOTIFICATION PANEL (PHASE 12) -->
    <div class="ui-section space-y-5" id="alarm-panel">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-line pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-[2px] {{ $criticalAlarms > 0 ? 'bg-danger-soft text-danger border border-danger/25' : ($activeAlarms > 0 ? 'bg-warning-soft text-warning border border-warning/25' : 'bg-accent-soft text-accent border border-line') }} flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm sm:text-base font-semibold text-current">Status Alarm &amp; Notifikasi Sensor</h3>
                        @if($criticalAlarms > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-danger-soft text-danger border border-danger/25 ">
                                {{ $criticalAlarms }} Critical
                            </span>
                        @elseif($activeAlarms > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-warning-soft text-warning border border-warning/25">
                                {{ $activeAlarms }} Warning
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-accent-soft text-accent border border-line">
                                Semua Sensor Normal
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-muted mt-0.5">Pemantauan deviasi ambang batas sensor mikroklimat dan level tandon secara otomatis.</p>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <a href="{{ route('alarms.index') }}"
                   class="px-3 py-1.5 rounded-[2px] bg-surface hover:bg-surface text-ink border border-line text-xs font-semibold flex items-center gap-1.5 transition-colors">
                    <span>Semua Riwayat</span>
                    <svg class="w-3.5 h-3.5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
                @if(auth()->check() && auth()->user()->isAdmin())
                    <a href="{{ route('alarms.thresholds') }}"
                       class="px-3 py-1.5 rounded-[2px] bg-accent-soft hover:bg-accent-soft text-accent border border-line text-xs font-semibold flex items-center gap-1.5 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>Kelola Ambang Batas</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Metrics Mini Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="p-3.5 rounded-[2px] bg-canvas border border-line flex items-center justify-between">
                <div>
                    <span class="text-xs   font-semibold text-muted block">Alarm Aktif</span>
                    <span class="text-xl font-semibold tabular-nums {{ $activeAlarms > 0 ? 'text-warning' : 'text-ink' }}">{{ $activeAlarms }}</span>
                </div>
                <span class="w-8 h-8 rounded-[2px] bg-warning-soft text-warning flex items-center justify-center text-xs font-semibold border border-warning/25">
                    !
                </span>
            </div>

            <div class="p-3.5 rounded-[2px] bg-canvas border border-line flex items-center justify-between">
                <div>
                    <span class="text-xs   font-semibold text-muted block">Alarm Kritis (Critical)</span>
                    <span class="text-xl font-semibold tabular-nums {{ $criticalAlarms > 0 ? 'text-danger' : 'text-ink' }}">{{ $criticalAlarms }}</span>
                </div>
                <span class="w-8 h-8 rounded-[2px] bg-danger-soft text-danger flex items-center justify-center text-xs font-semibold border border-danger/25">
                    !!
                </span>
            </div>

            <div class="p-3.5 rounded-[2px] bg-canvas border border-line flex items-center justify-between">
                <div>
                    <span class="text-xs   font-semibold text-muted block">Diselesaikan Hari Ini</span>
                    <span class="text-xl font-semibold tabular-nums text-accent">{{ $resolvedToday }}</span>
                </div>
                <span class="w-8 h-8 rounded-[2px] bg-accent-soft text-accent flex items-center justify-center text-xs font-semibold border border-line">
                    ✓
                </span>
            </div>
        </div>

        {{-- Daftar Alarm Terbaru / Empty State --}}
        <div>
            @if($latestAlarms->isEmpty())
                <div class="p-6 rounded-[2px] bg-canvas border border-dashed border-line text-center">
                    <div class="w-10 h-10 rounded-[2px] bg-accent-soft text-accent flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <p class="text-xs font-semibold text-current">Semua Parameter Dalam Kondisi Optimal</p>
                    <p class="text-xs text-muted mt-0.5">Tidak ada alarm aktif saat ini. Semua sensor beroperasi di dalam rentang ambang batas agronomis.</p>
                </div>
            @else
                <div class="overflow-x-auto rounded-[2px] border border-line bg-canvas">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-line text-xs   text-muted bg-canvas">
                                <th class="py-2.5 px-4 font-semibold">Tanaman</th>
                                <th class="py-2.5 px-4 font-semibold">Parameter</th>
                                <th class="py-2.5 px-4 font-semibold">Nilai Terakhir</th>
                                <th class="py-2.5 px-4 font-semibold">Ambang Batas</th>
                                <th class="py-2.5 px-4 font-semibold">Severity</th>
                                <th class="py-2.5 px-4 font-semibold">Status</th>
                                <th class="py-2.5 px-4 font-semibold">Waktu Terdeteksi</th>
                                <th class="py-2.5 px-4 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line text-xs">
                            @foreach($latestAlarms as $alarm)
                                <tr class="hover:bg-canvas transition-colors">
                                    <td class="py-2.5 px-4 font-medium text-current">
                                        {{ $alarm->plant?->name ?? 'N/A' }}
                                    </td>
                                    <td class="py-2.5 px-4 text-ink">
                                        <div class="font-medium">{{ $alarm->parameter_display_name }}</div>
                                        <div class="text-xs text-muted tabular-nums">{{ $alarm->parameter_key }}</div>
                                    </td>
                                    <td class="py-2.5 px-4 tabular-nums font-semibold {{ $alarm->isCritical() ? 'text-danger' : 'text-warning' }}">
                                        {{ number_format($alarm->last_abnormal_value ?? $alarm->sensor_value, 2) }} {{ $alarm->unit }}
                                    </td>
                                    <td class="py-2.5 px-4 tabular-nums text-xs text-muted">
                                        {{ number_format($alarm->threshold_min, 2) }} - {{ number_format($alarm->threshold_max, 2) }} {{ $alarm->unit }}
                                    </td>
                                    <td class="py-2.5 px-4">
                                        @if($alarm->isCritical())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-danger-soft text-danger border border-danger/25">
                                                Critical
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-warning-soft text-warning border border-warning/25">
                                                Warning
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-4">
                                        @if($alarm->isActive())
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-warning">
                                                <span class="w-1.5 h-1.5 rounded-[2px] bg-surface "></span>
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-accent">
                                                <span class="w-1.5 h-1.5 rounded-[2px] bg-accent-soft"></span>
                                                Resolved
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-4 text-xs text-muted">
                                        {{ $alarm->first_detected_at?->diffForHumans() ?? '-' }}
                                    </td>
                                    <td class="py-2.5 px-4 text-right">
                                        <a href="{{ route('alarms.show', $alarm->id) }}"
                                           class="inline-flex items-center px-2.5 py-1 rounded-[2px] text-xs font-semibold bg-surface hover:bg-surface text-ink hover:text-ink border border-line transition-colors">
                                            Detail &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- SIMULATED ACTUATORS SUMMARY WIDGET (PHASE 13) -->
    <div class="ui-section space-y-5" id="actuator-summary-widget">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-line pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-[2px] bg-accent-soft text-accent border border-line flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm sm:text-base font-semibold text-current">Status Perangkat Otomasi (Mode Simulasi)</h3>
                        <span class="px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-warning-soft text-warning border border-warning/25">
                            Simulasi DB
                        </span>
                    </div>
                    <p class="text-xs text-muted mt-0.5">Ringkasan status operasional pompa irigasi fertigasi dan kipas sirkulasi greenhouse.</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('actuators.index') }}"
                   class="px-3 py-1.5 rounded-[2px] bg-accent-soft hover:bg-accent-soft text-accent border border-line text-xs font-semibold flex items-center gap-1.5 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    </svg>
                    <span>Buka Panel Kontrol</span>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Pompa Irigasi --}}
            @php
                $pump = $actuatorSummary['pump'] ?? null;
                $pumpOn = $pump && $pump->isOn();
            @endphp
            <div class="p-4 rounded-[2px] bg-canvas border border-line flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-[2px] {{ $pumpOn ? 'bg-accent-soft text-accent border border-line' : 'bg-surface text-muted border border-line' }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-current">{{ $pump ? $pump->name : 'Pompa Irigasi Utama' }}</div>
                        <div class="text-xs text-muted tabular-nums mt-0.5">
                            Status: <span class="font-semibold {{ $pumpOn ? 'text-accent' : 'text-muted' }}">{{ $pumpOn ? 'AKTIF (ON)' : 'NON-AKTIF (OFF)' }}</span>
                        </div>
                    </div>
                </div>
                <div class="text-right text-xs text-muted">
                    <span class="block text-xs  font-semibold text-muted">Pembaruan</span>
                    <span class="tabular-nums text-ink">{{ $pump?->last_changed_at ? $pump->last_changed_at->diffForHumans() : '-' }}</span>
                </div>
            </div>

            {{-- Kipas Exhaust --}}
            @php
                $fan = $actuatorSummary['fan'] ?? null;
                $fanOn = $fan && $fan->isOn();
            @endphp
            <div class="p-4 rounded-[2px] bg-canvas border border-line flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-[2px] {{ $fanOn ? 'bg-accent-soft text-accent border border-line' : 'bg-surface text-muted border border-line' }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 {{ $fanOn ? '' : '' }}" style="animation-duration: 3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l2-1m-2 1l2 1m-2 1v-2.5M18 18l-2 1m2-1l-2-1m2 1v-2.5"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-current">{{ $fan ? $fan->name : 'Kipas Exhaust Sirkulasi' }}</div>
                        <div class="text-xs text-muted tabular-nums mt-0.5">
                            Status: <span class="font-semibold {{ $fanOn ? 'text-accent' : 'text-muted' }}">{{ $fanOn ? 'AKTIF (ON)' : 'NON-AKTIF (OFF)' }}</span>
                        </div>
                    </div>
                </div>
                <div class="text-right text-xs text-muted">
                    <span class="block text-xs  font-semibold text-muted">Pembaruan</span>
                    <span class="tabular-nums text-ink">{{ $fan?->last_changed_at ? $fan->last_changed_at->diffForHumans() : '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 1.3 ANALISIS KONDISI GREENHOUSE — FUZZY LOGIC (PHASE 15) -->
    <div class="ui-section space-y-5" id="fuzzy-analysis-widget">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-line pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-[2px] bg-surface text-muted border border-line flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm sm:text-base font-semibold text-current">Analisis Kondisi Greenhouse — Fuzzy Logic</h3>
                        @if($latestFuzzy)
                            @php
                                $statusBadge = match($latestFuzzy->inference_status) {
                                    'AVAILABLE' => 'bg-accent-soft text-accent border-line',
                                    'STALE' => 'bg-warning-soft text-warning border-warning/25',
                                    'INSUFFICIENT_DATA' => 'bg-surface text-ink border-line',
                                    'INSUFFICIENT_RULE_COVERAGE' => 'bg-surface text-muted border-line',
                                    'ERROR' => 'bg-danger-soft text-danger border-danger/25',
                                    default => 'bg-surface text-ink border-line',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-[2px] text-xs tabular-nums font-semibold border {{ $statusBadge }}">
                                {{ $latestFuzzy->inference_status }}
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-[2px] text-xs tabular-nums font-semibold bg-surface text-muted border border-line">
                                PENDING
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-muted mt-0.5">Penilaian cerdas kenyamanan mikroklimat tanaman melon berbasis inferensi Mamdani.</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('fuzzy.history') }}"
                   class="px-3 py-1.5 rounded-[2px] bg-surface hover:bg-surface text-ink border border-line text-xs font-semibold flex items-center gap-1.5 transition-colors">
                    <span>Riwayat Lengkap</span>
                    <svg class="w-3.5 h-3.5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>

        {{-- Label Peringatan Provisional --}}
        <div class="p-3.5 rounded-[2px] bg-warning-soft border border-warning/25 flex items-start gap-2.5 text-warning text-xs">
            <svg class="w-4 h-4 text-warning shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <span class="font-semibold">Aturan Fuzzy Simulasi — Belum Divalidasi Tim Elektro.</span>
                <span class="text-warning ml-1">Hasil inferensi berstatus provisional untuk pengujian perangkat lunak dan tidak merepresentasikan diagnosis medis/agronomis tanaman.</span>
            </div>
        </div>

        @if($latestFuzzy && $latestFuzzy->fuzzy_score !== null)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                {{-- Score Card --}}
                <div class="p-4 rounded-[2px] bg-canvas border border-line flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold   text-muted">Skor Kondisi</span>
                            @php
                                $catBadge = match($latestFuzzy->fuzzy_category) {
                                    'BAIK' => 'bg-accent-soft text-accent border-line',
                                    'PERLU_PERHATIAN' => 'bg-warning-soft text-warning border-warning/25',
                                    'BURUK' => 'bg-danger-soft text-danger border-danger/25',
                                    default => 'bg-surface text-ink border-line',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-[2px] text-xs font-semibold border {{ $catBadge }}">
                                {{ $latestFuzzy->fuzzy_category }}
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-4xl font-semibold text-current tabular-nums">{{ number_format($latestFuzzy->fuzzy_score, 1) }}</span>
                            <span class="text-xs text-muted">/ 100</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-line text-xs text-muted space-y-1 tabular-nums">
                        <div class="flex justify-between"><span>Versi Rule:</span> <strong class="text-ink">{{ $latestFuzzy->rule_version }}</strong></div>
                        <div class="flex justify-between"><span>Sumber:</span> <strong class="text-ink">{{ $latestFuzzy->inference_source }}</strong></div>
                        <div class="flex justify-between"><span>Waktu:</span> <strong class="text-ink">{{ $latestFuzzy->inferred_at ? $latestFuzzy->inferred_at->format('d M H:i') : '-' }}</strong></div>
                    </div>
                </div>

                {{-- Kesimpulan & Rekomendasi --}}
                <div class="lg:col-span-2 p-4 rounded-[2px] bg-canvas border border-line flex flex-col justify-between">
                    <div>
                        <div class="text-xs font-semibold   text-muted mb-1 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-[2px] bg-accent-soft"></span>
                            Kesimpulan Analisis
                        </div>
                        <p class="text-xs text-ink leading-relaxed">
                            {{ $latestFuzzy->conclusion }}
                        </p>

                        <div class="text-xs font-semibold   text-muted mt-3 mb-1 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-[2px] bg-accent-soft"></span>
                            Rekomendasi Tindakan
                        </div>
                        <p class="text-xs text-accent leading-relaxed">
                            {{ $latestFuzzy->recommendation }}
                        </p>
                    </div>

                    <div class="mt-3 pt-3 border-t border-line flex items-center justify-between text-xs text-muted">
                        <span>Tanaman: <strong class="text-current">{{ $latestFuzzy->plant->name ?? 'Melon' }}</strong></span>
                        <a href="{{ route('fuzzy.show', $latestFuzzy->id) }}" class="text-muted hover:text-muted font-semibold flex items-center gap-1">
                            Lihat Snapshot Matematika &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="p-6 rounded-[2px] bg-canvas border border-dashed border-line text-center">
                <div class="w-10 h-10 rounded-[2px] bg-surface text-muted flex items-center justify-center mx-auto mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-current">Belum Ada Analisis Logika Fuzzy</p>
                <p class="text-xs text-muted mt-0.5">Analisis kondisi greenhouse akan dievaluasi secara otomatis saat data observasi baru tercatat.</p>
            </div>
        @endif
    </div>

    <!-- SECTION G: STATUS KESEHATAN INTEGRASI & KESEGARAN TELEMETRI -->
    <div class="ui-section space-y-5" id="integration-health-section">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-line pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-[2px] bg-surface text-muted border border-line flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm sm:text-base font-semibold text-current">Status Integrasi Google Sheets &amp; Kesegaran Telemetri</h3>
                        @php
                            $isConfigured = $googleSheetsConfig && $googleSheetsConfig->isConfigured();
                            $syncStatus = $googleSheetsConfig?->last_status ?? 'IDLE';
                            $freshness = $googleSheetsConfig?->freshnessStatus() ?? 'no_data';
                        @endphp
                        <span class="px-2 py-0.5 rounded-[2px] text-xs tabular-nums font-semibold border {{ $isConfigured ? 'bg-accent-soft text-accent border-line' : 'bg-surface text-muted border-line' }}">
                            {{ $isConfigured ? 'CONFIGURED' : 'NOT_CONFIGURED' }}
                        </span>
                        <span class="px-2 py-0.5 rounded-[2px] text-xs tabular-nums font-semibold border {{ $freshness === 'connected' ? 'bg-accent-soft text-accent border-line' : ($freshness === 'stale' ? 'bg-warning-soft text-warning border-warning/25' : ($freshness === 'error' ? 'bg-danger-soft text-danger border-danger/25' : 'bg-surface text-muted border-line')) }}">
                            {{ strtoupper($freshness) }}
                        </span>
                    </div>
                    <p class="text-xs text-muted mt-0.5">Section G — Pemantauan kontinuitas jalur data telemetri otomatis dari Google Sheets dan perangkat IoT ESP32.</p>
                </div>
            </div>

            @if(auth()->check() && auth()->user()->isAdmin())
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.integrations.google-sheets.index') }}"
                       class="px-3 py-1.5 rounded-[2px] bg-surface hover:bg-surface text-ink border border-line text-xs font-semibold flex items-center gap-1.5 transition-colors">
                        <span>Buka Pengaturan Integrasi</span>
                        <svg class="w-3.5 h-3.5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            @endif
        </div>

        <!-- 4 Integration Health Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
            <!-- 1. Konfigurasi Spreadsheet -->
            <div class="p-3.5 rounded-[2px] bg-canvas border border-line space-y-1">
                <span class="text-xs   font-semibold text-muted block">Spreadsheet ID</span>
                <div class="text-xs tabular-nums font-semibold text-current truncate" title="{{ $googleSheetsConfig?->spreadsheet_id }}">
                    {{ $googleSheetsConfig?->spreadsheet_id ? \Illuminate\Support\Str::limit($googleSheetsConfig->spreadsheet_id, 16) : 'Belum Dikonfigurasi' }}
                </div>
                <div class="text-xs text-muted">
                    Mode: <strong class="text-ink tabular-nums">{{ strtoupper($googleSheetsConfig?->auth_mode ?? 'mock') }}</strong>
                </div>
            </div>

            <!-- 2. Sinkronisasi Terakhir -->
            <div class="p-3.5 rounded-[2px] bg-canvas border border-line space-y-1">
                <span class="text-xs   font-semibold text-muted block">Sinkronisasi Terakhir</span>
                <div class="text-xs tabular-nums font-semibold text-current">
                    {{ $googleSheetsConfig?->last_synced_at ? $googleSheetsConfig->last_synced_at->diffForHumans() : 'Belum Pernah' }}
                </div>
                <div class="text-xs text-muted">
                    Status: <span class="tabular-nums font-semibold {{ $syncStatus === 'SUCCESS' ? 'text-accent' : ($syncStatus === 'FAILED' ? 'text-danger' : 'text-ink') }}">{{ $syncStatus }}</span>
                </div>
            </div>

            <!-- 3. Telemetri ESP32 Terbaru -->
            <div class="p-3.5 rounded-[2px] bg-canvas border border-line space-y-1">
                <span class="text-xs   font-semibold text-muted block">Telemetri Lapangan Terbaru</span>
                <div class="text-xs tabular-nums font-semibold text-current">
                    {{ $latestTelemetryMonitoring ? $latestTelemetryMonitoring->monitored_at->diffForHumans() : 'Tidak Ada Data' }}
                </div>
                <div class="text-xs text-muted">
                    ID Perangkat: <span class="tabular-nums text-ink">{{ $latestTelemetryMonitoring?->device_id ?? '-' }}</span>
                </div>
            </div>

            <!-- 4. Kesegaran Telemetri -->
            <div class="p-3.5 rounded-[2px] bg-canvas border border-line space-y-1">
                <span class="text-xs   font-semibold text-muted block">Kondisi Kesegaran Data</span>
                <div class="text-xs font-semibold {{ $freshness === 'connected' ? 'text-accent' : ($freshness === 'stale' ? 'text-warning' : 'text-muted') }}">
                    {{ $googleSheetsConfig?->freshnessLabel() ?? 'Belum Terhubung' }}
                </div>
                <div class="text-xs text-muted tabular-nums">
                    Baris Terakhir: {{ $googleSheetsConfig?->last_synced_row ?? 0 }}
                </div>
            </div>
        </div>

        <!-- Academic & Distinction Notice -->
        <div class="p-3.5 rounded-[2px] bg-canvas border border-line flex items-start gap-2.5 text-xs text-muted">
            <svg class="w-4 h-4 text-muted shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <strong class="text-ink">Catatan Arsitektur Integrasi:</strong>
                Keberhasilan akses spreadsheet Google Sheets memverifikasi konektivitas API cloud, sedangkan kesegaran data telemetri bergantung pada transmisi firmware ESP32 di greenhouse. Status integrasi live lapangan dipertahankan <span class="text-warning tabular-nums font-semibold">PENDING</span> sampai validasi telemetri resmi bersama tim Teknik Elektro.
            </div>
        </div>
    </div>

    <!-- 2. FILTER MONITORING & QUICK RANGE BAR -->
    <div class="p-5 sm:p-6 rounded-[2px] bg-canvas border border-line  space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-line pb-4">
            <div>
                <h3 class="text-sm sm:text-base font-semibold text-current flex items-center gap-2">
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Filter Visualisasi &amp; Rentang Tanggal
                </h3>
                <p class="text-xs text-muted mt-0.5">
                    Saring data grafik tren dan statistik analitik berdasarkan tanaman atau periode observasi.
                </p>
            </div>
            
            <!-- Quick Range Selection -->
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-semibold text-muted mr-1 hidden sm:inline">Quick Range:</span>
                <a
                    href="{{ route('dashboard', array_merge(request()->except(['range', 'date_from', 'date_to']), ['range' => 'today'])) }}"
                    class="px-2.5 py-1 rounded-[2px] text-xs font-semibold transition-colors {{ $selectedRange === 'today' ? 'bg-accent text-current ' : 'bg-surface hover:bg-surface text-ink' }}"
                >
                    Hari Ini
                </a>
                <a
                    href="{{ route('dashboard', array_merge(request()->except(['range', 'date_from', 'date_to']), ['range' => '7d'])) }}"
                    class="px-2.5 py-1 rounded-[2px] text-xs font-semibold transition-colors {{ $selectedRange === '7d' ? 'bg-accent text-current ' : 'bg-surface hover:bg-surface text-ink' }}"
                >
                    7 Hari
                </a>
                <a
                    href="{{ route('dashboard', array_merge(request()->except(['range', 'date_from', 'date_to']), ['range' => '30d'])) }}"
                    class="px-2.5 py-1 rounded-[2px] text-xs font-semibold transition-colors {{ $selectedRange === '30d' ? 'bg-accent text-current ' : 'bg-surface hover:bg-surface text-ink' }}"
                >
                    30 Hari
                </a>
                <a
                    href="{{ route('dashboard', array_merge(request()->except(['range', 'date_from', 'date_to']), ['range' => 'all'])) }}"
                    class="px-2.5 py-1 rounded-[2px] text-xs font-semibold transition-colors {{ $selectedRange === 'all' && empty($dateFrom) && empty($dateTo) ? 'bg-accent text-current ' : 'bg-surface hover:bg-surface text-ink' }}"
                >
                    Semua Data
                </a>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 items-end">
            
            <!-- 1. Dropdown Tanaman -->
            <div>
                <label for="plant_filter" class="block text-xs font-semibold   text-ink mb-1.5">
                    Pilih Tanaman
                </label>
                <div class="relative">
                    <select
                        name="plant_id"
                        id="plant_filter"
                        class="w-full appearance-none pl-3.5 pr-8 py-2 rounded-[2px] bg-canvas border border-line hover:border-line text-current text-xs font-medium focus:outline-none focus:ring-2 focus:ring-accent cursor-pointer transition-colors"
                    >
                        <option value="all" {{ $selectedPlantId === 'all' ? 'selected' : '' }}>Semua Tanaman (Akumulasi)</option>
                        @foreach ($plants as $p)
                            <option value="{{ $p->id }}" {{ (string)$selectedPlantId === (string)$p->id ? 'selected' : '' }}>
                                {{ $p->name }} {{ $p->location ? '— ' . $p->location : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-muted">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- 2. Tanggal Mulai -->
            <div>
                <label for="date_from" class="block text-xs font-semibold   text-ink mb-1.5">
                    Tanggal Mulai
                </label>
                <input
                    type="date"
                    name="date_from"
                    id="date_from"
                    value="{{ $dateFrom }}"
                    class="w-full px-3.5 py-2 rounded-[2px] bg-canvas border border-line hover:border-line text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
                >
            </div>

            <!-- 3. Tanggal Akhir -->
            <div>
                <label for="date_to" class="block text-xs font-semibold   text-ink mb-1.5">
                    Tanggal Akhir
                </label>
                <input
                    type="date"
                    name="date_to"
                    id="date_to"
                    value="{{ $dateTo }}"
                    class="w-full px-3.5 py-2 rounded-[2px] bg-canvas border border-line hover:border-line text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
                >
            </div>

            <!-- 4. Tombol Aksi Filter -->
            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="flex-1 py-2 px-3 rounded-[2px] bg-accent hover:bg-accent text-current text-xs font-semibold transition-colors flex items-center justify-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <span>Terapkan Filter</span>
                </button>
                <a
                    href="{{ route('dashboard') }}"
                    class="py-2 px-3 rounded-[2px] bg-surface hover:bg-surface text-ink hover:text-ink text-xs font-semibold transition-colors border border-line flex items-center justify-center"
                    title="Reset Filter ke Default"
                >
                    Reset
                </a>
            </div>

        </form>

        <!-- Informasi Filter Aktif -->
        <div class="flex items-center justify-between text-xs text-muted pt-1">
            <div>
                Menampilkan data: <strong class="text-current">{{ $filteredCount }}</strong> rekaman observasi sesuai filter aktif.
            </div>
            @if ($selectedPlantId !== 'all' || !empty($dateFrom) || !empty($dateTo) || $selectedRange !== 'all')
                <span class="inline-flex items-center gap-1 text-accent font-medium">
                    <span class="w-1.5 h-1.5 rounded-[2px] bg-accent-soft"></span>
                    Filter Diterapkan
                </span>
            @endif
        </div>
    </div>

    <!-- 3. PARAMETER AGGREGATION ANALYTICS (AVG, MIN, MAX dari Database Aktual) -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm sm:text-base font-semibold text-current flex items-center gap-2">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Statistik Agregasi Parameter (Data Terfilter)
            </h3>
            <span class="text-xs text-muted">Agregasi SQL Server-Side (AVG / MIN / MAX)</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
            
            <!-- Parameter 1: Suhu Udara -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line ">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-semibold text-accent   text-xs">Suhu Udara</span>
                    <span class="tabular-nums text-xs bg-accent-soft text-accent px-1.5 py-0.5 rounded-[2px] border border-line">°C</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Rata-rata:</span>
                        <span class="font-semibold tabular-nums text-current">{{ $aggregations && $aggregations->avg_temp ? number_format($aggregations->avg_temp, 1) . ' °C' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Minimum:</span>
                        <span class="tabular-nums text-accent">{{ $aggregations && $aggregations->min_temp ? number_format($aggregations->min_temp, 1) . ' °C' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Maksimum:</span>
                        <span class="tabular-nums text-warning">{{ $aggregations && $aggregations->max_temp ? number_format($aggregations->max_temp, 1) . ' °C' : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Parameter 2: pH Media / Nutrisi -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line ">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-semibold text-accent   text-xs">pH Nutrisi</span>
                    <span class="tabular-nums text-xs bg-accent-soft text-accent px-1.5 py-0.5 rounded-[2px] border border-line">pH</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Rata-rata:</span>
                        <span class="font-semibold tabular-nums text-current">{{ $aggregations && $aggregations->avg_ph ? number_format($aggregations->avg_ph, 2) : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Minimum:</span>
                        <span class="tabular-nums text-accent">{{ $aggregations && $aggregations->min_ph ? number_format($aggregations->min_ph, 2) : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Maksimum:</span>
                        <span class="tabular-nums text-danger">{{ $aggregations && $aggregations->max_ph ? number_format($aggregations->max_ph, 2) : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Parameter 3: Kelembapan Udara -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line ">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-semibold text-muted   text-xs">Kelembapan</span>
                    <span class="tabular-nums text-xs bg-surface text-muted px-1.5 py-0.5 rounded-[2px] border border-line">% RH</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Rata-rata:</span>
                        <span class="font-semibold tabular-nums text-current">{{ $aggregations && $aggregations->avg_humidity ? number_format($aggregations->avg_humidity, 1) . '%' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Minimum:</span>
                        <span class="tabular-nums text-muted">{{ $aggregations && $aggregations->min_humidity ? number_format($aggregations->min_humidity, 1) . '%' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Maksimum:</span>
                        <span class="tabular-nums text-warning">{{ $aggregations && $aggregations->max_humidity ? number_format($aggregations->max_humidity, 1) . '%' : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Parameter 4: Tinggi Tanaman -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line ">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-semibold text-muted   text-xs">Tinggi Tanaman</span>
                    <span class="tabular-nums text-xs bg-surface text-muted px-1.5 py-0.5 rounded-[2px] border border-line">cm</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Rata-rata:</span>
                        <span class="font-semibold tabular-nums text-current">{{ $aggregations && $aggregations->avg_height ? number_format($aggregations->avg_height, 1) . ' cm' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Minimum:</span>
                        <span class="tabular-nums text-muted">{{ $aggregations && $aggregations->min_height ? number_format($aggregations->min_height, 1) . ' cm' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Maksimum:</span>
                        <span class="tabular-nums text-muted">{{ $aggregations && $aggregations->max_height ? number_format($aggregations->max_height, 1) . ' cm' : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Parameter 5: Kerapatan Kanopi -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line ">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-semibold text-warning   text-xs">Kerapatan Tajuk</span>
                    <span class="tabular-nums text-xs bg-warning-soft text-warning px-1.5 py-0.5 rounded-[2px] border border-warning/25">%</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Rata-rata:</span>
                        <span class="font-semibold tabular-nums text-current">{{ $aggregations && $aggregations->avg_density ? number_format($aggregations->avg_density, 1) . '%' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Minimum:</span>
                        <span class="tabular-nums text-warning">{{ $aggregations && $aggregations->min_density ? number_format($aggregations->min_density, 1) . '%' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Maksimum:</span>
                        <span class="tabular-nums text-accent">{{ $aggregations && $aggregations->max_density ? number_format($aggregations->max_density, 1) . '%' : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Parameter 6: Suhu Nutrisi -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line ">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-semibold text-danger   text-xs">Suhu Nutrisi</span>
                    <span class="tabular-nums text-xs bg-danger-soft text-danger px-1.5 py-0.5 rounded-[2px] border border-danger/25">°C</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Rata-rata:</span>
                        <span class="font-semibold tabular-nums text-current">{{ $aggregations && $aggregations->avg_nutrient_temp !== null ? number_format($aggregations->avg_nutrient_temp, 1) . ' °C' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Minimum:</span>
                        <span class="tabular-nums text-accent">{{ $aggregations && $aggregations->min_nutrient_temp !== null ? number_format($aggregations->min_nutrient_temp, 1) . ' °C' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Maksimum:</span>
                        <span class="tabular-nums text-danger">{{ $aggregations && $aggregations->max_nutrient_temp !== null ? number_format($aggregations->max_nutrient_temp, 1) . ' °C' : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Parameter 7: EC Nutrisi -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line ">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-semibold text-muted   text-xs">EC Nutrisi</span>
                    <span class="tabular-nums text-xs bg-surface text-muted px-1.5 py-0.5 rounded-[2px] border border-line">mS/cm</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Rata-rata:</span>
                        <span class="font-semibold tabular-nums text-current">{{ $aggregations && $aggregations->avg_ec !== null ? number_format($aggregations->avg_ec, 2) . ' mS' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Minimum:</span>
                        <span class="tabular-nums text-muted">{{ $aggregations && $aggregations->min_ec !== null ? number_format($aggregations->min_ec, 2) . ' mS' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Maksimum:</span>
                        <span class="tabular-nums text-warning">{{ $aggregations && $aggregations->max_ec !== null ? number_format($aggregations->max_ec, 2) . ' mS' : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Parameter 8: Kelembapan Tanah -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line ">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-semibold text-muted   text-xs">Kelembapan Tanah</span>
                    <span class="tabular-nums text-xs bg-surface text-muted px-1.5 py-0.5 rounded-[2px] border border-line">%</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Rata-rata:</span>
                        <span class="font-semibold tabular-nums text-current">{{ $aggregations && $aggregations->avg_soil_moisture !== null ? number_format($aggregations->avg_soil_moisture, 1) . '%' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Minimum:</span>
                        <span class="tabular-nums text-muted">{{ $aggregations && $aggregations->min_soil_moisture !== null ? number_format($aggregations->min_soil_moisture, 1) . '%' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Maksimum:</span>
                        <span class="tabular-nums text-warning">{{ $aggregations && $aggregations->max_soil_moisture !== null ? number_format($aggregations->max_soil_moisture, 1) . '%' : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Parameter 9: Intensitas Cahaya -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line ">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-semibold text-warning   text-xs">Intensitas Cahaya</span>
                    <span class="tabular-nums text-xs bg-warning-soft text-warning px-1.5 py-0.5 rounded-[2px] border border-warning/25">lux</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Rata-rata:</span>
                        <span class="font-semibold tabular-nums text-current">{{ $aggregations && $aggregations->avg_light !== null ? number_format($aggregations->avg_light, 0) . ' lx' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Minimum:</span>
                        <span class="tabular-nums text-warning">{{ $aggregations && $aggregations->min_light !== null ? number_format($aggregations->min_light, 0) . ' lx' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Maksimum:</span>
                        <span class="tabular-nums text-warning">{{ $aggregations && $aggregations->max_light !== null ? number_format($aggregations->max_light, 0) . ' lx' : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Parameter 10: Level Tandon Nutrisi -->
            <div class="p-4 rounded-[2px] bg-canvas border border-line ">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-semibold text-muted   text-xs">Level Tandon</span>
                    <span class="tabular-nums text-xs bg-surface text-muted px-1.5 py-0.5 rounded-[2px] border border-line">%</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Rata-rata:</span>
                        <span class="font-semibold tabular-nums text-current">{{ $aggregations && $aggregations->avg_tank_level !== null ? number_format($aggregations->avg_tank_level, 1) . '%' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Minimum:</span>
                        <span class="tabular-nums text-danger">{{ $aggregations && $aggregations->min_tank_level !== null ? number_format($aggregations->min_tank_level, 1) . '%' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-ink">
                        <span class="text-muted">Maksimum:</span>
                        <span class="tabular-nums text-muted">{{ $aggregations && $aggregations->max_tank_level !== null ? number_format($aggregations->max_tank_level, 1) . '%' : '-' }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- 4. SECTION GRAFIK VISUALISASI (CHART.JS DARI DATABASE) -->
    <div id="charts" class="space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-base sm:text-lg font-semibold text-current flex items-center gap-2">
                    <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                    Grafik Tren &amp; Visualisasi Analitik Melon
                </h3>
                <p class="text-xs text-muted">Visualisasi data observasi berkala bersumber langsung dari database MySQL.</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-[2px] text-xs tabular-nums bg-canvas border border-line text-ink self-start sm:self-auto">
                <span class="w-2 h-2 rounded-[2px] bg-accent-soft "></span>
                Chart.js Aktif
            </span>
        </div>

        <!-- EMPTY STATE JIKA TIDAK ADA DATA TERFILTER -->
        @if ($filteredCount === 0 || count($chartLabels) === 0)
            <div class="p-8 sm:p-12 text-center rounded-[2px] bg-canvas border border-line  space-y-4">
                <div class="w-14 h-14 mx-auto rounded-[2px] bg-warning-soft border border-warning/25 flex items-center justify-center text-warning">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-base sm:text-lg font-semibold text-current">Tidak ada data monitoring pada periode yang dipilih.</h4>
                    <p class="text-xs sm:text-sm text-muted max-w-md mx-auto mt-1">
                        Kombinasi filter tanaman atau rentang tanggal yang dipilih tidak menghasilkan rekaman observasi di database.
                    </p>
                </div>
                <div class="pt-2">
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-[2px] bg-surface hover:bg-surface text-ink border border-line text-xs font-semibold transition-colors"
                    >
                        <span>Reset Filter ke Semua Data</span>
                    </a>
                </div>
            </div>
        @endif

        <!-- A. MAIN MONITORING TRENDS (Suhu, pH, Kelembapan) -->
        <div class="space-y-3 {{ $filteredCount === 0 ? 'hidden' : '' }}">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-semibold   text-muted">1. Tren Parameter Lingkungan &amp; Nutrisi</h4>
                <span class="text-xs text-muted italic">*Garis putus-putus = Configured Monitoring Threshold</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                
                <!-- Chart 1: Suhu (°C) -->
                <div class="p-5 rounded-[2px] bg-canvas border border-line  flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-accent  ">Tren Suhu Monitoring</span>
                            <span class="text-xs tabular-nums bg-accent-soft text-accent px-2 py-0.5 rounded-[2px] border border-line">°C</span>
                        </div>
                        <p class="text-xs text-muted mb-3">
                            Configured Monitoring Threshold: {{ $thresholds['temperature']['min'] }}°C &ndash; {{ $thresholds['temperature']['max'] }}°C
                        </p>
                    </div>
                    <div class="relative h-60 w-full">
                        <canvas id="tempChart" role="img" aria-label="Grafik Suhu udara; nilai pengamatan tersedia di tabel riwayat di bawah">Grafik Suhu udara. Lihat tabel riwayat monitoring untuk data pengamatan.</canvas>
                    </div>
                </div>

                <!-- Chart 2: pH Nutrisi -->
                <div class="p-5 rounded-[2px] bg-canvas border border-line  flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-accent  ">Tren pH</span>
                            <span class="text-xs tabular-nums bg-accent-soft text-accent px-2 py-0.5 rounded-[2px] border border-line">pH</span>
                        </div>
                        <p class="text-xs text-muted mb-3">
                            Configured Monitoring Threshold: {{ $thresholds['ph']['min'] }} &ndash; {{ $thresholds['ph']['max'] }}
                        </p>
                    </div>
                    <div class="relative h-60 w-full">
                        <canvas id="phChart" role="img" aria-label="Grafik pH nutrisi; nilai pengamatan tersedia di tabel riwayat di bawah">Grafik pH nutrisi. Lihat tabel riwayat monitoring untuk data pengamatan.</canvas>
                    </div>
                </div>

                <!-- Chart 3: Kelembapan (%) -->
                <div class="p-5 rounded-[2px] bg-canvas border border-line  flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-muted  ">Tren Kelembapan Udara</span>
                            <span class="text-xs tabular-nums bg-surface text-muted px-2 py-0.5 rounded-[2px] border border-line">% RH</span>
                        </div>
                        <p class="text-xs text-muted mb-3">
                            Configured Monitoring Threshold: {{ $thresholds['humidity']['min'] }}% &ndash; {{ $thresholds['humidity']['max'] }}%
                        </p>
                    </div>
                    <div class="relative h-60 w-full">
                        <canvas id="humidityChart" role="img" aria-label="Grafik Kelembapan udara; nilai pengamatan tersedia di tabel riwayat di bawah">Grafik Kelembapan udara. Lihat tabel riwayat monitoring untuk data pengamatan.</canvas>
                    </div>
                </div>

            </div>
        </div>

        <!-- B. EXPANDED IOT SENSOR TRENDS (Nutrisi, Media & Tandon) -->
        <div class="space-y-3 {{ $filteredCount === 0 ? 'hidden' : '' }}">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-semibold   text-muted">2. Tren Nutrisi, Media Tanam &amp; Tandon (IoT Expansion)</h4>
                <span class="text-xs text-muted italic">*Nilai kosong ditampilkan empty-state (tidak diperlakukan nol)</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                
                <!-- Chart: EC Nutrisi (mS/cm) -->
                <div class="p-5 rounded-[2px] bg-canvas border border-line  flex flex-col justify-between" id="card-ecChart">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-muted  ">Tren EC Nutrisi</span>
                            <span class="text-xs tabular-nums bg-surface text-muted px-2 py-0.5 rounded-[2px] border border-line">mS/cm</span>
                        </div>
                        <p class="text-xs text-muted mb-3">
                            Configured Threshold: {{ $thresholds['ec']['min'] }} &ndash; {{ $thresholds['ec']['max'] }} mS/cm
                        </p>
                    </div>
                    <div class="relative h-60 w-full flex items-center justify-center">
                        <canvas id="ecChart" role="img" aria-label="Grafik Konduktivitas listrik; nilai pengamatan tersedia di tabel riwayat di bawah">Grafik Konduktivitas listrik. Lihat tabel riwayat monitoring untuk data pengamatan.</canvas>
                        <div id="empty-ecChart" class="hidden text-center py-8">
                            <svg class="w-8 h-8 mx-auto text-muted mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <p class="text-xs text-muted">Belum ada data sensor EC.</p>
                            <span class="inline-block mt-1 text-xs text-muted tabular-nums">Siap menerima telemetri ESP32</span>
                        </div>
                    </div>
                </div>

                <!-- Chart: Kelembapan Tanah (%) -->
                <div class="p-5 rounded-[2px] bg-canvas border border-line  flex flex-col justify-between" id="card-soilMoistureChart">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-muted  ">Tren Kelembapan Tanah</span>
                            <span class="text-xs tabular-nums bg-surface text-muted px-2 py-0.5 rounded-[2px] border border-line">%</span>
                        </div>
                        <p class="text-xs text-muted mb-3">
                            Configured Threshold: {{ $thresholds['soil_moisture']['min'] }}% &ndash; {{ $thresholds['soil_moisture']['max'] }}%
                        </p>
                    </div>
                    <div class="relative h-60 w-full flex items-center justify-center">
                        <canvas id="soilMoistureChart" role="img" aria-label="Grafik Kelembapan tanah; nilai pengamatan tersedia di tabel riwayat di bawah">Grafik Kelembapan tanah. Lihat tabel riwayat monitoring untuk data pengamatan.</canvas>
                        <div id="empty-soilMoistureChart" class="hidden text-center py-8">
                            <svg class="w-8 h-8 mx-auto text-muted mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                            <p class="text-xs text-muted">Belum ada data kelembapan tanah.</p>
                            <span class="inline-block mt-1 text-xs text-muted tabular-nums">Siap menerima telemetri ESP32</span>
                        </div>
                    </div>
                </div>

                <!-- Chart: Intensitas Cahaya (lux) -->
                <div class="p-5 rounded-[2px] bg-canvas border border-line  flex flex-col justify-between" id="card-lightChart">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-warning  ">Tren Intensitas Cahaya</span>
                            <span class="text-xs tabular-nums bg-warning-soft text-warning px-2 py-0.5 rounded-[2px] border border-warning/25">lux</span>
                        </div>
                        <p class="text-xs text-muted mb-3">
                            Configured Threshold: {{ number_format($thresholds['light_intensity']['min'], 0) }} &ndash; {{ number_format($thresholds['light_intensity']['max'], 0) }} lux
                        </p>
                    </div>
                    <div class="relative h-60 w-full flex items-center justify-center">
                        <canvas id="lightChart" role="img" aria-label="Grafik Intensitas cahaya; nilai pengamatan tersedia di tabel riwayat di bawah">Grafik Intensitas cahaya. Lihat tabel riwayat monitoring untuk data pengamatan.</canvas>
                        <div id="empty-lightChart" class="hidden text-center py-8">
                            <svg class="w-8 h-8 mx-auto text-muted mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <p class="text-xs text-muted">Belum ada data intensitas cahaya.</p>
                            <span class="inline-block mt-1 text-xs text-warning tabular-nums">Siap menerima telemetri ESP32</span>
                        </div>
                    </div>
                </div>

                <!-- Chart: Suhu Larutan Nutrisi (°C) -->
                <div class="p-5 rounded-[2px] bg-canvas border border-line  flex flex-col justify-between" id="card-nutrientTempChart">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-danger  ">Tren Suhu Nutrisi</span>
                            <span class="text-xs tabular-nums bg-danger-soft text-danger px-2 py-0.5 rounded-[2px] border border-danger/25">°C</span>
                        </div>
                        <p class="text-xs text-muted mb-3">
                            Configured Threshold: {{ $thresholds['nutrient_temperature']['min'] }}°C &ndash; {{ $thresholds['nutrient_temperature']['max'] }}°C
                        </p>
                    </div>
                    <div class="relative h-60 w-full flex items-center justify-center">
                        <canvas id="nutrientTempChart" role="img" aria-label="Grafik Suhu nutrisi; nilai pengamatan tersedia di tabel riwayat di bawah">Grafik Suhu nutrisi. Lihat tabel riwayat monitoring untuk data pengamatan.</canvas>
                        <div id="empty-nutrientTempChart" class="hidden text-center py-8">
                            <svg class="w-8 h-8 mx-auto text-muted mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <p class="text-xs text-muted">Belum ada data suhu nutrisi.</p>
                            <span class="inline-block mt-1 text-xs text-danger tabular-nums">Siap menerima telemetri ESP32</span>
                        </div>
                    </div>
                </div>

                <!-- Chart: Level Tandon Nutrisi (%) -->
                <div class="p-5 rounded-[2px] bg-canvas border border-line  flex flex-col justify-between" id="card-tankLevelChart">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-muted  ">Level Cadangan Tandon</span>
                            <span class="text-xs tabular-nums bg-surface text-muted px-2 py-0.5 rounded-[2px] border border-line">%</span>
                        </div>
                        <p class="text-xs text-muted mb-3">
                            Configured Threshold: {{ $thresholds['tank_level']['min'] }}% &ndash; {{ $thresholds['tank_level']['max'] }}%
                        </p>
                    </div>
                    <div class="relative h-60 w-full flex items-center justify-center">
                        <canvas id="tankLevelChart" role="img" aria-label="Grafik Level tandon; nilai pengamatan tersedia di tabel riwayat di bawah">Grafik Level tandon. Lihat tabel riwayat monitoring untuk data pengamatan.</canvas>
                        <div id="empty-tankLevelChart" class="hidden text-center py-8">
                            <svg class="w-8 h-8 mx-auto text-muted mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            <p class="text-xs text-muted">Belum ada data level tandon.</p>
                            <span class="inline-block mt-1 text-xs text-muted tabular-nums">Siap menerima telemetri ESP32</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- C. GROWTH TRENDS (Tinggi Tanaman & Kerapatan Kanopi) -->
        <div class="space-y-3 {{ $filteredCount === 0 ? 'hidden' : '' }}">
            <h4 class="text-xs font-semibold   text-muted">3. Tren Indikator Pertumbuhan Tanaman</h4>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                
                <!-- Chart 4: Tinggi Tanaman (cm) -->
                <div class="p-5 rounded-[2px] bg-canvas border border-line  flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-muted  ">Perkembangan Tinggi Tanaman</span>
                            <span class="text-xs tabular-nums bg-surface text-muted px-2 py-0.5 rounded-[2px] border border-line">cm</span>
                        </div>
                        <p class="text-xs text-muted mb-3">
                            Perubahan tinggi batang tanaman melon berdasarkan kronologis waktu observasi.
                        </p>
                    </div>
                    <div class="relative h-64 w-full">
                        <canvas id="heightChart" role="img" aria-label="Grafik Tinggi tanaman; nilai pengamatan tersedia di tabel riwayat di bawah">Grafik Tinggi tanaman. Lihat tabel riwayat monitoring untuk data pengamatan.</canvas>
                    </div>
                </div>

                <!-- Chart 5: Kerapatan Kanopi (%) -->
                <div class="p-5 rounded-[2px] bg-canvas border border-line  flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-warning  ">Perkembangan Kerapatan Kanopi</span>
                            <span class="text-xs tabular-nums bg-warning-soft text-warning px-2 py-0.5 rounded-[2px] border border-warning/25">%</span>
                        </div>
                        <p class="text-xs text-muted mb-3">
                            Perkembangan tutupan tajuk daun tanaman melon dari waktu ke waktu.
                        </p>
                    </div>
                    <div class="relative h-64 w-full">
                        <canvas id="densityChart" role="img" aria-label="Grafik Kerapatan tanaman; nilai pengamatan tersedia di tabel riwayat di bawah">Grafik Kerapatan tanaman. Lihat tabel riwayat monitoring untuk data pengamatan.</canvas>
                    </div>
                </div>

            </div>
        </div>

        <!-- C. DISTRIBUTION & FREQUENCY CHARTS (Status Tanaman, Kondisi, Frekuensi Monitoring) -->
        <div class="space-y-3">
            <h4 class="text-xs font-semibold   text-muted">3. Distribusi Status &amp; Frekuensi Monitoring</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                
                <!-- Chart 6: Distribusi Kondisi Tanaman (Doughnut) -->
                <div class="p-5 rounded-[2px] bg-canvas border border-line  flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-accent  ">Distribusi Kondisi Tanaman</span>
                            <span class="text-xs font-semibold bg-surface text-ink px-2 py-0.5 rounded-[2px]">Doughnut</span>
                        </div>
                        <p class="text-xs text-muted mb-3">
                            Proporsi kondisi kesehatan fisik tanaman dari data observasi database.
                        </p>
                    </div>
                    <div class="relative h-60 w-full flex items-center justify-center">
                        @if (count($conditionCounts) > 0)
                            <canvas id="conditionChart" role="img" aria-label="Grafik Distribusi kondisi tanaman; nilai pengamatan tersedia di tabel riwayat di bawah">Grafik Distribusi kondisi tanaman. Lihat tabel riwayat monitoring untuk data pengamatan.</canvas>
                        @else
                            <div class="text-xs text-muted text-center py-10">Tidak ada data kondisi.</div>
                        @endif
                    </div>
                </div>

                <!-- Chart 7: Status Budidaya Tanaman (Doughnut) -->
                <div class="p-5 rounded-[2px] bg-canvas border border-line  flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-muted  ">Status Budidaya Tanaman</span>
                            <span class="text-xs font-semibold bg-surface text-ink px-2 py-0.5 rounded-[2px]">Doughnut</span>
                        </div>
                        <p class="text-xs text-muted mb-3">
                            Sebaran status budidaya tanaman melon (Aktif, Sudah Dipanen, Tidak Aktif).
                        </p>
                    </div>
                    <div class="relative h-60 w-full flex items-center justify-center">
                        <canvas id="statusChart" role="img" aria-label="Grafik Distribusi status tanaman; nilai pengamatan tersedia di tabel riwayat di bawah">Grafik Distribusi status tanaman. Lihat tabel riwayat monitoring untuk data pengamatan.</canvas>
                    </div>
                </div>

                <!-- Chart 8: Monitoring Frequency (Bar Chart) -->
                <div class="p-5 rounded-[2px] bg-canvas border border-line  flex flex-col justify-between md:col-span-2 lg:col-span-1">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-accent  ">Jumlah Monitoring per Tanaman</span>
                            <span class="text-xs font-semibold bg-surface text-ink px-2 py-0.5 rounded-[2px]">Bar</span>
                        </div>
                        <p class="text-xs text-muted mb-3">
                            Frekuensi observasi tercatat untuk masing-masing spesimen tanaman.
                        </p>
                    </div>
                    <div class="relative h-60 w-full">
                        <canvas id="frequencyChart" role="img" aria-label="Grafik Frekuensi pengamatan; nilai pengamatan tersedia di tabel riwayat di bawah">Grafik Frekuensi pengamatan. Lihat tabel riwayat monitoring untuk data pengamatan.</canvas>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- 5. TABEL MONITORING TERBARU (Data Aktual dari MySQL) -->
    <div id="recent-table" class="p-5 sm:p-6 rounded-[2px] bg-canvas border border-line  space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-line pb-4">
            <div>
                <h3 class="text-base sm:text-lg font-semibold text-current flex items-center gap-2">
                    <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Data Pengamatan Terkini (Monitoring Terbaru)
                </h3>
                <p class="text-xs text-muted mt-0.5">Menampilkan hingga 8 catatan observasi terbaru yang tersimpan di database.</p>
            </div>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('monitorings.create') }}"
                    class="px-3 py-1.5 rounded-[2px] bg-accent-soft hover:bg-accent-soft text-accent border border-line text-xs font-semibold flex items-center gap-1.5 transition-colors"
                    title="Tambah Catatan Monitoring Baru"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>+ Tambah</span>
                </a>
                <a
                    href="{{ route('monitorings.index') }}"
                    class="px-3 py-1.5 rounded-[2px] bg-surface hover:bg-surface text-ink text-xs font-semibold flex items-center gap-1.5 transition-colors border border-line"
                    title="Buka Halaman Pengelolaan Monitoring Lengkap"
                >
                    <span>Semua Data ({{ $totalMonitorings }})</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Responsive Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead>
                    <tr class="border-b border-line text-xs font-semibold   text-muted bg-canvas">
                        <th class="py-3 px-4 rounded-l-xl">Tanaman / Lokasi</th>
                        <th class="py-3 px-4">Waktu Monitoring</th>
                        <th class="py-3 px-4 text-center">Suhu</th>
                        <th class="py-3 px-4 text-center">pH</th>
                        <th class="py-3 px-4 text-center">Kelembapan</th>
                        <th class="py-3 px-4 text-center">Tinggi</th>
                        <th class="py-3 px-4 text-center">Kerapatan</th>
                        <th class="py-3 px-4">Kondisi</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center rounded-r-xl">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($recentMonitorings as $item)
                        <tr class="hover:bg-surface transition-colors">
                            <!-- Kolom Tanaman -->
                            <td class="py-3 px-4">
                                <div class="font-semibold text-current">{{ $item->plant->name ?? 'Tanaman #' . $item->plant_id }}</div>
                                <div class="text-xs text-muted">{{ $item->plant->location ?? '-' }}</div>
                            </td>

                            <!-- Kolom Waktu -->
                            <td class="py-3 px-4 text-ink tabular-nums text-xs">
                                {{ $item->monitored_at->format('d M Y, H:i') }}
                            </td>

                            <!-- Kolom Suhu -->
                            <td class="py-3 px-4 text-center tabular-nums">
                                <span class="font-semibold {{ $item->temperature >= 20 && $item->temperature <= 32 ? 'text-accent' : 'text-warning' }}">
                                    {{ number_format($item->temperature, 1) }}°C
                                </span>
                            </td>

                            <!-- Kolom pH -->
                            <td class="py-3 px-4 text-center tabular-nums">
                                <span class="font-semibold {{ $item->ph >= 5.8 && $item->ph <= 7.2 ? 'text-accent' : 'text-danger' }}">
                                    {{ number_format($item->ph, 2) }}
                                </span>
                            </td>

                            <!-- Kolom Kelembapan -->
                            <td class="py-3 px-4 text-center tabular-nums">
                                <span class="font-semibold {{ $item->humidity >= 60 && $item->humidity <= 85 ? 'text-muted' : 'text-warning' }}">
                                    {{ number_format($item->humidity, 0) }}%
                                </span>
                            </td>

                            <!-- Kolom Tinggi -->
                            <td class="py-3 px-4 text-center tabular-nums text-ink">
                                {{ $item->plant_height ? number_format($item->plant_height, 1) . ' cm' : '-' }}
                            </td>

                            <!-- Kolom Kerapatan -->
                            <td class="py-3 px-4 text-center tabular-nums text-ink">
                                {{ $item->plant_density ? number_format($item->plant_density, 0) . '%' : '-' }}
                            </td>

                            <!-- Kolom Kondisi Visual -->
                            <td class="py-3 px-4">
                                <div class="text-ink">{{ $item->plant_condition }}</div>
                                <div class="text-xs text-muted">{{ $item->plant_color }}</div>
                            </td>

                            <!-- Kolom Status Badge -->
                            <td class="py-3 px-4 text-center">
                                <x-status-badge :status="$item->plant_condition" />
                            </td>

                            <!-- Kolom Aksi -->
                            <td class="py-3 px-4 text-center">
                                <a
                                    href="{{ route('monitorings.show', $item) }}"
                                    class="p-1.5 rounded-[2px] bg-surface hover:bg-surface text-ink hover:text-ink transition-colors border border-line inline-flex items-center gap-1 text-xs"
                                    title="Lihat Detail"
                                >
                                    <svg class="w-3.5 h-3.5 text-ink" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <span>Detail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-muted">
                                Belum ada data monitoring yang tercatat untuk filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 6. SECTION KHUSUS ROLE ADMINISTRATOR -->
    @if ($user->isAdmin())
        <div id="admin-section" class="p-6 rounded-[2px]     border border-line space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-[2px] bg-accent-soft border border-line flex items-center justify-center text-accent">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-current">Panel Administrasi (Khusus Admin)</h4>
                        <p class="text-xs text-muted">Parameter threshold acuan sistem monitoring Melon.</p>
                    </div>
                </div>
                <span class="px-2.5 py-0.5 rounded-[2px] text-xs font-semibold bg-accent-soft text-accent border border-line tabular-nums">
                    ADMIN ONLY
                </span>
            </div>

            <!-- Parameters Grid Table -->
            <div id="parameters-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-2">
                @foreach ($parameters as $param)
                    <div class="p-3.5 rounded-[2px] bg-canvas border border-line">
                        <div class="text-xs font-semibold text-ink truncate">{{ $param->parameter_name }}</div>
                        <div class="text-base font-semibold text-current mt-1">
                            {{ $param->minimum_value }} &ndash; {{ $param->maximum_value }}
                            <span class="text-xs font-normal text-muted">{{ $param->unit }}</span>
                        </div>
                        <p class="text-xs text-muted mt-1 line-clamp-1" title="{{ $param->description }}">
                            {{ $param->description }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Data dari Controller (Database MySQL)
    const labels = @json($chartLabels);
    const temperatures = @json($chartTemps);
    const phValues = @json($chartPh);
    const humidities = @json($chartHumidities);
    const nutrientTemps = @json($chartNutrientTemps);
    const ecValues = @json($chartEc);
    const soilMoistures = @json($chartSoilMoistures);
    const lightValues = @json($chartLights);
    const tankLevels = @json($chartTankLevels);
    const heights = @json($chartHeights);
    const densities = @json($chartDensities);
    const thresholds = @json($thresholds);

    const conditionLabels = @json($conditionLabels);
    const conditionCounts = @json($conditionCounts);

    const frequencyLabels = @json($frequencyLabels);
    const frequencyCounts = @json($frequencyCounts);

    const statusLabels = @json($statusLabels);
    const statusCounts = @json($statusCounts);

    // Opsi Global Garis & Sumbu Chart.js
    const commonLineOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    color: '#94a3b8',
                    font: { size: 10 },
                    boxWidth: 12,
                    boxHeight: 12,
                    usePointStyle: true,
                }
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                titleColor: '#e2e8f0',
                bodyColor: '#38bdf8',
                borderColor: '#334155',
                borderWidth: 1,
                padding: 10,
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(51, 65, 85, 0.25)' },
                ticks: { color: '#94a3b8', font: { size: 10 }, maxTicksLimit: 5, maxRotation: 30 }
            },
            y: {
                grid: { color: 'rgba(51, 65, 85, 0.25)' },
                ticks: { color: '#94a3b8', font: { size: 10 } }
            }
        }
    };

    if (labels && labels.length > 0) {
        // Chart 1: Suhu (°C) dengan Configured Monitoring Threshold
        const ctxTemp = document.getElementById('tempChart');
        if (ctxTemp) {
            new Chart(ctxTemp, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Suhu Observasi (°C)',
                            data: temperatures,
                            borderColor: '#10b981', // emerald-500
                            backgroundColor: 'rgba(16, 185, 129, 0.12)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointBackgroundColor: '#10b981',
                            pointRadius: 3,
                            pointHoverRadius: 5,
                        },
                        {
                            label: 'Configured Max (' + thresholds.temperature.max + '°C)',
                            data: labels.map(() => thresholds.temperature.max),
                            borderColor: 'rgba(239, 68, 68, 0.85)',
                            borderDash: [6, 4],
                            borderWidth: 1.5,
                            pointRadius: 0,
                            fill: false,
                        },
                        {
                            label: 'Configured Min (' + thresholds.temperature.min + '°C)',
                            data: labels.map(() => thresholds.temperature.min),
                            borderColor: 'rgba(245, 158, 11, 0.85)',
                            borderDash: [6, 4],
                            borderWidth: 1.5,
                            pointRadius: 0,
                            fill: false,
                        }
                    ]
                },
                options: {
                    ...commonLineOptions,
                    scales: {
                        ...commonLineOptions.scales,
                        y: {
                            ...commonLineOptions.scales.y,
                            suggestedMin: 18,
                            suggestedMax: 36,
                            ticks: {
                                ...commonLineOptions.scales.y.ticks,
                                callback: (val) => val + '°C'
                            }
                        }
                    }
                }
            });
        }

        // Chart 2: pH dengan Configured Monitoring Threshold
        const ctxPh = document.getElementById('phChart');
        if (ctxPh) {
            new Chart(ctxPh, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'pH Observasi',
                            data: phValues,
                            borderColor: '#14b8a6', // teal-500
                            backgroundColor: 'rgba(20, 184, 166, 0.12)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointBackgroundColor: '#14b8a6',
                            pointRadius: 3,
                            pointHoverRadius: 5,
                        },
                        {
                            label: 'Configured Max (' + thresholds.ph.max + ')',
                            data: labels.map(() => thresholds.ph.max),
                            borderColor: 'rgba(239, 68, 68, 0.85)',
                            borderDash: [6, 4],
                            borderWidth: 1.5,
                            pointRadius: 0,
                            fill: false,
                        },
                        {
                            label: 'Configured Min (' + thresholds.ph.min + ')',
                            data: labels.map(() => thresholds.ph.min),
                            borderColor: 'rgba(245, 158, 11, 0.85)',
                            borderDash: [6, 4],
                            borderWidth: 1.5,
                            pointRadius: 0,
                            fill: false,
                        }
                    ]
                },
                options: {
                    ...commonLineOptions,
                    scales: {
                        ...commonLineOptions.scales,
                        y: {
                            ...commonLineOptions.scales.y,
                            suggestedMin: 5.0,
                            suggestedMax: 8.5,
                            ticks: {
                                ...commonLineOptions.scales.y.ticks,
                                callback: (val) => val + ' pH'
                            }
                        }
                    }
                }
            });
        }

        // Chart 3: Kelembapan (%) dengan Configured Monitoring Threshold
        const ctxHumidity = document.getElementById('humidityChart');
        if (ctxHumidity) {
            new Chart(ctxHumidity, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Kelembapan Observasi (%)',
                            data: humidities,
                            borderColor: '#0ea5e9', // sky-500
                            backgroundColor: 'rgba(14, 165, 233, 0.12)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointBackgroundColor: '#0ea5e9',
                            pointRadius: 3,
                            pointHoverRadius: 5,
                        },
                        {
                            label: 'Configured Max (' + thresholds.humidity.max + '%)',
                            data: labels.map(() => thresholds.humidity.max),
                            borderColor: 'rgba(239, 68, 68, 0.85)',
                            borderDash: [6, 4],
                            borderWidth: 1.5,
                            pointRadius: 0,
                            fill: false,
                        },
                        {
                            label: 'Configured Min (' + thresholds.humidity.min + '%)',
                            data: labels.map(() => thresholds.humidity.min),
                            borderColor: 'rgba(245, 158, 11, 0.85)',
                            borderDash: [6, 4],
                            borderWidth: 1.5,
                            pointRadius: 0,
                            fill: false,
                        }
                    ]
                },
                options: {
                    ...commonLineOptions,
                    scales: {
                        ...commonLineOptions.scales,
                        y: {
                            ...commonLineOptions.scales.y,
                            suggestedMin: 40,
                            suggestedMax: 95,
                            ticks: {
                                ...commonLineOptions.scales.y.ticks,
                                callback: (val) => val + '%'
                            }
                        }
                    }
                }
            });
        }

        // Chart 4: Perkembangan Tinggi Tanaman (cm)
        const ctxHeight = document.getElementById('heightChart');
        if (ctxHeight) {
            new Chart(ctxHeight, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Tinggi Tanaman (cm)',
                        data: heights,
                        borderColor: '#8b5cf6', // violet-500
                        backgroundColor: 'rgba(139, 92, 246, 0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointBackgroundColor: '#8b5cf6',
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    }]
                },
                options: {
                    ...commonLineOptions,
                    scales: {
                        ...commonLineOptions.scales,
                        y: {
                            ...commonLineOptions.scales.y,
                            ticks: {
                                ...commonLineOptions.scales.y.ticks,
                                callback: (val) => val + ' cm'
                            }
                        }
                    }
                }
            });
        }

        // Chart 5: Perkembangan Kerapatan Kanopi (%)
        const ctxDensity = document.getElementById('densityChart');
        if (ctxDensity) {
            new Chart(ctxDensity, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Kerapatan Kanopi (%)',
                        data: densities,
                        borderColor: '#f59e0b', // amber-500
                        backgroundColor: 'rgba(245, 158, 11, 0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointBackgroundColor: '#f59e0b',
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    }]
                },
                options: {
                    ...commonLineOptions,
                    scales: {
                        ...commonLineOptions.scales,
                        y: {
                            ...commonLineOptions.scales.y,
                            ticks: {
                                ...commonLineOptions.scales.y.ticks,
                                callback: (val) => val + '%'
                            }
                        }
                    }
                }
            });
        }

        // Helper untuk inisialisasi sensor chart dengan penanganan null yang aman dan empty-state
        const initSensorChart = (canvasId, emptyId, label, dataValues, unit, color, minThreshold, maxThreshold, suggestedMin, suggestedMax) => {
            const canvas = document.getElementById(canvasId);
            const emptyEl = document.getElementById(emptyId);
            if (!canvas) return;

            const hasData = Array.isArray(dataValues) && dataValues.some(v => v !== null && v !== undefined);
            if (!hasData) {
                canvas.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                return;
            }

            const datasets = [
                {
                    label: label + ' (' + unit + ')',
                    data: dataValues,
                    borderColor: color,
                    backgroundColor: color.replace('rgb', 'rgba').replace(')', ', 0.12)'),
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointBackgroundColor: color,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    spanGaps: true,
                }
            ];

            if (maxThreshold !== undefined && maxThreshold !== null) {
                datasets.push({
                    label: 'Configured Max (' + maxThreshold + ' ' + unit + ')',
                    data: labels.map(() => maxThreshold),
                    borderColor: 'rgba(239, 68, 68, 0.85)',
                    borderDash: [6, 4],
                    borderWidth: 1.5,
                    pointRadius: 0,
                    fill: false,
                });
            }

            if (minThreshold !== undefined && minThreshold !== null) {
                datasets.push({
                    label: 'Configured Min (' + minThreshold + ' ' + unit + ')',
                    data: labels.map(() => minThreshold),
                    borderColor: 'rgba(245, 158, 11, 0.85)',
                    borderDash: [6, 4],
                    borderWidth: 1.5,
                    pointRadius: 0,
                    fill: false,
                });
            }

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    ...commonLineOptions,
                    plugins: {
                        ...commonLineOptions.plugins,
                        tooltip: {
                            ...commonLineOptions.plugins.tooltip,
                            callbacks: {
                                label: function(context) {
                                    if (context.raw === null || context.raw === undefined) {
                                        return context.dataset.label + ': Belum ada data';
                                    }
                                    return context.dataset.label + ': ' + context.raw + ' ' + unit;
                                }
                            }
                        }
                    },
                    scales: {
                        ...commonLineOptions.scales,
                        y: {
                            ...commonLineOptions.scales.y,
                            suggestedMin: suggestedMin,
                            suggestedMax: suggestedMax,
                            ticks: {
                                ...commonLineOptions.scales.y.ticks,
                                callback: (val) => val + ' ' + unit
                            }
                        }
                    }
                }
            });
        };

        // Inisialisasi 5 Grafik Parameter IoT Baru
        initSensorChart('ecChart', 'empty-ecChart', 'EC Nutrisi', ecValues, 'mS/cm', 'rgb(6, 182, 212)', thresholds.ec?.min, thresholds.ec?.max, 1.0, 3.5);
        initSensorChart('soilMoistureChart', 'empty-soilMoistureChart', 'Kelembapan Tanah', soilMoistures, '%', 'rgb(132, 204, 22)', thresholds.soil_moisture?.min, thresholds.soil_moisture?.max, 40, 100);
        initSensorChart('lightChart', 'empty-lightChart', 'Intensitas Cahaya', lightValues, 'lux', 'rgb(234, 179, 8)', thresholds.light_intensity?.min, thresholds.light_intensity?.max, 0, 60000);
        initSensorChart('nutrientTempChart', 'empty-nutrientTempChart', 'Suhu Larutan Nutrisi', nutrientTemps, '°C', 'rgb(244, 63, 94)', thresholds.nutrient_temperature?.min, thresholds.nutrient_temperature?.max, 15, 35);
        initSensorChart('tankLevelChart', 'empty-tankLevelChart', 'Level Tandon Nutrisi', tankLevels, '%', 'rgb(99, 102, 241)', thresholds.tank_level?.min, thresholds.tank_level?.max, 0, 100);
    }

    // Chart 6: Distribusi Kondisi Tanaman (Doughnut Chart)
    const ctxCondition = document.getElementById('conditionChart');
    if (ctxCondition && conditionLabels && conditionLabels.length > 0) {
        const palette = ['#10b981', '#0ea5e9', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6'];
        new Chart(ctxCondition, {
            type: 'doughnut',
            data: {
                labels: conditionLabels,
                datasets: [{
                    data: conditionCounts,
                    backgroundColor: palette.slice(0, conditionLabels.length),
                    borderColor: '#0f172a',
                    borderWidth: 2,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8',
                            font: { size: 11 },
                            boxWidth: 12,
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#e2e8f0',
                        bodyColor: '#38bdf8',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 10,
                    }
                },
                cutout: '65%',
            }
        });
    }

    // Chart 7: Status Budidaya Tanaman (Doughnut Chart)
    const ctxStatus = document.getElementById('statusChart');
    if (ctxStatus && statusLabels && statusLabels.length > 0) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: ['#10b981', '#0ea5e9', '#64748b'],
                    borderColor: '#0f172a',
                    borderWidth: 2,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8',
                            font: { size: 11 },
                            boxWidth: 12,
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#e2e8f0',
                        bodyColor: '#38bdf8',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 10,
                    }
                },
                cutout: '65%',
            }
        });
    }

    // Chart 8: Frekuensi Monitoring per Tanaman (Bar Chart)
    const ctxFrequency = document.getElementById('frequencyChart');
    if (ctxFrequency && frequencyLabels && frequencyLabels.length > 0) {
        new Chart(ctxFrequency, {
            type: 'bar',
            data: {
                labels: frequencyLabels,
                datasets: [{
                    label: 'Jumlah Observasi',
                    data: frequencyCounts,
                    backgroundColor: 'rgba(20, 184, 166, 0.7)',
                    borderColor: '#14b8a6',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    hoverBackgroundColor: 'rgba(20, 184, 166, 0.95)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#e2e8f0',
                        bodyColor: '#38bdf8',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 10,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 10 },
                            maxRotation: 45,
                            minRotation: 0,
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(51, 65, 85, 0.25)' },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 10 },
                            stepSize: 1,
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
