<?php

namespace App\Http\Middleware;

use App\Models\IotDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateInternalApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');
        if ($user) {
            if (!$user->isAdmin() && !$user->isPengamat()) {
                return response()->json(['success' => false, 'message' => 'Role tidak diizinkan membaca data internal.'], 403);
            }

            return $next($request);
        }
        // Device tokens grant ingestion access, never human read access.
        $token = $request->header('X-Device-Token') ?: $request->bearerToken();
        if ($token && IotDevice::verifyToken($token)) {
            return response()->json(['success' => false, 'message' => 'Token perangkat tidak memiliki izin membaca data internal.'], 403);
        }

        return response()->json(['success' => false, 'message' => 'Autentikasi pengguna diperlukan.'], 401);
    }
}
