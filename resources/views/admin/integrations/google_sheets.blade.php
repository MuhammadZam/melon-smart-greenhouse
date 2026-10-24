@extends('layouts.app')

@section('title', 'Integrasi Google Sheets & Keamanan Perangkat — Melon Monitor')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-accent transition-colors">Dashboard</a>
                <span>/</span>
                <span class="text-ink">Administrasi</span>
                <span>/</span>
                <span class="text-accent font-semibold">Integrasi Google Sheets</span>
            </nav>
            <h1 class="ui-page-title text-xl sm:text-2xl font-semibold text-current tracking-tight flex items-center gap-3">
                <span class="w-2.5 h-6 rounded-[2px] bg-accent"></span>
                Integrasi Telemetri Google Sheets &amp; Keamanan IoT
            </h1>
            <p class="text-xs sm:text-sm text-muted mt-1">
                Jalur pipa data: ESP32 &rarr; Google Sheets &rarr; Ingestion Service &rarr; Alarm &amp; Dashboard.
            </p>
        </div>

        <!-- Sync Button & Trigger -->
        <div class="flex items-center gap-3">
            <form action="{{ route('admin.integrations.google-sheets.sync') }}" method="POST" class="flex flex-wrap items-center gap-3" x-data="{ syncing: false }" @submit="syncing = true" :aria-busy="syncing.toString()">
                @csrf
                <label class="inline-flex items-center gap-1.5 text-xs text-muted hover:text-ink cursor-pointer select-none">
                    <input type="checkbox" name="reset_cursor" value="1" class="rounded-[2px] bg-canvas border-line text-accent focus:ring-accent">
                    <span>Pindai Ulang Baris Awal (Reset Kursor)</span>
                </label>
                <button
                    type="submit"
                    :disabled="syncing"
                    class="ui-button-primary inline-flex items-center gap-2 px-4 py-2 rounded-[4px] text-sm font-medium transition-colors focus-visible:outline"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span x-text="syncing ? 'Menyinkronkan...' : 'Sinkronkan Sekarang'">Sinkronkan Sekarang</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Flash Messages -->
    @if (session('success'))
        <div class="p-4 rounded-[2px] bg-accent-soft border border-line text-accent text-xs sm:text-sm flex items-start gap-3">
            <svg class="w-5 h-5 text-accent shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <p class="font-semibold">{{ session('success') }}</p>
                @if (session('generated_token'))
                    <div class="mt-2 p-3 bg-canvas rounded-[2px] border border-line text-xs">
                        <p class="text-warning font-semibold mb-1">PENTING: Salin token autentikasi ini sekarang. Token hanya ditampilkan satu kali demi keamanan!</p>
                        <div class="tabular-nums text-accent bg-canvas p-2 rounded-[2px] select-all break-all">
                            {{ session('generated_token') }}
                        </div>
                        <p class="text-muted mt-1">Header HTTP: <code class="text-accent tabular-nums">X-Device-Token: {{ session('generated_token') }}</code></p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if (session('warning'))
        <div class="p-4 rounded-[2px] bg-warning-soft border border-warning/25 text-warning text-xs sm:text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-warning shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 rounded-[2px] bg-danger-soft border border-danger/25 text-danger text-xs sm:text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-danger shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- 4 Metrik Status Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Data Diimpor -->
        <div class="p-5 rounded-[2px] bg-canvas border border-line relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-muted">Data Ingested</span>
                <span class="p-2 rounded-[2px] bg-accent-soft text-accent border border-line">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-semibold text-current tabular-nums">{{ number_format($totalImported) }}</span>
                <span class="text-xs text-muted">record</span>
            </div>
            <div class="mt-2 text-xs text-muted flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-[2px] bg-accent-soft"></span>
                <span>Sumber Google Sheets</span>
            </div>
        </div>

        <!-- 2. Status Kesegaran Data -->
        <div class="p-5 rounded-[2px] bg-canvas border border-line relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-muted">Kesegaran Data</span>
                @if ($freshnessStatus === 'connected')
                    <span class="p-2 rounded-[2px] bg-accent-soft text-accent border border-line">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </span>
                @elseif ($freshnessStatus === 'stale')
                    <span class="p-2 rounded-[2px] bg-warning-soft text-warning border border-warning/25">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </span>
                @elseif ($freshnessStatus === 'error')
                    <span class="p-2 rounded-[2px] bg-danger-soft text-danger border border-danger/25">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </span>
                @else
                    <span class="p-2 rounded-[2px] bg-surface text-muted border border-line">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </span>
                @endif
            </div>
            <div class="mt-3">
                <span class="text-sm font-semibold text-current block truncate">{{ $freshnessLabel }}</span>
                <span class="text-xs text-muted mt-1 block">
                    {{ $config->last_successful_sync_at ? $config->last_successful_sync_at->diffForHumans() : 'Belum pernah sinkron' }}
                </span>
            </div>
        </div>

        <!-- 3. Deduplikasi & Idempotensi -->
        <div class="p-5 rounded-[2px] bg-canvas border border-line relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-muted">Record Dilewati</span>
                <span class="p-2 rounded-[2px] bg-surface text-muted border border-line">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-semibold text-current tabular-nums">{{ number_format($totalLogsSkipped) }}</span>
                <span class="text-xs text-muted">duplikat diabaikan</span>
            </div>
            <div class="mt-2 text-xs text-muted">
                Idempotensi hash source_identifier
            </div>
        </div>

        <!-- 4. Keamanan Perangkat IoT -->
        <div class="p-5 rounded-[2px] bg-canvas border border-line relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-muted">Device Security</span>
                <span class="p-2 rounded-[2px] bg-surface text-muted border border-line">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-semibold text-current tabular-nums">{{ $activeDevicesCount }}</span>
                <span class="text-xs text-muted">aktif / {{ $devices->count() }} perangkat</span>
            </div>
            <div class="mt-2 text-xs text-accent">
                SHA-256 Hashed Tokens Server-side
            </div>
        </div>
    </div>

    <!-- Section: Konfigurasi Integrasi & Disclaimer Arsitektur -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kartu Detail Konfigurasi -->
        <div class="lg:col-span-2 p-6 rounded-[2px] bg-canvas border border-line space-y-4">
            <div class="flex items-center justify-between border-b border-line pb-3">
                <h2 class="text-sm font-semibold text-current flex items-center gap-2">
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Parameter Konfigurasi Connector
                </h2>
                <span class="px-2.5 py-0.5 rounded-[2px] text-xs font-semibold tabular-nums {{ $config->isMock() ? 'bg-warning-soft text-warning border border-warning/25' : 'bg-accent-soft text-accent border border-line' }}">
                    MODE: {{ strtoupper($config->auth_mode) }}
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="space-y-1">
                    <span class="text-muted block">Spreadsheet ID:</span>
                    <span class="tabular-nums text-ink bg-canvas px-2 py-1 rounded-[2px] block truncate">
                        {{ $config->spreadsheet_id ?: '(Belum Dikonfigurasi / Mock Default)' }}
                    </span>
                </div>

                <div class="space-y-1">
                    <span class="text-muted block">Sheet &amp; Data Range:</span>
                    <span class="tabular-nums text-ink bg-canvas px-2 py-1 rounded-[2px] block">
                        {{ $config->sheet_name }}!{{ $config->data_range }}
                    </span>
                </div>

                <div class="space-y-1">
                    <span class="text-muted block">Kursor Baris Terakhir (High-water Mark):</span>
                    <span class="tabular-nums text-accent bg-canvas px-2 py-1 rounded-[2px] block font-semibold">
                        Baris #{{ $config->last_synced_row }}
                    </span>
                </div>

                <div class="space-y-1">
                    <span class="text-muted block">Interval Polling Terjadwal:</span>
                    <span class="tabular-nums text-ink bg-canvas px-2 py-1 rounded-[2px] block">
                        Setiap {{ $config->polling_interval_minutes }} Menit
                    </span>
                </div>

                <div class="space-y-1">
                    <span class="text-muted block">Waktu Terakhir Sinkron:</span>
                    <span class="text-ink block">
                        {{ $config->last_synced_at ? $config->last_synced_at->format('d M Y H:i:s') : '-' }}
                    </span>
                </div>

                <div class="space-y-1">
                    <span class="text-muted block">Waktu Terakhir Berhasil:</span>
                    <span class="text-accent block font-medium">
                        {{ $config->last_successful_sync_at ? $config->last_successful_sync_at->format('d M Y H:i:s') : '-' }}
                    </span>
                </div>
            </div>

            @if ($config->last_error)
                <div class="p-3 bg-danger-soft border border-danger/25 rounded-[2px] text-xs text-danger">
                    <span class="font-semibold block mb-0.5">Catatan Kesalahan Terakhir:</span>
                    <p class="tabular-nums text-xs whitespace-pre-wrap">{{ $config->last_error }}</p>
                </div>
            @endif
        </div>

        <!-- Kartu Catatan Arsitektur & Kepatuhan Kontrak -->
        <div class="p-6 rounded-[2px] bg-canvas border border-line space-y-3">
            <h3 class="text-sm font-semibold text-current flex items-center gap-2">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Batasan &amp; Kepatuhan Phase 14
            </h3>
            <ul class="text-xs text-muted space-y-2 list-disc list-inside">
                <li><strong class="text-ink">ESP32 Fisik:</strong> Integrasi live Google Sheets berstatus <span class="text-warning font-semibold">PENDING</span> sampai kredensial Service Account diserahkan oleh Tim Elektro.</li>
                <li><strong class="text-ink">Isolasi Status Aktuator:</strong> Status fisik <code class="text-ink">reported_pump_state</code> terpisah mutlak dari <code class="text-ink">simulation_state</code>.</li>
                <li><strong class="text-ink">Aturan Alarm 4-Parameter:</strong> Data dari Google Sheets tetap mengikuti kebijakan ketat evaluasi hanya pada <code class="text-accent">suhu, kelembapan, kelembapan tanah, dan pH</code>.</li>
                <li><strong class="text-ink">Idempotensi:</strong> Duplikasi baris dicegah melalui identitas unik <code class="text-ink">source_identifier</code>.</li>
            </ul>
        </div>
    </div>

    <!-- Section: Registrasi & Keamanan Perangkat IoT -->
    <div class="p-6 rounded-[2px] bg-canvas border border-line space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-line pb-3">
            <div>
                <h2 class="text-sm font-semibold text-current flex items-center gap-2">
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Daftar Perangkat IoT &amp; Autentikasi Token
                </h2>
                <p class="text-xs text-muted mt-0.5">Token perangkat disimpan secara aman dalam format SHA-256 hash. Plaintext token tidak pernah disimpan di database.</p>
            </div>

            <!-- Form Pendaftaran Perangkat Cepat -->
            <form action="{{ route('admin.integrations.google-sheets.device.store') }}" method="POST" class="flex flex-wrap items-center gap-2">
                @csrf
                <input aria-label="ID perangkat"
                    type="text"
                    name="device_id"
                    placeholder="Device ID (misal: ESP32-02)"
                    required
                    class="px-2.5 py-1.5 rounded-[2px] bg-canvas border border-line text-xs text-current placeholder-muted focus:ring-1 focus:ring-accent"
                />
                <input aria-label="Nama perangkat"
                    type="text"
                    name="name"
                    placeholder="Nama Perangkat"
                    required
                    class="px-2.5 py-1.5 rounded-[2px] bg-canvas border border-line text-xs text-current placeholder-muted focus:ring-1 focus:ring-accent"
                />
                <select aria-label="Jenis perangkat"
                    name="type"
                    class="px-2.5 py-1.5 rounded-[2px] bg-canvas border border-line text-xs text-ink focus:ring-1 focus:ring-accent"
                >
                    <option value="esp32_hardware">ESP32 Hardware</option>
                    <option value="python_simulator">Python Simulator</option>
                    <option value="google_sheets_gateway">Google Sheets Gateway</option>
                </select>
                <button
                    type="submit"
                    class="px-3 py-1.5 rounded-[2px] bg-accent hover:bg-accent text-current font-semibold text-xs transition-colors"
                >
                    + Daftarkan
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-ink">
                <thead class="bg-canvas text-muted font-semibold border-b border-line">
                    <tr>
                        <th class="px-4 py-3">Device ID</th>
                        <th class="px-4 py-3">Nama Perangkat</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Token Prefix</th>
                        <th class="px-4 py-3">Heartbeat Terakhir</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line tabular-nums">
                    @forelse ($devices as $dev)
                        <tr class="hover:bg-surface transition-colors">
                            <td class="px-4 py-3 font-semibold text-current">{{ $dev->device_id }}</td>
                            <td class="px-4 py-3 font-sans">{{ $dev->name }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-[2px] text-xs font-sans font-semibold bg-surface text-ink border border-line">
                                    {{ $dev->type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted">{{ $dev->token_prefix }}••••••••</td>
                            <td class="px-4 py-3 text-muted font-sans">
                                {{ $dev->last_seen_at ? $dev->last_seen_at->diffForHumans() : 'Belum Pernah' }}
                            </td>
                            <td class="px-4 py-3 font-sans">
                                @if ($dev->is_active && !$dev->isRevoked())
                                    <span class="px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-accent-soft text-accent border border-line">
                                        Aktif
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-danger-soft text-danger border border-danger/25">
                                        Dicabut (Revoked)
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-sans">
                                @if ($dev->is_active && !$dev->isRevoked())
                                    <form action="{{ route('admin.integrations.google-sheets.device.revoke', $dev) }}" method="POST" class="inline">
                                        @csrf
                                        <button
                                            type="submit"
                                            onclick="return confirm('Apakah Anda yakin ingin mencabut token untuk perangkat {{ $dev->device_id }}?');"
                                            class="px-2.5 py-1 rounded-[2px] bg-danger-soft hover:bg-danger-soft text-danger border border-danger/25 text-xs font-medium transition-colors"
                                        >
                                            Cabut Token
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.integrations.google-sheets.device.activate', $dev) }}" method="POST" class="inline">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="px-2.5 py-1 rounded-[2px] bg-accent-soft hover:bg-accent-soft text-accent border border-line text-xs font-medium transition-colors"
                                        >
                                            Aktifkan
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-muted font-sans">
                                Belum ada perangkat IoT yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section: Riwayat Log Sinkronisasi Google Sheets -->
    <div class="p-6 rounded-[2px] bg-canvas border border-line space-y-4">
        <div class="flex items-center justify-between border-b border-line pb-3">
            <h2 class="text-sm font-semibold text-current flex items-center gap-2">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Audit Log Sinkronisasi Google Sheets
            </h2>
            <span class="text-xs text-muted">Total Eksekusi: {{ $logs->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-ink">
                <thead class="bg-canvas text-muted font-semibold border-b border-line">
                    <tr>
                        <th class="px-4 py-3">Waktu Mulai</th>
                        <th class="px-4 py-3">Mode</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Diimpor</th>
                        <th class="px-4 py-3 text-right">Dilewati</th>
                        <th class="px-4 py-3 text-right">Gagal</th>
                        <th class="px-4 py-3">Kursor</th>
                        <th class="px-4 py-3">Pemicu</th>
                        <th class="px-4 py-3">Detail / Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line tabular-nums">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-surface transition-colors">
                            <td class="px-4 py-3 text-ink">{{ $log->started_at->format('d M Y H:i:s') }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-[2px] text-xs font-sans font-semibold bg-surface text-ink">
                                    {{ $log->mode }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-sans">
                                @if ($log->status === 'SUCCESS')
                                    <span class="px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-accent-soft text-accent border border-line">
                                        SUCCESS
                                    </span>
                                @elseif ($log->status === 'PARTIAL')
                                    <span class="px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-warning-soft text-warning border border-warning/25">
                                        PARTIAL
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-danger-soft text-danger border border-danger/25">
                                        FAILED
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-accent font-semibold">{{ $log->records_imported }}</td>
                            <td class="px-4 py-3 text-right text-muted">{{ $log->records_skipped }}</td>
                            <td class="px-4 py-3 text-right {{ $log->records_failed > 0 ? 'text-danger font-semibold' : 'text-muted' }}">{{ $log->records_failed }}</td>
                            <td class="px-4 py-3 text-muted">#{{ $log->cursor_before }} &rarr; #{{ $log->cursor_after }}</td>
                            <td class="px-4 py-3 text-muted font-sans">{{ $log->triggered_by }}</td>
                            <td class="px-4 py-3 font-sans text-xs text-muted max-w-xs truncate" title="{{ $log->error_details }}">
                                {{ $log->error_details ?: 'Normal (Tanpa Error)' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-muted font-sans">
                                Belum ada log eksekusi sinkronisasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
