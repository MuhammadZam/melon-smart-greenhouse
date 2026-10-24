@extends('layouts.app')

@section('title', 'Detail Alarm #' . $alarm->id)

@section('content')
<div class="space-y-6 max-w-4xl">

    {{-- Back --}}
    <div>
        <a href="{{ route('alarms.index') }}"
           class="inline-flex items-center gap-2 text-xs text-muted hover:text-accent transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Riwayat Alarm
        </a>
    </div>

    {{-- Alarm Header Card --}}
    <div class="relative overflow-hidden rounded-[2px] border p-6
 {{ $alarm->severity === 'critical' ? 'bg-danger-soft border-danger/25' : ($alarm->status === 'active' ? 'bg-danger-soft border-danger/25' : 'bg-canvas border-line') }}">
        <div class="absolute inset-0
 {{ $alarm->severity === 'critical' ? '' : ($alarm->status === 'active' ? '' : '') }}
 pointer-events-none"></div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            {{-- Icon --}}
            <div class="w-16 h-16 rounded-[2px] flex items-center justify-center shrink-0
 {{ $alarm->severity === 'critical' ? 'bg-danger-soft border border-danger/25' : ($alarm->status === 'active' ? 'bg-danger-soft border border-danger/25' : 'bg-accent-soft border border-line') }}">
                @if($alarm->status === 'active')
                    <svg class="w-8 h-8 {{ $alarm->severity === 'critical' ? 'text-danger' : 'text-danger' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                @else
                    <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @endif
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="px-3 py-1 rounded-[2px] text-xs font-semibold
 {{ $alarm->severity === 'critical' ? 'bg-danger-soft text-danger border border-danger/25' : 'bg-warning-soft text-warning border border-warning/25' }}">
                        {{ $alarm->severity }}
                    </span>
                    <span class="px-3 py-1 rounded-[2px] text-xs font-semibold
 {{ $alarm->status === 'active' ? 'bg-danger-soft text-danger border border-danger/25' : 'bg-accent-soft text-accent border border-line' }}">
                        {{ $alarm->status === 'active' ? '● Aktif' : '✓ Diselesaikan' }}
                    </span>
                    <span class="text-muted text-xs tabular-nums">#{{ $alarm->id }}</span>
                </div>
                <h2 class="ui-page-title text-lg font-semibold text-current">
                    {{ $alarm->parameter_display_name }}
                    @if($alarm->unit)<span class="text-muted text-sm font-normal">({{ $alarm->unit }})</span>@endif
                </h2>
                <p class="text-sm text-muted mt-1">
                    Tanaman: <span class="text-current font-medium">{{ $alarm->plant?->name ?? 'N/A' }}</span>
                    @if($alarm->plant?->location)
                        · <span class="text-muted">{{ $alarm->plant->location }}</span>
                    @endif
                </p>
            </div>

            {{-- Sensor Value --}}
            <div class="text-right shrink-0">
                <p class="text-xs text-muted">Nilai Pemicu</p>
                <p class="text-3xl font-semibold tabular-nums
 {{ $alarm->severity === 'critical' ? 'text-danger' : 'text-danger' }}">
                    {{ number_format($alarm->sensor_value, 2) }}
                    @if($alarm->unit)<span class="text-lg text-muted">{{ $alarm->unit }}</span>@endif
                </p>
                <p class="text-xs {{ $alarm->violation_direction === 'below_minimum' ? 'text-muted' : 'text-warning' }} mt-1 font-semibold">
                    {{ $alarm->violation_direction === 'below_minimum' ? '▼ Di Bawah Minimum' : '▲ Di Atas Maksimum' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Detail Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Threshold Info --}}
        <div class="rounded-[2px] bg-canvas border border-line p-5 space-y-4">
            <h3 class="text-sm font-semibold text-ink flex items-center gap-2">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Threshold Parameter
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-line">
                    <span class="text-xs text-muted">Minimum Normal</span>
                    <span class="tabular-nums font-semibold text-ink">{{ number_format($alarm->threshold_min, 2) }} {{ $alarm->unit }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-line">
                    <span class="text-xs text-muted">Maksimum Normal</span>
                    <span class="tabular-nums font-semibold text-ink">{{ number_format($alarm->threshold_max, 2) }} {{ $alarm->unit }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-line">
                    <span class="text-xs text-muted">Nilai Saat Terpicu</span>
                    <span class="tabular-nums font-semibold {{ $alarm->severity === 'critical' ? 'text-danger' : 'text-danger' }}">
                        {{ number_format($alarm->sensor_value, 2) }} {{ $alarm->unit }}
                    </span>
                </div>
                @if($alarm->last_abnormal_value !== null && $alarm->last_abnormal_value != $alarm->sensor_value)
                <div class="flex justify-between items-center py-2">
                    <span class="text-xs text-muted">Nilai Abnormal Terakhir</span>
                    <span class="tabular-nums font-semibold text-warning">
                        {{ number_format($alarm->last_abnormal_value, 2) }} {{ $alarm->unit }}
                    </span>
                </div>
                @endif
            </div>
        </div>

        {{-- Timeline --}}
        <div class="rounded-[2px] bg-canvas border border-line p-5 space-y-4">
            <h3 class="text-sm font-semibold text-ink flex items-center gap-2">
                <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Kronologi Alarm
            </h3>
            <div class="space-y-3">
                <div class="flex items-start gap-3 py-2 border-b border-line">
                    <div class="w-2 h-2 rounded-[2px] bg-danger mt-1.5 shrink-0"></div>
                    <div>
                        <p class="text-xs font-semibold text-ink">Pertama Terdeteksi</p>
                        <p class="text-xs text-muted">{{ $alarm->first_detected_at?->format('d M Y, H:i:s') }}</p>
                        <p class="text-xs text-muted">{{ $alarm->first_detected_at?->diffForHumans() }}</p>
                    </div>
                </div>
                @if($alarm->last_abnormal_at && $alarm->last_abnormal_at != $alarm->first_detected_at)
                <div class="flex items-start gap-3 py-2 border-b border-line">
                    <div class="w-2 h-2 rounded-[2px] bg-surface mt-1.5 shrink-0"></div>
                    <div>
                        <p class="text-xs font-semibold text-ink">Terakhir Abnormal</p>
                        <p class="text-xs text-muted">{{ $alarm->last_abnormal_at?->format('d M Y, H:i:s') }}</p>
                        <p class="text-xs text-muted">{{ $alarm->last_abnormal_at?->diffForHumans() }}</p>
                    </div>
                </div>
                @endif
                @if($alarm->status === 'resolved')
                <div class="flex items-start gap-3 py-2">
                    <div class="w-2 h-2 rounded-[2px] bg-accent mt-1.5 shrink-0"></div>
                    <div>
                        <p class="text-xs font-semibold text-accent">Diselesaikan</p>
                        <p class="text-xs text-muted">{{ $alarm->resolved_at?->format('d M Y, H:i:s') }}</p>
                        @if($alarm->resolved_value !== null)
                        <p class="text-xs text-muted">Nilai kembali normal: <span class="tabular-nums text-accent">{{ number_format($alarm->resolved_value, 2) }} {{ $alarm->unit }}</span></p>
                        @endif
                        @if($alarm->notes)
                        <p class="text-xs text-muted mt-1 italic">{{ $alarm->notes }}</p>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Triggering Monitoring --}}
    @if($alarm->triggeringMonitoring)
    <div class="rounded-[2px] bg-canvas border border-line p-5">
        <h3 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Monitoring Pemicu
        </h3>
        <div class="flex flex-wrap items-center gap-4 text-xs">
            <div class="flex items-center gap-2">
                <span class="text-muted">ID Monitoring:</span>
                <a href="{{ route('monitorings.show', $alarm->triggeringMonitoring->id) }}"
                   class="tabular-nums text-accent hover:underline">#{{ $alarm->triggeringMonitoring->id }}</a>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-muted">Waktu Pembacaan:</span>
                <span class="text-ink">{{ $alarm->triggeringMonitoring->monitored_at?->format('d M Y, H:i') }}</span>
            </div>
            <a href="{{ route('monitorings.show', $alarm->triggeringMonitoring->id) }}"
               class="ml-auto px-3 py-1.5 rounded-[2px] bg-surface hover:bg-surface text-ink text-xs font-medium transition-colors">
                Lihat Detail Monitoring →
            </a>
        </div>
    </div>
    @endif

</div>
@endsection
