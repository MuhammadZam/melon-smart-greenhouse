<?php

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\Plant;
use App\Models\User;
use Tests\TestCase;

class MonitoringParameterExpansionTest extends TestCase
{
    protected Plant $plant;
    protected User $admin;
    protected User $pengamat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateDevice();

        $this->plant = Plant::first() ?? Plant::create([
            'name' => 'Melon Golden Aroma Test',
            'variety' => 'Golden Aroma',
            'location' => 'Greenhouse Alpha',
            'planted_at' => now()->subDays(30),
            'status' => 'active',
            'notes' => 'Tanaman uji test expansion',
        ]);

        $this->admin = User::firstWhere('email', 'admin@melon.com') ?? User::create([
            'name' => 'Administrator Test',
            'email' => 'admin@melon.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $this->pengamat = User::firstWhere('email', 'pengamat@melon.com') ?? User::create([
            'name' => 'Pengamat Test',
            'email' => 'pengamat@melon.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);
    }

    /**
     * 1. Test Ingestion Payload Lengkap (8 Parameter Sensor)
     */
    public function test_can_ingest_complete_eight_parameter_payload(): void
    {
        $payload = [
            'plant_id' => $this->plant->id,
            'monitored_at' => '2026-10-14 11:00:00',
            'temperature' => 28.5,
            'humidity' => 72.0,
            'soil_moisture' => 68.5,
            'ph' => 6.45,
            'ec' => 2.30,
            'light_intensity' => 32000.0,
            'nutrient_temperature' => 24.2,
            'tank_level' => 88.0,
            'plant_height' => 78.5,
            'canopy_density' => 65.0,
            'condition' => 'optimal',
            'leaf_color' => 'Hijau Segar',
            'notes' => 'Uji lengkap 8 sensor telemetri',
        ];

        $response = $this->postJson('/api/monitorings', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Data monitoring berhasil disimpan.',
                'data' => [
                    'plant_id' => $this->plant->id,
                    'temperature' => 28.5,
                    'humidity' => 72.0,
                    'soil_moisture' => 68.5,
                    'ph' => 6.45,
                    'ec' => 2.30,
                    'light_intensity' => 32000.0,
                    'nutrient_temperature' => 24.2,
                    'tank_level' => 88.0,
                    'plant_height' => 78.5,
                    'canopy_density' => 65.0,
                    'condition' => 'optimal',
                    'leaf_color' => 'Hijau Segar',
                ],
            ]);

        $this->assertDatabaseHas('monitorings', [
            'plant_id' => $this->plant->id,
            'ec' => 2.30,
            'soil_moisture' => 68.5,
            'light_intensity' => 32000.0,
            'nutrient_temperature' => 24.2,
            'tank_level' => 88.0,
        ]);
    }

