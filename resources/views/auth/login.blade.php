@extends('layouts.auth')

@section('title', 'Masuk ke Sistem')

@section('content')
<div class="w-full max-w-md mx-auto" x-data="{
    fillCredentials(email, pass) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = pass;
    }
}">
    <!-- Card Container -->
    <div class="bg-canvas border border-line rounded-[2px] p-7 sm:p-9">

        <!-- Header Card -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-[2px] border border-line text-accent mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="ui-page-title text-2xl font-semibold text-current tracking-tight">Selamat Datang</h2>
            <p class="text-xs sm:text-sm text-muted mt-1.5">Masuk untuk mengakses dashboard monitoring Melon</p>
        </div>

        <!-- Flash Notification: Success -->
        @if (session('success'))
            <div class="mb-6 p-4 rounded-[2px] bg-accent-soft border border-line flex items-start gap-3 text-accent text-xs sm:text-sm">
                <svg class="w-5 h-5 text-accent shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Flash Notification: Error -->
        @if (session('error'))
            <div class="mb-6 p-4 rounded-[2px] bg-danger-soft border border-danger/25 flex items-start gap-3 text-danger text-xs sm:text-sm">
                <svg class="w-5 h-5 text-danger shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Form Login -->
        <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
            @csrf

            <!-- Field: Email -->
            <div>
                <label for="email" class="block text-xs font-semibold text-ink mb-2">
                    Alamat Email
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-muted">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"></path>
                        </svg>
                    </div>
                    <input
                        type="email"
                        name="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('email') ? 'error-email' : '' }}"
                        id="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="nama@melon.com"
                        class="w-full pl-10 pr-4 py-2.5 rounded-[2px] bg-canvas border @error('email') border-danger/25 focus:ring-accent @else border-line focus:border-line focus:ring-accent @enderror text-current placeholder-muted text-sm focus:outline-none focus:ring-2 focus:ring-offset-0 transition-colors"
                    >
                </div>
                @error('email')
                    <p id="error-email" role="alert" class="mt-1.5 text-xs text-danger flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Field: Password -->
            <div>
                <label for="password" class="block text-xs font-semibold text-ink mb-2">
                    Kata Sandi (Password)
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-muted">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input
                        type="password"
                        name="password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('password') ? 'error-password' : '' }}"
                        id="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full pl-10 pr-4 py-2.5 rounded-[2px] bg-canvas border @error('password') border-danger/25 focus:ring-accent @else border-line focus:border-line focus:ring-accent @enderror text-current placeholder-muted text-sm focus:outline-none focus:ring-2 focus:ring-offset-0 transition-colors"
                    >
                </div>
                @error('password')
                    <p id="error-password" role="alert" class="mt-1.5 text-xs text-danger flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between text-xs text-muted pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="remember"
                        id="remember"
                        {{ old('remember') ? 'checked' : '' }}
                        class="w-4 h-4 rounded-[2px] bg-canvas border-line text-accent focus:ring-accent focus:ring-offset-0"
                    >
                    <span>Ingat sesi saya</span>
                </label>
                <span class="text-muted">Akses akun terdaftar</span>
            </div>

            <!-- Tombol Masuk -->
            <button
                type="submit"
                class="ui-button-primary w-full py-3 px-4 rounded-[4px] font-medium text-sm focus-visible:outline transition-colors duration-150 flex items-center justify-center gap-2"
            >
                <span>Masuk ke Dashboard</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            </button>
        </form>

        <!-- Divider -->
        <div class="relative my-7">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-line"></div>
            </div>
            <div class="relative flex justify-center text-xs">
                <span class="bg-canvas px-3 text-muted font-medium">Akun demo lokal</span>
            </div>
        </div>

        <!-- Quick Demo Accounts -->
        <div class="space-y-2.5">
            <button
                type="button"
                @click="fillCredentials('admin@melon.com', 'password123')"
                class="w-full p-3 rounded-[2px] bg-canvas hover:bg-surface border border-line hover:border-line text-left flex items-center justify-between group transition-all"
            >
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-[2px] bg-accent-soft border border-line text-accent flex items-center justify-center font-semibold text-xs">
                        AD
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-current group-hover:text-accent transition-colors">Role: Administrator</div>
                        <div class="text-xs text-muted tabular-nums">admin@melon.com</div>
                    </div>
                </div>
                <span class="text-xs text-accent group-hover:text-accent font-medium flex items-center gap-1">
                    Gunakan
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </span>
            </button>

            <button
                type="button"
                @click="fillCredentials('pengamat@melon.com', 'password123')"
                class="w-full p-3 rounded-[2px] bg-canvas hover:bg-surface border border-line hover:border-line text-left flex items-center justify-between group transition-all"
            >
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-[2px] bg-accent-soft border border-line text-accent flex items-center justify-center font-semibold text-xs">
                        US
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-current group-hover:text-accent transition-colors">Role: User / Pengamat</div>
                        <div class="text-xs text-muted tabular-nums">pengamat@melon.com</div>
                    </div>
                </div>
                <span class="text-xs text-accent group-hover:text-accent font-medium flex items-center gap-1">
                    Gunakan
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </span>
            </button>
        </div>
    </div>
</div>
@endsection
