<?php

namespace Database\Seeders;

use App\Models\MonitoringParameter;
use Illuminate\Database\Seeder;

class MonitoringParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parameters = [
            [
                'parameter_name' => 'Suhu Air',
                'unit'           => '°C',
                'minimum_value'  => 20.00,
                'maximum_value'  => 30.00,
                'description'    => 'Rentang suhu air ideal untuk pertumbuhan biomassa Azolla secara optimal.',
            ],
            [
                'parameter_name' => 'pH Air',
                'unit'           => 'pH',
                'minimum_value'  => 6.00,
                'maximum_value'  => 7.50,
                'description'    => 'Tingkat keasaman air ideal agar penyerapan nutrisi tidak terganggu.',
            ],
            [
                'parameter_name' => 'Kelembapan Udara',
                'unit'           => '%',
                'minimum_value'  => 60.00,
                'maximum_value'  => 85.00,
                'description'    => 'Kelembapan lingkungan sekitar permukaan air untuk mencegah kekeringan frond.',
            ],
            [
                'parameter_name' => 'Tinggi Tanaman',
                'unit'           => 'cm',
                'minimum_value'  => 1.00,
                'maximum_value'  => 5.00,
                'description'    => 'Ketebalan hamparan dan tinggi tajuk tanaman Azolla.',
            ],
            [
                'parameter_name' => 'Kepadatan Kolam',
                'unit'           => '%',
                'minimum_value'  => 50.00,
                'maximum_value'  => 95.00,
                'description'    => 'Persentase tutupan biomassa Azolla di atas permukaan kolam.',
            ],
        ];

        foreach ($parameters as $param) {
            MonitoringParameter::firstOrCreate(
                ['parameter_name' => $param['parameter_name']],
                $param
            );
        }
    }
}
