<?php

namespace Tests\Feature\Api;

use App\Models\Monitoring;
use App\Models\Plant;
use Tests\ApiTestCase as TestCase;

class DashboardApiTest extends TestCase
{
    /**
     * Test 1: Mengambil ringkasan data metrik dashboard melon via REST API.
     */
    public function test_can_get_dashboard_summary(): void
    {
        $response = $this->getJson('/api/dashboard/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Ringkasan dashboard berhasil diambil.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_plants',
                    'active_plants',
                    'harvested_plants',
                    'inactive_plants',
                    'total_monitorings',
                    'today_monitorings',
                    'latest_monitoring',
                    'latest_temperature',
                    'latest_ph',
                    'latest_humidity',
                    'plant_condition_summary',
                ],
            ]);
    }

    /**
     * Test 2: Data ringkasan dashboard sesuai dengan hitungan aktual di database.
     */
    public function test_dashboard_summary_matches_database_counts(): void
    {
        $expectedPlants = Plant::count();
        $expectedMonitorings = Monitoring::count();

        $response = $this->getJson('/api/dashboard/summary');

        $response->assertStatus(200);
        $this->assertEquals($expectedPlants, $response->json('data.total_plants'));
        $this->assertEquals($expectedMonitorings, $response->json('data.total_monitorings'));
    }

    /**
     * Test 3: Seluruh endpoint API tidak mengekspos field sensitif (password, secret, token).
     */
    public function test_api_endpoints_do_not_expose_sensitive_data(): void
    {
        $plantResponse = $this->getJson('/api/plants');
        $plantContent = $plantResponse->getContent();
        $this->assertStringNotContainsString('password', $plantContent);
        $this->assertStringNotContainsString('remember_token', $plantContent);

        $monitoringResponse = $this->getJson('/api/monitorings');
        $monitoringContent = $monitoringResponse->getContent();
        $this->assertStringNotContainsString('password', $monitoringContent);
        $this->assertStringNotContainsString('remember_token', $monitoringContent);

        $dashboardResponse = $this->getJson('/api/dashboard/summary');
        $dashboardContent = $dashboardResponse->getContent();
        $this->assertStringNotContainsString('password', $dashboardContent);
        $this->assertStringNotContainsString('remember_token', $dashboardContent);
    }
}
