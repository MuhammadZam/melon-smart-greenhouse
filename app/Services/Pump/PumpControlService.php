<?php

namespace App\Services\Pump;

use App\Models\{Actuator, ActuatorCommandLog, Monitoring, User};
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Single writer for PUMP-01. All decisions are serialized under its row lock. */
class PumpControlService
{
    public const REASONS = [
        'MANUAL_ON' => 'Pompa dinyalakan oleh administrator.',
        'MANUAL_OFF' => 'Pompa dimatikan oleh administrator.',
        'MODE_MANUAL' => 'Mode manual aktif; pompa OFF sampai ada perintah baru.',
        'WAITING_NEW_READING' => 'AUTO menunggu pembacaan baru setelah diaktifkan atau dihentikan.',
        'SOIL_DRY' => 'Kelembapan tanah mencapai ambang ON provisional.',
        'SOIL_WET' => 'Kelembapan tanah mencapai ambang OFF provisional.',
        'HYSTERESIS_HOLD' => 'Nilai di antara ambang ON/OFF; status dipertahankan.',
        'REST_INTERVAL' => 'Pompa OFF selama interval istirahat minimum.',
        'MAX_RUNTIME' => 'Batas waktu menyala tercapai; pompa dimatikan.',
        'RUNTIME_UNKNOWN' => 'Waktu mulai tidak diketahui; pompa dimatikan.',
        'SENSOR_INVALID' => 'Data sensor kosong atau di luar 0–100%; pompa OFF.',
        'SENSOR_STALE' => 'Data sensor terlalu lama atau bertanggal masa depan; pompa OFF.',
        'NOT_CONFIGURED' => 'Tanaman dan ambang AUTO belum dikonfigurasi dengan benar.',
        'DISABLED' => 'Perangkat nonaktif; pompa OFF.',
        'AUTO_OWNS_CONTROL' => 'Mode AUTO aktif; perintah manual ditolak.',
        'SETTINGS_UPDATED' => 'Konfigurasi diperbarui; pompa OFF dan AUTO menunggu pembacaan baru.',
    ];

    public function __construct(private PumpTransport $transport) {}

    public function ready(): bool
    {
        return Schema::hasColumn('actuators', 'control_mode');
    }

    public function manualCommand(Actuator|int $actuator, string $command, ?User $user, string $source, ?string $notes = null, ?int $expectedVersion = null): array
    {
        $this->authorize($user);
        $command = strtoupper(trim($command));
        if (!in_array($command, ['TURN_ON', 'TURN_OFF', 'TOGGLE'], true)) {
            throw ValidationException::withMessages(['command' => 'Perintah pompa tidak valid.']);
        }
        return $this->locked($actuator, function (Actuator $pump) use ($command, $user, $source, $notes, $expectedVersion) {
            $this->checkVersion($pump, $expectedVersion);
            // Return a rejection result, so its audit record commits before HTTP rejection.
            if (!$pump->is_enabled || $pump->control_mode !== 'MANUAL') {
                $reason = !$pump->is_enabled ? 'DISABLED' : 'AUTO_OWNS_CONTROL';
                return $this->record($pump, $pump->simulation_state, $reason, $command, $source, $user, null, $notes, true);
            }
            $state = match ($command) {
                'TURN_ON' => 'ON', 'TURN_OFF' => 'OFF',
                'TOGGLE' => $pump->isOn() ? 'OFF' : 'ON',
            };
            if ($state === 'ON') {
                if ($pump->isOn() && ($reason = $this->runtimeStop($pump))) {
                    return $this->record($pump, 'OFF', $reason, $command, $source, $user, null, $notes);
                }
                if ($pump->isOff() && $this->resting($pump)) {
                    return $this->record($pump, 'OFF', 'REST_INTERVAL', $command, $source, $user, null, $notes, true);
                }
            }
            return $this->record($pump, $state, $state === 'ON' ? 'MANUAL_ON' : 'MANUAL_OFF', $command, $source, $user, null, $notes);
        });
    }

    public function changeMode(Actuator|int $actuator, string $mode, User $user, ?int $expectedVersion = null): array
    {
        $this->authorize($user);
        if (!in_array($mode, ['MANUAL', 'AUTO'], true)) {
            throw ValidationException::withMessages(['control_mode' => 'Pilih MANUAL atau AUTO.']);
        }
        return $this->locked($actuator, function (Actuator $pump) use ($mode, $user, $expectedVersion) {
            $this->checkVersion($pump, $expectedVersion);
            $previousMode = $pump->control_mode;
            if ($previousMode === $mode) {
                return $this->record($pump, $pump->simulation_state, $pump->control_reason ?? 'MODE_MANUAL', 'SET_MODE', 'web_mode', $user);
            }
            $pump->control_mode = $mode;
            $this->arm($pump);
            // Never carry an ON command across ownership changes.
            return $this->record($pump, 'OFF', $mode === 'AUTO' ? ($this->configured($pump) ? 'WAITING_NEW_READING' : 'NOT_CONFIGURED') : 'MODE_MANUAL', 'SET_MODE', 'web_mode', $user, null, null, false, $previousMode);
        });
    }

