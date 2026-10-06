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
    <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-3xl p-7 sm:p-9 shadow-2xl shadow-emerald-950/20">
        
        <!-- Header Card -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500/20 to-teal-500/10 border border-emerald-500/30 text-emerald-400 mb-4 shadow-inner">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Selamat Datang</h2>
            <p class="text-xs sm:text-sm text-slate-400 mt-1.5">Masuk untuk mengakses dashboard monitoring Melon</p>
        </div>

        <!-- Flash Notification: Success -->
        @if (session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-start gap-3 text-emerald-300 text-xs sm:text-sm">
                <svg class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Flash Notification: Error -->
        @if (session('error'))
            <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 flex items-start gap-3 text-rose-300 text-xs sm:text-sm">
                <svg class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                    Alamat Email
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"></path>
                        </svg>
                    </div>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="nama@melon.com"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950/70 border @error('email') border-rose-500 focus:ring-rose-500 @else border-slate-700/80 focus:border-emerald-500 focus:ring-emerald-500 @enderror text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-offset-0 transition-colors"
                    >
                </div>
                @error('email')
                    <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Field: Password -->
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                    Kata Sandi (Password)
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950/70 border @error('password') border-rose-500 focus:ring-rose-500 @else border-slate-700/80 focus:border-emerald-500 focus:ring-emerald-500 @enderror text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-offset-0 transition-colors"
                    >
                </div>
                @error('password')
                    <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between text-xs text-slate-400 pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="remember"
                        id="remember"
                        {{ old('remember') ? 'checked' : '' }}
                        class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-0"
                    >
                    <span>Ingat sesi saya</span>
                </label>
                <span class="text-slate-500">Tersertifikasi SSL/TLS</span>
            </div>

            <!-- Tombol Masuk -->
            <button
                type="submit"
                class="w-full py-3 px-4 rounded-xl font-semibold text-sm text-slate-950 bg-gradient-to-r from-emerald-400 to-teal-400 hover:from-emerald-300 hover:to-teal-300 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-slate-900 shadow-lg shadow-emerald-500/20 active:scale-[0.99] transition-all duration-150 flex items-center justify-center gap-2"
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
                <div class="w-full border-t border-slate-800"></div>
            </div>
            <div class="relative flex justify-center text-xs uppercase tracking-wider">
                <span class="bg-slate-900/90 px-3 text-slate-500 font-medium">Akun Demo Pengujian (Phase 3)</span>
            </div>
        </div>

        <!-- Quick Demo Accounts -->
        <div class="space-y-2.5">
            <button
                type="button"
                @click="fillCredentials('admin@melon.com', 'password123')"
                class="w-full p-3 rounded-xl bg-slate-950/60 hover:bg-slate-800/80 border border-slate-800 hover:border-emerald-500/40 text-left flex items-center justify-between group transition-all"
            >
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs">
                        AD
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-white group-hover:text-emerald-300 transition-colors">Role: Administrator</div>
                        <div class="text-[11px] text-slate-400 font-mono">admin@melon.com</div>
                    </div>
                </div>
                <span class="text-[11px] text-emerald-400/80 group-hover:text-emerald-300 font-medium flex items-center gap-1">
                    Gunakan
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </span>
            </button>

            <button
                type="button"
                @click="fillCredentials('pengamat@melon.com', 'password123')"
                class="w-full p-3 rounded-xl bg-slate-950/60 hover:bg-slate-800/80 border border-slate-800 hover:border-teal-500/40 text-left flex items-center justify-between group transition-all"
            >
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-teal-500/10 border border-teal-500/20 text-teal-400 flex items-center justify-center font-bold text-xs">
                        US
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-white group-hover:text-teal-300 transition-colors">Role: User / Pengamat</div>
                        <div class="text-[11px] text-slate-400 font-mono">pengamat@melon.com</div>
                    </div>
                </div>
                <span class="text-[11px] text-teal-400/80 group-hover:text-teal-300 font-medium flex items-center gap-1">
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
