<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuzzyInferenceResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitoring_id',
        'plant_id',
        'inference_source',
        'inference_status',
        'fuzzy_score',
        'fuzzy_category',
        'conclusion',
        'recommendation',
        'rule_version',
        'input_snapshot',
        'membership_snapshot',
        'rule_activation_snapshot',
        'inferred_at',
    ];

    protected function casts(): array
    {
        return [
            'fuzzy_score'              => 'float',
            'input_snapshot'           => 'array',
            'membership_snapshot'      => 'array',
            'rule_activation_snapshot' => 'array',
            'inferred_at'              => 'datetime',
        ];
    }

    public function monitoring(): BelongsTo
    {
        return $this->belongsTo(Monitoring::class);
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    /**
     * Scope untuk filter riwayat fuzzy.
     */
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['plant_id'] ?? null, function ($q, $plantId) {
            if ($plantId !== 'all' && !empty($plantId) && is_numeric($plantId)) {
                $q->where('plant_id', $plantId);
            }
        });

        $query->when($filters['category'] ?? null, function ($q, $cat) {
            if ($cat !== 'all' && !empty($cat)) {
                $q->where('fuzzy_category', $cat);
            }
        });

        $query->when($filters['status'] ?? null, function ($q, $status) {
            if ($status !== 'all' && !empty($status)) {
                $q->where('inference_status', $status);
            }
        });

        $query->when($filters['source'] ?? null, function ($q, $source) {
            if ($source !== 'all' && !empty($source)) {
                $q->where('inference_source', $source);
            }
        });

        $query->when($filters['date_from'] ?? null, function ($q, $from) {
            $q->whereDate('inferred_at', '>=', $from);
        });

        $query->when($filters['date_to'] ?? null, function ($q, $to) {
            $q->whereDate('inferred_at', '<=', $to);
        });

        return $query;
    }
}