    public function configure(Actuator|int $actuator, array $settings, User $user, ?int $expectedVersion = null): array
    {
        $this->authorize($user);
        $settings = validator($settings, [
            'control_plant_id' => ['required', 'integer', 'exists:plants,id'],
            'auto_on_threshold' => ['required', 'numeric', 'between:0,100'],
            'auto_off_threshold' => ['required', 'numeric', 'between:0,100', 'gt:auto_on_threshold'],
            'max_runtime_seconds' => ['required', 'integer', 'between:1,3600'],
            'min_rest_seconds' => ['required', 'integer', 'between:1,3600'],
            'sensor_max_age_seconds' => ['required', 'integer', 'between:1,3600'],
        ])->validate();
        return $this->locked($actuator, function (Actuator $pump) use ($settings, $user, $expectedVersion) {
            $this->checkVersion($pump, $expectedVersion);
            $before = $this->settings($pump);
            $pump->fill($settings);
            $this->arm($pump);
            return $this->record($pump, 'OFF', 'SETTINGS_UPDATED', 'CONFIGURE', 'web_settings', $user, null, json_encode(['before' => $before, 'after' => $this->settings($pump)], JSON_THROW_ON_ERROR));
        });
    }

    public function tick(string $source = 'scheduler'): ?array
    {
        // Allows existing ingestion/read flows to keep working before approved deployment.
        if (!$this->ready()) return null;
        $pump = Actuator::where('code', 'PUMP-01')->first();
        if (!$pump) return null;
        return $this->locked($pump, function (Actuator $pump) use ($source) {
            // Heartbeat is not a control revision; polling must not invalidate user forms.
            $pump->last_evaluated_at = now();
            if ($source === 'scheduler') $pump->last_scheduler_at = now();
            $pump->saveQuietly();
            $reading = $this->latestReading($pump);
            $state = $pump->simulation_state;
            $reason = $pump->control_reason;
            if (!$pump->is_enabled) {
                $state = 'OFF'; $reason = 'DISABLED';
            } elseif ($pump->isOn() && ($stop = $this->runtimeStop($pump))) {
                $state = 'OFF'; $reason = $stop;
            } elseif ($pump->control_mode === 'AUTO') {
                [$state, $reason] = $this->automaticDecision($pump, $reading);
            } elseif ($pump->control_mode !== 'MANUAL') {
                $state = 'OFF'; $reason = 'NOT_CONFIGURED';
            }
            if ($state === $pump->simulation_state && $reason === $pump->control_reason) return null;
            return $this->record($pump, $state, $reason ?? 'MANUAL_OFF', 'EVALUATE', $source, null, $reading);
        });
    }

    public function latestReading(Actuator $pump): ?Monitoring
    {
        if (!$pump->control_plant_id) return null;
        return Monitoring::where('plant_id', $pump->control_plant_id)->orderByDesc('monitored_at')->orderByDesc('id')->first();
    }

    public function statusData(Actuator $pump): array
    {
        $reading = $this->latestReading($pump);
        return [
            'control_mode' => $pump->control_mode, 'simulation_state' => $pump->simulation_state,
            'control_version' => $pump->control_version, 'is_enabled' => $pump->is_enabled,
            'reason' => self::REASONS[$pump->control_reason] ?? 'Belum ada perubahan kontrol.',
            'reason_code' => $pump->control_reason,
            'soil_moisture' => $reading?->soil_moisture,
            'monitored_at' => $reading?->monitored_at?->toIso8601String(),
            'reported_pump_state' => $reading?->reported_pump_state,
            'last_evaluated_at' => $pump->last_evaluated_at?->toIso8601String(),
            'worker_recent' => $pump->last_scheduler_at && $pump->last_scheduler_at->gte(now()->subSeconds(15)),
            'mode' => 'simulation', 'hardware_dispatched' => false,
        ];
    }

    private function automaticDecision(Actuator $pump, ?Monitoring $reading): array
    {
        if (!$this->configured($pump)) return ['OFF', 'NOT_CONFIGURED'];
        if (!$reading || $reading->soil_moisture === null || !is_finite((float) $reading->soil_moisture) || $reading->soil_moisture < 0 || $reading->soil_moisture > 100) return ['OFF', 'SENSOR_INVALID'];
        if (!$reading->monitored_at || $reading->monitored_at->isFuture() || $reading->monitored_at->lt(now()->subSeconds($pump->sensor_max_age_seconds))) return ['OFF', 'SENSOR_STALE'];
        if (!$pump->auto_armed_at || $reading->id <= $pump->auto_after_monitoring_id || $reading->monitored_at->lt($pump->auto_armed_at) || !$reading->created_at || $reading->created_at->lt($pump->auto_armed_at)) return ['OFF', 'WAITING_NEW_READING'];
        if ($reading->soil_moisture >= $pump->auto_off_threshold) return ['OFF', 'SOIL_WET'];
        if ($pump->isOff() && $this->resting($pump)) return ['OFF', 'REST_INTERVAL'];
        if ($reading->soil_moisture <= $pump->auto_on_threshold) return ['ON', 'SOIL_DRY'];
        return [$pump->simulation_state, 'HYSTERESIS_HOLD'];
    }

