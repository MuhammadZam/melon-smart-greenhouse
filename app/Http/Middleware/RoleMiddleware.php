<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Pastikan user sudah login
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        }

        $user = Auth::user();

        // 2. Jika tidak ada spesifikasi role, loloskan
        if (empty($roles)) {
            return $next($request);
        }

        // 3. Cek apakah role user ada di daftar role yang diizinkan
        if (!in_array($user->role, $roles, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Akses ditolak: Anda tidak memiliki wewenang (role) untuk mengakses fitur ini.');
        }

        return $next($request);
    }
}
