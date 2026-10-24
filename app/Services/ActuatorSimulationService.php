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
 * Bertanggung jawab atas pengelolaan simulasi kontrol aktuator (Pompa Irigasi & Kipas Exhaust).
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
        // 1. Validasi Hak Akses (Khusus Administrator)
        if ($user && $user->role !== 'admin') {
            Log::warning('[ActuatorSimulationService] Akses ditolak: Pengguna bukan admin', [
                'user_id' => $user->id,
                'role'    => $user->role,
                'command' => $command,
            ]);
            throw new HttpException(403, 'Akses ditolak: Hanya Administrator yang berwenang mengubah status simulasi aktuator.');
        }

        // 2. Normalisasi & Validasi Perintah
        $normalizedCommand = strtoupper(trim($command));
        if (!in_array($normalizedCommand, self::VALID_COMMANDS, true)) {
            throw ValidationException::withMessages([
                'command' => "Perintah '{$command}' tidak valid. Pilihan yang diizinkan: " . implode(', ', self::VALID_COMMANDS),
            ]);
        }

        $actuatorId = $actuator instanceof Actuator ? $actuator->id : $actuator;

        // 3. Validasi Keberadaan dan Status Aktif Perangkat
        $preCheck = Actuator::find($actuatorId);
        if (!$preCheck) {
            throw new HttpException(404, 'Perangkat aktuator tidak ditemukan.');
        }

        if (!$preCheck->is_enabled) {
            // Catat log penolakan (Audit trail tetap tersimpan)
            ActuatorCommandLog::create([
                'actuator_id'      => $preCheck->id,
                'user_id'          => $user?->id,
                'command'          => $normalizedCommand,
                'previous_state'   => $preCheck->simulation_state,
                'resulting_state'  => $preCheck->simulation_state,
                'execution_status' => ActuatorCommandLog::STATUS_REJECTED,
                'source'           => $source,
                'executed_at'      => now(),
                'notes'            => 'Perintah ditolak karena perangkat dalam kondisi non-aktif (disabled).',
            ]);

            throw new HttpException(422, "Perangkat '{$preCheck->name}' sedang dinonaktifkan dan tidak dapat menerima perintah.");
        }

        // 4. Eksekusi dalam Transaksi Database dengan Pesimistic Lock
        return DB::transaction(function () use ($actuatorId, $normalizedCommand, $user, $source, $notes) {
            /** @var Actuator $targetActuator */
            $targetActuator = Actuator::where('id', $actuatorId)
                ->lockForUpdate()
                ->firstOrFail();

            $previousState = $targetActuator->simulation_state;

            // Hitung target state
            $targetState = match ($normalizedCommand) {
                ActuatorCommandLog::COMMAND_TURN_ON  => Actuator::STATE_ON,
                ActuatorCommandLog::COMMAND_TURN_OFF => Actuator::STATE_OFF,
                ActuatorCommandLog::COMMAND_TOGGLE   => $previousState === Actuator::STATE_ON ? Actuator::STATE_OFF : Actuator::STATE_ON,
            };

            // 4. Penanganan Idempotensi
            $isIdempotent = ($previousState === $targetState);
            $finalNotes = $notes;

            if ($isIdempotent) {
                $idempotentNote = "Permintaan idempotent: status simulasi perangkat sudah berada pada kondisi {$targetState}.";
                $finalNotes = $notes ? "{$notes} | {$idempotentNote}" : $idempotentNote;
            } else {
                // Perbarui state aktuator
                $targetActuator->update([
                    'simulation_state' => $targetState,
                    'last_changed_at'  => now(),
                    'last_user_id'     => $user?->id,
                ]);
            }

            // 5. Catat Command Log (Audit Trail)
            $log = ActuatorCommandLog::create([
                'actuator_id'      => $targetActuator->id,
                'user_id'          => $user?->id,
                'command'          => $normalizedCommand,
                'previous_state'   => $previousState,
                'resulting_state'  => $targetState,
                'execution_status' => ActuatorCommandLog::STATUS_SUCCESS,
                'source'           => $source,
                'executed_at'      => now(),
                'notes'            => $finalNotes,
            ]);

            Log::info('[ActuatorSimulationService] Perintah simulasi berhasil dieksekusi', [
                'actuator_id'     => $targetActuator->id,
                'actuator_code'   => $targetActuator->code,
                'command'         => $normalizedCommand,
                'previous_state'  => $previousState,
                'resulting_state' => $targetState,
                'is_idempotent'   => $isIdempotent,
                'user_id'         => $user?->id,
            ]);

            return [
                'success'             => true,
                'is_idempotent'       => $isIdempotent,
                'message'             => $isIdempotent
                    ? "Perangkat {$targetActuator->name} sudah dalam kondisi {$targetState}."
                    : "Status simulasi {$targetActuator->name} berhasil diubah menjadi {$targetState}.",
                'actuator'            => $targetActuator->fresh(['lastUser']),
                'command_log'         => $log,
                'previous_state'      => $previousState,
                'resulting_state'     => $targetState,
                'mode'                => Actuator::MODE_SIMULATION,
                'hardware_dispatched' => false, // Jaminan eksplisit: tidak ada dispatch hardware
            ];
        });
    }

    /**
     * Ambil seluruh daftar aktuator beserta relasi operator terakhir.
     *
     * @return Collection<int, Actuator>
     */
    public function getAllActuators(): Collection
    {
        return Actuator::with('lastUser')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Ambil ringkasan aktuator untuk widget dashboard.
     *
     * @return array{pump: Actuator|null, fan: Actuator|null, last_updated: \Illuminate\Support\Carbon|null}
     */
    public function getDashboardSummary(): array
    {
        $pump = Actuator::with('lastUser')->pumps()->first();
        $fan  = Actuator::with('lastUser')->fans()->first();

        $lastUpdated = collect([$pump?->last_changed_at, $fan?->last_changed_at])
            ->filter()
            ->sortDesc()
            ->first();

        return [
            'pump'         => $pump,
            'fan'          => $fan,
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
        return ActuatorCommandLog::with(['actuator', 'user'])
            ->filter($filters)
            ->orderByDesc('executed_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
