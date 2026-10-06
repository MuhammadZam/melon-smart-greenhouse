@extends('layouts.app')

@section('title', 'Dashboard Monitoring & Analytics')

@section('content')
<div class="space-y-6">

    <!-- Top Action & Filter Bar Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 backdrop-blur-xl shadow-xl">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider {{ $user->isAdmin() ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-teal-500/10 text-teal-400 border border-teal-500/20' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $user->isAdmin() ? 'bg-emerald-400' : 'bg-teal-400' }} mr-1.5 animate-pulse"></span>
                    {{ $user->isAdmin() ? 'Mode Administrator' : 'Mode Pengamat (User)' }}
                </span>
                <span class="text-xs text-slate-400">Database Live Analytics</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                Ringkasan Pemantauan <span class="bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent">Tanaman Melon</span>
            </h2>
            <p class="text-xs sm:text-sm text-slate-400 mt-0.5">
                Visualisasi komprehensif mikroklimat greenhouse, dinamika nutrisi, dan perkembangan vegetatif melon.
            </p>
        </div>

        <div class="flex items-center gap-2.5 self-start md:self-auto">
            <a
                href="{{ route('monitorings.index') }}"
                class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-sm hover:text-white"
            >
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span>Kelola Monitoring</span>
            </a>
            <a
                href="{{ route('monitorings.create') }}"
                class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-1.5 transition-colors shadow-md shadow-emerald-600/30"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>+ Catat Data</span>
            </a>
        </div>
    </div>

    <!-- 1. SUMMARY CARDS (Data Aktual dari Database) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total Tanaman -->
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
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Terdaftar di database
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
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Hari ini: <strong class="text-teal-300">{{ $todayMonitoringCount }}</strong> observasi
            </x-slot:subtitle>
        </x-summary-card>

        <!-- Card 3: Monitoring Terakhir -->
        <x-summary-card
            title="Monitoring Terakhir"
            color="sky"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </x-slot:icon>
            <div class="text-base sm:text-lg font-bold text-white tracking-tight truncate">
                @if ($latestMonitoring)
                    {{ $latestMonitoring->monitored_at->format('d M Y, H:i') }}
                @else
                    Belum Ada Data
                @endif
            </div>
            <div class="text-[11px] text-slate-400 mt-2 flex items-center gap-2">
                @if ($latestMonitoring)
                    <span class="text-emerald-400 font-mono font-semibold">{{ $latestMonitoring->temperature }}°C</span> &bull;
                    <span class="text-teal-400 font-mono font-semibold">pH {{ $latestMonitoring->ph }}</span> &bull;
                    <span class="text-sky-400 font-mono font-semibold">{{ $latestMonitoring->humidity }}%</span>
                @else
                    <span>Menunggu input pertama</span>
                @endif
            </div>
        </x-summary-card>

        <!-- Card 4: Status Tanaman (Active, Harvested, Inactive) -->
        <x-summary-card
            id="plant-summary"
            title="Status Tanaman"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </x-slot:icon>
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-xs">
                    <x-status-badge status="active" />
                    <span class="font-bold text-white">{{ $plantStatusCounts['active'] }} Tanaman</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <x-status-badge status="harvested" />
                    <span class="font-semibold text-slate-300">{{ $plantStatusCounts['harvested'] }} Tanaman</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <x-status-badge status="inactive" />
                    <span class="font-semibold text-slate-300">{{ $plantStatusCounts['inactive'] }} Tanaman</span>
                </div>
            </div>
        </x-summary-card>

    </div>

    <!-- 2. FILTER MONITORING & QUICK RANGE BAR -->
    <div class="p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-slate-800/80 pb-4">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Filter Visualisasi &amp; Rentang Tanggal
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    Saring data grafik tren dan statistik analitik berdasarkan tanaman atau periode observasi.
                </p>
            </div>
            
            <!-- Quick Range Selection -->
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] font-semibold text-slate-400 mr-1 hidden sm:inline">Quick Range:</span>
                <a
                    href="{{ route('dashboard', array_merge(request()->except(['range', 'date_from', 'date_to']), ['range' => 'today'])) }}"
                    class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors {{ $selectedRange === 'today' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-800 hover:bg-slate-700 text-slate-300' }}"
                >
                    Hari Ini
                </a>
                <a
                    href="{{ route('dashboard', array_merge(request()->except(['range', 'date_from', 'date_to']), ['range' => '7d'])) }}"
                    class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors {{ $selectedRange === '7d' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-800 hover:bg-slate-700 text-slate-300' }}"
                >
                    7 Hari
                </a>
                <a
                    href="{{ route('dashboard', array_merge(request()->except(['range', 'date_from', 'date_to']), ['range' => '30d'])) }}"
                    class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors {{ $selectedRange === '30d' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-800 hover:bg-slate-700 text-slate-300' }}"
                >
                    30 Hari
                </a>
                <a
                    href="{{ route('dashboard', array_merge(request()->except(['range', 'date_from', 'date_to']), ['range' => 'all'])) }}"
                    class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors {{ $selectedRange === 'all' && empty($dateFrom) && empty($dateTo) ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-800 hover:bg-slate-700 text-slate-300' }}"
                >
                    Semua Data
                </a>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 items-end">
            
            <!-- 1. Dropdown Tanaman -->
            <div>
                <label for="plant_filter" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                    Pilih Tanaman
                </label>
                <div class="relative">
                    <select
                        name="plant_id"
                        id="plant_filter"
                        class="w-full appearance-none pl-3.5 pr-8 py-2 rounded-xl bg-slate-950 border border-slate-700/80 hover:border-emerald-500/50 text-white text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer transition-colors"
                    >
                        <option value="all" {{ $selectedPlantId === 'all' ? 'selected' : '' }}>Semua Tanaman (Akumulasi)</option>
                        @foreach ($plants as $p)
                            <option value="{{ $p->id }}" {{ (string)$selectedPlantId === (string)$p->id ? 'selected' : '' }}>
                                {{ $p->name }} {{ $p->location ? '— ' . $p->location : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- 2. Tanggal Mulai -->
            <div>
                <label for="date_from" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                    Tanggal Mulai
                </label>
                <input
                    type="date"
                    name="date_from"
                    id="date_from"
                    value="{{ $dateFrom }}"
                    class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-700/80 hover:border-emerald-500/50 text-white text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                >
            </div>

            <!-- 3. Tanggal Akhir -->
            <div>
                <label for="date_to" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                    Tanggal Akhir
                </label>
                <input
                    type="date"
                    name="date_to"
                    id="date_to"
                    value="{{ $dateTo }}"
                    class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-700/80 hover:border-emerald-500/50 text-white text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                >
            </div>

            <!-- 4. Tombol Aksi Filter -->
            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="flex-1 py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition-colors shadow-sm flex items-center justify-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <span>Terapkan Filter</span>
                </button>
                <a
                    href="{{ route('dashboard') }}"
                    class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition-colors border border-slate-700 flex items-center justify-center"
                    title="Reset Filter ke Default"
                >
                    Reset
                </a>
            </div>

        </form>

        <!-- Informasi Filter Aktif -->
        <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1">
            <div>
                Menampilkan data: <strong class="text-white">{{ $filteredCount }}</strong> rekaman observasi sesuai filter aktif.
            </div>
            @if ($selectedPlantId !== 'all' || !empty($dateFrom) || !empty($dateTo) || $selectedRange !== 'all')
                <span class="inline-flex items-center gap-1 text-emerald-400 font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    Filter Diterapkan
                </span>
            @endif
        </div>
    </div>

    <!-- 3. PARAMETER AGGREGATION ANALYTICS (AVG, MIN, MAX dari Database Aktual) -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Statistik Agregasi Parameter (Data Terfilter)
            </h3>
            <span class="text-xs text-slate-400">Agregasi SQL Server-Side (AVG / MIN / MAX)</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
            
            <!-- Parameter 1: Suhu Udara -->
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-md">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-bold text-emerald-400 uppercase tracking-wider text-[11px]">Suhu Udara</span>
                    <span class="font-mono text-[10px] bg-emerald-500/10 text-emerald-300 px-1.5 py-0.5 rounded border border-emerald-500/20">°C</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Rata-rata:</span>
                        <span class="font-bold font-mono text-white">{{ $aggregations && $aggregations->avg_temp ? number_format($aggregations->avg_temp, 1) . ' °C' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Minimum:</span>
                        <span class="font-mono text-emerald-400">{{ $aggregations && $aggregations->min_temp ? number_format($aggregations->min_temp, 1) . ' °C' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Maksimum:</span>
                        <span class="font-mono text-amber-400">{{ $aggregations && $aggregations->max_temp ? number_format($aggregations->max_temp, 1) . ' °C' : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Parameter 2: pH Media / Nutrisi -->
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-md">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-bold text-teal-400 uppercase tracking-wider text-[11px]">pH Nutrisi</span>
                    <span class="font-mono text-[10px] bg-teal-500/10 text-teal-300 px-1.5 py-0.5 rounded border border-teal-500/20">pH</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Rata-rata:</span>
                        <span class="font-bold font-mono text-white">{{ $aggregations && $aggregations->avg_ph ? number_format($aggregations->avg_ph, 2) : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Minimum:</span>
                        <span class="font-mono text-teal-400">{{ $aggregations && $aggregations->min_ph ? number_format($aggregations->min_ph, 2) : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Maksimum:</span>
                        <span class="font-mono text-rose-400">{{ $aggregations && $aggregations->max_ph ? number_format($aggregations->max_ph, 2) : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Parameter 3: Kelembapan Udara -->
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-md">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-bold text-sky-400 uppercase tracking-wider text-[11px]">Kelembapan</span>
                    <span class="font-mono text-[10px] bg-sky-500/10 text-sky-300 px-1.5 py-0.5 rounded border border-sky-500/20">% RH</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Rata-rata:</span>
                        <span class="font-bold font-mono text-white">{{ $aggregations && $aggregations->avg_humidity ? number_format($aggregations->avg_humidity, 1) . '%' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Minimum:</span>
                        <span class="font-mono text-sky-400">{{ $aggregations && $aggregations->min_humidity ? number_format($aggregations->min_humidity, 1) . '%' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Maksimum:</span>
                        <span class="font-mono text-amber-400">{{ $aggregations && $aggregations->max_humidity ? number_format($aggregations->max_humidity, 1) . '%' : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Parameter 4: Tinggi Tanaman -->
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-md">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-bold text-violet-400 uppercase tracking-wider text-[11px]">Tinggi Tanaman</span>
                    <span class="font-mono text-[10px] bg-violet-500/10 text-violet-300 px-1.5 py-0.5 rounded border border-violet-500/20">cm</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Rata-rata:</span>
                        <span class="font-bold font-mono text-white">{{ $aggregations && $aggregations->avg_height ? number_format($aggregations->avg_height, 1) . ' cm' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Minimum:</span>
                        <span class="font-mono text-violet-400">{{ $aggregations && $aggregations->min_height ? number_format($aggregations->min_height, 1) . ' cm' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Maksimum:</span>
                        <span class="font-mono text-violet-300">{{ $aggregations && $aggregations->max_height ? number_format($aggregations->max_height, 1) . ' cm' : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Parameter 5: Kerapatan Kanopi -->
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-md">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-bold text-amber-400 uppercase tracking-wider text-[11px]">Kerapatan Tajuk</span>
                    <span class="font-mono text-[10px] bg-amber-500/10 text-amber-300 px-1.5 py-0.5 rounded border border-amber-500/20">%</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Rata-rata:</span>
                        <span class="font-bold font-mono text-white">{{ $aggregations && $aggregations->avg_density ? number_format($aggregations->avg_density, 1) . '%' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Minimum:</span>
                        <span class="font-mono text-amber-400">{{ $aggregations && $aggregations->min_density ? number_format($aggregations->min_density, 1) . '%' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Maksimum:</span>
                        <span class="font-mono text-emerald-400">{{ $aggregations && $aggregations->max_density ? number_format($aggregations->max_density, 1) . '%' : '-' }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- 4. SECTION GRAFIK VISUALISASI (CHART.JS DARI DATABASE) -->
    <div id="charts" class="space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                    Grafik Tren &amp; Visualisasi Analitik Melon
                </h3>
                <p class="text-xs text-slate-400">Visualisasi data observasi berkala bersumber langsung dari database MySQL.</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono bg-slate-900 border border-slate-800 text-slate-300 self-start sm:self-auto">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Chart.js Aktif
            </span>
        </div>

        <!-- EMPTY STATE JIKA TIDAK ADA DATA TERFILTER -->
        @if ($filteredCount === 0 || count($chartLabels) === 0)
            <div class="p-8 sm:p-12 text-center rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-base sm:text-lg font-bold text-white">Tidak ada data monitoring pada periode yang dipilih.</h4>
                    <p class="text-xs sm:text-sm text-slate-400 max-w-md mx-auto mt-1">
                        Kombinasi filter tanaman atau rentang tanggal yang dipilih tidak menghasilkan rekaman observasi di database.
                    </p>
                </div>
                <div class="pt-2">
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold transition-colors"
                    >
                        <span>Reset Filter ke Semua Data</span>
                    </a>
                </div>
            </div>
        @endif

        <!-- A. MAIN MONITORING TRENDS (Suhu, pH, Kelembapan) -->
        <div class="space-y-3 {{ $filteredCount === 0 ? 'hidden' : '' }}">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">1. Tren Parameter Lingkungan &amp; Nutrisi</h4>
                <span class="text-[11px] text-slate-500 italic">*Garis putus-putus = Configured Monitoring Threshold</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                
                <!-- Chart 1: Suhu (°C) -->
                <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Tren Suhu Monitoring</span>
                            <span class="text-[11px] font-mono bg-emerald-500/10 text-emerald-300 px-2 py-0.5 rounded border border-emerald-500/20">°C</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-3">
                            Configured Monitoring Threshold: {{ $thresholds['temperature']['min'] }}°C &ndash; {{ $thresholds['temperature']['max'] }}°C
                        </p>
                    </div>
                    <div class="relative h-60 w-full">
                        <canvas id="tempChart"></canvas>
                    </div>
                </div>

                <!-- Chart 2: pH Nutrisi -->
                <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-teal-400 uppercase tracking-wider">Tren pH</span>
                            <span class="text-[11px] font-mono bg-teal-500/10 text-teal-300 px-2 py-0.5 rounded border border-teal-500/20">pH</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-3">
                            Configured Monitoring Threshold: {{ $thresholds['ph']['min'] }} &ndash; {{ $thresholds['ph']['max'] }}
                        </p>
                    </div>
                    <div class="relative h-60 w-full">
                        <canvas id="phChart"></canvas>
                    </div>
                </div>

                <!-- Chart 3: Kelembapan (%) -->
                <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-sky-400 uppercase tracking-wider">Tren Kelembapan Udara</span>
                            <span class="text-[11px] font-mono bg-sky-500/10 text-sky-300 px-2 py-0.5 rounded border border-sky-500/20">% RH</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-3">
                            Configured Monitoring Threshold: {{ $thresholds['humidity']['min'] }}% &ndash; {{ $thresholds['humidity']['max'] }}%
                        </p>
                    </div>
                    <div class="relative h-60 w-full">
                        <canvas id="humidityChart"></canvas>
                    </div>
                </div>

            </div>
        </div>

        <!-- B. GROWTH TRENDS (Tinggi Tanaman & Kerapatan Kanopi) -->
        <div class="space-y-3 {{ $filteredCount === 0 ? 'hidden' : '' }}">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">2. Tren Indikator Pertumbuhan Tanaman</h4>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                
                <!-- Chart 4: Tinggi Tanaman (cm) -->
                <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-violet-400 uppercase tracking-wider">Perkembangan Tinggi Tanaman</span>
                            <span class="text-[11px] font-mono bg-violet-500/10 text-violet-300 px-2 py-0.5 rounded border border-violet-500/20">cm</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-3">
                            Perubahan tinggi batang tanaman melon berdasarkan kronologis waktu observasi.
                        </p>
                    </div>
                    <div class="relative h-64 w-full">
                        <canvas id="heightChart"></canvas>
                    </div>
                </div>

                <!-- Chart 5: Kerapatan Kanopi (%) -->
                <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-amber-400 uppercase tracking-wider">Perkembangan Kerapatan Kanopi</span>
                            <span class="text-[11px] font-mono bg-amber-500/10 text-amber-300 px-2 py-0.5 rounded border border-amber-500/20">%</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-3">
                            Perkembangan tutupan tajuk daun tanaman melon dari waktu ke waktu.
                        </p>
                    </div>
                    <div class="relative h-64 w-full">
                        <canvas id="densityChart"></canvas>
                    </div>
                </div>

            </div>
        </div>

        <!-- C. DISTRIBUTION & FREQUENCY CHARTS (Status Tanaman, Kondisi, Frekuensi Monitoring) -->
        <div class="space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">3. Distribusi Status &amp; Frekuensi Monitoring</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                
                <!-- Chart 6: Distribusi Kondisi Tanaman (Doughnut) -->
                <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Distribusi Kondisi Tanaman</span>
                            <span class="text-[10px] font-semibold bg-slate-800 text-slate-300 px-2 py-0.5 rounded">Doughnut</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-3">
                            Proporsi kondisi kesehatan fisik tanaman dari data observasi database.
                        </p>
                    </div>
                    <div class="relative h-60 w-full flex items-center justify-center">
                        @if (count($conditionCounts) > 0)
                            <canvas id="conditionChart"></canvas>
                        @else
                            <div class="text-xs text-slate-400 text-center py-10">Tidak ada data kondisi.</div>
                        @endif
                    </div>
                </div>

                <!-- Chart 7: Status Budidaya Tanaman (Doughnut) -->
                <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-sky-400 uppercase tracking-wider">Status Budidaya Tanaman</span>
                            <span class="text-[10px] font-semibold bg-slate-800 text-slate-300 px-2 py-0.5 rounded">Doughnut</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-3">
                            Sebaran status budidaya tanaman melon (Aktif, Sudah Dipanen, Tidak Aktif).
                        </p>
                    </div>
                    <div class="relative h-60 w-full flex items-center justify-center">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- Chart 8: Monitoring Frequency (Bar Chart) -->
                <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl flex flex-col justify-between md:col-span-2 lg:col-span-1">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-teal-400 uppercase tracking-wider">Jumlah Monitoring per Tanaman</span>
                            <span class="text-[10px] font-semibold bg-slate-800 text-slate-300 px-2 py-0.5 rounded">Bar</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-3">
                            Frekuensi observasi tercatat untuk masing-masing spesimen tanaman.
                        </p>
                    </div>
                    <div class="relative h-60 w-full">
                        <canvas id="frequencyChart"></canvas>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- 5. TABEL MONITORING TERBARU (Data Aktual dari MySQL) -->
    <div id="recent-table" class="p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800/80 pb-4">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Data Pengamatan Terkini (Monitoring Terbaru)
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Menampilkan hingga 8 catatan observasi terbaru yang tersimpan di database.</p>
            </div>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('monitorings.create') }}"
                    class="px-3 py-1.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-xs font-semibold flex items-center gap-1.5 transition-colors"
                    title="Tambah Catatan Monitoring Baru"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>+ Tambah</span>
                </a>
                <a
                    href="{{ route('monitorings.index') }}"
                    class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold flex items-center gap-1.5 transition-colors border border-slate-700/60"
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
                    <tr class="border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400 bg-slate-950/40">
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
                <tbody class="divide-y divide-slate-800/60">
                    @forelse ($recentMonitorings as $item)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <!-- Kolom Tanaman -->
                            <td class="py-3 px-4">
                                <div class="font-semibold text-white">{{ $item->plant->name ?? 'Tanaman #' . $item->plant_id }}</div>
                                <div class="text-[10px] text-slate-400">{{ $item->plant->location ?? '-' }}</div>
                            </td>

                            <!-- Kolom Waktu -->
                            <td class="py-3 px-4 text-slate-300 font-mono text-[11px]">
                                {{ $item->monitored_at->format('d M Y, H:i') }}
                            </td>

                            <!-- Kolom Suhu -->
                            <td class="py-3 px-4 text-center font-mono">
                                <span class="font-bold {{ $item->temperature >= 20 && $item->temperature <= 32 ? 'text-emerald-400' : 'text-amber-400' }}">
                                    {{ number_format($item->temperature, 1) }}°C
                                </span>
                            </td>

                            <!-- Kolom pH -->
                            <td class="py-3 px-4 text-center font-mono">
                                <span class="font-bold {{ $item->ph >= 5.8 && $item->ph <= 7.2 ? 'text-teal-400' : 'text-rose-400' }}">
                                    {{ number_format($item->ph, 2) }}
                                </span>
                            </td>

                            <!-- Kolom Kelembapan -->
                            <td class="py-3 px-4 text-center font-mono">
                                <span class="font-bold {{ $item->humidity >= 60 && $item->humidity <= 85 ? 'text-sky-400' : 'text-amber-400' }}">
                                    {{ number_format($item->humidity, 0) }}%
                                </span>
                            </td>

                            <!-- Kolom Tinggi -->
                            <td class="py-3 px-4 text-center font-mono text-slate-200">
                                {{ $item->plant_height ? number_format($item->plant_height, 1) . ' cm' : '-' }}
                            </td>

                            <!-- Kolom Kerapatan -->
                            <td class="py-3 px-4 text-center font-mono text-slate-200">
                                {{ $item->plant_density ? number_format($item->plant_density, 0) . '%' : '-' }}
                            </td>

                            <!-- Kolom Kondisi Visual -->
                            <td class="py-3 px-4">
                                <div class="text-slate-200">{{ $item->plant_condition }}</div>
                                <div class="text-[10px] text-slate-400">{{ $item->plant_color }}</div>
                            </td>

                            <!-- Kolom Status Badge -->
                            <td class="py-3 px-4 text-center">
                                <x-status-badge :status="$item->plant_condition" />
                            </td>

                            <!-- Kolom Aksi -->
                            <td class="py-3 px-4 text-center">
                                <a
                                    href="{{ route('monitorings.show', $item) }}"
                                    class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors border border-slate-700/60 inline-flex items-center gap-1 text-[11px]"
                                    title="Lihat Detail"
                                >
                                    <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <span>Detail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-slate-400">
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
        <div id="admin-section" class="p-6 rounded-3xl bg-gradient-to-r from-emerald-950/30 via-slate-900 to-slate-900 border border-emerald-500/20 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">Panel Administrasi (Khusus Admin)</h4>
                        <p class="text-xs text-slate-400">Parameter threshold acuan sistem monitoring Melon.</p>
                    </div>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono">
                    ADMIN ONLY
                </span>
            </div>

            <!-- Parameters Grid Table -->
            <div id="parameters-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-2">
                @foreach ($parameters as $param)
                    <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800">
                        <div class="text-[11px] font-bold text-slate-300 truncate">{{ $param->parameter_name }}</div>
                        <div class="text-base font-extrabold text-white mt-1">
                            {{ $param->minimum_value }} &ndash; {{ $param->maximum_value }}
                            <span class="text-[11px] font-normal text-slate-400">{{ $param->unit }}</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1 line-clamp-1" title="{{ $param->description }}">
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
                ticks: { color: '#94a3b8', font: { size: 10 } }
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
