<?php

namespace Database\Seeders;

use App\Models\MonitoringParameter;
use Illuminate\Database\Seeder;

/**
 * Phase 12: Seed parameter_key dan critical threshold ke monitoring_parameters yang sudah ada.
 * Idempotent: menggunakan updateOrCreate/update, tidak menghapus data.
 */
class AlarmParameterKeySeeder extends Seeder
{
    public function run(): void
    {
        $mappings = [
            'Suhu Udara'                   => ['key' => 'temperature',          'crit_min' => null,   'crit_max' => 38.0],
            'Kelembapan Udara'             => ['key' => 'humidity',             'crit_min' => 40.0,  'crit_max' => 95.0],
            'Kelembapan Tanah'             => ['key' => 'soil_moisture',        'crit_min' => 30.0,  'crit_max' => null],
            'pH Nutrisi/Media'             => ['key' => 'ph',                   'crit_min' => 4.5,   'crit_max' => 8.5],
            'Konduktivitas Listrik (EC)'   => ['key' => 'ec',                   'crit_min' => 0.5,   'crit_max' => 4.0],
            'Intensitas Cahaya'            => ['key' => 'light_intensity',      'crit_min' => 5000,  'crit_max' => null],
            'Suhu Nutrisi'                 => ['key' => 'nutrient_temperature', 'crit_min' => 15.0,  'crit_max' => 32.0],
            'Level Tandon Nutrisi'         => ['key' => 'tank_level',           'crit_min' => 25.0,  'crit_max' => null],
            'Tinggi Tanaman'               => ['key' => 'plant_height',         'crit_min' => null,   'crit_max' => null],
            'Kerapatan Kanopi'             => ['key' => 'plant_density',        'crit_min' => null,   'crit_max' => null],
        ];

        foreach ($mappings as $name => $data) {
            MonitoringParameter::where('parameter_name', $name)->update([
                'parameter_key'      => $data['key'],
                'critical_min_value' => $data['crit_min'],
                'critical_max_value' => $data['crit_max'],
            ]);
        }

        $this->command->info('Phase 12: parameter_key dan critical thresholds berhasil diset.');
    }
}
