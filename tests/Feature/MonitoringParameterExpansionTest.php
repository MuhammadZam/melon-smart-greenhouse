<?php

namespace Tests\Feature;

use App\Models\{Monitoring, Plant, User, Actuator, MonitoringAlarm};
use App\Services\GoogleSheets\GoogleSheetsTelemetryService;
use App\Services\FuzzyLogic\FuzzyPipelineService;
use Tests\TestCase;

/** Final three-sensor contract supersedes the eight-sensor expansion contract. */
class MonitoringParameterExpansionTest extends TestCase
{
    private function payload(): array
    {
        return ['plant_id' => Plant::first()->id, 'monitored_at' => now()->toIso8601String(),
            'temperature' => 27, 'soil_moisture' => 70, 'water_ph' => 6.5, 'event_id' => 'three-input-event'];
    }

    public function test_minimal_three_sensor_payload_replay_and_conflict(): void
    {
        $this->authenticateDevice(); $payload = $this->payload();
        $before = Monitoring::count();
        $first = $this->postJson('/api/monitorings', $payload)->assertCreated()->assertJsonPath('data.water_ph', 6.5);
        foreach (['humidity', 'ec', 'light_intensity', 'nutrient_temperature', 'tank_level', 'ph'] as $key) {
            $first->assertJsonMissingPath('data.'.$key);
        }
        $id = $first->json('data.id');
        $this->postJson('/api/monitorings', $payload)->assertOk()->assertJsonPath('data.id', $id);
        $this->postJson('/api/monitorings', array_replace($payload, ['water_ph' => 7]))->assertConflict();
        $this->assertSame($before + 1, Monitoring::count());
        $this->assertNull(Monitoring::find($id)->humidity);
    }

    public function test_old_alias_and_retired_garbage_are_compatible_but_not_saved(): void
    {
        $this->authenticateDevice(); $payload = $this->payload();
        unset($payload['water_ph']); $payload['ph'] = 6.5;
        foreach (['humidity', 'ec', 'light_intensity', 'nutrient_temperature', 'tank_level'] as $key) $payload[$key] = 'retired-invalid';
        $id = $this->postJson('/api/monitorings', $payload)->assertCreated()->json('data.id');
        $row = Monitoring::find($id);
        foreach (['humidity', 'ec', 'light_intensity', 'nutrient_temperature', 'tank_level'] as $key) $this->assertNull($row->$key);
        $this->assertEquals(6.5, $row->ph);
    }

    public function test_missing_soil_is_rejected_without_fabricating_a_reading(): void
    {
        $this->authenticateDevice(); $payload = $this->payload(); unset($payload['soil_moisture']);
        $this->postJson('/api/monitorings', $payload)->assertUnprocessable()->assertJsonValidationErrors('soil_moisture');
    }

    public function test_conflicting_ph_aliases_are_rejected(): void
    {
        $this->authenticateDevice();
        $this->postJson('/api/monitorings', $this->payload() + ['ph' => 7])->assertUnprocessable()->assertJsonValidationErrors('water_ph');
    }

    public function test_active_sensor_physical_validation(): void
    {
        $this->authenticateDevice();
        foreach (['temperature' => -1, 'soil_moisture' => 101, 'water_ph' => 15] as $key => $value) {
            $this->postJson('/api/monitorings', array_replace($this->payload(), [$key => $value]))->assertUnprocessable();
        }
    }

    public function test_api_statistics_and_summary_only_publish_active_sensors(): void
    {
        $this->actingAs(User::first());
        $this->getJson('/api/monitorings/statistics')->assertOk()->assertJsonStructure(['data' => ['temperature', 'soil_moisture', 'water_ph']])->assertJsonMissingPath('data.humidity')->assertJsonMissingPath('data.ec');
        $this->getJson('/api/dashboard/summary')->assertOk()->assertJsonStructure(['data' => ['latest_temperature', 'latest_soil_moisture', 'latest_water_ph']])->assertJsonMissingPath('data.latest_humidity');
    }

    public function test_historical_values_survive_web_update(): void
    {
        $row = Monitoring::factory()->create(['plant_id' => Plant::first()->id, 'humidity' => 75, 'ec' => 2.3]);
        $this->actingAs(User::where('role', 'admin')->first());
        $this->put(route('monitorings.update', $row), ['plant_id' => $row->plant_id, 'monitored_at' => now()->format('Y-m-d H:i:s'), 'temperature' => 28, 'water_ph' => 6.7, 'soil_moisture' => 72, 'plant_condition' => 'optimal', 'plant_color' => 'Hijau'])->assertRedirect();
        $this->assertEquals(75, $row->fresh()->humidity); $this->assertEquals(2.3, $row->fresh()->ec);
    }

