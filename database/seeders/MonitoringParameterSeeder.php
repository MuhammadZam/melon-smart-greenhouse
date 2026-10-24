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
                'parameter_name' => 'Suhu Udara',
                'unit'           => '°C',
                'minimum_value'  => 20.00,
                'maximum_value'  => 32.00,
                'description'    => 'Ambang batas suhu lingkungan greenhouse yang dikonfigurasi untuk monitoring tanaman melon.',
            ],
            [
                'parameter_name' => 'pH Nutrisi/Media',
                'unit'           => 'pH',
                'minimum_value'  => 5.80,
                'maximum_value'  => 7.20,
                'description'    => 'Ambang batas keasaman larutan nutrisi/media yang dikonfigurasi pada sistem monitoring melon.',
            ],
            [
                'parameter_name' => 'Kelembapan Udara',
                'unit'           => '%',
                'minimum_value'  => 60.00,
                'maximum_value'  => 85.00,
                'description'    => 'Ambang batas kelembapan relatif udara greenhouse yang dikonfigurasi pada sistem monitoring.',
            ],
            [
                'parameter_name' => 'Tinggi Tanaman',
                'unit'           => 'cm',
                'minimum_value'  => 10.00,
                'maximum_value'  => 150.00,
                'description'    => 'Ambang batas tinggi tajuk dan sulur tanaman melon yang dikonfigurasi untuk monitoring pertumbuhan.',
            ],
            [
                'parameter_name' => 'Kerapatan Kanopi',
                'unit'           => '%',
                'minimum_value'  => 40.00,
                'maximum_value'  => 90.00,
                'description'    => 'Ambang batas persentase kerapatan kanopi daun tanaman melon yang dikonfigurasi pada sistem.',
            ],
            [
                'parameter_name' => 'Kelembapan Tanah',
                'unit'           => '%',
                'minimum_value'  => 60.00,
                'maximum_value'  => 80.00,
                'description'    => 'Ambang batas kelembapan media tanam/tanah yang dikonfigurasi untuk tanaman melon (perlu validasi dengan tim Elektro).',
            ],
            [
                'parameter_name' => 'Konduktivitas Listrik (EC)',
                'unit'           => 'mS/cm',
                'minimum_value'  => 1.80,
                'maximum_value'  => 2.80,
                'description'    => 'Ambang batas kepekatan larutan nutrisi (EC) fase vegetatif-generatif melon.',
            ],
            [
                'parameter_name' => 'Intensitas Cahaya',
                'unit'           => 'lux',
                'minimum_value'  => 15000.00,
                'maximum_value'  => 50000.00,
                'description'    => 'Ambang batas intensitas radiasi cahaya optimal greenhouse untuk fotosintesis melon.',
            ],
            [
                'parameter_name' => 'Suhu Nutrisi',
                'unit'           => '°C',
                'minimum_value'  => 20.00,
                'maximum_value'  => 26.00,
                'description'    => 'Ambang batas suhu larutan tandon nutrisi hidroponik/fertigasi melon.',
            ],
            [
                'parameter_name' => 'Level Tandon Nutrisi',
                'unit'           => '%',
                'minimum_value'  => 40.00,
                'maximum_value'  => 95.00,
                'description'    => 'Ambang batas volume cadangan tandon air/nutrisi tanaman melon.',
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
