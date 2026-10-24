<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActuatorCommandLog extends Model
{
    use HasFactory;

    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_REJECTED = 'REJECTED';

    public const COMMAND_TURN_ON = 'TURN_ON';
    public const COMMAND_TURN_OFF = 'TURN_OFF';
    public const COMMAND_TOGGLE = 'TOGGLE';

    public const SOURCE_WEB_SIMULATION = 'web_simulation';
    public const SOURCE_API_SIMULATION = 'api_simulation';
    public const SOURCE_SYSTEM_SIMULATION = 'system_simulation';

    protected $fillable = [
        'actuator_id',
        'user_id',
        'command',
        'previous_state',
        'resulting_state',
        'execution_status',
        'source',
        'executed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function actuator(): BelongsTo
    {
        return $this->belongsTo(Actuator::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['actuator_id'] ?? null, function ($q, $actuatorId) {
            if ($actuatorId !== 'all' && is_numeric($actuatorId)) {
                $q->where('actuator_id', $actuatorId);
            }
        });

        $query->when($filters['command'] ?? null, function ($q, $command) {
            if ($command !== 'all' && !empty($command)) {
                $q->where('command', $command);
            }
        });

        $query->when($filters['execution_status'] ?? null, function ($q, $status) {
            if ($status !== 'all' && !empty($status)) {
                $q->where('execution_status', $status);
            }
        });

        $query->when($filters['date_from'] ?? null, function ($q, $from) {
            if (!empty($from)) {
                $q->whereDate('executed_at', '>=', $from);
            }
        });

        $query->when($filters['date_to'] ?? null, function ($q, $to) {
            if (!empty($to)) {
                $q->whereDate('executed_at', '<=', $to);
            }
        });

        return $query;
    }
}
