@extends('layouts.app')

@section('title', 'Detail Evaluasi Fuzzy Logic #' . $fuzzyInference->id)

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb & Header --}}
    <div class="ui-page-header flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1.5 text-xs">
                <a href="{{ route('fuzzy.history') }}" class="text-muted hover:text-muted font-semibold flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Riwayat
                </a>
                <span class="text-muted">/</span>
                <span class="text-muted">Audit Trail #{{ $fuzzyInference->id }}</span>
            </div>
            <h2 class="ui-page-title text-xl sm:text-2xl font-semibold text-current tracking-tight flex items-center gap-2">
                Audit Trail Evaluasi <span class="text-ink">Fuzzy Mamdani</span>
            </h2>
            <p class="text-xs sm:text-sm text-muted mt-0.5">
                Rincian matematis fuzzifikasi, aktivasi aturan (firing strength), dan defuzzifikasi centroid.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($fuzzyInference->monitoring_id)
            <a href="{{ route('monitorings.show', $fuzzyInference->monitoring_id) }}"
               class="px-3.5 py-2 rounded-[2px] bg-surface hover:bg-surface text-ink border border-line text-xs font-semibold flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Lihat Data Monitoring
            </a>
            @endif
        </div>
    </div>

    {{-- Alert Banner --}}
    <div class="p-4 rounded-[2px] bg-warning-soft border border-warning/25 flex items-start gap-3 text-warning text-xs">
        <svg class="w-5 h-5 text-warning shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <span class="font-semibold tracking-wide">Rule Set: {{ $fuzzyInference->rule_version }} (PROVISIONAL_FOR_SOFTWARE_TESTING)</span>
            <span class="text-warning block mt-0.5">
                Hasil inferensi ini dihitung secara deterministik menggunakan metode Mamdani provisional dan belum disahkan secara formal oleh Tim Elektro.
            </span>
        </div>
    </div>

    {{-- Main Score & Conclusion Card --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Score & Category Box --}}
        <div class="rounded-[2px] bg-canvas border border-line p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted">Skor Defuzzifikasi</span>
                    <span class="px-2 py-0.5 rounded-[2px] text-xs tabular-nums bg-surface text-muted border border-line">
                        {{ $fuzzyInference->inference_status }}
                    </span>
                </div>
                <div class="mt-4 flex items-baseline gap-3">
                    <span class="text-5xl font-black text-current tabular-nums tracking-tight">
                        {{ $fuzzyInference->fuzzy_score !== null ? number_format($fuzzyInference->fuzzy_score, 1) : '-' }}
                    </span>
                    <span class="text-sm font-semibold text-muted">/ 100</span>
                </div>

                @php
                    $badgeClass = match($fuzzyInference->fuzzy_category) {
                        'BAIK' => 'bg-accent-soft text-accent border-line',
                        'PERLU_PERHATIAN' => 'bg-warning-soft text-warning border-warning/25',
                        'BURUK' => 'bg-danger-soft text-danger border-danger/25',
                        default => 'bg-surface text-ink border-line',
                    };
                @endphp
                <div class="mt-3">
                    <span class="inline-block px-3 py-1 rounded-[2px] text-xs font-semibold border {{ $badgeClass }}">
                        Kondisi: {{ $fuzzyInference->fuzzy_category ?? 'Tidak Terdefinisi' }}
                    </span>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-line text-xs text-muted space-y-1.5 tabular-nums">
                <div class="flex justify-between"><span>Metode:</span> <strong class="text-ink">Mamdani (Centroid COG)</strong></div>
                <div class="flex justify-between"><span>Sumber:</span> <strong class="text-ink">{{ $fuzzyInference->inference_source }}</strong></div>
                <div class="flex justify-between"><span>Waktu Evaluasi:</span> <strong class="text-ink">{{ $fuzzyInference->inferred_at ? $fuzzyInference->inferred_at->format('Y-m-d H:i:s') : '-' }}</strong></div>
                <div class="flex justify-between"><span>Tanaman:</span> <strong class="text-ink">{{ $fuzzyInference->plant->name ?? '-' }}</strong></div>
            </div>
        </div>

        {{-- Conclusion & Recommendation Box --}}
        <div class="lg:col-span-2 rounded-[2px] bg-canvas border border-line p-6 flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-semibold text-current flex items-center gap-2 mb-3">
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Kesimpulan Agronomis Sistem
                </h3>
                <div class="p-4 rounded-[2px] bg-surface border border-line text-ink text-xs sm:text-sm leading-relaxed">
                    {{ $fuzzyInference->conclusion ?? 'Tidak ada kesimpulan yang tercatat.' }}
                </div>

                <h3 class="text-sm font-semibold text-current flex items-center gap-2 mt-5 mb-2">
                    <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Rekomendasi Tindakan Budidaya
                </h3>
                <div class="p-4 rounded-[2px] bg-surface border border-line text-accent text-xs sm:text-sm leading-relaxed">
                    {{ $fuzzyInference->recommendation ?? 'Tidak ada rekomendasi spesifik.' }}
                </div>
            </div>

            <p class="text-xs text-muted italic mt-4">
                * Rekomendasi di atas merupakan panduan pendukung keputusan dan tidak mengendalikan aktuator secara langsung.
            </p>
        </div>
    </div>

    {{-- Input Snapshot & Fuzzification Details --}}
    <div class="rounded-[2px] bg-canvas border border-line p-6">
        <h3 class="text-sm font-semibold text-current flex items-center gap-2 mb-4">
            <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            Tahap 1: Snapshot Input Sensor &amp; Derajat Keanggotaan (Fuzzifikasi)
        </h3>

        @php
            $inputs = $fuzzyInference->input_snapshot ?? [];
            $memberships = $fuzzyInference->membership_snapshot ?? [];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Temperature --}}
            <div class="p-4 rounded-[2px] bg-surface border border-line">
                <div class="text-xs font-semibold text-muted">Suhu Udara</div>
                <div class="text-xl font-semibold text-current tabular-nums mt-1">
                    {{ isset($inputs['temperature']) ? number_format($inputs['temperature'], 1) . ' °C' : '-' }}
                </div>
                <div class="mt-3 space-y-1.5 border-t border-line pt-2 text-xs">
                    @foreach($memberships['temperature'] ?? [] as $set => $mu)
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-muted capitalize">&mu;({{ $set }}):</span>
                            <span class="tabular-nums font-semibold {{ $mu > 0 ? 'text-muted' : 'text-muted' }}">{{ number_format($mu, 4) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Humidity --}}
            <div class="p-4 rounded-[2px] bg-surface border border-line">
                <div class="text-xs font-semibold text-muted">Kelembapan Udara</div>
                <div class="text-xl font-semibold text-current tabular-nums mt-1">
                    {{ isset($inputs['humidity']) ? number_format($inputs['humidity'], 1) . ' %' : '-' }}
                </div>
                <div class="mt-3 space-y-1.5 border-t border-line pt-2 text-xs">
                    @foreach($memberships['humidity'] ?? [] as $set => $mu)
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-muted capitalize">&mu;({{ $set }}):</span>
                            <span class="tabular-nums font-semibold {{ $mu > 0 ? 'text-muted' : 'text-muted' }}">{{ number_format($mu, 4) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Soil Moisture --}}
            <div class="p-4 rounded-[2px] bg-surface border border-line">
                <div class="text-xs font-semibold text-muted">Kelembapan Tanah</div>
                <div class="text-xl font-semibold text-current tabular-nums mt-1">
                    {{ isset($inputs['soil_moisture']) ? number_format($inputs['soil_moisture'], 1) . ' %' : '-' }}
                </div>
                <div class="mt-3 space-y-1.5 border-t border-line pt-2 text-xs">
                    @foreach($memberships['soil_moisture'] ?? [] as $set => $mu)
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-muted capitalize">&mu;({{ $set }}):</span>
                            <span class="tabular-nums font-semibold {{ $mu > 0 ? 'text-muted' : 'text-muted' }}">{{ number_format($mu, 4) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- pH --}}
            <div class="p-4 rounded-[2px] bg-surface border border-line">
                <div class="text-xs font-semibold text-muted">pH Nutrisi/Media</div>
                <div class="text-xl font-semibold text-current tabular-nums mt-1">
                    {{ isset($inputs['ph']) ? number_format($inputs['ph'], 2) : '-' }}
                </div>
                <div class="mt-3 space-y-1.5 border-t border-line pt-2 text-xs">
                    @foreach($memberships['ph'] ?? [] as $set => $mu)
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-muted capitalize">&mu;({{ $set }}):</span>
                            <span class="tabular-nums font-semibold {{ $mu > 0 ? 'text-muted' : 'text-muted' }}">{{ number_format($mu, 4) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Rule Activation Snapshot --}}
    @php
        $ruleSnapshot = $fuzzyInference->rule_activation_snapshot ?? [];
        $activatedRules = $ruleSnapshot['activated_rules'] ?? [];
    @endphp
    <div class="rounded-[2px] bg-canvas border border-line p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-semibold text-current flex items-center gap-2">
                    <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Tahap 2: Evaluasi Aturan &amp; Kekuatan Penyalaan (Firing Strength &alpha;)
                </h3>
                <p class="text-xs text-muted mt-0.5">Daftar aturan IF-THEN yang aktif (&alpha; &gt; 0) pada saat evaluasi</p>
            </div>
            <span class="text-xs text-ink tabular-nums">
                Total Aktif: <strong class="text-muted">{{ count($activatedRules) }}</strong>
            </span>
        </div>

        @if(!empty($activatedRules))
        <div class="space-y-3">
            @foreach($activatedRules as $rule)
            <div class="p-4 rounded-[2px] bg-surface border border-line text-xs">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-[2px] tabular-nums font-semibold bg-surface text-muted border border-line">
                            {{ $rule['id'] ?? 'RULE' }}
                        </span>
                        <strong class="text-current">{{ $rule['name'] ?? '-' }}</strong>
                    </div>
                    <div class="flex items-center gap-2 tabular-nums">
                        <span class="text-muted">Firing Strength (&alpha;):</span>
                        <span class="text-sm font-black text-muted">{{ number_format($rule['firing_strength'], 4) }}</span>
                    </div>
                </div>

                <div class="mt-2.5 pt-2 border-t border-line grid grid-cols-1 md:grid-cols-2 gap-2 text-ink">
                    <div>
                        <span class="text-muted text-xs">Anteseden:</span>
                        <div class="tabular-nums text-xs text-ink mt-0.5">
                            @foreach($rule['antecedents_mu'] ?? [] as $k => $mu)
                                <span class="inline-block mr-2">{{ $k }}: <strong>{{ number_format($mu, 4) }}</strong></span>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs">Konsekuen:</span>
                        <span class="inline-block ml-1 font-semibold text-accent">{{ $rule['consequent'] ?? '-' }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="p-6 text-center text-muted text-xs italic bg-surface rounded-[2px] border border-line">
            Tidak ada aturan fuzzy yang aktif (&alpha; = 0) untuk kombinasi input sensor ini.
        </div>
        @endif
    </div>

</div>
@endsection
