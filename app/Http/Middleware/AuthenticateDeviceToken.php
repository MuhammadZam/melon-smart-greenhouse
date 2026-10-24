<?php

namespace App\Http\Middleware;

use App\Models\IotDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDeviceToken
{
    /**
     * Handle an incoming request for IoT device telemetry ingestion.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Ekstraksi Token dari Header HTTP
        $token = $request->header('X-Device-Token');

        if (!$token && $request->bearerToken()) {
            $token = $request->bearerToken();
        }

        // 2. Jika Token Disediakan, Validasi Hash Token Server-side
        if (!empty($token)) {
            $device = IotDevice::verifyToken($token);

            if (!$device) {
                // Token tidak cocok dengan hash terdaftar mana pun
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid device authentication token.',
                    'error_code' => 'DEVICE_TOKEN_INVALID',
                ], 401);
            }

            if ($device->isRevoked() || !$device->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device token has been revoked or is inactive.',
                    'error_code' => 'DEVICE_TOKEN_REVOKED',
                ], 403);
            }

            // Catat heartbeat aktivitas perangkat
            $device->recordHeartbeat($request->ip());

            // Bind device ID ke request
            $request->attributes->set('iot_device', $device);
            if (!$request->has('device_id')) {
                $request->merge(['device_id' => $device->device_id]);
            }

            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Device authentication token required. Telemetry rejected.',
            'error_code' => 'DEVICE_TOKEN_REQUIRED',
        ], 401);
    }
}
