<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Menampilkan form login aplikasi.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $demoEmails = collect();
        if (app()->environment('local')) {
            $demoEmails = \App\Models\User::whereIn('email', [
                'admin@melon.com', 'admin@azolla.com',
                'pengamat@melon.com', 'pengamat@azolla.com',
            ])->get()->filter(fn ($user) => \Illuminate\Support\Facades\Hash::check('password123', $user->password))
                ->pluck('email', 'role');
        }

        return view('auth.login', [
            'demoAdminEmail' => $demoEmails->get('admin'),
            'demoObserverEmail' => $demoEmails->get('user'),
        ]);
    }

    /**
     * Memproses autentikasi pengguna.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();
            $roleLabel = $user->isAdmin() ? 'Administrator' : 'Pengamat Lapangan';

            return redirect()->intended(route('dashboard'))
                ->with('success', "Selamat datang kembali, {$user->name}! Anda masuk sebagai {$roleLabel}.");
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => 'Kombinasi alamat email atau kata sandi yang Anda masukkan salah.',
            ]);
    }

    /**
     * Memproses logout pengguna dan menginvalasi sesi.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil keluar dari sistem monitoring Melon.');
    }
}
