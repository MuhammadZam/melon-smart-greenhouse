<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringParameter extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     */
    protected $table = 'monitoring_parameters';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'parameter_name',
        'unit',
        'minimum_value',
        'maximum_value',
        'description',
    ];

    /**
     * Tipe casting atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minimum_value' => 'float',
            'maximum_value' => 'float',
        ];
    }
}
