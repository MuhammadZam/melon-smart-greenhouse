@extends('layouts.app')

@section('title', 'Edit Tanaman - ' . $plant->name)

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
                <span class="text-warning font-medium">Edit #{{ $plant->id }}</span>
            </div>

            <div class="flex items-center gap-2.5">
                <h2 class="ui-page-title text-xl sm:text-2xl font-semibold text-current tracking-tight">
                    Edit Tanaman <span class="    text-ink">{{ $plant->name }}</span>
                </h2>
                <span class="px-2 py-0.5 rounded-[2px] text-xs tabular-nums font-semibold bg-warning-soft text-warning border border-warning/25">
                    MODE ADMIN
                </span>
            </div>
            <p class="text-xs sm:text-sm text-muted mt-1">
                Perbarui identitas plot, penempatan greenhouse, atau status budidaya melon.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('plants.show', $plant) }}"
                class="px-3.5 py-2 rounded-[2px] bg-surface hover:bg-surface text-ink hover:text-ink text-xs font-semibold transition-colors flex items-center gap-2 border border-line"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <span>Lihat Detail</span>
            </a>

            <a
                href="{{ route('plants.index') }}"
                class="px-3.5 py-2 rounded-[2px] bg-surface hover:bg-surface text-ink hover:text-ink text-xs font-semibold transition-colors flex items-center gap-2 border border-line"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Batal</span>
            </a>
        </div>
    </div>

    <!-- Alert Kesalahan Validasi -->
    @if ($errors->any())
        <div role="alert" class="p-4 rounded-[2px] bg-danger-soft border border-danger/25 text-danger text-xs  space-y-1">
            <div class="font-semibold flex items-center gap-2 text-danger">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Terdapat kesalahan saat memperbarui data tanaman:
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-xs pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Edit Tanaman -->
    <form method="POST" action="{{ route('plants.update', $plant) }}" class="p-5 sm:p-7 rounded-[2px] bg-canvas border border-line  space-y-5">
        @csrf
        @method('PUT')

        <!-- 1. Nama Tanaman / Plot -->
        <div>
            <label for="name" class="block text-xs font-semibold text-ink mb-1.5">
                Nama Tanaman / Plot Melon <span class="text-danger">*</span>
            </label>
            <input
                type="text"
                name="name" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('name') ? 'error-name' : '' }}"
                id="name"
                value="{{ old('name', $plant->name) }}"
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
                    value="{{ old('location', $plant->location) }}"
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
                    <option value="active" {{ old('status', $plant->status) === 'active' ? 'selected' : '' }}>Aktif (Dalam Pemeliharaan)</option>
                    <option value="harvested" {{ old('status', $plant->status) === 'harvested' ? 'selected' : '' }}>Sudah Dipanen</option>
                    <option value="inactive" {{ old('status', $plant->status) === 'inactive' ? 'selected' : '' }}>Tidak Aktif (Selesai/Rehat)</option>
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
            >{{ old('description', $plant->description) }}</textarea>
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
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-[2px] text-current text-xs font-semibold transition-all transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Perbarui Data Tanaman</span>
            </button>
        </div>

    </form>

</div>
@endsection
