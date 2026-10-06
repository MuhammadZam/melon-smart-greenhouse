@extends('layouts.app')

@section('title', 'Detail Monitoring #' . $monitoring->id . ' - ' . ($monitoring->plant->name ?? 'Melon'))

@section('content')
<div class="space-y-6">

    <!-- Header & Breadcrumb -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 backdrop-blur-xl shadow-xl">
        <div>
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-400 transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                <span>/</span>
                <a href="{{ route('monitorings.index') }}" class="hover:text-emerald-400 transition-colors">
                    Data Monitoring
                </a>
                <span>/</span>
                <span class="text-emerald-400 font-medium">Detail #{{ $monitoring->id }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                    Detail Pengamatan <span class="bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent">#{{ $monitoring->id }}</span>
                </h2>
                <x-status-badge :status="$monitoring->plant_condition" />
            </div>
            <p class="text-xs sm:text-sm text-slate-400 mt-1 flex items-center gap-2">
                <span>Diperiksa pada: <strong class="text-slate-200 font-mono">{{ $monitoring->monitored_at->format('d M Y, H:i') }} WIB</strong></span>
                <span class="text-slate-400">({{ $monitoring->monitored_at->diffForHumans() }})</span>
            </p>
        </div>

        <!-- Tombol Tindakan -->
        <div class="flex flex-wrap items-center gap-2.5">
            <a
                href="{{ route('monitorings.index') }}"
                class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition-colors flex items-center gap-2 border border-slate-700/60"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Kembali</span>
            </a>

            <!-- Tombol Khusus Admin -->
            @if (auth()->user()->isAdmin())
                <a
                    href="{{ route('monitorings.edit', $monitoring) }}"
                    class="px-4 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-semibold transition-colors flex items-center gap-2 shadow-sm"
                >
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    <span>Edit Data</span>
                </a>

                <form
                    method="POST"
                    action="{{ route('monitorings.destroy', $monitoring) }}"
                    class="inline-block"
                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus data monitoring ini?')"
                >
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-semibold transition-colors flex items-center gap-2 shadow-sm"
                    >
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>Hapus</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Informasi Profil Tanaman -->
    <div class="p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-800/80">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white shadow-lg shadow-emerald-600/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">
                        {{ $monitoring->plant->name ?? 'Tanaman #' . $monitoring->plant_id }}
                    </h3>
                    <div class="text-xs text-slate-400 flex items-center gap-2 mt-0.5">
                        <span class="flex items-center gap-1 text-slate-300">
                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            </svg>
                            {{ $monitoring->plant->location ?? '-' }}
                        </span>
                        <span>&bull;</span>
                        <span>Status Plot:</span>
                        <x-status-badge :status="$monitoring->plant->status ?? 'active'" />
                    </div>
                </div>
            </div>

            <div class="text-xs text-slate-400 bg-slate-950/60 px-4 py-2.5 rounded-2xl border border-slate-800">
                <div class="text-[10px] uppercase font-mono text-slate-400">ID Tanaman Database</div>
                <div class="font-bold text-white font-mono text-sm">PLANT-{{ str_pad($monitoring->plant_id, 4, '0', STR_PAD_LEFT) }}</div>
            </div>
        </div>

        @if ($monitoring->plant && $monitoring->plant->description)
            <div class="mt-4 text-xs text-slate-300 bg-slate-950/40 p-3 rounded-xl border border-slate-800/60">
                <span class="font-semibold text-slate-400 block mb-0.5">Deskripsi Plot Budidaya:</span>
                {{ $monitoring->plant->description }}
            </div>
        @endif
    </div>

    <!-- Parameter Sensor Lingkungan & Morfologi -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        
        <!-- 1. Suhu Lingkungan -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    Suhu Udara
                </span>
                <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">
                    °C
                </span>
            </div>

            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white font-mono tracking-tight">
                    {{ number_format($monitoring->temperature, 1) }}
                </span>
                <span class="text-sm font-semibold text-slate-400">°C</span>
            </div>

            <div class="pt-2 border-t border-slate-800/80 text-xs space-y-1">
                <div class="flex items-center justify-between text-slate-400 text-[11px]">
                    <span>Ambang Batas Ideal:</span>
                    <span class="font-mono text-slate-300">20.0°C &ndash; 32.0°C</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                    <span>Status Evaluasi:</span>
                    @if ($monitoring->temperature >= 20 && $monitoring->temperature <= 32)
                        <span class="font-semibold text-emerald-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            Optimal (Ideal)
                        </span>
                    @elseif ($monitoring->temperature < 20)
                        <span class="font-semibold text-sky-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                            Terlalu Dingin
                        </span>
                    @else
                        <span class="font-semibold text-rose-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                            Terlalu Panas
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- 2. Derajat Keasaman pH -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-teal-400 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                    pH Nutrisi / Media
                </span>
                <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-teal-500/10 text-teal-300 border border-teal-500/20">
                    pH
                </span>
            </div>

            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white font-mono tracking-tight">
                    {{ number_format($monitoring->ph, 2) }}
                </span>
                <span class="text-sm font-semibold text-slate-400">pH</span>
            </div>

            <div class="pt-2 border-t border-slate-800/80 text-xs space-y-1">
                <div class="flex items-center justify-between text-slate-400 text-[11px]">
                    <span>Ambang Batas Ideal:</span>
                    <span class="font-mono text-slate-300">5.80 &ndash; 7.20</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                    <span>Status Evaluasi:</span>
                    @if ($monitoring->ph >= 5.8 && $monitoring->ph <= 7.2)
                        <span class="font-semibold text-teal-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-teal-400"></span>
                            Optimal (Normal)
                        </span>
                    @elseif ($monitoring->ph < 5.8)
                        <span class="font-semibold text-amber-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                            Terlalu Asam
                        </span>
                    @else
                        <span class="font-semibold text-rose-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                            Terlalu Basa
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- 3. Kelembapan Udara -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-sky-400 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                    Kelembapan Relatif
                </span>
                <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-sky-500/10 text-sky-300 border border-sky-500/20">
                    % RH
                </span>
            </div>

            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white font-mono tracking-tight">
                    {{ number_format($monitoring->humidity, 1) }}
                </span>
                <span class="text-sm font-semibold text-slate-400">%</span>
            </div>

            <div class="pt-2 border-t border-slate-800/80 text-xs space-y-1">
                <div class="flex items-center justify-between text-slate-400 text-[11px]">
                    <span>Ambang Batas Ideal:</span>
                    <span class="font-mono text-slate-300">60% &ndash; 85%</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                    <span>Status Evaluasi:</span>
                    @if ($monitoring->humidity >= 60 && $monitoring->humidity <= 85)
                        <span class="font-semibold text-sky-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                            Optimal (Ideal)
                        </span>
                    @elseif ($monitoring->humidity < 60)
                        <span class="font-semibold text-amber-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                            Udara Kering
                        </span>
                    @else
                        <span class="font-semibold text-rose-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                            Terlalu Lembap
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- 4. Tinggi Tanaman -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Tinggi Tajuk &amp; Sulur
                </span>
                <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700">
                    cm
                </span>
            </div>

            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white font-mono tracking-tight">
                    {{ $monitoring->plant_height !== null ? number_format($monitoring->plant_height, 1) : '-' }}
                </span>
                <span class="text-sm font-semibold text-slate-400">cm</span>
            </div>

            <div class="pt-2 border-t border-slate-800/80 text-xs text-slate-400 flex items-center justify-between text-[11px]">
                <span>Parameter Morfologi:</span>
                <span class="text-slate-300">Tinggi tanaman melon</span>
            </div>
        </div>

        <!-- 5. Kerapatan Kanopi -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                    Kerapatan Kanopi
                </span>
                <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700">
                    %
                </span>
            </div>

            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white font-mono tracking-tight">
                    {{ $monitoring->plant_density !== null ? number_format($monitoring->plant_density, 0) : '-' }}
                </span>
                <span class="text-sm font-semibold text-slate-400">%</span>
            </div>

            <div class="pt-2 border-t border-slate-800/80 text-xs text-slate-400 flex items-center justify-between text-[11px]">
                <span>Cakupan Daun:</span>
                <span class="text-slate-300">Persentase kanopi plot</span>
            </div>
        </div>

        <!-- 6. Warna Daun & Visual -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    Warna Daun Tanaman
                </span>
                <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">
                    Visual
                </span>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center">
                    <span class="w-4 h-4 rounded-full bg-emerald-400"></span>
                </div>
                <div class="text-base font-bold text-white">
                    {{ $monitoring->plant_color }}
                </div>
            </div>

            <div class="pt-2 border-t border-slate-800/80 text-xs text-slate-400 flex items-center justify-between text-[11px]">
                <span>Indikasi Klorofil:</span>
                <span class="text-slate-300">Fisik daun teramati</span>
            </div>
        </div>

    </div>

    <!-- Catatan Observasi & Temuan Lapangan -->
    <div class="p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
        <h3 class="text-base font-bold text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Catatan Observasi Petugas Lapangan
        </h3>

        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/80 text-slate-300 text-xs sm:text-sm leading-relaxed">
            @if ($monitoring->notes)
                {{ $monitoring->notes }}
            @else
                <span class="text-slate-400 italic">Tidak ada catatan tambahan untuk observasi ini.</span>
            @endif
        </div>

        <div class="flex flex-wrap items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-800/60">
            <div>Dibuat pada: <span class="font-mono text-slate-300">{{ $monitoring->created_at->format('d M Y H:i:s') }}</span></div>
            <div>Terakhir Diperbarui: <span class="font-mono text-slate-300">{{ $monitoring->updated_at->format('d M Y H:i:s') }}</span></div>
        </div>
    </div>

</div>
@endsection
