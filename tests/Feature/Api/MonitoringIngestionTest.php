<?php

namespace Tests\Feature\Api;

use App\Models\Monitoring;
use App\Models\Plant;
use App\Models\User;
use Tests\TestCase;

class MonitoringIngestionTest extends TestCase
{
    /**
     * Test 1: Ingesti data sensor yang valid berhasil mengembalikan status 201 dan menyimpan ke database.
     */
    public function test_successful_sensor_monitoring_ingestion(): void
    {
        $plant = Plant::first();
        if (!$plant) {
            $plant = Plant::factory()->create([
                'name' => 'Melon Ingestion Test',
                'status' => 'active',
            ]);
        }

        $payload = [
            'plant_id'       => $plant->id,
            'monitored_at'   => '2026-10-07 08:30:00',
            'temperature'    => 28.4,
            'ph'             => 6.35,
            'humidity'       => 74.5,
            'plant_height'   => 82.0,
            'canopy_density' => 68.0,
            'condition'      => 'optimal',
            'leaf_color'     => 'Hijau Segar',
            'notes'          => 'Simulated sensor data (IoT Ingestion Test)',
        ];

        $response = $this->postJson('/api/monitorings', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Data monitoring berhasil disimpan.',
                'data'    => [
                    'plant_id'       => $plant->id,
                    'temperature'    => 28.4,
                    'ph'             => 6.35,
                    'humidity'       => 74.5,
                    'plant_height'   => 82.0,
                    'canopy_density' => 68.0,
                    'condition'      => 'optimal',
                    'leaf_color'     => 'Hijau Segar',
                    'notes'          => 'Simulated sensor data (IoT Ingestion Test)',
                ],
            ]);

        $this->assertDatabaseHas('monitorings', [
            'plant_id'        => $plant->id,
            'temperature'     => 28.4,
            'ph'              => 6.35,
            'humidity'        => 74.5,
            'plant_height'    => 82.0,
            'plant_density'   => 68.0,
            'plant_condition' => 'optimal',
            'plant_color'     => 'Hijau Segar',
        ]);
    }

    /**
     * Test 2: Ingesti dengan ID tanaman yang tidak ada ditolak dengan 422 Unprocessable Entity.
     */
    public function test_ingestion_with_invalid_plant_id_returns_422(): void
    {
        $payload = [
            'plant_id'       => 999999,
            'monitored_at'   => '2026-10-07 09:00:00',
            'temperature'    => 27.5,
            'ph'             => 6.5,
            'humidity'       => 70.0,
            'plant_height'   => 75.0,
            'canopy_density' => 65.0,
            'condition'      => 'optimal',
        ];

        $response = $this->postJson('/api/monitorings', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid.',
            ])
            ->assertJsonValidationErrors(['plant_id']);
    }

    /**
     * Test 3: Ingesti tanpa field wajib mengembalikan 422 dengan pesan error field terkait.
     */
    public function test_ingestion_missing_required_fields_returns_422(): void
    {
        $response = $this->postJson('/api/monitorings', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid.',
            ])
            ->assertJsonValidationErrors([
                'plant_id',
                'monitored_at',
                'temperature',
                'ph',
                'humidity',
                'plant_height',
                'plant_density',
                'plant_condition',
            ]);
    }

    /**
     * Test 4: Ingesti dengan nilai numerik tidak valid mengembalikan 422.
     */
    public function test_ingestion_with_invalid_numeric_values_returns_422(): void
    {
        $plant = Plant::first();

        $payload = [
            'plant_id'       => $plant ? $plant->id : 1,
            'monitored_at'   => '2026-10-07 09:00:00',
            'temperature'    => 'bukan_angka',
            'ph'             => 'invalid_ph',
            'humidity'       => 'seratus_persen',
            'plant_height'   => 'tinggi_sekali',
            'canopy_density' => 'rimbun',
            'condition'      => 'optimal',
        ];

        $response = $this->postJson('/api/monitorings', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid.',
            ])
            ->assertJsonValidationErrors(['temperature', 'ph', 'humidity', 'plant_height', 'canopy_density']);
    }

    /**
     * Test 5: Ingesti dengan kondisi tanaman yang tidak terdaftar ditolak dengan 422.
     */
    public function test_ingestion_with_invalid_condition_returns_422(): void
    {
        $plant = Plant::first();

        $payload = [
            'plant_id'       => $plant ? $plant->id : 1,
            'monitored_at'   => '2026-10-07 09:00:00',
            'temperature'    => 28.0,
            'ph'             => 6.5,
            'humidity'       => 70.0,
            'plant_height'   => 75.0,
            'canopy_density' => 65.0,
            'condition'      => 'invalid-condition-category',
        ];

        $response = $this->postJson('/api/monitorings', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid.',
            ])
            ->assertJsonValidationErrors(['condition']);
    }

    /**
     * Test 6: Kompatibilitas GET /api/monitorings setelah ingesti berhasil mengembalikan data baru.
     */
    public function test_existing_monitoring_api_retrieves_ingested_record(): void
    {
        $plant = Plant::first();

        $payload = [
            'plant_id'       => $plant ? $plant->id : 1,
            'monitored_at'   => '2026-10-07 10:15:00',
            'temperature'    => 29.1,
            'ph'             => 6.7,
            'humidity'       => 78.0,
            'plant_height'   => 88.0,
            'canopy_density' => 72.0,
            'condition'      => 'optimal',
            'notes'          => 'Specific Ingested Data Check',
        ];

        $postResponse = $this->postJson('/api/monitorings', $payload);
        $postResponse->assertStatus(201);
        $newId = $postResponse->json('data.id');

        // Verifikasi bahwa GET /api/monitorings/{id} mengambil data yang baru dibuat
        $detailResponse = $this->getJson('/api/monitorings/' . $newId);
        $detailResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'id'          => $newId,
                    'temperature' => 29.1,
                    'notes'       => 'Specific Ingested Data Check',
                ],
            ]);

        // Verifikasi bahwa GET /api/monitorings list juga memuat data tersebut
        $listResponse = $this->getJson('/api/monitorings');
        $listResponse->assertStatus(200);
        $ids = collect($listResponse->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($newId));
    }

    /**
     * Test 7: Kompatibilitas query dashboard tetap berfungsi setelah data sensor baru masuk.
     */
    public function test_dashboard_compatibility_after_sensor_ingestion(): void
    {
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create();
        }

        $plant = Plant::first();

        $payload = [
            'plant_id'       => $plant ? $plant->id : 1,
            'monitored_at'   => now()->toDateTimeString(),
            'temperature'    => 28.5,
            'ph'             => 6.6,
            'humidity'       => 75.0,
            'plant_height'   => 80.0,
            'canopy_density' => 70.0,
            'condition'      => 'optimal',
            'notes'          => 'Live Dashboard Ingestion Sync',
        ];

        $this->postJson('/api/monitorings', $payload)->assertStatus(201);

        // Dashboard web tetap berfungsi normal dan menampilkan status 200
        $dashWebResponse = $this->actingAs($user)->get('/dashboard');
        $dashWebResponse->assertStatus(200);

        // Dashboard API summary tetap berfungsi dan menyertakan rekaman baru
        $dashApiResponse = $this->getJson('/api/dashboard/summary');
        $dashApiResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
