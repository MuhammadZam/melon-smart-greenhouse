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
                'name'        => 'Azolla Microphylla Kolam A',
                'location'    => 'Greenhouse 1 - Bak Utama',
                'description' => 'Kolam percontohan budidaya Azolla dengan penyinaran semi-tertutup.',
                'status'      => 'active',
            ],
            [
                'name'        => 'Azolla Microphylla Kolam B',
                'location'    => 'Greenhouse 1 - Bak Pembibitan',
                'description' => 'Bak pembibitan dan pembiakan spora Azolla muda.',
                'status'      => 'active',
            ],
            [
                'name'        => 'Azolla Pinnata Kolam C',
                'location'    => 'Outdoor - Kolam Terbuka 2',
                'description' => 'Pengujian ketahanan Azolla varietas Pinnata di lingkungan kolam terbuka.',
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
