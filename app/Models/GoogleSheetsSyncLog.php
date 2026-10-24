<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleSheetsSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'config_id',
        'mode',
        'status',
        'records_imported',
        'records_skipped',
        'records_failed',
        'started_at',
        'finished_at',
        'error_details',
        'cursor_before',
        'cursor_after',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'records_imported' => 'integer',
            'records_skipped'  => 'integer',
            'records_failed'   => 'integer',
            'started_at'       => 'datetime',
            'finished_at'      => 'datetime',
        ];
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(GoogleSheetsSyncConfig::class, 'config_id');
    }

    public function durationInSeconds(): ?float
    {
        if ($this->started_at && $this->finished_at) {
            return $this->finished_at->diffInRealSeconds($this->started_at);
        }

        return null;
    }
}
