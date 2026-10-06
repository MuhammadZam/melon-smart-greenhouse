<?php

namespace Database\Seeders;

use App\Models\Plant;
use Illuminate\Database\Seeder;

class PlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plants = [
            [
                'name'        => 'Melon Golden Aroma - Greenhouse A',
                'location'    => 'Greenhouse 1 - Sektor Timur',
                'description' => 'Plot budidaya tanaman melon varietas Golden Aroma dengan sistem fertigasi presisi.',
                'status'      => 'active',
            ],
            [
                'name'        => 'Melon Rock Action - Greenhouse B',
                'location'    => 'Greenhouse 1 - Sektor Barat',
                'description' => 'Plot pemeliharaan melon varietas Rock Action fase pertumbuhan vegetatif lanjut.',
                'status'      => 'active',
            ],
            [
                'name'        => 'Melon Sky Rocket - Greenhouse C',
                'location'    => 'Greenhouse 2 - Sektor Utama',
                'description' => 'Plot observasi adaptasi melon varietas Sky Rocket di lingkungan greenhouse terkontrol.',
                'status'      => 'active',
            ],
        ];

        foreach ($plants as $plant) {
            Plant::firstOrCreate(
                ['name' => $plant['name']],
                $plant
            );
        }
    }
}
