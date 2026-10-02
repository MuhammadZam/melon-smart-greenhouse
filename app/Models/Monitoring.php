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
        'ph',
        'humidity',
        'plant_height',
        'plant_density',
        'plant_condition',
        'plant_color',
        'notes',
        'monitored_at',
    ];

    /**
     * Tipe casting atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'temperature'   => 'float',
            'ph'            => 'float',
            'humidity'      => 'float',
            'plant_height'  => 'float',
            'plant_density' => 'float',
            'monitored_at'  => 'datetime',
        ];
    }

    /**
     * Relasi ke tanaman: Setiap record monitoring dimiliki oleh 1 tanaman.
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }
}
