@extends('layouts.app')

@section('title', 'Manajemen Ambang Batas (Threshold) Sensor')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto" x-data="{ editingId: null }">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-line">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('alarms.index') }}" class="text-xs font-semibold text-accent hover:text-accent transition-colors">
                    &larr; Riwayat Alarm
                </a>
            </div>
            <h1 class="ui-page-title text-xl sm:text-2xl font-semibold text-current tracking-tight flex items-center gap-2.5">
                <span class="p-2 rounded-[2px] bg-accent-soft text-accent border border-line">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </span>
                <span>Manajemen Ambang Batas Sensor</span>
            </h1>
            <p class="text-xs sm:text-sm text-muted mt-1">
                Konfigurasi rentang batas normal (Warning) dan batas kritis (Critical) untuk evaluasi alarm otomatis.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[2px] bg-surface border border-line text-xs font-semibold text-ink">
                <span class="w-2 h-2 rounded-[2px] bg-accent-soft"></span>
                Khusus Administrator
            </span>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="p-4 rounded-[2px] bg-accent-soft border border-line text-accent text-xs sm:text-sm flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 shrink-0 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-accent hover:text-accent">
                &times;
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-[2px] bg-danger-soft border border-danger/25 text-danger text-xs sm:text-sm">
            <div class="font-semibold flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span>Validasi Gagal:</span>
            </div>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Petunjuk Teknis Ambang Batas --}}
    {{-- Petunjuk Teknis Ambang Batas --}}
    <div class="p-4 rounded-[2px] bg-canvas border border-line text-xs text-muted space-y-1.5">
        <p class="font-semibold text-ink flex items-center gap-2">
            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Panduan Operasional Ambang Batas &amp; Kebijakan Alarm (Phase 13):
        </p>
        <ul class="list-disc list-inside space-y-1 pl-1 text-xs leading-relaxed">
            <li><strong>Parameter Alarm Aktif (4 Parameter):</strong> Sesuai spesifikasi Tim Elektro, hanya <code class="text-accent">temperature</code>, <code class="text-accent">humidity</code>, <code class="text-accent">soil_moisture</code>, dan <code class="text-accent">ph</code> yang dievaluasi untuk membuat alarm aktif.</li>
            <li><strong>Parameter Monitoring Visual:</strong> Parameter lainnya (<code class="text-ink">ec</code>, <code class="text-ink">light_intensity</code>, <code class="text-ink">nutrient_temperature</code>, <code class="text-ink">tank_level</code>, <code class="text-ink">plant_height</code>, <code class="text-ink">plant_density</code>) tetap disimpan dan divisualisasikan dalam grafik, namun tidak memicu alarm. Ambang batasnya disimpan sebagai referensi batas wajar agronomis.</li>
            <li><strong>Nilai Normal &amp; Batas Kritis:</strong> Pada parameter alarm, nilai di luar rentang <code class="text-warning">Min</code> hingga <code class="text-warning">Max</code> mencatat alarm <strong>Warning</strong>. Melewati batas <code class="text-danger">Critical Min</code> atau <code class="text-danger">Critical Max</code> mencatat alarm <strong>Critical</strong>.</li>
            <li><strong>Integritas Historis:</strong> Histori alarm sebelumnya tetap tersimpan utuh dan tidak dihapus.</li>
        </ul>
    </div>

    {{-- Parameter List & Forms --}}
    <div class="grid grid-cols-1 gap-4">
        @foreach($parameters as $param)
            @php
                $isAlarmEligible = \App\Services\MonitoringAlarmService::isAlarmEligible($param->parameter_key);
            @endphp
            <div class="rounded-[2px] bg-canvas border border-line overflow-hidden hover:border-line transition-colors"
                 id="param-card-{{ $param->id }}">

                {{-- Card Header --}}
                <div class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-canvas">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h2 class="text-base font-semibold text-current">{{ $param->parameter_name }}</h2>
                            @if($param->parameter_key)
                                <code class="px-2 py-0.5 rounded-[2px] text-xs tabular-nums font-semibold bg-surface text-ink border border-line">
                                    {{ $param->parameter_key }}
                                </code>
                            @endif
                            <span class="px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-surface text-ink border border-line">
                                Satuan: {{ $param->unit }}
                            </span>
                            @if($isAlarmEligible)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-[2px] text-xs font-semibold bg-accent-soft text-accent border border-line">
                                    <span class="w-1.5 h-1.5 rounded-[2px] bg-accent-soft"></span>
                                    Parameter Alarm Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-[2px] text-xs font-medium bg-surface text-muted border border-line">
                                    <span class="w-1.5 h-1.5 rounded-[2px] bg-surface"></span>
                                    Monitoring Visual Saja
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-muted max-w-3xl leading-relaxed">
                            {{ $param->description ?: 'Belum ada deskripsi parameter.' }}
                        </p>
                    </div>

                    {{-- Current Values Summary --}}
                    <div class="flex items-center gap-3 shrink-0">
                        <div class="px-3 py-1.5 rounded-[2px] bg-surface border border-line text-center">
                            <span class="block text-xs text-muted font-semibold">Rentang Normal</span>
                            <span class="text-xs tabular-nums font-semibold text-accent">
                                {{ number_format($param->minimum_value, 2) }} - {{ number_format($param->maximum_value, 2) }} {{ $param->unit }}
                            </span>
                        </div>

                        <div class="px-3 py-1.5 rounded-[2px] bg-surface border border-line text-center">
                            <span class="block text-xs text-muted font-semibold">Batas Kritis</span>
                            <span class="text-xs tabular-nums font-semibold text-danger">
                                @if($param->critical_min_value !== null || $param->critical_max_value !== null)
                                    {{ $param->critical_min_value !== null ? '< ' . number_format($param->critical_min_value, 2) : '-' }}
                                    /
                                    {{ $param->critical_max_value !== null ? '> ' . number_format($param->critical_max_value, 2) : '-' }}
                                @else
                                    <span class="text-muted">Default</span>
                                @endif
                            </span>
                        </div>

                        <button type="button"
                                @click="editingId = editingId === {{ $param->id }} ? null : {{ $param->id }}"
                                class="px-3.5 py-2 rounded-[2px] text-xs font-semibold bg-accent-soft hover:bg-accent-soft text-accent hover:text-accent border border-line transition-all flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            <span x-text="editingId === {{ $param->id }} ? 'Batal' : 'Ubah'">Ubah</span>
                        </button>
                    </div>
                </div>

                {{-- Edit Form (Toggle with Alpine) --}}
                <div x-show="editingId === {{ $param->id }}"
                     x-collapse
                     class="p-4 sm:p-5 border-t border-line bg-canvas"
                     style="display: none;">

                    <form method="POST" action="{{ route('alarms.thresholds.update', $param->id) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            {{-- Minimum Normal --}}
                            <div>
                                <label for="min-{{ $param->id }}" class="block text-xs font-medium text-ink mb-1">
                                    Ambang Batas Min Normal <span class="text-danger">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" step="0.01" name="minimum_value" id="min-{{ $param->id }}"
                                           value="{{ old('minimum_value', $param->minimum_value) }}"
                                           required
                                           class="w-full px-3 py-2 bg-canvas border border-line rounded-[2px] text-xs tabular-nums text-current focus:outline-none focus:border-line transition-colors">
                                    <span class="absolute right-3 top-2 text-xs text-muted">{{ $param->unit }}</span>
                                </div>
                                <span class="text-xs text-muted">Di bawah nilai ini: Warning</span>
                            </div>

                            {{-- Maximum Normal --}}
                            <div>
                                <label for="max-{{ $param->id }}" class="block text-xs font-medium text-ink mb-1">
                                    Ambang Batas Maks Normal <span class="text-danger">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" step="0.01" name="maximum_value" id="max-{{ $param->id }}"
                                           value="{{ old('maximum_value', $param->maximum_value) }}"
                                           required
                                           class="w-full px-3 py-2 bg-canvas border border-line rounded-[2px] text-xs tabular-nums text-current focus:outline-none focus:border-line transition-colors">
                                    <span class="absolute right-3 top-2 text-xs text-muted">{{ $param->unit }}</span>
                                </div>
                                <span class="text-xs text-muted">Di atas nilai ini: Warning</span>
                            </div>

                            {{-- Critical Min (Optional) --}}
                            <div>
                                <label for="crit-min-{{ $param->id }}" class="block text-xs font-medium text-ink mb-1">
                                    Ambang Kritis Min (Critical)
                                </label>
                                <div class="relative">
                                    <input type="number" step="0.01" name="critical_min_value" id="crit-min-{{ $param->id }}"
                                           value="{{ old('critical_min_value', $param->critical_min_value) }}"
                                           placeholder="Kosongkan jika default"
                                           class="w-full px-3 py-2 bg-canvas border border-line rounded-[2px] text-xs tabular-nums text-current focus:outline-none focus:border-danger/25 transition-colors">
                                    <span class="absolute right-3 top-2 text-xs text-muted">{{ $param->unit }}</span>
                                </div>
                                <span class="text-xs text-muted">Di bawah nilai ini: Critical</span>
                            </div>

                            {{-- Critical Max (Optional) --}}
                            <div>
                                <label for="crit-max-{{ $param->id }}" class="block text-xs font-medium text-ink mb-1">
                                    Ambang Kritis Maks (Critical)
                                </label>
                                <div class="relative">
                                    <input type="number" step="0.01" name="critical_max_value" id="crit-max-{{ $param->id }}"
                                           value="{{ old('critical_max_value', $param->critical_max_value) }}"
                                           placeholder="Kosongkan jika default"
                                           class="w-full px-3 py-2 bg-canvas border border-line rounded-[2px] text-xs tabular-nums text-current focus:outline-none focus:border-danger/25 transition-colors">
                                    <span class="absolute right-3 top-2 text-xs text-muted">{{ $param->unit }}</span>
                                </div>
                                <span class="text-xs text-muted">Di atas nilai ini: Critical</span>
                            </div>
                        </div>

                        {{-- Deskripsi --}}
                        <div>
                            <label for="desc-{{ $param->id }}" class="block text-xs font-medium text-ink mb-1">
                                Deskripsi / Catatan Konfigurasi
                            </label>
                            <input type="text" name="description" id="desc-{{ $param->id }}"
                                   value="{{ old('description', $param->description) }}"
                                   maxlength="500"
                                   class="w-full px-3 py-2 bg-canvas border border-line rounded-[2px] text-xs text-current focus:outline-none focus:border-line transition-colors"
                                   placeholder="Catatan tujuan rentang parameter agronomis tanaman melon">
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-line">
                            <button type="button" @click="editingId = null"
                                    class="px-4 py-2 rounded-[2px] text-xs font-medium text-muted hover:text-ink hover:bg-surface transition-colors">
                                Batal
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 rounded-[2px] text-xs font-semibold bg-accent hover:bg-accent-soft text-current transition-colors flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Simpan Perubahan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
