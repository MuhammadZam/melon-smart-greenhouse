<?php

namespace Database\Seeders;

use App\Models\Monitoring;
use App\Models\Plant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MonitoringSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plants = Plant::all();

        if ($plants->isEmpty()) {
            return;
        }

        // Buat data monitoring berkala selama 7 hari terakhir
        foreach ($plants as $plant) {
            for ($i = 6; $i >= 0; $i--) {
                $monitoredTime = Carbon::now()->subDays($i)->setHour(9)->setMinute(0)->setSecond(0);

                // Variasi nilai yang realistis untuk tanaman Azolla
                $tempVariation = sin($i) * 1.5;
                $phVariation   = cos($i) * 0.2;

                $temperature = round(26.5 + $tempVariation, 2);
                $ph          = round(6.8 + $phVariation, 2);
                $humidity    = round(75.0 + ($i % 3) * 3.5, 2);
                $height      = round(2.0 + (6 - $i) * 0.25, 2);
                $density     = round(60.0 + (6 - $i) * 4.5, 2);

                $condition = 'Subur & Baik';
                $color     = 'Hijau Segar';

                if ($temperature > 30.0 || $ph > 7.5 || $ph < 6.2) {
                    $condition = 'Perlu Perhatian';
                    $color     = 'Hijau Kekuningan';
                }

                Monitoring::create([
                    'plant_id'        => $plant->id,
                    'temperature'     => $temperature,
                    'ph'              => $ph,
                    'humidity'        => $humidity,
                    'plant_height'    => $height,
                    'plant_density'   => $density,
                    'plant_condition' => $condition,
                    'plant_color'     => $color,
                    'notes'           => "Pencatatan harian hari ke-" . (7 - $i) . " untuk {$plant->name}.",
                    'monitored_at'    => $monitoredTime,
                ]);
            }
        }
    }
}
