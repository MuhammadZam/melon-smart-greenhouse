@extends('layouts.app')

@section('title', 'Edit Data Monitoring #' . $monitoring->id)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header & Breadcrumb -->
    <div class="ui-page-header flex flex-col sm:flex-row sm:items-center justify-between gap-4">
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
                <span class="text-warning font-medium">Edit #{{ $monitoring->id }}</span>
            </div>

            <div class="flex items-center gap-2.5">
                <h2 class="ui-page-title text-xl sm:text-2xl font-semibold text-current tracking-tight">
                    Koreksi Data <span class="text-ink">Monitoring #{{ $monitoring->id }}</span>
                </h2>
                <span class="px-2 py-0.5 rounded-[2px] text-xs tabular-nums font-semibold bg-warning-soft text-warning border border-warning/25">
                    MODE ADMIN
                </span>
            </div>
            <p class="text-xs sm:text-sm text-muted mt-1">
                Koreksi parameter observasi atau kalibrasi pembacaan sensor tanaman melon.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('monitorings.show', $monitoring) }}"
                class="px-3.5 py-2 rounded-[2px] bg-surface hover:bg-surface text-ink hover:text-ink text-xs font-semibold transition-colors flex items-center gap-2 border border-line"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <span>Lihat Detail</span>
            </a>

            <a
                href="{{ route('monitorings.index') }}"
                class="px-3.5 py-2 rounded-[2px] bg-surface hover:bg-surface text-ink hover:text-ink text-xs font-semibold transition-colors flex items-center gap-2 border border-line"
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
        <div role="alert" class="p-4 rounded-[2px] bg-danger-soft border border-danger/25 text-danger text-xs space-y-1">
            <div class="font-semibold flex items-center gap-2 text-danger">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Terdapat beberapa kesalahan input saat memperbarui data:
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-xs pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Edit Monitoring -->
    <form method="POST" action="{{ route('monitorings.update', $monitoring) }}" class="p-5 sm:p-7 rounded-[2px] bg-canvas border border-line space-y-6">
        @csrf
        @method('PUT')

        <!-- SECTION 1: Tanaman & Waktu Pengamatan -->
        <div class="space-y-4">
            <div class="border-b border-line pb-2">
                <h3 class="text-sm font-semibold text-warning flex items-center gap-2">
                    <span class="w-2 h-2 rounded-[2px] bg-surface"></span>
                    1. Identitas Tanaman &amp; Waktu Pengamatan
                </h3>
                <p class="text-xs text-muted">Pilih plot tanaman melon yang diamati serta waktu pencatatan data.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Dropdown Tanaman -->
                <div>
                    <label for="plant_id" class="block text-xs font-semibold text-ink mb-1.5">
                        Pilih Tanaman Melon <span class="text-danger">*</span>
                    </label>
                    <select
                        name="plant_id" aria-invalid="{{ $errors->has('plant_id') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('plant_id') ? 'error-plant_id' : '' }}"
                        id="plant_id"
                        required
                        class="w-full px-3.5 py-2.5 rounded-[2px] bg-canvas border {{ $errors->has('plant_id') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors cursor-pointer"
                    >
                        @foreach ($plants as $p)
                            <option value="{{ $p->id }}" {{ (string)old('plant_id', $monitoring->plant_id) === (string)$p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->location ?? 'Greenhouse' }})
                            </option>
                        @endforeach
                    </select>
                    @error('plant_id')
                        <p id="error-plant_id" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Waktu Monitoring -->
                <div>
                    <label for="monitored_at" class="block text-xs font-semibold text-ink mb-1.5">
                        Waktu Observasi <span class="text-danger">*</span>
                    </label>
                    <input
                        type="datetime-local"
                        name="monitored_at" aria-invalid="{{ $errors->has('monitored_at') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('monitored_at') ? 'error-monitored_at' : '' }}"
                        id="monitored_at"
                        value="{{ old('monitored_at', $monitoring->monitored_at ? $monitoring->monitored_at->format('Y-m-d\TH:i') : '') }}"
                        required
                        class="w-full px-3.5 py-2.5 rounded-[2px] bg-canvas border {{ $errors->has('monitored_at') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
                    >
                    @error('monitored_at')
                        <p id="error-monitored_at" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- SECTION 2: Parameter Mikroklimat & Sensor -->
        <div class="space-y-4 pt-2">
            <div class="border-b border-line pb-2">
                <h3 class="text-sm font-semibold text-accent flex items-center gap-2">
                    <span class="w-2 h-2 rounded-[2px] bg-accent-soft"></span>
                    2. Parameter Mikroklimat Greenhouse
                </h3>
                <p class="text-xs text-muted">Pengukuran pembacaan suhu udara, pH larutan nutrisi, dan kelembapan relatif.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                <!-- Suhu (°C) -->
                <div>
                    <label for="temperature" class="block text-xs font-semibold text-ink mb-1.5">
                        Suhu Udara (°C) <span class="text-danger">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="temperature" aria-invalid="{{ $errors->has('temperature') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('temperature') ? 'error-temperature' : '' }}"
                            id="temperature"
                            step="0.01"
                            value="{{ old('temperature', $monitoring->temperature) }}"
                            placeholder="Contoh: 27.50"
                            required
                            class="w-full px-3.5 py-2.5 pr-10 rounded-[2px] bg-canvas border {{ $errors->has('temperature') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors tabular-nums"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted text-xs">
                            °C
                        </span>
                    </div>
                    <span class="text-xs text-muted mt-1 block">Optimal melon: 20°C &ndash; 32°C</span>
                    @error('temperature')
                        <p id="error-temperature" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- pH -->
                <div>
                    <label for="ph" class="block text-xs font-semibold text-ink mb-1.5">
                        pH Nutrisi / Media <span class="text-danger">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="ph" aria-invalid="{{ $errors->has('ph') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('ph') ? 'error-ph' : '' }}"
                            id="ph"
                            step="0.01"
                            value="{{ old('ph', $monitoring->ph) }}"
                            placeholder="Contoh: 6.50"
                            required
                            class="w-full px-3.5 py-2.5 pr-10 rounded-[2px] bg-canvas border {{ $errors->has('ph') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors tabular-nums"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted text-xs">
                            pH
                        </span>
                    </div>
                    <span class="text-xs text-muted mt-1 block">Optimal melon: 5.80 &ndash; 7.20</span>
                    @error('ph')
                        <p id="error-ph" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kelembapan (%) -->
                <div>
                    <label for="humidity" class="block text-xs font-semibold text-ink mb-1.5">
                        Kelembapan Udara (%) <span class="text-danger">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="humidity" aria-invalid="{{ $errors->has('humidity') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('humidity') ? 'error-humidity' : '' }}"
                            id="humidity"
                            step="0.01"
                            value="{{ old('humidity', $monitoring->humidity) }}"
                            placeholder="Contoh: 75.00"
                            required
                            class="w-full px-3.5 py-2.5 pr-10 rounded-[2px] bg-canvas border {{ $errors->has('humidity') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors tabular-nums"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted text-xs">
                            %
                        </span>
                    </div>
                    <span class="text-xs text-muted mt-1 block">Optimal melon: 60% &ndash; 85%</span>
                    @error('humidity')
                        <p id="error-humidity" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        <!-- SECTION 3: Parameter Sensor IoT & Fertigasi (Opsional) -->
        <div class="space-y-4 pt-2">
            <div class="border-b border-line pb-2">
                <h3 class="text-sm font-semibold text-muted flex items-center gap-2">
                    <span class="w-2 h-2 rounded-[2px] bg-surface"></span>
                    3. Parameter Sensor IoT &amp; Fertigasi (Opsional)
                </h3>
                <p class="text-xs text-muted">Pengukuran sensor larutan nutrisi, media tanam, intensitas radiasi cahaya, dan cadangan tandon.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-4">

                <!-- Suhu Nutrisi (°C) -->
                <div>
                    <label for="nutrient_temperature" class="block text-xs font-semibold text-ink mb-1.5">
                        Suhu Nutrisi (°C)
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="nutrient_temperature" aria-invalid="{{ $errors->has('nutrient_temperature') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('nutrient_temperature') ? 'error-nutrient_temperature' : '' }}"
                            id="nutrient_temperature"
                            step="0.01"
                            value="{{ old('nutrient_temperature', $monitoring->nutrient_temperature) }}"
                            placeholder="Misal: 24.50"
                            class="w-full px-3.5 py-2.5 pr-10 rounded-[2px] bg-canvas border {{ $errors->has('nutrient_temperature') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors tabular-nums"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted text-xs">
                            °C
                        </span>
                    </div>
                    <span class="text-xs text-muted mt-1 block">Acuan: 20°C &ndash; 26°C</span>
                    @error('nutrient_temperature')
                        <p id="error-nutrient_temperature" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- EC Nutrisi (mS/cm) -->
                <div>
                    <label for="ec" class="block text-xs font-semibold text-ink mb-1.5">
                        EC Nutrisi (mS/cm)
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="ec" aria-invalid="{{ $errors->has('ec') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('ec') ? 'error-ec' : '' }}"
                            id="ec"
                            step="0.01"
                            value="{{ old('ec', $monitoring->ec) }}"
                            placeholder="Misal: 2.20"
                            class="w-full px-3.5 py-2.5 pr-12 rounded-[2px] bg-canvas border {{ $errors->has('ec') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors tabular-nums"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-muted text-xs">
                            mS/cm
                        </span>
                    </div>
                    <span class="text-xs text-muted mt-1 block">Acuan: 1.80 &ndash; 2.80</span>
                    @error('ec')
                        <p id="error-ec" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kelembapan Tanah (%) -->
                <div>
                    <label for="soil_moisture" class="block text-xs font-semibold text-ink mb-1.5">
                        Kelembapan Tanah (%)
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="soil_moisture" aria-invalid="{{ $errors->has('soil_moisture') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('soil_moisture') ? 'error-soil_moisture' : '' }}"
                            id="soil_moisture"
                            step="0.01"
                            value="{{ old('soil_moisture', $monitoring->soil_moisture) }}"
                            placeholder="Misal: 70.00"
                            class="w-full px-3.5 py-2.5 pr-10 rounded-[2px] bg-canvas border {{ $errors->has('soil_moisture') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors tabular-nums"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted text-xs">
                            %
                        </span>
                    </div>
                    <span class="text-xs text-muted mt-1 block">Acuan: 60% &ndash; 80%</span>
                    @error('soil_moisture')
                        <p id="error-soil_moisture" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Intensitas Cahaya (lux) -->
                <div>
                    <label for="light_intensity" class="block text-xs font-semibold text-ink mb-1.5">
                        Intensitas Cahaya (lux)
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="light_intensity" aria-invalid="{{ $errors->has('light_intensity') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('light_intensity') ? 'error-light_intensity' : '' }}"
                            id="light_intensity"
                            step="0.1"
                            value="{{ old('light_intensity', $monitoring->light_intensity) }}"
                            placeholder="Misal: 35000"
                            class="w-full px-3.5 py-2.5 pr-10 rounded-[2px] bg-canvas border {{ $errors->has('light_intensity') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors tabular-nums"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-muted text-xs">
                            lux
                        </span>
                    </div>
                    <span class="text-xs text-muted mt-1 block">Acuan: 15k &ndash; 50k lux</span>
                    @error('light_intensity')
                        <p id="error-light_intensity" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Level Tandon Nutrisi (%) -->
                <div>
                    <label for="tank_level" class="block text-xs font-semibold text-ink mb-1.5">
                        Level Tandon (%)
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="tank_level" aria-invalid="{{ $errors->has('tank_level') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('tank_level') ? 'error-tank_level' : '' }}"
                            id="tank_level"
                            step="0.01"
                            value="{{ old('tank_level', $monitoring->tank_level) }}"
                            placeholder="Misal: 85.00"
                            class="w-full px-3.5 py-2.5 pr-10 rounded-[2px] bg-canvas border {{ $errors->has('tank_level') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors tabular-nums"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted text-xs">
                            %
                        </span>
                    </div>
                    <span class="text-xs text-muted mt-1 block">Cadangan: &gt; 40%</span>
                    @error('tank_level')
                        <p id="error-tank_level" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        <!-- SECTION 4: Parameter Morfologi & Pertumbuhan -->
        <div class="space-y-4 pt-2">
            <div class="border-b border-line pb-2">
                <h3 class="text-sm font-semibold text-muted flex items-center gap-2">
                    <span class="w-2 h-2 rounded-[2px] bg-surface"></span>
                    4. Pertumbuhan Morfologi Tanaman (Opsional)
                </h3>
                <p class="text-xs text-muted">Ukuran pertumbuhan tinggi tajuk sulur dan persentase kerapatan kanopi daun.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <!-- Tinggi Tanaman -->
                <div>
                    <label for="plant_height" class="block text-xs font-semibold text-ink mb-1.5">
                        Tinggi Tanaman (cm)
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="plant_height" aria-invalid="{{ $errors->has('plant_height') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('plant_height') ? 'error-plant_height' : '' }}"
                            id="plant_height"
                            step="0.1"
                            value="{{ old('plant_height', $monitoring->plant_height) }}"
                            placeholder="Contoh: 45.0"
                            class="w-full px-3.5 py-2.5 pr-10 rounded-[2px] bg-canvas border {{ $errors->has('plant_height') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors tabular-nums"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted text-xs">
                            cm
                        </span>
                    </div>
                    @error('plant_height')
                        <p id="error-plant_height" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kerapatan Kanopi -->
                <div>
                    <label for="plant_density" class="block text-xs font-semibold text-ink mb-1.5">
                        Kerapatan Kanopi Daun (%)
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="plant_density" aria-invalid="{{ $errors->has('plant_density') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('plant_density') ? 'error-plant_density' : '' }}"
                            id="plant_density"
                            step="0.1"
                            value="{{ old('plant_density', $monitoring->plant_density) }}"
                            placeholder="Contoh: 70.0"
                            class="w-full px-3.5 py-2.5 pr-10 rounded-[2px] bg-canvas border {{ $errors->has('plant_density') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors tabular-nums"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted text-xs">
                            %
                        </span>
                    </div>
                    @error('plant_density')
                        <p id="error-plant_density" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        <!-- SECTION 5: Kondisi Visual & Warna -->
        <div class="space-y-4 pt-2">
            <div class="border-b border-line pb-2">
                <h3 class="text-sm font-semibold text-accent flex items-center gap-2">
                    <span class="w-2 h-2 rounded-[2px] bg-accent-soft"></span>
                    5. Evaluasi Visual Lapangan
                </h3>
                <p class="text-xs text-muted">Kondisi kesehatan umum fisik melon dan kenampakan visual warna daun.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <!-- Kondisi Tanaman -->
                <div>
                    <label for="plant_condition" class="block text-xs font-semibold text-ink mb-1.5">
                        Kondisi Tanaman <span class="text-danger">*</span>
                    </label>
                    <select
                        name="plant_condition" aria-invalid="{{ $errors->has('plant_condition') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('plant_condition') ? 'error-plant_condition' : '' }}"
                        id="plant_condition"
                        required
                        class="w-full px-3.5 py-2.5 rounded-[2px] bg-canvas border {{ $errors->has('plant_condition') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors cursor-pointer"
                    >
                        <option value="">-- Pilih Kondisi Fisik --</option>
                        <option value="Subur & Baik" {{ old('plant_condition', $monitoring->plant_condition) === 'Subur & Baik' ? 'selected' : '' }}>Subur &amp; Baik (Optimal)</option>
                        <option value="Baik" {{ old('plant_condition', $monitoring->plant_condition) === 'Baik' ? 'selected' : '' }}>Baik</option>
                        <option value="Perlu Perhatian" {{ old('plant_condition', $monitoring->plant_condition) === 'Perlu Perhatian' ? 'selected' : '' }}>Perlu Perhatian (Warning)</option>
                        <option value="Kurang Baik / Kritis" {{ old('plant_condition', $monitoring->plant_condition) === 'Kurang Baik / Kritis' ? 'selected' : '' }}>Kurang Baik / Kritis</option>
                    </select>
                    @error('plant_condition')
                        <p id="error-plant_condition" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Warna Daun -->
                <div>
                    <label for="plant_color" class="block text-xs font-semibold text-ink mb-1.5">
                        Warna Daun / Tajuk <span class="text-danger">*</span>
                    </label>
                    <select
                        name="plant_color" aria-invalid="{{ $errors->has('plant_color') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('plant_color') ? 'error-plant_color' : '' }}"
                        id="plant_color"
                        required
                        class="w-full px-3.5 py-2.5 rounded-[2px] bg-canvas border {{ $errors->has('plant_color') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors cursor-pointer"
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
                        <p id="error-plant_color" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        <!-- SECTION 5: Catatan Observasi Lapangan -->
        <div class="space-y-2 pt-2">
            <label for="notes" class="block text-xs font-semibold text-ink">
                Catatan Observasi Tambahan
            </label>
            <textarea
                name="notes" aria-invalid="{{ $errors->has('notes') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('notes') ? 'error-notes' : '' }}"
                id="notes"
                rows="3"
                placeholder="Tuliskan catatan perbaikan atau observasi..."
                class="w-full px-3.5 py-2.5 rounded-[2px] bg-canvas border {{ $errors->has('notes') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
            >{{ old('notes', $monitoring->notes) }}</textarea>
            @error('notes')
                <p id="error-notes" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Bar Tombol Aksi Simpan -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-line">
            <a
                href="{{ route('monitorings.index') }}"
                class="px-4 py-2.5 rounded-[2px] bg-surface hover:bg-surface text-ink text-xs font-semibold transition-colors"
            >
                Batal
            </a>

            <button
                type="submit"
                class="ui-button-primary inline-flex items-center gap-2 px-5 py-2.5 rounded-[2px] text-current text-xs font-semibold transition-all transform"
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