    /**
     * 2. Test Ingestion Payload Legacy (Backward Compatibility Phase 1-10)
     */
    public function test_can_ingest_legacy_payload_without_new_parameters(): void
    {
        $payload = [
            'plant_id' => $this->plant->id,
            'monitored_at' => '2026-10-14 11:15:00',
            'temperature' => 27.8,
            'humidity' => 75.2,
            'ph' => 6.20,
            'plant_height' => 60.0,
            'canopy_density' => 58.0,
            'condition' => 'optimal',
            'leaf_color' => 'Hijau Segar',
            'notes' => 'Payload legacy lama tanpa parameter baru',
        ];

        $response = $this->postJson('/api/monitorings', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'plant_id' => $this->plant->id,
                    'temperature' => 27.8,
                    'humidity' => 75.2,
                    'ph' => 6.20,
                    'ec' => null,
                    'soil_moisture' => null,
                    'light_intensity' => null,
                    'nutrient_temperature' => null,
                    'tank_level' => null,
                ],
            ]);

        // Verifikasi bahwa null TIDAK dikonversi menjadi 0
        $data = $response->json('data');
        $this->assertNull($data['ec']);
        $this->assertNull($data['soil_moisture']);
        $this->assertNull($data['light_intensity']);
        $this->assertNull($data['nutrient_temperature']);
        $this->assertNull($data['tank_level']);
    }

    /**
     * 3. Test Ingestion Payload Parsial (Sebagian Sensor Saja)
     */
    public function test_can_ingest_partial_sensor_payload(): void
    {
        $payload = [
            'plant_id' => $this->plant->id,
            'monitored_at' => '2026-10-14 11:30:00',
            'temperature' => 29.0,
            'humidity' => 69.5,
            'ph' => 6.30,
            'plant_height' => 50.0,
            'plant_density' => 55.0,
            'plant_condition' => 'Subur & Baik',
            // Hanya 2 dari 5 parameter baru yang dikirim (parsial)
            'ec' => 2.15,
            'soil_moisture' => 72.0,
        ];

        $response = $this->postJson('/api/monitorings', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'temperature' => 29.0,
                    'humidity' => 69.5,
                    'ph' => 6.30,
                    'ec' => 2.15,
                    'soil_moisture' => 72.0,
                    'light_intensity' => null,
                    'nutrient_temperature' => null,
                    'tank_level' => null,
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(2.15, $data['ec']);
        $this->assertEquals(72.0, $data['soil_moisture']);
        $this->assertNull($data['light_intensity']);
        $this->assertNull($data['nutrient_temperature']);
        $this->assertNull($data['tank_level']);
    }

    /**
     * 4. Test Validasi Batas Fisik Parameter Sensor (Rejection HTTP 422)
     */
    public function test_validates_physical_boundaries_for_all_parameters(): void
    {
        // Uji nilai EC di luar batas fisik (> 20.0 mS/cm)
        $response = $this->postJson('/api/monitorings', [
            'plant_id' => $this->plant->id,
            'ec' => 25.5,
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['ec']);

        // Uji nilai kelembapan tanah di luar batas fisik (> 100%)
        $response = $this->postJson('/api/monitorings', [
            'plant_id' => $this->plant->id,
            'soil_moisture' => 120.0,
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['soil_moisture']);

        // Uji intensitas cahaya negatif
        $response = $this->postJson('/api/monitorings', [
            'plant_id' => $this->plant->id,
            'light_intensity' => -100.0,
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['light_intensity']);

        // Uji suhu nutrisi di atas 60°C
        $response = $this->postJson('/api/monitorings', [
            'plant_id' => $this->plant->id,
            'nutrient_temperature' => 75.0,
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['nutrient_temperature']);

        // Uji level tandon di luar 0-100%
        $response = $this->postJson('/api/monitorings', [
            'plant_id' => $this->plant->id,
            'tank_level' => 105.0,
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['tank_level']);
    }

    /**
     * 5. Test Endpoint Statistik Mengagregasikan 8 Parameter Sensor
     */
    public function test_api_statistics_includes_all_eight_parameters(): void
    {
        $response = $this->actingAs($this->pengamat)->getJson('/api/monitorings/statistics?plant_id=' . $this->plant->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Statistik monitoring berhasil diambil.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'total_monitorings',
                    'average_temperature',
                    'minimum_temperature',
                    'maximum_temperature',
                    'average_nutrient_temperature',
                    'minimum_nutrient_temperature',
                    'maximum_nutrient_temperature',
                    'average_ph',
                    'minimum_ph',
                    'maximum_ph',
                    'average_ec',
                    'minimum_ec',
                    'maximum_ec',
                    'average_humidity',
                    'minimum_humidity',
                    'maximum_humidity',
                    'average_soil_moisture',
                    'minimum_soil_moisture',
                    'maximum_soil_moisture',
                    'average_light_intensity',
                    'minimum_light_intensity',
                    'maximum_light_intensity',
                    'average_tank_level',
                    'minimum_tank_level',
                    'maximum_tank_level',
                    'average_plant_height',
                    'minimum_plant_height',
                    'maximum_plant_height',
                    'average_canopy_density',
                    'minimum_canopy_density',
                    'maximum_canopy_density',
                ],
            ]);
    }

    /**
     * 6. Test Endpoint Summary Dashboard Menyertakan Pembacaan Sensor Terbaru
     */
    public function test_api_dashboard_summary_includes_latest_sensor_readings(): void
    {
        $response = $this->actingAs($this->pengamat)->getJson('/api/dashboard/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_plants',
                    'active_plants',
                    'latest_monitoring',
                    'latest_temperature',
                    'latest_ph',
                    'latest_humidity',
                    'latest_ec',
                    'latest_soil_moisture',
                    'latest_light_intensity',
                    'latest_nutrient_temperature',
                    'latest_tank_level',
                ],
            ]);
    }

    /**
     * 7. Test Admin Dapat Menyimpan dan Mengedit Parameter Baru via Web Form
     */
    public function test_admin_can_create_and_update_monitoring_with_new_parameters_via_web(): void
    {
        $createData = [
            'plant_id' => $this->plant->id,
            'monitored_at' => now()->format('Y-m-d\TH:i'),
            'temperature' => 28.0,
            'ph' => 6.5,
            'humidity' => 75.0,
            'nutrient_temperature' => 23.5,
            'ec' => 2.1,
            'soil_moisture' => 70.0,
            'light_intensity' => 30000.0,
            'tank_level' => 85.0,
            'plant_height' => 50.0,
            'plant_density' => 60.0,
            'plant_condition' => 'Subur & Baik',
            'plant_color' => 'Hijau Segar',
            'notes' => 'Entri web form admin',
        ];

        $response = $this->actingAs($this->admin)->post(route('monitorings.store'), $createData);
        $response->assertRedirect(route('monitorings.index'));

        $monitoring = Monitoring::latest('id')->first();
        $this->assertNotNull($monitoring);
        $this->assertEquals(2.1, $monitoring->ec);
        $this->assertEquals(70.0, $monitoring->soil_moisture);

        // Update via web
        $updateData = array_merge($createData, [
            'ec' => 2.4,
            'soil_moisture' => 73.0,
            'notes' => 'Entri web form update',
        ]);

        $updateResponse = $this->actingAs($this->admin)->put(route('monitorings.update', $monitoring), $updateData);
        $updateResponse->assertRedirect(route('monitorings.index'));

        $monitoring->refresh();
        $this->assertEquals(2.4, $monitoring->ec);
        $this->assertEquals(73.0, $monitoring->soil_moisture);
    }

    /**
     * 8. Test Otorisasi RBAC: Pengamat Dapat Input Data Baru, Namun Dilarang Mengedit/Menghapus
     */
    public function test_pengamat_access_control_for_monitoring_web_routes(): void
    {
        $monitoring = Monitoring::first();

        // Pengamat diizinkan membuka formulir input pengamatan baru
        $createResponse = $this->actingAs($this->pengamat)->get(route('monitorings.create'));
        $createResponse->assertStatus(200);

        // Pengamat DILARANG mengedit data observasi yang sudah tersimpan (Hanya Admin)
        if ($monitoring) {
            $editResponse = $this->actingAs($this->pengamat)->get(route('monitorings.edit', $monitoring));
            $editResponse->assertStatus(403);

            $deleteResponse = $this->actingAs($this->pengamat)->delete(route('monitorings.destroy', $monitoring));
            $deleteResponse->assertStatus(403);
        }
    }

    /**
     * 9. Test Halaman Dashboard Web Merender Seluruh Chart Baru dan Empty State
     */
    public function test_dashboard_web_view_renders_all_sensor_charts(): void
    {
        $response = $this->actingAs($this->pengamat)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('tempChart');
        $response->assertSee('phChart');
        $response->assertSee('humidityChart');
        $response->assertSee('ecChart');
        $response->assertSee('soilMoistureChart');
        $response->assertSee('lightChart');
        $response->assertSee('nutrientTempChart');
        $response->assertSee('tankLevelChart');
    }

    /**
     * 10. Test Halaman Detail Monitoring Menampilkan 8 Parameter Sensor
     */
    public function test_monitoring_show_view_displays_expanded_parameters(): void
    {
        $monitoring = Monitoring::create([
            'plant_id' => $this->plant->id,
            'monitored_at' => now(),
            'temperature' => 28.0,
            'ph' => 6.5,
            'humidity' => 70.0,
            'ec' => 2.2,
            'soil_moisture' => 75.0,
            'light_intensity' => 35000.0,
            'nutrient_temperature' => 24.0,
            'tank_level' => 90.0,
            'plant_height' => 60.0,
            'plant_density' => 65.0,
            'plant_condition' => 'Subur & Baik',
            'plant_color' => 'Hijau Segar',
            'notes' => 'Observasi detail',
        ]);

        $response = $this->actingAs($this->pengamat)->get(route('monitorings.show', $monitoring));

        $response->assertStatus(200);
        $response->assertSee('2.20');
        $response->assertSee('mS/cm');
        $response->assertSee('75.0');
        $response->assertSee('35,000');
        $response->assertSee('24.0');
        $response->assertSee('90.0');
    }
}
