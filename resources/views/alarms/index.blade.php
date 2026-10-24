@extends('layouts.app')

@section('title', 'Riwayat Alarm')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="ui-page-title text-xl font-semibold text-current flex items-center gap-3">
                <div class="w-9 h-9 rounded-[2px] flex items-center justify-center">
                    <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                Riwayat Alarm Sensor
            </h2>
            <p class="text-xs text-muted mt-1 ml-12">Deteksi otomatis pelanggaran threshold parameter sensor IoT</p>
        </div>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('alarms.thresholds') }}"
           class="flex items-center gap-2 px-4 py-2 rounded-[2px] bg-accent-soft hover:bg-accent-soft border border-line text-accent text-xs font-semibold transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Kelola Threshold
        </a>
        @endif
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Active Alarms --}}
        <div class="relative overflow-hidden rounded-[2px] bg-canvas border border-danger/25 p-5">
            <div class="absolute inset-0 pointer-events-none"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-danger">Alarm Aktif</p>
                    <p class="text-3xl font-semibold text-danger mt-1">{{ $totalActive }}</p>
                    <p class="text-xs text-muted mt-1">Butuh perhatian segera</p>
                </div>
                <div class="w-12 h-12 rounded-[2px] bg-danger-soft border border-danger/25 flex items-center justify-center {{ $totalActive > 0 ? '' : '' }}">
                    <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Critical Alarms --}}
        <div class="relative overflow-hidden rounded-[2px] bg-canvas border border-danger/25 p-5">
            <div class="absolute inset-0 pointer-events-none"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-danger">Alarm Kritis</p>
                    <p class="text-3xl font-semibold text-danger mt-1">{{ $totalCritical }}</p>
                    <p class="text-xs text-muted mt-1">Severity critical aktif</p>
                </div>
                <div class="w-12 h-12 rounded-[2px] bg-danger-soft border border-danger/25 flex items-center justify-center {{ $totalCritical > 0 ? '' : '' }}">
                    <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Resolved --}}
        <div class="relative overflow-hidden rounded-[2px] bg-canvas border border-line p-5">
            <div class="absolute inset-0 pointer-events-none"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-accent">Total Diselesaikan</p>
                    <p class="text-3xl font-semibold text-accent mt-1">{{ $totalResolved }}</p>
                    <p class="text-xs text-muted mt-1">Semua waktu</p>
                </div>
                <div class="w-12 h-12 rounded-[2px] bg-accent-soft border border-line flex items-center justify-center">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="rounded-[2px] bg-canvas border border-line p-5">
        <form method="GET" action="{{ route('alarms.index') }}" class="space-y-4" id="alarm-filter-form">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <span class="text-xs font-semibold text-ink">Filter Alarm</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                {{-- Plant --}}
                <select aria-label="Tanaman" id="filter-plant" name="plant_id"
                    class="col-span-2 sm:col-span-1 bg-surface border border-line rounded-[2px] text-xs text-ink px-3 py-2 focus:outline-none focus:border-line transition-colors">
                    <option value="" {{ empty($filters['plant_id']) || $filters['plant_id'] === 'all' ? 'selected' : '' }}>Semua Tanaman</option>
                    @foreach($plants as $plant)
                        <option value="{{ $plant->id }}" {{ ($filters['plant_id'] ?? 'all') == $plant->id ? 'selected' : '' }}>
                            {{ $plant->name }}
                        </option>
                    @endforeach
                </select>

                {{-- Parameter --}}
                <select aria-label="Parameter sensor" id="filter-parameter" name="parameter_key"
                    class="col-span-2 sm:col-span-1 bg-surface border border-line rounded-[2px] text-xs text-ink px-3 py-2 focus:outline-none focus:border-line transition-colors">
                    <option value="all">Semua Parameter</option>
                    @foreach($parameterKeys as $key => $label)
                        <option value="{{ $key }}" {{ ($filters['parameter_key'] ?? 'all') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                {{-- Severity --}}
                <select aria-label="Tingkat alarm" id="filter-severity" name="severity"
                    class="bg-surface border border-line rounded-[2px] text-xs text-ink px-3 py-2 focus:outline-none focus:border-line transition-colors">
                    <option value="all" {{ ($filters['severity'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua Severity</option>
                    <option value="warning" {{ ($filters['severity'] ?? '') === 'warning' ? 'selected' : '' }}>⚠ Warning</option>
                    <option value="critical" {{ ($filters['severity'] ?? '') === 'critical' ? 'selected' : '' }}>🔴 Critical</option>
                </select>

                {{-- Status --}}
                <select aria-label="Status alarm" id="filter-status" name="status"
                    class="bg-surface border border-line rounded-[2px] text-xs text-ink px-3 py-2 focus:outline-none focus:border-line transition-colors">
                    <option value="all" {{ ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>🔴 Aktif</option>
                    <option value="resolved" {{ ($filters['status'] ?? '') === 'resolved' ? 'selected' : '' }}>✅ Selesai</option>
                </select>

                {{-- Date From --}}
                <input aria-label="Tanggal mulai" type="date" id="filter-date-from" name="date_from"
                    value="{{ $filters['date_from'] ?? '' }}"
                    class="bg-surface border border-line rounded-[2px] text-xs text-ink px-3 py-2 focus:outline-none focus:border-line transition-colors">

                {{-- Date To --}}
                <input aria-label="Tanggal akhir" type="date" id="filter-date-to" name="date_to"
                    value="{{ $filters['date_to'] ?? '' }}"
                    class="bg-surface border border-line rounded-[2px] text-xs text-ink px-3 py-2 focus:outline-none focus:border-line transition-colors">
            </div>

            <div class="flex items-center gap-3 pt-1">
                <button type="submit" id="btn-filter-apply"
                    class="px-4 py-2 rounded-[2px] bg-accent hover:bg-accent text-current text-xs font-semibold transition-all">
                    Terapkan Filter
                </button>
                <a href="{{ route('alarms.index') }}"
                    class="px-4 py-2 rounded-[2px] bg-surface hover:bg-surface text-ink text-xs font-semibold transition-all">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Alarm Table --}}
    <div class="rounded-[2px] bg-canvas border border-line overflow-hidden">
        <div class="px-5 py-4 border-b border-line flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-current">Daftar Alarm</h3>
                <p class="text-xs text-muted mt-0.5">{{ $alarms->total() }} alarm ditemukan</p>
            </div>
        </div>

        @if($alarms->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-muted">
            <svg class="w-12 h-12 mb-4 text-ink" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="font-medium text-muted">Tidak ada alarm ditemukan</p>
            <p class="text-xs mt-1 text-muted">Semua parameter sensor dalam kondisi normal</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas">
                        <th class="text-left px-4 py-3 text-muted font-semibold">Status</th>
                        <th class="text-left px-4 py-3 text-muted font-semibold">Tanaman</th>
                        <th class="text-left px-4 py-3 text-muted font-semibold">Parameter</th>
                        <th class="text-left px-4 py-3 text-muted font-semibold">Nilai Sensor</th>
                        <th class="text-left px-4 py-3 text-muted font-semibold">Threshold</th>
                        <th class="text-left px-4 py-3 text-muted font-semibold">Pelanggaran</th>
                        <th class="text-left px-4 py-3 text-muted font-semibold">Pertama Terdeteksi</th>
                        <th class="text-left px-4 py-3 text-muted font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach($alarms as $alarm)
                    <tr class="hover:bg-surface transition-colors group">
                        {{-- Status + Severity --}}
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1">
                                @if($alarm->status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[2px] text-xs font-semibold
 {{ $alarm->severity === 'critical' ? 'bg-danger-soft text-danger border border-danger/25' : 'bg-danger-soft text-danger border border-danger/25' }}">
                                        <span class="w-1.5 h-1.5 rounded-[2px] {{ $alarm->severity === 'critical' ? 'bg-surface ' : 'bg-surface' }}"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-accent-soft text-accent border border-line">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Selesai
                                    </span>
                                @endif
                                <span class="text-xs tabular-nums
 {{ $alarm->severity === 'critical' ? 'text-danger' : 'text-warning' }}">
                                    {{ $alarm->severity }}
                                </span>
                            </div>
                        </td>

                        {{-- Plant --}}
                        <td class="px-4 py-3">
                            <span class="font-medium text-ink">{{ $alarm->plant?->name ?? 'N/A' }}</span>
                        </td>

                        {{-- Parameter --}}
                        <td class="px-4 py-3">
                            <div>
                                <span class="font-medium text-ink">{{ $alarm->parameter_display_name }}</span>
                                @if($alarm->unit)
                                    <span class="text-muted ml-1">({{ $alarm->unit }})</span>
                                @endif
                            </div>
                        </td>

                        {{-- Sensor Value --}}
                        <td class="px-4 py-3">
                            <span class="tabular-nums font-semibold
 {{ $alarm->severity === 'critical' ? 'text-danger' : 'text-danger' }}">
                                {{ number_format($alarm->sensor_value, 2) }}
                                @if($alarm->unit) <span class="font-normal text-muted">{{ $alarm->unit }}</span> @endif
                            </span>
                        </td>

                        {{-- Threshold --}}
                        <td class="px-4 py-3 text-muted">
                            <span class="tabular-nums text-xs">
                                {{ number_format($alarm->threshold_min, 2) }} – {{ number_format($alarm->threshold_max, 2) }}
                            </span>
                        </td>

                        {{-- Violation Direction --}}
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 text-xs
 {{ $alarm->violation_direction === 'below_minimum' ? 'text-muted' : 'text-warning' }}">
                                @if($alarm->violation_direction === 'below_minimum')
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                    Di Bawah Min
                                @else
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                                    </svg>
                                    Di Atas Maks
                                @endif
                            </span>
                        </td>

                        {{-- First Detected --}}
                        <td class="px-4 py-3 text-muted">
                            <div>{{ $alarm->first_detected_at?->format('d M Y') }}</div>
                            <div class="text-xs text-muted">{{ $alarm->first_detected_at?->format('H:i') }} · {{ $alarm->first_detected_at?->diffForHumans() }}</div>
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3">
                            <a href="{{ route('alarms.show', $alarm->id) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[2px] bg-surface hover:bg-surface text-ink hover:text-ink text-xs font-medium transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Detail
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($alarms->hasPages())
        <div class="px-5 py-4 border-t border-line">
            {{ $alarms->links('pagination::tailwind') }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
