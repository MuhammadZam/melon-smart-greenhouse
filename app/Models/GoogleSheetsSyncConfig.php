<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleSheetsSyncConfig extends Model
{
    use HasFactory;

    public const MODE_MOCK = 'mock';
    public const MODE_SERVICE_ACCOUNT = 'service_account';

    public const STATUS_IDLE = 'IDLE';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_PARTIAL = 'PARTIAL';
    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'name',
        'spreadsheet_id',
        'sheet_name',
        'data_range',
        'is_active',
        'polling_interval_minutes',
        'auth_mode',
        'credentials_path',
        'last_synced_at',
        'last_successful_sync_at',
        'last_synced_row',
        'last_status',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'is_active'                => 'boolean',
            'polling_interval_minutes' => 'integer',
            'last_synced_row'          => 'integer',
            'last_synced_at'           => 'datetime',
            'last_successful_sync_at'  => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function logs(): HasMany
    {
        return $this->hasMany(GoogleSheetsSyncLog::class, 'config_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isMock(): bool
    {
        return $this->auth_mode === self::MODE_MOCK;
    }

    public function isLive(): bool
    {
        return $this->auth_mode === self::MODE_SERVICE_ACCOUNT;
    }

    public function isConfigured(): bool
    {
        if ($this->isMock()) {
            return true;
        }

        return !empty($this->spreadsheet_id) && !empty($this->credentials_path) && file_exists($this->credentials_path);
    }

    /**
     * Evaluasi kesegaran data telemetri Google Sheets:
     * - 'connected' : Sinkronisasi berhasil dalam rentang wajar (< 15 menit)
     * - 'stale'     : Sinkronisasi terakhir lebih dari 15 menit lalu
     * - 'error'     : Terjadi error pada sinkronisasi terakhir
     * - 'no_data'   : Belum pernah dilakukan sinkronisasi
     */
    public function freshnessStatus(): string
    {
        if ($this->last_status === self::STATUS_FAILED) {
            return 'error';
        }

        if ($this->last_successful_sync_at === null) {
            return 'no_data';
        }

        if ($this->last_successful_sync_at->diffInMinutes(now()) <= 15) {
            return 'connected';
        }

        return 'stale';
    }

    public function freshnessLabel(): string
    {
        return match ($this->freshnessStatus()) {
            'connected' => 'Telemetri Segar (Terhubung)',
            'stale'     => 'Data Usang (Stale)',
            'error'     => 'Kegagalan Sinkronisasi (Error)',
            'no_data'   => 'Belum Ada Data Sinkronisasi',
            default     => 'Tidak Diketahui',
        };
    }
}
