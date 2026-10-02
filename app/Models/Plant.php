<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plant extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'location',
        'description',
        'status',
    ];

    /**
     * Relasi ke data monitoring: 1 Tanaman memiliki banyak riwayat monitoring.
     */
    public function monitorings(): HasMany
    {
        return $this->hasMany(Monitoring::class);
    }

    /**
     * Mendapatkan monitoring terakhir untuk tanaman ini.
     */
    public function latestMonitoring()
    {
        return $this->hasOne(Monitoring::class)->latestOfMany('monitored_at');
    }
}
