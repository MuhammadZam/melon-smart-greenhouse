@extends('layouts.app')

@section('title', 'Data Tanaman Melon')

@section('content')
<div class="space-y-6">

    <!-- Header & Breadcrumbs -->
    <div class="ui-page-header flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-muted mb-2">
                <a href="{{ route('dashboard') }}" class="hover:text-accent transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                <span>/</span>
                <span class="text-accent font-medium">Data Tanaman</span>
            </div>

            <div class="flex items-center gap-2.5">
                <h2 class="ui-page-title text-xl sm:text-2xl font-semibold text-current tracking-tight">
                    Data Tanaman <span class="text-ink">Melon</span>
                </h2>
                <span class="hidden sm:inline-flex items-center px-2.5 py-0.5 rounded-[2px] text-xs font-semibold {{ auth()->user()->isAdmin() ? 'bg-accent-soft text-accent border border-line' : 'bg-accent-soft text-accent border border-line' }}">
                    {{ auth()->user()->isAdmin() ? 'Akses Admin (Full Master CRUD)' : 'Akses Pengamat (Lihat Master Data)' }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-muted mt-1">
                Kelola master data plot tanaman melon, lokasi greenhouse, status budidaya, dan riwayat keterkaitan monitoring.
            </p>
        </div>

        {{-- Tombol Tambah Tanaman (HANYA UNTUK ROLE ADMIN) --}}
        @if (auth()->user()->isAdmin())
            <div class="flex items-center gap-3">
                <a
                    href="{{ route('plants.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-[2px] text-current text-xs font-semibold tracking-wide transition-all transform"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Tambah Tanaman</span>
                </a>
            </div>
        @endif
    </div>

    <!-- Ringkasan Statistik Status Tanaman -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">

        <!-- Total Tanaman -->
        <div class="p-4 rounded-[2px] bg-canvas border border-line flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-muted">Total Tanaman</span>
                <div class="text-xl sm:text-2xl font-semibold text-current mt-1">{{ $totalPlants }}</div>
            </div>
            <div class="w-9 h-9 rounded-[2px] bg-accent-soft border border-line text-accent flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>

        <!-- Aktif -->
        <div class="p-4 rounded-[2px] bg-canvas border border-line flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-accent">Tanaman Aktif</span>
                <div class="text-xl sm:text-2xl font-semibold text-accent mt-1">{{ $activePlants }}</div>
            </div>
            <div class="w-9 h-9 rounded-[2px] bg-accent-soft border border-line text-accent flex items-center justify-center">
                <span class="w-2.5 h-2.5 rounded-[2px] bg-accent-soft"></span>
            </div>
        </div>

        <!-- Sudah Dipanen -->
        <div class="p-4 rounded-[2px] bg-canvas border border-line flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-warning">Sudah Dipanen</span>
                <div class="text-xl sm:text-2xl font-semibold text-warning mt-1">{{ $harvestedPlants }}</div>
            </div>
            <div class="w-9 h-9 rounded-[2px] bg-warning-soft border border-warning/25 text-warning flex items-center justify-center">
                <span class="w-2.5 h-2.5 rounded-[2px] bg-surface"></span>
            </div>
        </div>

        <!-- Tidak Aktif -->
        <div class="p-4 rounded-[2px] bg-canvas border border-line flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-muted">Tidak Aktif</span>
                <div class="text-xl sm:text-2xl font-semibold text-ink mt-1">{{ $inactivePlants }}</div>
            </div>
            <div class="w-9 h-9 rounded-[2px] bg-surface border border-line text-muted flex items-center justify-center">
                <span class="w-2.5 h-2.5 rounded-[2px] bg-surface"></span>
            </div>
        </div>

    </div>

    <!-- Filter & Pencarian Card -->
    <div class="p-5 sm:p-6 rounded-[2px] bg-canvas border border-line space-y-4">
        <form method="GET" action="{{ route('plants.index') }}" class="space-y-4">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">

                <!-- 1. Pencarian Teks -->
                <div class="md:col-span-2">
                    <label for="search" class="block text-xs font-semibold text-muted mb-1.5">
                        Pencarian Tanaman / Lokasi / Deskripsi
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            value="{{ request('search') }}"
                            placeholder="Cari berdasarkan nama varietas, sektor greenhouse, atau deskripsi..."
                            class="w-full pl-9 pr-3 py-2 rounded-[2px] bg-canvas border border-line hover:border-line text-current text-xs placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
                        >
                    </div>
                </div>

                <!-- 2. Filter Status Tanaman -->
                <div>
                    <label for="status" class="block text-xs font-semibold text-muted mb-1.5">
                        Filter Status
                    </label>
                    <select
                        name="status"
                        id="status"
                        class="w-full px-3 py-2 rounded-[2px] bg-canvas border border-line hover:border-line text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors cursor-pointer"
                    >
                        <option value="all">Semua Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="harvested" {{ request('status') === 'harvested' ? 'selected' : '' }}>Sudah Dipanen</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                    </select>
                </div>

            </div>

            <!-- Bar Tombol Filter -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-line">
                <div class="flex items-center gap-2 text-xs text-muted">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-[2px] bg-surface text-ink tabular-nums text-xs">
                        {{ $filteredCount }}
                    </span>
                    <span>plot ditemukan dari total <strong class="text-current">{{ $totalPlants }}</strong> tanaman terdaftar</span>

                    @if(request()->anyFilled(['search', 'status']) && request('status') !== 'all')
                        <span class="hidden sm:inline-block text-warning text-xs">&bull; (Filter Aktif)</span>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('plants.index') }}"
                        class="px-3.5 py-1.5 rounded-[2px] bg-surface hover:bg-surface text-ink text-xs font-semibold transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Reset</span>
                    </a>

                    <button
                        type="submit"
                        class="px-4 py-1.5 rounded-[2px] bg-accent hover:bg-accent text-current text-xs font-semibold transition-colors flex items-center gap-1.5"
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

    <!-- Tabel Daftar Tanaman -->
    <div class="p-5 sm:p-6 rounded-[2px] bg-canvas border border-line space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead>
                    <tr class="border-b border-line text-xs font-semibold text-muted bg-canvas">
                        <th class="py-3 px-4 rounded-l-xl">No / ID</th>
                        <th class="py-3 px-4">Tanaman / Plot</th>
                        <th class="py-3 px-4">Lokasi Greenhouse</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Jumlah Monitoring</th>
                        <th class="py-3 px-4">Monitoring Terakhir</th>
                        <th class="py-3 px-4 text-center rounded-r-xl">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($plants as $item)
                        <tr class="hover:bg-surface transition-colors">

                            <!-- No & ID -->
                            <td class="py-3 px-4 tabular-nums text-muted">
                                <span class="px-2 py-0.5 rounded-[2px] bg-surface text-xs font-semibold text-ink">
                                    #{{ $item->id }}
                                </span>
                            </td>

                            <!-- Tanaman / Plot -->
                            <td class="py-3 px-4">
                                <div class="font-semibold text-current">
                                    {{ $item->name }}
                                </div>
                                @if ($item->description)
                                    <div class="text-xs text-muted truncate max-w-xs mt-0.5" title="{{ $item->description }}">
                                        {{ $item->description }}
                                    </div>
                                @endif
                            </td>

                            <!-- Lokasi Greenhouse -->
                            <td class="py-3 px-4 text-ink">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    </svg>
                                    <span>{{ $item->location }}</span>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="py-3 px-4 text-center">
                                <x-status-badge :status="$item->status" />
                            </td>

                            <!-- Jumlah Monitoring (Relationship Count) -->
                            <td class="py-3 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-[2px] text-xs tabular-nums font-semibold {{ $item->monitorings_count > 0 ? 'bg-accent-soft text-accent border border-line' : 'bg-surface text-muted border border-line' }}">
                                    {{ $item->monitorings_count }} Pengamatan
                                </span>
                            </td>

                            <!-- Monitoring Terakhir -->
                            <td class="py-3 px-4 text-ink tabular-nums text-xs">
                                @if ($item->latestMonitoring)
                                    <div>{{ $item->latestMonitoring->monitored_at->format('d M Y, H:i') }}</div>
                                    <div class="text-xs text-muted">
                                        {{ $item->latestMonitoring->temperature }}°C &bull; pH {{ $item->latestMonitoring->ph }}
                                    </div>
                                @else
                                    <span class="text-muted italic">Belum ada pengamatan</span>
                                @endif
                            </td>

                            <!-- Tombol Aksi Sesuai Role Matrix -->
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">

                                    <!-- 1. Lihat Detail Tanaman (Admin: ✅ | Pengamat: ✅) -->
                                    <a
                                        href="{{ route('plants.show', $item) }}"
                                        class="p-1.5 rounded-[2px] bg-surface hover:bg-surface text-ink hover:text-ink transition-colors border border-line"
                                        title="Lihat Detail Tanaman"
                                    >
                                        <svg class="w-4 h-4 text-ink" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>

                                    {{-- 2. Edit Tanaman (Admin: ✅ | Pengamat: ❌) --}}
                                    @if (auth()->user()->isAdmin())
                                        <a
                                            href="{{ route('plants.edit', $item) }}"
                                            class="p-1.5 rounded-[2px] bg-surface hover:bg-surface text-muted hover:text-muted transition-colors border border-line"
                                            title="Edit Data Tanaman (Admin)"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>

                                        {{-- 3. Hapus Tanaman (Admin: ✅ | Pengamat: ❌) dengan Proteksi Monitoring --}}
                                        @if ($item->monitorings_count > 0)
                                            <!-- Tanaman memiliki data monitoring: Ditandai proteksi -->
                                            <button
                                                type="button"
                                                onclick="alert('Tanaman tidak dapat dihapus karena masih memiliki data monitoring. Hapus data monitoring terkait terlebih dahulu.')"
                                                class="p-1.5 rounded-[2px] bg-surface text-muted border border-line cursor-not-allowed opacity-60"
                                                title="Dilindungi: Memiliki {{ $item->monitorings_count }} riwayat monitoring"
                                            >
                                                <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m4-11a4 4 0 00-8 0v3h8V4z"></path>
                                                </svg>
                                            </button>
                                        @else
                                            <!-- Tanaman tanpa data monitoring: Dapat dihapus -->
                                            <form
                                                method="POST"
                                                action="{{ route('plants.destroy', $item) }}"
                                                class="inline-block"
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus tanaman ini?')"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="p-1.5 rounded-[2px] bg-danger-soft hover:bg-danger-soft text-danger hover:text-danger transition-colors border border-danger/25"
                                                    title="Hapus Data Tanaman (Admin)"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    @endif

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 px-4 text-center">
                                <div class="max-w-sm mx-auto space-y-3">
                                    <div class="w-12 h-12 rounded-[2px] bg-surface text-muted flex items-center justify-center mx-auto">
                                        <svg class="w-6 h-6 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="text-sm font-semibold text-current">Tidak ada data tanaman ditemukan</div>
                                    <p class="text-xs text-muted">
                                        Coba sesuaikan kriteria pencarian atau status filter Anda.
                                    </p>
                                    <div class="pt-2 flex items-center justify-center gap-2">
                                        <a
                                            href="{{ route('plants.index') }}"
                                            class="px-3 py-1.5 rounded-[2px] bg-surface hover:bg-surface text-ink text-xs font-semibold transition-colors"
                                        >
                                            Reset Filter
                                        </a>
                                        @if (auth()->user()->isAdmin())
                                            <a
                                                href="{{ route('plants.create') }}"
                                                class="px-3.5 py-1.5 rounded-[2px] bg-accent hover:bg-accent text-current text-xs font-semibold transition-colors"
                                            >
                                                Tambah Tanaman Baru
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Section -->
        @if ($plants->hasPages())
            <div class="pt-4 border-t border-line flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="text-xs text-muted">
                    Menampilkan data <span class="font-semibold text-current">{{ $plants->firstItem() ?? 0 }}</span> sampai <span class="font-semibold text-current">{{ $plants->lastItem() ?? 0 }}</span> dari total <span class="font-semibold text-current">{{ $plants->total() }}</span> hasil
                </div>
                <div class="custom-pagination">
                    {{ $plants->links() }}
                </div>
            </div>
        @endif

    </div>

</div>
@endsection
