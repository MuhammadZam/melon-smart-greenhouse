<?php

namespace Tests\Feature\Api;

use App\Models\Monitoring;
use App\Models\Plant;
use Tests\TestCase;

class MonitoringApiTest extends TestCase
{
    /**
     * Test 1: Mengambil daftar monitoring dengan format JSON terstruktur.
     */
    public function test_can_get_all_monitorings(): void
    {
        $response = $this->getJson('/api/monitorings');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data monitoring berhasil diambil.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'plant_id',
                        'plant',
                        'monitored_at',
                        'temperature',
                        'ph',
                        'humidity',
                        'plant_height',
                        'canopy_density',
                        'condition',
                        'leaf_color',
                        'notes',
                    ],
                ],
            ]);
    }

    /**
     * Test 2: Filter data monitoring berdasarkan plant_id.
     */
    public function test_can_filter_monitorings_by_plant_id(): void
    {
        $plant = Plant::has('monitorings')->first();
        if (!$plant) {
            $plant = Plant::first();
        }

        $response = $this->getJson('/api/monitorings?plant_id=' . $plant->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals($plant->id, $item['plant_id']);
        }
    }

    /**
     * Test 3: Filter data monitoring berdasarkan kondisi tanaman.
     */
    public function test_can_filter_monitorings_by_condition(): void
    {
        $monitoring = Monitoring::first();
        $condition = $monitoring ? $monitoring->plant_condition : 'Optimal';

        $response = $this->getJson('/api/monitorings?condition=' . urlencode($condition));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test 4: Filter data monitoring berdasarkan rentang tanggal.
     */
    public function test_can_filter_monitorings_by_date(): void
    {
        $response = $this->getJson('/api/monitorings?date_from=2020-01-01&date_to=2030-12-31');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test 5: Mengambil detail satu data monitoring.
     */
    public function test_can_get_monitoring_detail(): void
    {
        $monitoring = Monitoring::with('plant')->first();
        $this->assertNotNull($monitoring, 'Seeded monitoring should exist');

        $response = $this->getJson('/api/monitorings/' . $monitoring->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Detail monitoring berhasil diambil.',
                'data' => [
                    'id' => $monitoring->id,
                    'plant_id' => $monitoring->plant_id,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'plant_id',
                    'plant' => [
                        'id',
                        'name',
                        'location',
                    ],
                    'monitored_at',
                    'temperature',
                    'ph',
                    'humidity',
                    'plant_height',
                    'canopy_density',
                    'condition',
                    'leaf_color',
                    'notes',
                ],
            ]);
    }

    /**
     * Test 6: Mengambil data monitoring yang tidak ada mengembalikan 404 JSON.
     */
    public function test_nonexistent_monitoring_returns_404_json(): void
    {
        $response = $this->getJson('/api/monitorings/999999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
                'data'    => null,
            ]);
    }

    /**
     * Test 7: Endpoint statistik agregasi monitoring mengembalikan metrik akurat.
     */
    public function test_monitoring_statistics_endpoint_works(): void
    {
        $response = $this->getJson('/api/monitorings/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Statistik monitoring berhasil diambil.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_monitorings',
                    'average_temperature',
                    'minimum_temperature',
                    'maximum_temperature',
                    'average_ph',
                    'minimum_ph',
                    'maximum_ph',
                    'average_humidity',
                    'minimum_humidity',
                    'maximum_humidity',
                    'average_plant_height',
                    'minimum_plant_height',
                    'maximum_plant_height',
                    'average_canopy_density',
                    'minimum_canopy_density',
                    'maximum_canopy_density',
                    'temperature' => ['average', 'min', 'max'],
                    'ph' => ['average', 'min', 'max'],
                    'humidity' => ['average', 'min', 'max'],
                    'plant_height' => ['average', 'min', 'max'],
                    'canopy_density' => ['average', 'min', 'max'],
                ],
            ]);

        $this->assertGreaterThan(0, $response->json('data.total_monitorings'));
    }

    /**
     * Test 8: Endpoint statistik agregasi mendukung pemfilteran tanaman.
     */
    public function test_monitoring_statistics_supports_filtering(): void
    {
        $plant = Plant::has('monitorings')->first();
        if (!$plant) {
            $plant = Plant::first();
        }

        $response = $this->getJson('/api/monitorings/statistics?plant_id=' . $plant->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test 9: Validasi menolak input query parameter yang tidak valid dengan 422 JSON.
     */
    public function test_invalid_query_parameters_return_422_validation_error(): void
    {
        // 1. Plant ID tidak ada di tabel plants
        $responsePlant = $this->getJson('/api/monitorings?plant_id=999999');
        $responsePlant->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid.',
            ])
            ->assertJsonValidationErrors(['plant_id']);

        // 2. Format tanggal tidak valid pada monitoring
        $responseDate = $this->getJson('/api/monitorings?date_from=invalid-date');
        $responseDate->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid.',
            ])
            ->assertJsonValidationErrors(['date_from']);

        // 3. Format tanggal tidak valid pada statistik
        $responseStat = $this->getJson('/api/monitorings/statistics?date_to=bukan-tanggal');
        $responseStat->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid.',
            ])
            ->assertJsonValidationErrors(['date_to']);
    }
}
