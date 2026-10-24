@extends('layouts.app')

@section('title', 'Riwayat Analisis Fuzzy Logic')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="ui-page-header flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-[2px] text-xs font-semibold   bg-surface text-muted border border-line">
                    <span class="w-1.5 h-1.5 rounded-[2px] bg-surface mr-1.5 "></span>
                    Mamdani Inference Engine
                </span>
                <span class="text-xs text-muted">Versi: {{ config('fuzzy.rule_version', 'v1.0-provisional') }}</span>
            </div>
            <h2 class="ui-page-title text-xl sm:text-2xl font-semibold text-current tracking-tight">
                Riwayat Analisis <span class="    text-ink">Kondisi Greenhouse</span>
            </h2>
            <p class="text-xs sm:text-sm text-muted mt-0.5">
                Audit trail komprehensif evaluasi multi-parameter lingkungan melon menggunakan Logika Fuzzy.
            </p>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.fuzzy.config') }}"
               class="px-4 py-2 rounded-[2px] bg-surface hover:bg-surface text-ink border border-line text-xs font-semibold flex items-center gap-2 transition-all">
                <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Audit Konfigurasi
            </a>
            @endif
        </div>
    </div>

    {{-- Alert Banner: Status Provisional --}}
    <div class="p-4 rounded-[2px] bg-warning-soft border border-warning/25 flex items-start gap-3 text-warning text-xs ">
        <svg class="w-5 h-5 text-warning shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <span class="font-semibold tracking-wide ">Pemberitahuan Status Model:</span>
            Aturan Fuzzy Simulasi — Belum Divalidasi Tim Elektro.
            <span class="text-warning block mt-0.5">
                Seluruh batas fungsi keanggotaan dan matriks inferensi berstatus provisional untuk verifikasi teknis perangkat lunak dan bukan diagnosis agronomis resmi.
            </span>
        </div>
    </div>

    {{-- Summary KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Inferences --}}
        <div class="rounded-[2px] bg-canvas border border-line p-5  relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold   text-muted">Total Evaluasi</p>
                    <p class="text-3xl font-semibold text-current mt-1">{{ number_format($totalEvaluations) }}</p>
                    <p class="text-xs text-muted mt-1">Audit trail tersimpan</p>
                </div>
                <div class="w-12 h-12 rounded-[2px] bg-surface border border-line flex items-center justify-center text-muted">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Baik --}}
        <div class="rounded-[2px] bg-canvas border border-line p-5  relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold   text-accent">Kondisi Baik</p>
                    <p class="text-3xl font-semibold text-accent mt-1">{{ number_format($countBaik) }}</p>
                    <p class="text-xs text-muted mt-1">Skor &ge; 65.0</p>
                </div>
                <div class="w-12 h-12 rounded-[2px] bg-accent-soft border border-line flex items-center justify-center text-accent">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Perlu Perhatian --}}
        <div class="rounded-[2px] bg-canvas border border-warning/25 p-5  relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold   text-warning">Perlu Perhatian</p>
                    <p class="text-3xl font-semibold text-warning mt-1">{{ number_format($countPerhatian) }}</p>
                    <p class="text-xs text-muted mt-1">Skor 40.0 - 64.99</p>
                </div>
                <div class="w-12 h-12 rounded-[2px] bg-warning-soft border border-warning/25 flex items-center justify-center text-warning">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Buruk & Avg Score --}}
        <div class="rounded-[2px] bg-canvas border border-danger/25 p-5  relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold   text-danger">Kondisi Buruk</p>
                    <p class="text-3xl font-semibold text-danger mt-1">{{ number_format($countBuruk) }}</p>
                    <p class="text-xs text-muted mt-1">Rata-rata: <strong class="text-current">{{ $avgScore !== null ? number_format($avgScore, 1) : '-' }}</strong></p>
                </div>
                <div class="w-12 h-12 rounded-[2px] bg-danger-soft border border-danger/25 flex items-center justify-center text-danger">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Grafik Tren Skor Fuzzy --}}
    @if(!empty($chartScores))
    <div class="rounded-[2px] bg-canvas border border-line p-5 sm:p-6  ">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-semibold text-current flex items-center gap-2">
                    <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                    Tren Skor Kondisi Greenhouse (20 Evaluasi Terakhir)
                </h3>
                <p class="text-xs text-muted mt-0.5">Dinamika indeks kenyamanan lingkungan (0-100) hasil defuzzifikasi Centroid</p>
            </div>
            <span class="text-xs text-muted tabular-nums bg-surface border border-line px-2.5 py-1 rounded-[2px]">
                Centroid COG
            </span>
        </div>
        <div class="h-48 sm:h-56">
            <canvas id="fuzzyTrendChart" role="img" aria-label="Tren skor fuzzy terbaru; detail skor dan kategori tersedia di tabel riwayat">Tren skor fuzzy. Lihat tabel riwayat untuk nilai setiap pengamatan.</canvas>
        </div>
    </div>
    @endif

    {{-- Filter Bar --}}
    <div class="rounded-[2px] bg-canvas border border-line p-5 sm:p-6  ">
        <form method="GET" action="{{ route('fuzzy.history') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3.5">
            {{-- Filter Tanaman --}}
            <div>
                <label for="filter-plant_id" class="block text-xs font-semibold   text-muted mb-1.5">Tanaman</label>
                <select id="filter-plant_id" name="plant_id" class="w-full bg-surface border border-line rounded-[2px] px-3 py-2 text-xs text-ink focus:outline-none focus:border-line">
                    <option value="all">Semua Tanaman</option>
                    @foreach($plants as $plant)
                        <option value="{{ $plant->id }}" {{ ($validated['plant_id'] ?? '') == $plant->id ? 'selected' : '' }}>
                            {{ $plant->name }} ({{ $plant->location ?? 'Kavling' }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Kategori --}}
            <div>
                <label for="filter-category" class="block text-xs font-semibold   text-muted mb-1.5">Kategori</label>
                <select id="filter-category" name="category" class="w-full bg-surface border border-line rounded-[2px] px-3 py-2 text-xs text-ink focus:outline-none focus:border-line">
                    <option value="all">Semua Kategori</option>
                    <option value="BAIK" {{ ($validated['category'] ?? '') === 'BAIK' ? 'selected' : '' }}>BAIK</option>
                    <option value="PERLU_PERHATIAN" {{ ($validated['category'] ?? '') === 'PERLU_PERHATIAN' ? 'selected' : '' }}>PERLU PERHATIAN</option>
                    <option value="BURUK" {{ ($validated['category'] ?? '') === 'BURUK' ? 'selected' : '' }}>BURUK</option>
                </select>
            </div>

            {{-- Filter Status --}}
            <div>
                <label for="filter-status" class="block text-xs font-semibold   text-muted mb-1.5">Status</label>
                <select id="filter-status" name="status" class="w-full bg-surface border border-line rounded-[2px] px-3 py-2 text-xs text-ink focus:outline-none focus:border-line">
                    <option value="all">Semua Status</option>
                    <option value="AVAILABLE" {{ ($validated['status'] ?? '') === 'AVAILABLE' ? 'selected' : '' }}>AVAILABLE</option>
                    <option value="INSUFFICIENT_DATA" {{ ($validated['status'] ?? '') === 'INSUFFICIENT_DATA' ? 'selected' : '' }}>INSUFFICIENT DATA</option>
                    <option value="INSUFFICIENT_RULE_COVERAGE" {{ ($validated['status'] ?? '') === 'INSUFFICIENT_RULE_COVERAGE' ? 'selected' : '' }}>RULE UNCOVERED</option>
                    <option value="STALE" {{ ($validated['status'] ?? '') === 'STALE' ? 'selected' : '' }}>STALE</option>
                    <option value="ERROR" {{ ($validated['status'] ?? '') === 'ERROR' ? 'selected' : '' }}>ERROR</option>
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label for="filter-date_from" class="block text-xs font-semibold   text-muted mb-1.5">Dari Tanggal</label>
                <input id="filter-date_from" type="date" name="date_from" value="{{ $validated['date_from'] ?? '' }}"
                       class="w-full bg-surface border border-line rounded-[2px] px-3 py-2 text-xs text-ink focus:outline-none focus:border-line">
            </div>

            {{-- Date To --}}
            <div>
                <label for="filter-date_to" class="block text-xs font-semibold   text-muted mb-1.5">Sampai Tanggal</label>
                <input id="filter-date_to" type="date" name="date_to" value="{{ $validated['date_to'] ?? '' }}"
                       class="w-full bg-surface border border-line rounded-[2px] px-3 py-2 text-xs text-ink focus:outline-none focus:border-line">
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-surface hover:bg-surface text-current font-semibold rounded-[2px] text-xs transition-colors flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                </button>
                <a href="{{ route('fuzzy.history') }}"
                   class="px-3 py-2 bg-surface hover:bg-surface text-ink font-semibold rounded-[2px] text-xs transition-colors border border-line">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Tabel Riwayat Inferensi --}}
    <div class="rounded-[2px] bg-canvas border border-line overflow-hidden  ">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas text-muted text-xs font-semibold  ">
                        <th class="py-3.5 px-4">Waktu &amp; Tanaman</th>
                        <th class="py-3.5 px-4">Input Sensor</th>
                        <th class="py-3.5 px-4">Skor &amp; Kategori</th>
                        <th class="py-3.5 px-4">Kesimpulan &amp; Rekomendasi</th>
                        <th class="py-3.5 px-4">Sumber &amp; Versi</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line text-ink">
                    @forelse($inferences as $item)
                    <tr class="hover:bg-surface transition-colors">
                        {{-- Waktu & Tanaman --}}
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <div class="font-semibold text-current">{{ $item->plant->name ?? 'Tanaman #' . $item->plant_id }}</div>
                            <div class="text-xs text-muted">{{ $item->inferred_at ? $item->inferred_at->format('d M Y H:i:s') : '-' }}</div>
                            <span class="inline-block mt-1 px-2 py-0.5 rounded-[2px] text-xs tabular-nums
                                {{ $item->inference_status === 'AVAILABLE' ? 'bg-accent-soft text-accent border border-line' : ($item->inference_status === 'STALE' ? 'bg-warning-soft text-warning border border-warning/25' : 'bg-surface text-ink') }}">
                                {{ $item->inference_status }}
                            </span>
                        </td>

                        {{-- Input Sensor Snapshot --}}
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @php
                                $inputs = $item->input_snapshot ?? [];
                            @endphp
                            <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs tabular-nums">
                                <div><span class="text-muted">Suhu:</span> <strong class="text-current">{{ isset($inputs['temperature']) ? number_format($inputs['temperature'], 1) . '°C' : '-' }}</strong></div>
                                <div><span class="text-muted">Lembap:</span> <strong class="text-current">{{ isset($inputs['humidity']) ? number_format($inputs['humidity'], 0) . '%' : '-' }}</strong></div>
                                <div><span class="text-muted">Tanah:</span> <strong class="text-current">{{ isset($inputs['soil_moisture']) ? number_format($inputs['soil_moisture'], 0) . '%' : '-' }}</strong></div>
                                <div><span class="text-muted">pH:</span> <strong class="text-current">{{ isset($inputs['ph']) ? number_format($inputs['ph'], 2) : '-' }}</strong></div>
                            </div>
                        </td>

                        {{-- Skor & Kategori --}}
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($item->fuzzy_score !== null)
                                <div class="flex items-center gap-2">
                                    <span class="text-base font-semibold text-current tabular-nums">{{ number_format($item->fuzzy_score, 1) }}</span>
                                    <span class="text-xs text-muted">/100</span>
                                </div>
                                @php
                                    $catClass = match($item->fuzzy_category) {
                                        'BAIK' => 'bg-accent-soft text-accent border-line',
                                        'PERLU_PERHATIAN' => 'bg-warning-soft text-warning border-warning/25',
                                        'BURUK' => 'bg-danger-soft text-danger border-danger/25',
                                        default => 'bg-surface text-ink border-line',
                                    };
                                @endphp
                                <span class="inline-block mt-1 px-2.5 py-0.5 rounded-[2px] text-xs font-semibold border {{ $catClass }}">
                                    {{ $item->fuzzy_category ?? 'TIDAK TERDEFINISI' }}
                                </span>
                            @else
                                <span class="text-xs text-muted italic">Tanpa Skor</span>
                            @endif
                        </td>

                        {{-- Kesimpulan & Rekomendasi --}}
                        <td class="py-3.5 px-4 max-w-xs">
                            <p class="text-ink line-clamp-2 text-xs leading-relaxed">{{ $item->conclusion ?? '-' }}</p>
                            @if($item->recommendation)
                                <p class="text-accent text-xs line-clamp-1 mt-0.5">
                                    &bull; Rekomendasi: {{ $item->recommendation }}
                                </p>
                            @endif
                        </td>

                        {{-- Sumber & Versi --}}
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <div class="text-xs tabular-nums text-ink">{{ $item->inference_source }}</div>
                            <div class="text-xs text-muted tabular-nums">{{ $item->rule_version }}</div>
                        </td>

                        {{-- Aksi --}}
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <a href="{{ route('fuzzy.show', $item->id) }}"
                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-[2px] bg-surface hover:bg-surface text-muted border border-line text-xs font-semibold transition-all">
                                <span>Detail</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-muted">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <div class="w-12 h-12 rounded-[2px] bg-surface border border-line flex items-center justify-center text-muted">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-ink">Belum ada rekaman analisis fuzzy</span>
                                <span class="text-xs text-muted">Data analisis akan terisi otomatis seiring masuknya data telemetri monitoring baru.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($inferences->hasPages())
        <div class="p-4 border-t border-line bg-canvas">
            {{ $inferences->links() }}
        </div>
        @endif
    </div>

</div>

@push('scripts')
@if(!empty($chartScores))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('fuzzyTrendChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [{
                    label: 'Skor Kondisi (0-100)',
                    data: {!! json_encode($chartScores) !!},
                    borderColor: '#818cf8',
                    backgroundColor: 'rgba(129, 140, 248, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#6366f1',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        min: 0,
                        max: 100,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#94a3b8', font: { size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 10 } }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    });
</script>
@endif
@endpush
@endsection