    private function runtimeStop(Actuator $pump): ?string
    {
        if (!$pump->pump_started_at || $pump->pump_started_at->isFuture()) return 'RUNTIME_UNKNOWN';
        return $pump->pump_started_at->lte(now()->subSeconds($pump->max_runtime_seconds)) ? 'MAX_RUNTIME' : null;
    }

    private function resting(Actuator $pump): bool
    {
        return $pump->pump_stopped_at && $pump->pump_stopped_at->gt(now()->subSeconds($pump->min_rest_seconds));
    }

    private function configured(Actuator $pump): bool
    {
        return $pump->control_plant_id !== null && $pump->auto_on_threshold !== null && $pump->auto_off_threshold !== null
            && $pump->auto_on_threshold >= 0 && $pump->auto_off_threshold <= 100 && $pump->auto_on_threshold < $pump->auto_off_threshold
            && $pump->max_runtime_seconds > 0 && $pump->sensor_max_age_seconds > 0 && $pump->min_rest_seconds >= 0;
    }

    private function arm(Actuator $pump): void
    {
        $pump->auto_armed_at = now()->startOfSecond();
        $pump->auto_after_monitoring_id = $pump->control_plant_id ? (Monitoring::where('plant_id', $pump->control_plant_id)->max('id') ?? 0) : 0;
    }

    private function locked(Actuator|int $actuator, callable $action): mixed
    {
        abort_unless($this->ready(), 503, 'Migration kontrol pompa belum diterapkan. Kontrol baru belum tersedia.');
        $id = $actuator instanceof Actuator ? $actuator->id : $actuator;
        return DB::transaction(function () use ($id, $action) {
            $pump = Actuator::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($pump->code === 'PUMP-01' && $pump->mode === 'simulation', 422, 'Hanya simulasi PUMP-01 yang tersedia.');
            return $action($pump);
        }, 5);
    }

    private function authorize(?User $user): void
    {
        abort_unless($user && $user->role === 'admin', 403, 'Hanya administrator yang dapat mengendalikan pompa.');
    }

    private function checkVersion(Actuator $pump, ?int $expected): void
    {
        if ($expected !== null && $expected !== $pump->control_version) {
            throw new HttpException(409, 'Status pompa telah berubah. Muat ulang sebelum mengirim perintah.');
        }
    }

    private function settings(Actuator $pump): array
    {
        return $pump->only(['control_plant_id', 'auto_on_threshold', 'auto_off_threshold', 'max_runtime_seconds', 'min_rest_seconds', 'sensor_max_age_seconds']);
    }

    private function record(Actuator $pump, string $state, string $reason, string $command, string $source, ?User $user = null, ?Monitoring $reading = null, ?string $notes = null, bool $rejected = false, ?string $previousMode = null): array
    {
        $previous = $pump->simulation_state;
        $previousMode ??= $pump->control_mode;
        $changed = $state !== $previous;
        if (!$rejected) {
            if ($changed) {
                if ($state === 'ON') $pump->pump_started_at = now();
                else {
                    $pump->pump_started_at = null;
                    $pump->pump_stopped_at = now();
                    // A stop must never restart from the same dry sample.
                    if ($pump->control_mode === 'AUTO') $this->arm($pump);
                }
                $pump->last_changed_at = now();
                $pump->last_user_id = $user?->id;
            }
            $this->transport->apply($pump, $state);
            $pump->control_reason = $reason;
            if ($pump->isDirty()) {
                $pump->control_version++;
                $pump->save();
            }
        }
        $log = ActuatorCommandLog::create([
            'actuator_id' => $pump->id, 'user_id' => $user?->id, 'command' => $command,
            'previous_state' => $previous, 'resulting_state' => $state,
            'previous_mode' => $previousMode, 'resulting_mode' => $pump->control_mode,
            'execution_status' => $rejected ? 'REJECTED' : 'SUCCESS', 'source' => $source,
            'executed_at' => now(), 'reason_code' => $reason, 'monitoring_id' => $reading?->id,
            'control_snapshot' => $this->settings($pump) + ['soil_moisture' => $reading?->soil_moisture, 'monitored_at' => $reading?->monitored_at?->toIso8601String(), 'validation_status' => 'PROVISIONAL'],
            'notes' => $notes ?? (self::REASONS[$reason] ?? 'Perintah manual ditolak karena mode AUTO aktif.'),
        ]);
        return ['success' => !$rejected, 'is_idempotent' => !$changed, 'actuator' => $pump->fresh(['lastUser']),
            'command_log' => $log, 'previous_state' => $previous, 'resulting_state' => $state,
            'control_mode' => $pump->control_mode, 'reason_code' => $reason,
            'message' => self::REASONS[$reason] ?? 'Mode AUTO sedang memegang kontrol pompa.',
            'mode' => 'simulation', 'hardware_dispatched' => false];
    }
}
