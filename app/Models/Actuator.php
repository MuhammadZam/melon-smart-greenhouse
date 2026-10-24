<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Actuator extends Model
{
    use HasFactory;

    public const TYPE_IRRIGATION_PUMP = 'irrigation_pump';
    public const TYPE_EXHAUST_FAN = 'exhaust_fan';

    public const STATE_ON = 'ON';
    public const STATE_OFF = 'OFF';

    public const MODE_SIMULATION = 'simulation';

    protected $fillable = [
        'code',
        'name',
        'type',
        'simulation_state',
        'mode',
        'is_enabled',
        'last_changed_at',
        'last_user_id',
        'location',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled'      => 'boolean',
            'last_changed_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function lastUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_user_id');
    }

    public function commandLogs(): HasMany
    {
        return $this->hasMany(ActuatorCommandLog::class, 'actuator_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopePumps($query)
    {
        return $query->where('type', self::TYPE_IRRIGATION_PUMP);
    }

    public function scopeFans($query)
    {
        return $query->where('type', self::TYPE_EXHAUST_FAN);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isOn(): bool
    {
        return $this->simulation_state === self::STATE_ON;
    }

    public function isOff(): bool
    {
        return $this->simulation_state === self::STATE_OFF;
    }

    public function isSimulation(): bool
    {
        return $this->mode === self::MODE_SIMULATION;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_IRRIGATION_PUMP => 'Pompa Irigasi',
            self::TYPE_EXHAUST_FAN     => 'Kipas Exhaust',
            default                    => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
}
