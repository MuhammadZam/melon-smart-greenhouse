@extends('layouts.app')

@section('title', 'Riwayat Kontrol Perangkat (Audit Trail)')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-line">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('actuators.index') }}" class="text-xs font-semibold text-accent hover:text-accent transition-colors">
                    &larr; Kontrol Perangkat
                </a>
            </div>
            <h1 class="ui-page-title text-xl sm:text-2xl font-semibold text-current tracking-tight flex items-center gap-2.5">
                <span class="p-2 rounded-[2px] bg-accent-soft text-accent border border-line">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </span>
                <span>Riwayat Perintah Kontrol (Audit Trail)</span>
            </h1>
            <p class="text-xs sm:text-sm text-muted mt-1">
                Catatan lengkap seluruh eksekusi perintah aktuator, operator, perubahan state, dan sumber simulasi.
            </p>
        </div>

        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[2px] bg-surface border border-line text-xs font-semibold text-ink">
                <span class="w-2 h-2 rounded-[2px] bg-accent-soft"></span>
                Total: {{ $logs->total() }} Catatan
            </span>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="p-4 rounded-[2px] bg-canvas border border-line">
        <form action="{{ route('actuators.history') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 items-end">
            <div>
                <label for="filter-actuator" class="block text-xs font-semibold text-muted mb-1">Perangkat</label>
                <select name="actuator_id" id="filter-actuator" class="w-full px-3 py-2 rounded-[2px] text-xs bg-canvas border border-line text-current focus:outline-none focus:border-line">
                    <option value="all">Semua Perangkat</option>
                    @foreach($actuators as $act)
                        <option value="{{ $act->id }}" {{ ($filters['actuator_id'] ?? '') == $act->id ? 'selected' : '' }}>
                            {{ $act->name }} ({{ $act->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-command" class="block text-xs font-semibold text-muted mb-1">Jenis Perintah</label>
                <select name="command" id="filter-command" class="w-full px-3 py-2 rounded-[2px] text-xs bg-canvas border border-line text-current focus:outline-none focus:border-line">
                    <option value="all">Semua Perintah</option>
                    <option value="TURN_ON" {{ ($filters['command'] ?? '') === 'TURN_ON' ? 'selected' : '' }}>TURN_ON</option>
                    <option value="TURN_OFF" {{ ($filters['command'] ?? '') === 'TURN_OFF' ? 'selected' : '' }}>TURN_OFF</option>
                    <option value="TOGGLE" {{ ($filters['command'] ?? '') === 'TOGGLE' ? 'selected' : '' }}>TOGGLE</option>
                </select>
            </div>

            <div>
                <label for="filter-date-from" class="block text-xs font-semibold text-muted mb-1">Dari Tanggal</label>
                <input type="date"
                       name="date_from"
                       id="filter-date-from"
                       value="{{ $filters['date_from'] ?? '' }}"
                       class="w-full px-3 py-2 rounded-[2px] text-xs bg-canvas border border-line text-current focus:outline-none focus:border-line">
            </div>

            <div>
                <label for="filter-date-to" class="block text-xs font-semibold text-muted mb-1">Sampai Tanggal</label>
                <input type="date"
                       name="date_to"
                       id="filter-date-to"
                       value="{{ $filters['date_to'] ?? '' }}"
                       class="w-full px-3 py-2 rounded-[2px] text-xs bg-canvas border border-line text-current focus:outline-none focus:border-line">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 px-3 rounded-[2px] text-xs font-semibold bg-accent hover:bg-accent text-current transition-all flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <span>Filter</span>
                </button>

                <a href="{{ route('actuators.history') }}" class="py-2 px-3 rounded-[2px] text-xs font-semibold bg-surface hover:bg-surface text-ink transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Tabel Riwayat --}}
    <div class="rounded-[2px] bg-canvas border border-line overflow-hidden">
        @if($logs->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-xs font-semibold text-muted bg-canvas border-b border-line">
                        <tr>
                            <th class="py-3 px-4">Waktu Eksekusi</th>
                            <th class="py-3 px-4">Perangkat</th>
                            <th class="py-3 px-4">Perintah</th>
                            <th class="py-3 px-4">State Sebelumnya</th>
                            <th class="py-3 px-4">State Hasil</th>
                            <th class="py-3 px-4">Operator</th>
                            <th class="py-3 px-4">Sumber</th>
                            <th class="py-3 px-4">Status Hasil</th>
                            <th class="py-3 px-4">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($logs as $log)
                            <tr class="hover:bg-surface transition-colors">
                                <td class="py-3 px-4 tabular-nums text-muted whitespace-nowrap">
                                    {{ $log->executed_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-semibold text-current">{{ $log->actuator->name }}</div>
                                    <code class="text-xs text-muted tabular-nums">{{ $log->actuator->code }}</code>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-[2px] text-xs tabular-nums font-semibold bg-surface text-ink border border-line">
                                        {{ $log->command }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 tabular-nums font-semibold text-muted whitespace-nowrap">
                                    {{ $log->previous_state }}
                                </td>
                                <td class="py-3 px-4 tabular-nums font-semibold whitespace-nowrap {{ $log->resulting_state === 'ON' ? 'text-accent' : 'text-muted' }}">
                                    {{ $log->resulting_state }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-ink">
                                    {{ $log->user?->name ?: 'Sistem (Otomatis)' }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap tabular-nums text-xs text-muted">
                                    {{ $log->source }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if($log->execution_status === 'SUCCESS')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-accent-soft text-accent border border-line">
                                            SUKSES
                                        </span>
                                    @elseif($log->execution_status === 'REJECTED')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-warning-soft text-warning border border-warning/25">
                                            DITOLAK
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-danger-soft text-danger border border-danger/25">
                                            GAGAL
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-xs text-muted max-w-xs truncate" title="{{ $log->notes }}">
                                    {{ $log->notes ?: '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination Links --}}
            <div class="p-4 border-t border-line bg-canvas">
                {{ $logs->links() }}
            </div>
        @else
            {{-- Empty State --}}
            <div class="p-12 text-center space-y-3">
                <div class="w-12 h-12 rounded-[2px] bg-surface text-muted flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-current">Tidak Ada Riwayat Ditemukan</h3>
                <p class="text-xs text-muted max-w-sm mx-auto">
                    Belum ada catatan perintah kontrol yang sesuai dengan kriteria filter saat ini.
                </p>
                <div class="pt-2">
                    <a href="{{ route('actuators.history') }}" class="px-3.5 py-1.5 rounded-[2px] text-xs font-semibold bg-surface hover:bg-surface text-ink transition-colors inline-block">
                        Reset Filter
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
