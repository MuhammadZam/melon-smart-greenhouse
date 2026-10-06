@extends('layouts.app')

@section('title', 'Detail Tanaman - ' . $plant->name)

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
                <a href="{{ route('plants.index') }}" class="hover:text-emerald-400 transition-colors">
                    Data Tanaman
                </a>
                <span>/</span>
                <span class="text-emerald-400 font-medium">Detail #{{ $plant->id }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                    {{ $plant->name }}
                </h2>
                <x-status-badge :status="$plant->status" />
            </div>
            <p class="text-xs sm:text-sm text-slate-400 mt-1 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                </svg>
                <span>Lokasi: <strong class="text-slate-200">{{ $plant->location }}</strong></span>
                <span>&bull;</span>
                <span>Terdaftar sejak: <span class="font-mono text-slate-300">{{ $plant->created_at->format('d M Y') }}</span></span>
            </p>
        </div>

        <!-- Tombol Aksi -->
        <div class="flex flex-wrap items-center gap-2.5">
            <a
                href="{{ route('plants.index') }}"
                class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition-colors flex items-center gap-2 border border-slate-700/60"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Kembali</span>
            </a>

            <!-- Tombol Tambah Monitoring untuk Tanaman Ini (Admin & Pengamat Boleh) -->
            <a
                href="{{ route('monitorings.create') }}"
                class="px-3.5 py-2 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-xs font-semibold transition-colors flex items-center gap-2 shadow-sm"
            >
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Catat Monitoring</span>
            </a>

            {{-- Tombol Khusus Admin: Edit & Hapus --}}
            @if (auth()->user()->isAdmin())
                <a
                    href="{{ route('plants.edit', $plant) }}"
                    class="px-4 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-semibold transition-colors flex items-center gap-2 shadow-sm"
                >
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    <span>Edit Tanaman</span>
                </a>

                @if ($plant->monitorings_count > 0)
                    <button
                        type="button"
                        onclick="alert('Tanaman tidak dapat dihapus karena masih memiliki data monitoring.')"
                        class="px-4 py-2 rounded-xl bg-slate-800 text-slate-500 border border-slate-700 text-xs font-semibold cursor-not-allowed opacity-60 flex items-center gap-2"
                        title="Dilindungi: Memiliki riwayat monitoring"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m4-11a4 4 0 00-8 0v3h8V4z"></path>
                        </svg>
                        <span>Hapus (Terkunci)</span>
                    </button>
                @else
                    <form
                        method="POST"
                        action="{{ route('plants.destroy', $plant) }}"
                        class="inline-block"
                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus tanaman ini?')"
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
                            <span>Hapus Tanaman</span>
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    <!-- Informasi Profil Master Tanaman -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        
        <!-- Kartu 1: Status & Lokasi -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-3">
            <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                Status Budidaya &amp; Lokasi
            </div>
            <div>
                <div class="text-xs text-slate-400">Status Saat Ini:</div>
                <div class="mt-1"><x-status-badge :status="$plant->status" /></div>
            </div>
            <div class="pt-2 border-t border-slate-800 text-xs">
                <span class="text-slate-400 block">Penempatan Greenhouse:</span>
                <span class="font-semibold text-white">{{ $plant->location }}</span>
            </div>
        </div>

        <!-- Kartu 2: Volume Data Monitoring -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-3">
            <div class="text-[11px] font-bold uppercase tracking-wider text-teal-400 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                Volume Data Monitoring
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white font-mono">{{ $plant->monitorings_count }}</span>
                <span class="text-xs font-semibold text-slate-400">Riwayat Observasi</span>
            </div>
            <div class="pt-2 border-t border-slate-800 text-xs text-slate-400">
                Data telemetri mikroklimat tercatat di MySQL
            </div>
        </div>

        <!-- Kartu 3: Deskripsi Plot -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-2">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                Deskripsi Plot Budidaya
            </div>
            <div class="text-xs text-slate-300 leading-relaxed">
                @if ($plant->description)
                    {{ $plant->description }}
                @else
                    <span class="text-slate-500 italic">Belum ada keterangan khusus yang ditambahkan untuk tanaman ini.</span>
                @endif
            </div>
        </div>

    </div>

    <!-- Highlight: Data Pengamatan Terakhir (Jika Ada) -->
    @if ($latestMonitoring)
        <div class="p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800/80 pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                        Hasil Pemantauan Terkini (Monitoring Terakhir)
                    </h3>
                </div>
                <div class="text-xs text-slate-400 font-mono">
                    Waktu: <span class="text-slate-200 font-semibold">{{ $latestMonitoring->monitored_at->format('d M Y, H:i') }} WIB</span>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                
                <!-- Suhu -->
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Suhu Udara</span>
                    <span class="text-lg font-bold font-mono text-emerald-400">{{ number_format($latestMonitoring->temperature, 1) }}°C</span>
                </div>

                <!-- pH -->
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">pH Media</span>
                    <span class="text-lg font-bold font-mono text-teal-400">{{ number_format($latestMonitoring->ph, 2) }}</span>
                </div>

                <!-- Kelembapan -->
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Kelembapan</span>
                    <span class="text-lg font-bold font-mono text-sky-400">{{ number_format($latestMonitoring->humidity, 0) }}%</span>
                </div>

                <!-- Tinggi -->
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Tinggi Tajuk</span>
                    <span class="text-lg font-bold font-mono text-white">{{ $latestMonitoring->plant_height ? $latestMonitoring->plant_height . ' cm' : '-' }}</span>
                </div>

                <!-- Kondisi -->
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Kondisi</span>
                    <x-status-badge :status="$latestMonitoring->plant_condition" />
                </div>

                <!-- Aksi Detail -->
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center justify-center">
                    <a
                        href="{{ route('monitorings.show', $latestMonitoring) }}"
                        class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition-colors flex items-center gap-1"
                    >
                        <span>Lihat Detail</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>

            </div>
        </div>
    @endif

    <!-- Riwayat Pengamatan Berkala Tanaman Ini (Relationship hasMany) -->
    <div class="p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800/80 pb-3">
            <div>
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Riwayat Monitoring Tanaman (Hingga 10 Data Terakhir)
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Daftar observasi mikroklimat yang terkait langsung dengan plot tanaman melon ini.</p>
            </div>
            
            <a
                href="{{ route('monitorings.index', ['plant_id' => $plant->id]) }}"
                class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition-colors flex items-center gap-1 self-start sm:self-auto"
            >
                <span>Buka di Halaman Monitoring</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead>
                    <tr class="border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400 bg-slate-950/40">
                        <th class="py-3 px-4 rounded-l-xl">ID</th>
                        <th class="py-3 px-4">Waktu Pengamatan</th>
                        <th class="py-3 px-4 text-center">Suhu (°C)</th>
                        <th class="py-3 px-4 text-center">pH Media</th>
                        <th class="py-3 px-4 text-center">Kelembapan</th>
                        <th class="py-3 px-4">Kondisi &amp; Daun</th>
                        <th class="py-3 px-4 text-center rounded-r-xl">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse ($plant->monitorings as $item)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4 font-mono text-slate-400">#{{ $item->id }}</td>
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-300">
                                {{ $item->monitored_at->format('d M Y, H:i') }} WIB
                            </td>
                            <td class="py-3 px-4 text-center font-mono">
                                <span class="font-bold {{ $item->temperature >= 20 && $item->temperature <= 32 ? 'text-emerald-400' : 'text-amber-400' }}">
                                    {{ number_format($item->temperature, 1) }}°C
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-mono">
                                <span class="font-bold {{ $item->ph >= 5.8 && $item->ph <= 7.2 ? 'text-teal-400' : 'text-rose-400' }}">
                                    {{ number_format($item->ph, 2) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-mono">
                                <span class="font-bold {{ $item->humidity >= 60 && $item->humidity <= 85 ? 'text-sky-400' : 'text-amber-400' }}">
                                    {{ number_format($item->humidity, 0) }}%
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <x-status-badge :status="$item->plant_condition" />
                                <span class="text-[10px] text-slate-400 ml-1.5">{{ $item->plant_color }}</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a
                                    href="{{ route('monitorings.show', $item) }}"
                                    class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors border border-slate-700/60 inline-flex items-center gap-1 text-[11px]"
                                    title="Lihat Detail Monitoring"
                                >
                                    <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <span>Detail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <div class="text-xs">Belum ada data monitoring yang dicatat untuk tanaman ini.</div>
                                    <a
                                        href="{{ route('monitorings.create') }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-xs font-semibold transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        <span>Catat Monitoring Pertama</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
