@extends('layouts.app')

@section('title', 'Edit Data Monitoring #' . $monitoring->id)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 backdrop-blur-xl shadow-xl">
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
                <span class="text-amber-400 font-medium">Edit #{{ $monitoring->id }}</span>
            </div>

            <div class="flex items-center gap-2.5">
                <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                    Koreksi Data <span class="bg-gradient-to-r from-amber-400 to-emerald-400 bg-clip-text text-transparent">Monitoring #{{ $monitoring->id }}</span>
                </h2>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20">
                    MODE ADMIN
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Koreksi parameter observasi atau kalibrasi pembacaan sensor tanaman melon.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('monitorings.show', $monitoring) }}"
                class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition-colors flex items-center gap-2 border border-slate-700/60"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <span>Lihat Detail</span>
            </a>

            <a
                href="{{ route('monitorings.index') }}"
                class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition-colors flex items-center gap-2 border border-slate-700/60"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Batal</span>
            </a>
        </div>
    </div>

    <!-- Alert Ringkasan Error jika Validasi Gagal -->
    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs shadow-sm space-y-1">
            <div class="font-bold flex items-center gap-2 text-rose-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Terdapat beberapa kesalahan input saat memperbarui data:
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-[11px] pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Edit Monitoring -->
    <form method="POST" action="{{ route('monitorings.update', $monitoring) }}" class="p-5 sm:p-7 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-6">
        @csrf
        @method('PUT')

        <!-- SECTION 1: Tanaman & Waktu Pengamatan -->
        <div class="space-y-4">
            <div class="border-b border-slate-800/80 pb-2">
                <h3 class="text-sm font-bold text-amber-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    1. Identitas Tanaman &amp; Waktu Pengamatan
                </h3>
                <p class="text-xs text-slate-400">Pilih plot tanaman melon yang diamati serta waktu pencatatan data.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Dropdown Tanaman -->
                <div>
                    <label for="plant_id" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Pilih Tanaman Melon <span class="text-rose-400">*</span>
                    </label>
                    <select
                        name="plant_id"
                        id="plant_id"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border {{ $errors->has('plant_id') ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-700/80' }} text-white text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors cursor-pointer"
                    >
                        @foreach ($plants as $p)
                            <option value="{{ $p->id }}" {{ (string)old('plant_id', $monitoring->plant_id) === (string)$p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->location ?? 'Greenhouse' }})
                            </option>
                        @endforeach
                    </select>
                    @error('plant_id')
                        <p class="text-rose-400 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Waktu Monitoring -->
                <div>
                    <label for="monitored_at" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Waktu Observasi <span class="text-rose-400">*</span>
                    </label>
                    <input
                        type="datetime-local"
                        name="monitored_at"
                        id="monitored_at"
                        value="{{ old('monitored_at', $monitoring->monitored_at ? $monitoring->monitored_at->format('Y-m-d\TH:i') : '') }}"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border {{ $errors->has('monitored_at') ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-700/80' }} text-white text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors"
                    >
                    @error('monitored_at')
                        <p class="text-rose-400 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- SECTION 2: Parameter Mikroklimat & Sensor -->
        <div class="space-y-4 pt-2">
            <div class="border-b border-slate-800/80 pb-2">
                <h3 class="text-sm font-bold text-teal-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                    2. Parameter Mikroklimat Greenhouse
                </h3>
                <p class="text-xs text-slate-400">Pengukuran pembacaan suhu udara, pH larutan nutrisi, dan kelembapan relatif.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                
                <!-- Suhu (°C) -->
                <div>
                    <label for="temperature" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Suhu Udara (°C) <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="temperature"
                            id="temperature"
                            step="0.01"
                            value="{{ old('temperature', $monitoring->temperature) }}"
                            placeholder="Contoh: 27.50"
                            required
                            class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-950 border {{ $errors->has('temperature') ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-700/80' }} text-white text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors font-mono"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400 text-xs">
                            °C
                        </span>
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 block">Optimal melon: 20°C &ndash; 32°C</span>
                    @error('temperature')
                        <p class="text-rose-400 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- pH -->
                <div>
                    <label for="ph" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        pH Nutrisi / Media <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="ph"
                            id="ph"
                            step="0.01"
                            value="{{ old('ph', $monitoring->ph) }}"
                            placeholder="Contoh: 6.50"
                            required
                            class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-950 border {{ $errors->has('ph') ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-700/80' }} text-white text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors font-mono"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400 text-xs">
                            pH
                        </span>
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 block">Optimal melon: 5.80 &ndash; 7.20</span>
                    @error('ph')
                        <p class="text-rose-400 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kelembapan (%) -->
                <div>
                    <label for="humidity" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Kelembapan Udara (%) <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="humidity"
                            id="humidity"
                            step="0.01"
                            value="{{ old('humidity', $monitoring->humidity) }}"
                            placeholder="Contoh: 75.00"
                            required
                            class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-950 border {{ $errors->has('humidity') ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-700/80' }} text-white text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors font-mono"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400 text-xs">
                            %
                        </span>
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 block">Optimal melon: 60% &ndash; 85%</span>
                    @error('humidity')
                        <p class="text-rose-400 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        <!-- SECTION 3: Parameter Morfologi & Pertumbuhan -->
        <div class="space-y-4 pt-2">
            <div class="border-b border-slate-800/80 pb-2">
                <h3 class="text-sm font-bold text-sky-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                    3. Pertumbuhan Morfologi Tanaman (Opsional)
                </h3>
                <p class="text-xs text-slate-400">Ukuran pertumbuhan tinggi tajuk sulur dan persentase kerapatan kanopi daun.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <!-- Tinggi Tanaman -->
                <div>
                    <label for="plant_height" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Tinggi Tanaman (cm)
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="plant_height"
                            id="plant_height"
                            step="0.1"
                            value="{{ old('plant_height', $monitoring->plant_height) }}"
                            placeholder="Contoh: 45.0"
                            class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-950 border {{ $errors->has('plant_height') ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-700/80' }} text-white text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors font-mono"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400 text-xs">
                            cm
                        </span>
                    </div>
                    @error('plant_height')
                        <p class="text-rose-400 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kerapatan Kanopi -->
                <div>
                    <label for="plant_density" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Kerapatan Kanopi Daun (%)
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="plant_density"
                            id="plant_density"
                            step="0.1"
                            value="{{ old('plant_density', $monitoring->plant_density) }}"
                            placeholder="Contoh: 70.0"
                            class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-950 border {{ $errors->has('plant_density') ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-700/80' }} text-white text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors font-mono"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400 text-xs">
                            %
                        </span>
                    </div>
                    @error('plant_density')
                        <p class="text-rose-400 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        <!-- SECTION 4: Kondisi Visual & Warna -->
        <div class="space-y-4 pt-2">
            <div class="border-b border-slate-800/80 pb-2">
                <h3 class="text-sm font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    4. Evaluasi Visual Lapangan
                </h3>
                <p class="text-xs text-slate-400">Kondisi kesehatan umum fisik melon dan kenampakan visual warna daun.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <!-- Kondisi Tanaman -->
                <div>
                    <label for="plant_condition" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Kondisi Tanaman <span class="text-rose-400">*</span>
                    </label>
                    <select
                        name="plant_condition"
                        id="plant_condition"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border {{ $errors->has('plant_condition') ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-700/80' }} text-white text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors cursor-pointer"
                    >
                        <option value="">-- Pilih Kondisi Fisik --</option>
                        <option value="Subur & Baik" {{ old('plant_condition', $monitoring->plant_condition) === 'Subur & Baik' ? 'selected' : '' }}>Subur &amp; Baik (Optimal)</option>
                        <option value="Baik" {{ old('plant_condition', $monitoring->plant_condition) === 'Baik' ? 'selected' : '' }}>Baik</option>
                        <option value="Perlu Perhatian" {{ old('plant_condition', $monitoring->plant_condition) === 'Perlu Perhatian' ? 'selected' : '' }}>Perlu Perhatian (Warning)</option>
                        <option value="Kurang Baik / Kritis" {{ old('plant_condition', $monitoring->plant_condition) === 'Kurang Baik / Kritis' ? 'selected' : '' }}>Kurang Baik / Kritis</option>
                    </select>
                    @error('plant_condition')
                        <p class="text-rose-400 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Warna Daun -->
                <div>
                    <label for="plant_color" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Warna Daun / Tajuk <span class="text-rose-400">*</span>
                    </label>
                    <select
                        name="plant_color"
                        id="plant_color"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border {{ $errors->has('plant_color') ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-700/80' }} text-white text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors cursor-pointer"
                    >
                        <option value="">-- Pilih Warna Daun --</option>
                        <option value="Hijau Segar" {{ old('plant_color', $monitoring->plant_color) === 'Hijau Segar' ? 'selected' : '' }}>Hijau Segar</option>
                        <option value="Hijau Pekat" {{ old('plant_color', $monitoring->plant_color) === 'Hijau Pekat' ? 'selected' : '' }}>Hijau Pekat</option>
                        <option value="Hijau Muda" {{ old('plant_color', $monitoring->plant_color) === 'Hijau Muda' ? 'selected' : '' }}>Hijau Muda</option>
                        <option value="Hijau Kekuningan" {{ old('plant_color', $monitoring->plant_color) === 'Hijau Kekuningan' ? 'selected' : '' }}>Hijau Kekuningan</option>
                        <option value="Kekuningan" {{ old('plant_color', $monitoring->plant_color) === 'Kekuningan' ? 'selected' : '' }}>Kekuningan</option>
                        <option value="Cokelat Kering" {{ old('plant_color', $monitoring->plant_color) === 'Cokelat Kering' ? 'selected' : '' }}>Cokelat Kering</option>
                    </select>
                    @error('plant_color')
                        <p class="text-rose-400 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        <!-- SECTION 5: Catatan Observasi Lapangan -->
        <div class="space-y-2 pt-2">
            <label for="notes" class="block text-xs font-semibold text-slate-300">
                Catatan Observasi Tambahan
            </label>
            <textarea
                name="notes"
                id="notes"
                rows="3"
                placeholder="Tuliskan catatan perbaikan atau observasi..."
                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border {{ $errors->has('notes') ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-700/80' }} text-white text-xs placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors"
            >{{ old('notes', $monitoring->notes) }}</textarea>
            @error('notes')
                <p class="text-rose-400 text-[11px] mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Bar Tombol Aksi Simpan -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <a
                href="{{ route('monitorings.index') }}"
                class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors"
            >
                Batal
            </a>

            <button
                type="submit"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-emerald-600 hover:from-amber-500 hover:to-emerald-500 text-white text-xs font-bold shadow-lg shadow-amber-600/25 transition-all transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Perbarui Data Monitoring</span>
            </button>
        </div>

    </form>

</div>
@endsection
