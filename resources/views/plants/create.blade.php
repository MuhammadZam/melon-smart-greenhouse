@extends('layouts.app')

@section('title', 'Tambah Data Tanaman Melon')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header & Breadcrumbs -->
    <div class="ui-page-header flex flex-col sm:flex-row sm:items-center justify-between gap-4">
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
                <a href="{{ route('plants.index') }}" class="hover:text-accent transition-colors">
                    Data Tanaman
                </a>
                <span>/</span>
                <span class="text-accent font-medium">Tambah Data</span>
            </div>

            <div class="flex items-center gap-2.5">
                <h2 class="ui-page-title text-xl sm:text-2xl font-semibold text-current tracking-tight">
                    Tambah Plot <span class="text-ink">Tanaman Melon</span>
                </h2>
                <span class="px-2 py-0.5 rounded-[2px] text-xs tabular-nums font-semibold bg-accent-soft text-accent border border-line">
                    MODE ADMIN
                </span>
            </div>
            <p class="text-xs sm:text-sm text-muted mt-1">
                Daftarkan plot budidaya tanaman melon baru ke dalam sistem pemantauan.
            </p>
        </div>

        <a
            href="{{ route('plants.index') }}"
            class="px-3.5 py-2 rounded-[2px] bg-surface hover:bg-surface text-ink hover:text-ink text-xs font-semibold transition-colors flex items-center gap-2 border border-line self-start sm:self-auto"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Kembali</span>
        </a>
    </div>

    <!-- Alert Kesalahan Validasi -->
    @if ($errors->any())
        <div role="alert" class="p-4 rounded-[2px] bg-danger-soft border border-danger/25 text-danger text-xs space-y-1">
            <div class="font-semibold flex items-center gap-2 text-danger">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Terdapat kesalahan pengisian data tanaman:
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-xs pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Tambah Tanaman -->
    <form method="POST" action="{{ route('plants.store') }}" class="p-5 sm:p-7 rounded-[2px] bg-canvas border border-line space-y-5">
        @csrf

        <!-- 1. Nama Tanaman / Plot -->
        <div>
            <label for="name" class="block text-xs font-semibold text-ink mb-1.5">
                Nama Tanaman / Plot Melon <span class="text-danger">*</span>
            </label>
            <input
                type="text"
                name="name" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('name') ? 'error-name' : '' }}"
                id="name"
                value="{{ old('name') }}"
                placeholder="Contoh: Melon Golden Aroma - Greenhouse D"
                required
                class="w-full px-3.5 py-2.5 rounded-[2px] bg-canvas border {{ $errors->has('name') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
            >
            @error('name')
                <p id="error-name" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- 2. Lokasi Greenhouse & Status -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <!-- Lokasi -->
            <div>
                <label for="location" class="block text-xs font-semibold text-ink mb-1.5">
                    Lokasi Greenhouse / Sektor <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    name="location" aria-invalid="{{ $errors->has('location') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('location') ? 'error-location' : '' }}"
                    id="location"
                    value="{{ old('location') }}"
                    placeholder="Contoh: Greenhouse 2 - Sektor Barat"
                    required
                    class="w-full px-3.5 py-2.5 rounded-[2px] bg-canvas border {{ $errors->has('location') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
                >
                @error('location')
                    <p id="error-location" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status Budidaya -->
            <div>
                <label for="status" class="block text-xs font-semibold text-ink mb-1.5">
                    Status Budidaya Tanaman <span class="text-danger">*</span>
                </label>
                <select
                    name="status" aria-invalid="{{ $errors->has('status') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('status') ? 'error-status' : '' }}"
                    id="status"
                    required
                    class="w-full px-3.5 py-2.5 rounded-[2px] bg-canvas border {{ $errors->has('status') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs focus:outline-none focus:ring-2 focus:ring-accent transition-colors cursor-pointer"
                >
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Aktif (Dalam Pemeliharaan)</option>
                    <option value="harvested" {{ old('status') === 'harvested' ? 'selected' : '' }}>Sudah Dipanen</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Tidak Aktif (Selesai/Rehat)</option>
                </select>
                @error('status')
                    <p id="error-status" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

        </div>

        <!-- 3. Deskripsi Plot -->
        <div>
            <label for="description" class="block text-xs font-semibold text-ink mb-1.5">
                Deskripsi Plot Budidaya (Opsional)
            </label>
            <textarea
                name="description" aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('description') ? 'error-description' : '' }}"
                id="description"
                rows="3"
                placeholder="Tuliskan keterangan varietas melon, jadwal semai/tanam, atau metode fertigasi yang diterapkan..."
                class="w-full px-3.5 py-2.5 rounded-[2px] bg-canvas border {{ $errors->has('description') ? 'border-danger/25 ring-1 ring-accent' : 'border-line' }} text-current text-xs placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
            >{{ old('description') }}</textarea>
            @error('description')
                <p id="error-description" role="alert" class="text-danger text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Tombol Aksi Simpan -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-line">
            <a
                href="{{ route('plants.index') }}"
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
                <span>Simpan Data Tanaman</span>
            </button>
        </div>

    </form>

</div>
@endsection
