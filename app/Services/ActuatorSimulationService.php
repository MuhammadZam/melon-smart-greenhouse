<?php

namespace App\Services;

use App\Models\Actuator;
use App\Models\ActuatorCommandLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * ActuatorSimulationService — Phase 13
 *
 * Bertanggung jawab atas pengelolaan simulasi kontrol aktuator (Pompa air PUMP-01).
 *
 * Prinsip Desain & Batasan:
 *  1. MODE SIMULASI PENUH: Kontrol hanya mengubah state di database aplikasi (simulation_state).
 *  2. TIDAK ADA KOMUNIKASI HARDWARE: Tidak ada HTTP request ke ESP32, MQTT publish, WebSocket,
 *     atau manipulasi relay fisik.
 *  3. IDEMPOTENSI: Perintah berulang yang menghasilkan state yang sama ditangani secara aman
 *     tanpa melempar exception, namun tetap dicatat dalam audit trail.
 *  4. CONCURRENCY SAFETY: Menggunakan DB transaction dan lockForUpdate() untuk mencegah race condition.
 *  5. AUDIT TRAIL LENGKAP: Setiap perintah (sukses/gagal/idempotent) dicatat dalam actuator_command_logs.
 */
class ActuatorSimulationService
{
    /**
     * Daftar perintah yang diizinkan.
     */
    public const VALID_COMMANDS = [
        ActuatorCommandLog::COMMAND_TURN_ON,
        ActuatorCommandLog::COMMAND_TURN_OFF,
        ActuatorCommandLog::COMMAND_TOGGLE,
    ];

    /**
     * Eksekusi perintah kontrol simulasi pada aktuator.
     *
     * @param  Actuator|int  $actuator
     * @param  string        $command   'TURN_ON', 'TURN_OFF', atau 'TOGGLE'
     * @param  User|null     $user      Operator pengeksekusi
     * @param  string        $source    Sumber trigger perintah
     * @param  string|null   $notes     Catatan tambahan
     * @return array
     *
     * @throws HttpException
     * @throws ValidationException
     * @throws \Throwable
     */
    public function executeCommand(
        Actuator|int $actuator,
        string $command,
        ?User $user = null,
        string $source = ActuatorCommandLog::SOURCE_WEB_SIMULATION,
        ?string $notes = null
    ): array {
        $result = app(\App\Services\Pump\PumpControlService::class)->manualCommand($actuator, $command, $user, $source, $notes);
        if (!$result['success']) throw new HttpException(422, $result['message']);
        return $result;
    }

    /**
     * Ambil seluruh daftar aktuator beserta relasi operator terakhir.
     *
     * @return Collection<int, Actuator>
     */
    public function getAllActuators(): Collection
    {
        return Actuator::where('code', 'PUMP-01')->with('lastUser')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Ambil ringkasan aktuator untuk widget dashboard.
     *
     * @return array{pump: Actuator|null, last_updated: \Illuminate\Support\Carbon|null}
     */
    public function getDashboardSummary(): array
    {
        $pump = Actuator::where('code', 'PUMP-01')->with('lastUser')->first();

        $lastUpdated = collect([$pump?->last_changed_at])
            ->filter()
            ->sortDesc()
            ->first();

        return [
            'pump'         => $pump,
            'last_updated' => $lastUpdated,
        ];
    }

    /**
     * Ambil riwayat perintah aktuator dengan filter dan pagination.
     *
     * @param  array  $filters
     * @param  int    $perPage
     * @return LengthAwarePaginator
     */
    public function getHistory(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return ActuatorCommandLog::whereHas('actuator', fn ($q) => $q->where('code', 'PUMP-01'))->with(['actuator', 'user'])
            ->filter($filters)
            ->orderByDesc('executed_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
