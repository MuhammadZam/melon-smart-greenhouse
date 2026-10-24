<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringAlarm extends Model
{
    use HasFactory;

    protected $table = 'monitoring_alarms';

    /**
     * Parameter keys yang tersedia di model Monitoring beserta label display-nya.
     * Digunakan untuk validasi, label UI, dan mapping ke monitoring_parameters.parameter_key.
     *
     * @var array<string, string>
     */
    public const PARAMETER_LABELS = [
        'temperature'          => 'Suhu Udara',
        'humidity'             => 'Kelembapan Udara',
        'soil_moisture'        => 'Kelembapan Tanah',
        'ph'                   => 'pH Nutrisi/Media',
        'ec'                   => 'Konduktivitas Listrik (EC)',
        'light_intensity'      => 'Intensitas Cahaya',
        'nutrient_temperature' => 'Suhu Nutrisi',
        'tank_level'           => 'Level Tandon Nutrisi',
        'plant_height'         => 'Tinggi Tanaman',
        'plant_density'        => 'Kerapatan Kanopi',
    ];

    /**
     * Parameter yang dikategorikan sebagai kritis jika melanggar arah tertentu.
     * tank_level → kritis jika di bawah minimum.
     *
     * @var array<string, 'below_minimum'|'above_maximum'|'both'>
     */
    public const CRITICAL_PARAMETERS = [
        'tank_level' => 'below_minimum',
    ];

    protected $fillable = [
        'plant_id',
        'triggered_by_monitoring_id',
        'parameter_key',
        'parameter_display_name',
        'unit',
        'violation_direction',
        'sensor_value',
        'threshold_min',
        'threshold_max',
        'severity',
        'status',
        'first_detected_at',
        'last_abnormal_value',
        'last_abnormal_at',
        'resolved_value',
        'resolved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sensor_value'        => 'float',
            'threshold_min'       => 'float',
            'threshold_max'       => 'float',
            'last_abnormal_value' => 'float',
            'resolved_value'      => 'float',
            'first_detected_at'   => 'datetime',
            'last_abnormal_at'    => 'datetime',
            'resolved_at'         => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    public function triggeringMonitoring(): BelongsTo
    {
        return $this->belongsTo(Monitoring::class, 'triggered_by_monitoring_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeWarning($query)
    {
        return $query->where('severity', 'warning');
    }

    /**
     * Filter alarm berdasarkan berbagai parameter halaman riwayat.
     */
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['plant_id'] ?? null, function ($q, $plantId) {
            if ($plantId !== 'all' && is_numeric($plantId)) {
                $q->where('plant_id', $plantId);
            }
        });

        $query->when($filters['parameter_key'] ?? null, function ($q, $key) {
            if ($key !== 'all' && !empty($key)) {
                $q->where('parameter_key', $key);
            }
        });

        $query->when($filters['severity'] ?? null, function ($q, $sev) {
            if ($sev !== 'all' && in_array($sev, ['warning', 'critical'])) {
                $q->where('severity', $sev);
            }
        });

        $query->when($filters['status'] ?? null, function ($q, $status) {
            if ($status !== 'all' && in_array($status, ['active', 'resolved'])) {
                $q->where('status', $status);
            }
        });

        $query->when($filters['date_from'] ?? null, function ($q, $from) {
            if (!empty($from)) {
                $q->whereDate('first_detected_at', '>=', $from);
            }
        });

        $query->when($filters['date_to'] ?? null, function ($q, $to) {
            if (!empty($to)) {
                $q->whereDate('first_detected_at', '<=', $to);
            }
        });

        return $query;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Label arah pelanggaran dalam Bahasa Indonesia.
     */
    public function violationLabel(): string
    {
        return $this->violation_direction === 'below_minimum'
            ? 'Di Bawah Minimum'
            : 'Di Atas Maksimum';
    }
}
