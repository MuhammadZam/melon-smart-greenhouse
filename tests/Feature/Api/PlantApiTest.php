<?php

namespace Tests\Feature\Api;

use App\Models\Monitoring;
use App\Models\Plant;
use Tests\TestCase;

class PlantApiTest extends TestCase
{
    /**
     * Test 1: Mengambil seluruh daftar tanaman melon beserta relasi dan jumlah monitoring.
     */
    public function test_can_get_all_plants(): void
    {
        $response = $this->getJson('/api/plants');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data tanaman berhasil diambil.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'location',
                        'status',
                        'description',
                        'monitorings_count',
                        'latest_monitoring',
                    ],
                ],
            ]);
    }

    /**
     * Test 2: Mengambil detail satu tanaman melon yang ada di database.
     */
    public function test_can_get_plant_detail(): void
    {
        $plant = Plant::first();
        if (!$plant) {
            $plant = Plant::factory()->create([
                'name' => 'Melon Action Test',
                'location' => 'Greenhouse A',
                'status' => 'active',
            ]);
        }

        $response = $this->getJson('/api/plants/' . $plant->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Detail data tanaman berhasil diambil.',
                'data' => [
                    'id' => $plant->id,
                    'name' => $plant->name,
                    'location' => $plant->location,
                    'status' => $plant->status,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'location',
                    'status',
                    'description',
                    'monitorings_count',
                ],
            ]);
    }

    /**
     * Test 3: Permintaan detail tanaman yang tidak ada mengembalikan 404 JSON standard.
     */
    public function test_nonexistent_plant_returns_404_json(): void
    {
        $response = $this->getJson('/api/plants/999999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
                'data'    => null,
            ]);
    }

    /**
     * Test 4: Memfilter tanaman berdasarkan status budidaya.
     */
    public function test_can_filter_plants_by_status(): void
    {
        $response = $this->getJson('/api/plants?status=active');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals('active', $item['status']);
        }
    }

    /**
     * Test 5: Filter tanaman dengan status tidak valid mengembalikan 422 JSON validation error.
     */
    public function test_invalid_plant_status_filter_returns_422(): void
    {
        $response = $this->getJson('/api/plants?status=invalid_status_value');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid.',
            ])
            ->assertJsonValidationErrors(['status']);
    }
}
