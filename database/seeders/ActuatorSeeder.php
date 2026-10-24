<?php

namespace Database\Seeders;

use App\Models\Actuator;
use Illuminate\Database\Seeder;

class ActuatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $actuators = [
            [
                'code'             => 'PUMP-01',
                'name'             => 'Pompa Irigasi Utama (Greenhouse A)',
                'type'             => Actuator::TYPE_IRRIGATION_PUMP,
                'simulation_state' => Actuator::STATE_OFF,
                'mode'             => Actuator::MODE_SIMULATION,
                'is_enabled'       => true,
                'location'         => 'Greenhouse Melon Zona A - Sektor Irigasi',
                'description'      => 'Simulasi pompa fertigasi & irigasi tetes untuk blok budidaya melon.',
                'last_changed_at'  => now(),
            ],
            [
                'code'             => 'FAN-01',
                'name'             => 'Kipas Exhaust Sirkulasi (Greenhouse A)',
                'type'             => Actuator::TYPE_EXHAUST_FAN,
                'simulation_state' => Actuator::STATE_OFF,
                'mode'             => Actuator::MODE_SIMULATION,
                'is_enabled'       => true,
                'location'         => 'Greenhouse Melon Dinding Timur',
                'description'      => 'Simulasi kipas ventilasi & sirkulasi udara penurun suhu/kelembapan ruangan.',
                'last_changed_at'  => now(),
            ],
        ];

        foreach ($actuators as $data) {
            Actuator::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
