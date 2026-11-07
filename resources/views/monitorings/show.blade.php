@extends('layouts.app')

@section('title', 'Detail Monitoring #' . $monitoring->id . ' - ' . ($monitoring->plant->name ?? 'Melon'))

@section('content')
<div class="space-y-6">

    <!-- Header & Breadcrumb -->
    <div class="ui-page-header flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-xs text-muted mb-2">
                <a href="{{ route('dashboard') }}" class="hover:text-accent transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                <span>/</span>
                <a href="{{ route('monitorings.index') }}" class="hover:text-accent transition-colors">
                    Data Monitoring
                </a>
                <span>/</span>
                <span class="text-accent font-medium">Detail #{{ $monitoring->id }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <h2 class="ui-page-title text-xl sm:text-2xl font-semibold text-current tracking-tight">
                    Detail Pengamatan <span class="text-ink">#{{ $monitoring->id }}</span>
                </h2>
                <x-status-badge :status="$monitoring->plant_condition" />
            </div>
            <p class="text-xs sm:text-sm text-muted mt-1 flex items-center gap-2">
                <span>Diperiksa pada: <strong class="text-ink tabular-nums">{{ $monitoring->monitored_at->format('d M Y, H:i') }} WIB</strong></span>
                <span class="text-muted">({{ $monitoring->monitored_at->diffForHumans() }})</span>
            </p>
        </div>

        <!-- Tombol Tindakan -->
        <div class="flex flex-wrap items-center gap-2.5">
            <a
                href="{{ route('monitorings.index') }}"
                class="px-3.5 py-2 rounded-[2px] bg-surface hover:bg-surface text-ink hover:text-ink text-xs font-semibold transition-colors flex items-center gap-2 border border-line"
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
                    class="px-4 py-2 rounded-[2px] bg-surface hover:bg-surface text-muted border border-line text-xs font-semibold transition-colors flex items-center gap-2"
                >
                    <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        class="px-4 py-2 rounded-[2px] bg-danger-soft hover:bg-danger-soft text-danger border border-danger/25 text-xs font-semibold transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>Hapus</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Informasi Profil Tanaman -->
    <div class="p-5 sm:p-6 rounded-[2px] bg-canvas border border-line">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-line">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-[2px] flex items-center justify-center text-current">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-current">
                        {{ $monitoring->plant->name ?? 'Tanaman #' . $monitoring->plant_id }}
                    </h3>
                    <div class="text-xs text-muted flex items-center gap-2 mt-0.5">
                        <span class="flex items-center gap-1 text-ink">
                            <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            <div class="text-xs text-muted bg-canvas px-4 py-2.5 rounded-[2px] border border-line">
                <div class="text-xs tabular-nums text-muted">ID Tanaman Database</div>
                <div class="font-semibold text-current tabular-nums text-sm">PLANT-{{ str_pad($monitoring->plant_id, 4, '0', STR_PAD_LEFT) }}</div>
            </div>
        </div>

        @if ($monitoring->plant && $monitoring->plant->description)
            <div class="mt-4 text-xs text-ink bg-canvas p-3 rounded-[2px] border border-line">
                <span class="font-semibold text-muted block mb-0.5">Deskripsi Plot Budidaya:</span>
                {{ $monitoring->plant->description }}
            </div>
        @endif
    </div>

    <x-active-sensor-values :monitoring="$monitoring" />
    <!-- Observasi morfologi manual -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

        <!-- 4. Tinggi Tanaman -->
        <div class="p-5 rounded-[2px] bg-canvas border border-line space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-ink flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-[2px] bg-accent"></span>
                    Tinggi Tajuk &amp; Sulur
                </span>
                <span class="text-xs tabular-nums px-2 py-0.5 rounded-[2px] bg-surface text-ink border border-line">
                    cm
                </span>
            </div>

            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-semibold text-current tabular-nums tracking-tight">
                    {{ $monitoring->plant_height !== null ? number_format($monitoring->plant_height, 1) : '-' }}
                </span>
                <span class="text-sm font-semibold text-muted">cm</span>
            </div>

            <div class="pt-2 border-t border-line text-xs text-muted flex items-center justify-between text-xs">
                <span>Parameter Morfologi:</span>
                <span class="text-ink">Tinggi tanaman melon</span>
            </div>
        </div>

        <!-- 5. Kerapatan Kanopi -->
        <div class="p-5 rounded-[2px] bg-canvas border border-line space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-ink flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-[2px] bg-accent"></span>
                    Kerapatan Kanopi
                </span>
                <span class="text-xs tabular-nums px-2 py-0.5 rounded-[2px] bg-surface text-ink border border-line">
                    %
                </span>
            </div>

            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-semibold text-current tabular-nums tracking-tight">
                    {{ $monitoring->plant_density !== null ? number_format($monitoring->plant_density, 0) : '-' }}
                </span>
                <span class="text-sm font-semibold text-muted">%</span>
            </div>

            <div class="pt-2 border-t border-line text-xs text-muted flex items-center justify-between text-xs">
                <span>Cakupan Daun:</span>
                <span class="text-ink">Persentase kanopi plot</span>
            </div>
        </div>

        <!-- 6. Suhu Larutan Nutrisi (Baru) -->


        <!-- 7. Konduktivitas Listrik EC (Baru) -->

        <!-- 11. Warna Daun & Visual -->
        <div class="p-5 rounded-[2px] bg-canvas border border-line space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-ink flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-[2px] bg-accent-soft"></span>
                    Warna Daun Tanaman
                </span>
                <span class="text-xs tabular-nums px-2 py-0.5 rounded-[2px] bg-accent-soft text-accent border border-line">
                    Visual
                </span>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-[2px] bg-accent-soft border border-line flex items-center justify-center">
                    <span class="w-4 h-4 rounded-[2px] bg-accent-soft"></span>
                </div>
                <div class="text-base font-semibold text-current">
                    {{ $monitoring->plant_color }}
                </div>
            </div>

            <div class="pt-2 border-t border-line text-xs text-muted flex items-center justify-between text-xs">
                <span>Indikasi Klorofil:</span>
                <span class="text-ink">Fisik daun teramati</span>
            </div>
        </div>

    </div>

    <!-- Catatan Observasi & Temuan Lapangan -->
    <div class="p-5 sm:p-6 rounded-[2px] bg-canvas border border-line space-y-4">
        <h3 class="text-base font-semibold text-current flex items-center gap-2">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Catatan Observasi Petugas Lapangan
        </h3>

        <div class="p-4 rounded-[2px] bg-canvas border border-line text-ink text-xs sm:text-sm leading-relaxed">
            @if ($monitoring->notes)
                {{ $monitoring->notes }}
            @else
                <span class="text-muted italic">Tidak ada catatan tambahan untuk observasi ini.</span>
            @endif
        </div>

        <div class="flex flex-wrap items-center justify-between text-xs text-muted pt-2 border-t border-line">
            <div>Dibuat pada: <span class="tabular-nums text-ink">{{ $monitoring->created_at->format('d M Y H:i:s') }}</span></div>
            <div>Terakhir Diperbarui: <span class="tabular-nums text-ink">{{ $monitoring->updated_at->format('d M Y H:i:s') }}</span></div>
        </div>
    </div>

</div>
@endsection
