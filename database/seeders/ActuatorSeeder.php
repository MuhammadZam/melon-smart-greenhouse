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
                'name'             => 'Pompa air',
                'type'             => Actuator::TYPE_IRRIGATION_PUMP,
                'simulation_state' => Actuator::STATE_OFF,
                'mode'             => Actuator::MODE_SIMULATION,
                'is_enabled'       => true,
                'location'         => 'Greenhouse Melon Zona A - Sektor Irigasi',
                'description'      => 'Simulasi pompa air; belum terintegrasi ESP32.',
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
