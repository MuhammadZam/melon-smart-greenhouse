@extends('layouts.app')

@section('title', 'Data Monitoring Tanaman Melon')

@section('content')
<div class="space-y-6">

    <!-- Header & Breadcrumbs -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 backdrop-blur-xl shadow-xl">
        <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-400 transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                <span>/</span>
                <span class="text-emerald-400 font-medium">Data Monitoring</span>
            </div>

            <div class="flex items-center gap-2.5">
                <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                    Data Monitoring <span class="bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent">Tanaman Melon</span>
                </h2>
                <span class="hidden sm:inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold {{ auth()->user()->isAdmin() ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-teal-500/10 text-teal-400 border border-teal-500/20' }}">
                    {{ auth()->user()->isAdmin() ? 'Akses Admin (Full CRUD)' : 'Akses Pengamat (Catat & Lihat)' }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Kelola riwayat observasi parameter lingkungan greenhouse, kondisi tanaman, dan nilai sensor mikroklimat.
            </p>
        </div>

        <!-- Tombol Aksi Utama -->
        <div class="flex items-center gap-2 sm:gap-3">
            <a
                href="{{ route('dashboard') }}#charts"
                class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 text-xs font-semibold tracking-wide shadow-md transition-all hover:text-white"
                title="Lihat Visualisasi Grafik Tren & Analytics"
            >
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                </svg>
                <span>Lihat Analytics</span>
            </a>
            <a
                href="{{ route('monitorings.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white text-xs font-bold tracking-wide shadow-lg shadow-emerald-600/25 hover:shadow-emerald-500/35 transition-all transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Tambah Monitoring</span>
            </a>
        </div>
    </div>

    <!-- Filter, Search, & Kategori Card -->
    <div class="p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
        <form method="GET" action="{{ route('monitorings.index') }}" class="space-y-4">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3.5">
                
                <!-- 1. Pencarian Teks -->
                <div class="lg:col-span-2">
                    <label for="search" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                        Pencarian
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            value="{{ request('search') }}"
                            placeholder="Cari catatan, kondisi, warna, atau nama tanaman..."
                            class="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-950 border border-slate-700/80 hover:border-slate-600 text-white text-xs placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                        >
                    </div>
                </div>

                <!-- 2. Filter Tanaman -->
                <div>
                    <label for="plant_id" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                        Tanaman Melon
                    </label>
                    <select
                        name="plant_id"
                        id="plant_id"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700/80 hover:border-slate-600 text-white text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors cursor-pointer"
                    >
                        <option value="all">Semua Tanaman</option>
                        @foreach ($plants as $plant)
                            <option value="{{ $plant->id }}" {{ (string)request('plant_id') === (string)$plant->id ? 'selected' : '' }}>
                                {{ $plant->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Filter Kondisi -->
                <div>
                    <label for="condition" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                        Kondisi Fisik
                    </label>
                    <select
                        name="condition"
                        id="condition"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700/80 hover:border-slate-600 text-white text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors cursor-pointer"
                    >
                        <option value="all">Semua Kondisi</option>
                        @foreach ($conditions as $cond)
                            <option value="{{ $cond }}" {{ request('condition') === $cond ? 'selected' : '' }}>
                                {{ $cond }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 4. Rentang Tanggal Dari -->
                <div>
                    <label for="date_from" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                        Tanggal Mulai
                    </label>
                    <input
                        type="date"
                        name="date_from"
                        id="date_from"
                        value="{{ request('date_from') }}"
                        class="w-full px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-700/80 hover:border-slate-600 text-white text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                    >
                </div>

            </div>

            <!-- Bar Tombol Filter -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-800/80">
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 font-mono text-[11px]">
                        {{ $filteredCount }}
                    </span>
                    <span>data ditemukan dari total <strong class="text-white">{{ $totalRecords }}</strong> riwayat</span>
                    
                    @if(request()->anyFilled(['search', 'plant_id', 'condition', 'date_from', 'date_to']) && (request('plant_id') !== 'all' || request('condition') !== 'all'))
                        <span class="hidden sm:inline-block text-amber-400 text-[11px]">&bull; (Filter Aktif)</span>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('monitorings.index') }}"
                        class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Reset</span>
                    </a>

                    <button
                        type="submit"
                        class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-sm"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </div>

        </form>
    </div>

    <!-- Tabel Daftar Monitoring -->
    <div class="p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead>
                    <tr class="border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400 bg-slate-950/40">
                        <th class="py-3 px-4 rounded-l-xl">No / ID</th>
                        <th class="py-3 px-4">Tanaman &amp; Lokasi</th>
                        <th class="py-3 px-4">Waktu Pengamatan</th>
                        <th class="py-3 px-4 text-center">Suhu (°C)</th>
                        <th class="py-3 px-4 text-center">pH Media</th>
                        <th class="py-3 px-4 text-center">Kelembapan</th>
                        <th class="py-3 px-4 text-center">Morfologi</th>
                        <th class="py-3 px-4">Kondisi &amp; Daun</th>
                        <th class="py-3 px-4 text-center rounded-r-xl">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse ($monitorings as $index => $item)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            
                            <!-- No & ID -->
                            <td class="py-3 px-4 font-mono text-slate-400">
                                <span class="px-2 py-0.5 rounded bg-slate-800/80 text-[11px] font-semibold text-slate-300">
                                    #{{ $item->id }}
                                </span>
                            </td>

                            <!-- Tanaman & Lokasi -->
                            <td class="py-3 px-4">
                                <div class="font-semibold text-white">
                                    {{ $item->plant->name ?? 'Tanaman #' . $item->plant_id }}
                                </div>
                                <div class="text-[10px] text-slate-400 flex items-center gap-1 mt-0.5">
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    </svg>
                                    {{ $item->plant->location ?? '-' }}
                                </div>
                            </td>

                            <!-- Waktu Pengamatan -->
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-300">
                                <div>{{ $item->monitored_at->format('d M Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $item->monitored_at->format('H:i:s') }} WIB</div>
                            </td>

                            <!-- Suhu -->
                            <td class="py-3 px-4 text-center font-mono">
                                <span class="font-bold px-2 py-0.5 rounded-lg text-xs {{ $item->temperature >= 20 && $item->temperature <= 32 ? 'text-emerald-400 bg-emerald-500/10 border border-emerald-500/20' : 'text-amber-400 bg-amber-500/10 border border-amber-500/20' }}">
                                    {{ number_format($item->temperature, 1) }}°C
                                </span>
                            </td>

                            <!-- pH -->
                            <td class="py-3 px-4 text-center font-mono">
                                <span class="font-bold px-2 py-0.5 rounded-lg text-xs {{ $item->ph >= 5.8 && $item->ph <= 7.2 ? 'text-teal-400 bg-teal-500/10 border border-teal-500/20' : 'text-rose-400 bg-rose-500/10 border border-rose-500/20' }}">
                                    {{ number_format($item->ph, 2) }}
                                </span>
                            </td>

                            <!-- Kelembapan -->
                            <td class="py-3 px-4 text-center font-mono">
                                <span class="font-bold px-2 py-0.5 rounded-lg text-xs {{ $item->humidity >= 60 && $item->humidity <= 85 ? 'text-sky-400 bg-sky-500/10 border border-sky-500/20' : 'text-amber-400 bg-amber-500/10 border border-amber-500/20' }}">
                                    {{ number_format($item->humidity, 0) }}%
                                </span>
                            </td>

                            <!-- Morfologi (Tinggi & Kerapatan) -->
                            <td class="py-3 px-4 text-center font-mono text-[11px] text-slate-300">
                                <div>{{ $item->plant_height ? number_format($item->plant_height, 1) . ' cm' : '-' }}</div>
                                <div class="text-[10px] text-slate-400">Kanopi: {{ $item->plant_density ? number_format($item->plant_density, 0) . '%' : '-' }}</div>
                            </td>

                            <!-- Kondisi Visual & Warna -->
                            <td class="py-3 px-4">
                                <div>
                                    <x-status-badge :status="$item->plant_condition" />
                                </div>
                                <div class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    {{ $item->plant_color }}
                                </div>
                            </td>

                            <!-- Tombol Aksi Sesuai Role Matrix -->
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    
                                    <!-- 1. Lihat Detail (Admin: ✅ | Pengamat: ✅) -->
                                    <a
                                        href="{{ route('monitorings.show', $item) }}"
                                        class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors border border-slate-700/60"
                                        title="Lihat Detail Monitoring"
                                    >
                                        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>

                                    <!-- 2. Edit Monitoring (Admin: ✅ | Pengamat: ❌) -->
                                    @if (auth()->user()->isAdmin())
                                        <a
                                            href="{{ route('monitorings.edit', $item) }}"
                                            class="p-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 hover:text-amber-300 transition-colors border border-amber-500/25"
                                            title="Edit Data Monitoring (Admin)"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>

                                        <!-- 3. Hapus Monitoring (Admin: ✅ | Pengamat: ❌) -->
                                        <form
                                            method="POST"
                                            action="{{ route('monitorings.destroy', $item) }}"
                                            class="inline-block"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan monitoring #{{ $item->id }} ini? Tindakan ini tidak dapat dibatalkan.')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 transition-colors border border-rose-500/25"
                                                title="Hapus Data Monitoring (Admin)"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 px-4 text-center">
                                <div class="max-w-sm mx-auto space-y-3">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="text-sm font-semibold text-white">Tidak ada data monitoring ditemukan</div>
                                    <p class="text-xs text-slate-400">
                                        Coba sesuaikan kriteria pencarian atau filter Anda, atau tambahkan data monitoring baru.
                                    </p>
                                    <div class="pt-2 flex items-center justify-center gap-2">
                                        <a
                                            href="{{ route('monitorings.index') }}"
                                            class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors"
                                        >
                                            Reset Filter
                                        </a>
                                        <a
                                            href="{{ route('monitorings.create') }}"
                                            class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition-colors"
                                        >
                                            Tambah Data
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Section -->
        @if ($monitorings->hasPages())
            <div class="pt-4 border-t border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="text-xs text-slate-400">
                    Menampilkan data <span class="font-bold text-white">{{ $monitorings->firstItem() ?? 0 }}</span> sampai <span class="font-bold text-white">{{ $monitorings->lastItem() ?? 0 }}</span> dari total <span class="font-bold text-white">{{ $monitorings->total() }}</span> hasil
                </div>
                <div class="custom-pagination">
                    {{ $monitorings->links() }}
                </div>
            </div>
        @endif

    </div>

</div>
@endsection
