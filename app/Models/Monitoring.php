<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Monitoring extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plant_id',
        'temperature',
        'nutrient_temperature',
        'ph',
        'ec',
        'humidity',
        'soil_moisture',
        'light_intensity',
        'tank_level',
        'plant_height',
        'plant_density',
        'plant_condition',
        'plant_color',
        'notes',
        'monitored_at',
        'source',
        'device_id',
        'source_identifier',
        'reported_pump_state',
        'reported_fan_state',
        'fuzzy_score',
        'fuzzy_label',
    ];

    /**
     * Tipe casting atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'temperature'          => 'float',
            'nutrient_temperature' => 'float',
            'ph'                   => 'float',
            'ec'                   => 'float',
            'humidity'             => 'float',
            'soil_moisture'        => 'float',
            'light_intensity'      => 'float',
            'tank_level'           => 'float',
            'plant_height'         => 'float',
            'plant_density'        => 'float',
            'fuzzy_score'          => 'float',
            'monitored_at'         => 'datetime',
        ];
    }

    /**
     * Relasi ke tanaman: Setiap record monitoring dimiliki oleh 1 tanaman.
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    /**
     * Relasi ke perangkat IoT pengirim (opsional).
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'device_id', 'device_id');
    }

    /**
     * Relasi ke seluruh hasil inferensi fuzzy (berbagai versi/sumber).
     */
    public function fuzzyInferences(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FuzzyInferenceResult::class);
    }

    /**
     * Relasi ke hasil inferensi fuzzy terbaru.
     */
    public function latestFuzzyInference(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FuzzyInferenceResult::class)->latestOfMany('inferred_at');
    }


    /**
     * Scope query untuk filter dan pencarian monitoring melon.
     */
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['plant_id'] ?? null, function ($q, $plantId) {
            if ($plantId !== 'all' && !empty($plantId) && is_numeric($plantId)) {
                $q->where('plant_id', $plantId);
            }
        });

        $query->when($filters['condition'] ?? null, function ($q, $condition) {
            if ($condition !== 'all' && !empty($condition)) {
                $q->where('plant_condition', 'like', "%{$condition}%");
            }
        });

        $query->when($filters['search'] ?? null, function ($q, $search) {
            $search = trim($search);
            if (!empty($search)) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('notes', 'like', "%{$search}%")
                        ->orWhere('plant_condition', 'like', "%{$search}%")
                        ->orWhere('plant_color', 'like', "%{$search}%")
                        ->orWhereHas('plant', function ($pq) use ($search) {
                            $pq->where('name', 'like', "%{$search}%")
                               ->orWhere('location', 'like', "%{$search}%");
                        });
                });
            }
        });

        $query->when($filters['date_from'] ?? null, function ($q, $from) {
            if (!empty($from)) {
                $q->whereDate('monitored_at', '>=', $from);
            }
        });

        $query->when($filters['date_to'] ?? null, function ($q, $to) {
            if (!empty($to)) {
                $q->whereDate('monitored_at', '<=', $to);
            }
        });

        return $query;
    }
}
