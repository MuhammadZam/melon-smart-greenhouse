@extends('layouts.app')

@section('title', 'Dashboard Monitoring')

@section('content')
<div class="space-y-6">

    <!-- Top Action & Filter Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 backdrop-blur-xl">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider {{ $user->isAdmin() ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-teal-500/10 text-teal-400 border border-teal-500/20' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $user->isAdmin() ? 'bg-emerald-400' : 'bg-teal-400' }} mr-1.5 animate-pulse"></span>
                    {{ $user->isAdmin() ? 'Mode Administrator' : 'Mode Pengamat (User)' }}
                </span>
                <span class="text-xs text-slate-400">Database Live Sync</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                Ringkasan Pemantauan <span class="bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent">Tanaman Azolla</span>
            </h2>
            <p class="text-xs sm:text-sm text-slate-400 mt-0.5">
                Data aktual mikroklimat kolam, suhu, pH, dan kelembapan dari database MySQL.
            </p>
        </div>

        <!-- Filter Tanaman Dropdown -->
        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2.5 self-start md:self-auto">
            <label for="plant_filter" class="text-xs font-semibold text-slate-300 whitespace-nowrap flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                <span>Filter Kolam:</span>
            </label>
            <div class="relative">
                <select
                    name="plant_id"
                    id="plant_filter"
                    onchange="this.form.submit()"
                    class="appearance-none pl-3.5 pr-8 py-2 rounded-xl bg-slate-950 border border-slate-700/80 hover:border-emerald-500/50 text-white text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer transition-colors shadow-sm"
                >
                    <option value="all" {{ $selectedPlantId === 'all' ? 'selected' : '' }}>Semua Kolam (Akumulasi)</option>
                    @foreach ($plants as $p)
                        <option value="{{ $p->id }}" {{ (string)$selectedPlantId === (string)$p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
        </form>
    </div>

    <!-- 1. SUMMARY CARDS (Data Aktual dari MySQL) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total Tanaman -->
        <x-summary-card
            title="Total Tanaman"
            :value="$totalPlants"
            unit="Kolam"
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

        <!-- Card 2: Total Monitoring -->
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                Histori tercatat
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
                    <span class="font-bold text-white">{{ $plantStatusCounts['active'] }} Kolam</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <x-status-badge status="harvested" />
                    <span class="font-semibold text-slate-300">{{ $plantStatusCounts['harvested'] }} Kolam</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <x-status-badge status="inactive" />
                    <span class="font-semibold text-slate-300">{{ $plantStatusCounts['inactive'] }} Kolam</span>
                </div>
            </div>
        </x-summary-card>

    </div>

    <!-- 2. GRAFIK MONITORING (CHART.JS DARI DATABASE) -->
    <div id="charts" class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                    Grafik Tren Perkembangan Lingkungan Azolla
                </h3>
                <p class="text-xs text-slate-400">Visualisasi data berkala yang diambil langsung dari database MySQL.</p>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono bg-slate-900 border border-slate-800 text-slate-300">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Chart.js Aktif
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            
            <!-- Chart 1: Suhu Air (°C) -->
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Perubahan Suhu Air</span>
                        <span class="text-[11px] font-mono bg-emerald-500/10 text-emerald-300 px-2 py-0.5 rounded border border-emerald-500/20">°C</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mb-4">Rentang ideal: 20°C &ndash; 30°C</p>
                </div>
                <div class="relative h-60 w-full">
                    <canvas id="tempChart"></canvas>
                </div>
            </div>

            <!-- Chart 2: Derajat Keasaman pH -->
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-teal-400 uppercase tracking-wider">Tingkat Keasaman (pH)</span>
                        <span class="text-[11px] font-mono bg-teal-500/10 text-teal-300 px-2 py-0.5 rounded border border-teal-500/20">pH</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mb-4">Rentang ideal: 6.0 &ndash; 7.5</p>
                </div>
                <div class="relative h-60 w-full">
                    <canvas id="phChart"></canvas>
                </div>
            </div>

            <!-- Chart 3: Kelembapan Udara (%) -->
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-sky-400 uppercase tracking-wider">Kelembapan Udara</span>
                        <span class="text-[11px] font-mono bg-sky-500/10 text-sky-300 px-2 py-0.5 rounded border border-sky-500/20">% RH</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mb-4">Rentang ideal: 60% &ndash; 85%</p>
                </div>
                <div class="relative h-60 w-full">
                    <canvas id="humidityChart"></canvas>
                </div>
            </div>

        </div>
    </div>

    <!-- 3. TABEL MONITORING TERBARU (Data Aktual) -->
    <div id="recent-table" class="p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800/80 pb-4">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Data Pengamatan Terkini (Monitoring Terbaru)
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Menampilkan hingga 8 catatan observasi terbaru yang tersimpan di MySQL.</p>
            </div>
            <span class="text-xs text-slate-400 font-mono">
                Total Riwayat: <span class="text-white font-bold">{{ $totalMonitorings }}</span>
            </span>
        </div>

        <!-- Responsive Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead>
                    <tr class="border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400 bg-slate-950/40">
                        <th class="py-3 px-4 rounded-l-xl">Tanaman / Kolam</th>
                        <th class="py-3 px-4">Waktu Monitoring</th>
                        <th class="py-3 px-4 text-center">Suhu</th>
                        <th class="py-3 px-4 text-center">pH</th>
                        <th class="py-3 px-4 text-center">Kelembapan</th>
                        <th class="py-3 px-4">Kondisi &amp; Warna</th>
                        <th class="py-3 px-4 text-center rounded-r-xl">Status</th>
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
                                <span class="font-bold {{ $item->temperature >= 20 && $item->temperature <= 30 ? 'text-emerald-400' : 'text-amber-400' }}">
                                    {{ number_format($item->temperature, 1) }}°C
                                </span>
                            </td>

                            <!-- Kolom pH -->
                            <td class="py-3 px-4 text-center font-mono">
                                <span class="font-bold {{ $item->ph >= 6.0 && $item->ph <= 7.5 ? 'text-teal-400' : 'text-rose-400' }}">
                                    {{ number_format($item->ph, 2) }}
                                </span>
                            </td>

                            <!-- Kolom Kelembapan -->
                            <td class="py-3 px-4 text-center font-mono">
                                <span class="font-bold {{ $item->humidity >= 60 && $item->humidity <= 85 ? 'text-sky-400' : 'text-amber-400' }}">
                                    {{ number_format($item->humidity, 0) }}%
                                </span>
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                Belum ada data monitoring yang tercatat untuk filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 4. SECTION KHUSUS ROLE ADMINISTRATOR -->
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
                        <p class="text-xs text-slate-400">Parameter threshold acuan sistem monitoring Azolla.</p>
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

    // Opsi Global Chart.js
    const commonOptions = {
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
                displayColors: false,
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

    // Chart 1: Suhu (°C)
    const ctxTemp = document.getElementById('tempChart');
    if (ctxTemp) {
        new Chart(ctxTemp, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Suhu (°C)',
                    data: temperatures,
                    borderColor: '#10b981', // emerald-500
                    backgroundColor: 'rgba(16, 185, 129, 0.12)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    ...commonOptions.scales,
                    y: {
                        ...commonOptions.scales.y,
                        min: 18,
                        max: 35,
                        ticks: {
                            ...commonOptions.scales.y.ticks,
                            callback: (val) => val + '°C'
                        }
                    }
                }
            }
        });
    }

    // Chart 2: pH
    const ctxPh = document.getElementById('phChart');
    if (ctxPh) {
        new Chart(ctxPh, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'pH Air',
                    data: phValues,
                    borderColor: '#14b8a6', // teal-500
                    backgroundColor: 'rgba(20, 184, 166, 0.12)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointBackgroundColor: '#14b8a6',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    ...commonOptions.scales,
                    y: {
                        ...commonOptions.scales.y,
                        min: 5.0,
                        max: 9.0,
                        ticks: {
                            ...commonOptions.scales.y.ticks,
                            callback: (val) => val + ' pH'
                        }
                    }
                }
            }
        });
    }

    // Chart 3: Kelembapan (%)
    const ctxHumidity = document.getElementById('humidityChart');
    if (ctxHumidity) {
        new Chart(ctxHumidity, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Kelembapan (%)',
                    data: humidities,
                    borderColor: '#0ea5e9', // sky-500
                    backgroundColor: 'rgba(14, 165, 233, 0.12)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointBackgroundColor: '#0ea5e9',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    ...commonOptions.scales,
                    y: {
                        ...commonOptions.scales.y,
                        min: 40,
                        max: 100,
                        ticks: {
                            ...commonOptions.scales.y.ticks,
                            callback: (val) => val + '%'
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