    public function test_sheets_old_and_new_explicit_positional_layouts(): void
    {
        $parser = app(GoogleSheetsTelemetryService::class);
        $old = ['SHEET-REV', now()->format('Y-m-d H:i:s'), Plant::first()->name, 27, 'retired', 70, 6.5, 'garbage'];
        config(['services.google_sheets.telemetry_layout' => 'legacy']);
        $this->assertTrue($parser->parseAndValidateRow($old, 2)['valid']);
        config(['services.google_sheets.telemetry_layout' => 'three_input']);
        $new = ['SHEET-REV', now()->format('Y-m-d H:i:s'), Plant::first()->name, 27, 70, 6.5];
        $parsed = $parser->parseAndValidateRow($new, 2);
        $this->assertTrue($parsed['valid']); $this->assertEquals(70, $parsed['data']['soil_moisture']);
        $this->assertArrayNotHasKey('humidity', $parsed['data']);
    }

    public function test_fuzzy_does_not_need_humidity_and_preserves_old_inference(): void
    {
        $row = Monitoring::factory()->create(['plant_id' => Plant::first()->id, 'temperature' => 27, 'soil_moisture' => 70, 'ph' => 6.5, 'humidity' => null, 'monitored_at' => now()]);
        $old = $row->fuzzyInferences()->create(['plant_id' => $row->plant_id, 'rule_version' => 'v1.0-provisional', 'inference_source' => 'web_form', 'inference_status' => 'AVAILABLE', 'fuzzy_score' => 50, 'inferred_at' => now()]);
        $result = app(FuzzyPipelineService::class)->process($row, 'web_form');
        $this->assertSame('AVAILABLE', $result->inference_status);
        $this->assertSame(['temperature', 'soil_moisture', 'water_ph'], array_keys($result->input_snapshot));
        $this->assertEquals(50, $old->fresh()->fuzzy_score);
        $this->assertSame(2, $row->fuzzyInferences()->count());
    }

    public function test_old_fan_is_hidden_and_direct_control_is_rejected(): void
    {
        $fan = Actuator::create(['code' => 'FAN-01', 'name' => 'Retired fan', 'type' => Actuator::TYPE_EXHAUST_FAN, 'mode' => 'simulation', 'simulation_state' => 'OFF', 'is_enabled' => true]);
        $this->actingAs(User::where('role', 'admin')->first());
        $this->get(route('actuators.index'))->assertOk()->assertSee('PUMP-01')->assertDontSee('FAN-01');
        $this->postJson(route('actuators.control', $fan), ['command' => 'TURN_ON'])->assertUnprocessable();
        $this->assertSame('OFF', $fan->fresh()->simulation_state);
    }

    public function test_retired_alarm_is_excluded_but_remains_in_database(): void
    {
        $row = Monitoring::first();
        $alarm = MonitoringAlarm::create(['plant_id' => $row->plant_id, 'triggered_by_monitoring_id' => $row->id, 'parameter_key' => 'humidity', 'parameter_display_name' => 'Legacy humidity', 'sensor_value' => 99, 'threshold_min' => 60, 'threshold_max' => 85, 'severity' => 'warning', 'status' => 'active', 'violation_direction' => 'above_maximum', 'first_detected_at' => now()]);
        $this->assertFalse(MonitoringAlarm::active()->whereKey($alarm->id)->exists());
        $this->assertTrue(MonitoringAlarm::whereKey($alarm->id)->exists());
        $this->actingAs(User::first())->get(route('alarms.show', $alarm))->assertNotFound();
    }

    public function test_seeder_preserves_historical_ph_registry_and_thresholds(): void
    {
        $parameter = \App\Models\MonitoringParameter::where('parameter_key', 'ph')->firstOrFail();
        $parameter->update(['parameter_name' => 'pH Nutrisi/Media', 'minimum_value' => 6.1, 'maximum_value' => 7.1]);
        $before = \App\Models\MonitoringParameter::count();
        $this->seed(\Database\Seeders\MonitoringParameterSeeder::class);
        $this->assertSame($before, \App\Models\MonitoringParameter::count());
        $this->assertEquals(6.1, $parameter->fresh()->minimum_value);
        $this->assertEquals(7.1, $parameter->fresh()->maximum_value);
        $this->assertSame('pH Nutrisi/Media', $parameter->fresh()->getRawOriginal('parameter_name'));
    }

    public function test_dashboard_forms_and_reports_hide_retired_sensor_features(): void
    {
        $this->actingAs(User::where('role', 'admin')->first());
        foreach (['/dashboard', '/monitorings', '/monitorings/create', '/reports/monitoring', '/'] as $url) {
            $response = $this->get($url)->assertOk();
            foreach (['Kelembapan Udara', 'EC Nutrisi', 'Intensitas Cahaya', 'Suhu Nutrisi', 'Level Tandon', 'FAN-01'] as $label) $response->assertDontSee($label);
        }
    }
}
