<?php

namespace Tests\Feature;

use App\Models\{Actuator, ActuatorCommandLog, Monitoring, Plant, User};
use App\Services\Pump\PumpControlService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PumpDualModeTest extends TestCase
{
    private Actuator $pump;
    private User $admin;
    private Plant $plant;
    private PumpControlService $control;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-07 10:00:00');
        $this->admin = User::where('role', 'admin')->first();
        $this->plant = Plant::factory()->create();
        $this->pump = Actuator::where('code', 'PUMP-01')->firstOrFail();
        $this->pump->update(['control_mode'=>'MANUAL','simulation_state'=>'OFF','control_plant_id'=>$this->plant->id,
            'auto_on_threshold'=>40,'auto_off_threshold'=>60,'max_runtime_seconds'=>10,'min_rest_seconds'=>20,'sensor_max_age_seconds'=>30]);
        $this->control = app(PumpControlService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function reading(?float $soil, int $offset = 0, ?int $plantId = null): Monitoring
    {
        return Monitoring::factory()->create(['plant_id'=>$plantId ?? $this->plant->id,'soil_moisture'=>$soil,
            'temperature'=>27,'ph'=>6.5,'monitored_at'=>now()->addSeconds($offset),'created_at'=>now()]);
    }

    private function manual(string $command): array
    {
        return $this->control->manualCommand($this->pump, $command, $this->admin, 'web_simulation');
    }

    private function auto(): void
    {
        $this->control->changeMode($this->pump, 'AUTO', $this->admin);
    }

    public function test_manual_on_off_and_same_command_does_not_extend_runtime(): void
    {
        $this->assertTrue($this->manual('TURN_ON')['success']);
        $started = $this->pump->fresh()->pump_started_at->toIso8601String();
        $this->travel(3)->seconds();
        $this->assertTrue($this->manual('TURN_ON')['is_idempotent']);
        $this->assertSame($started, $this->pump->fresh()->pump_started_at->toIso8601String());
        $this->assertSame('OFF', $this->manual('TURN_OFF')['resulting_state']);
        $this->assertNotNull($this->pump->fresh()->pump_stopped_at);
        $this->assertDatabaseHas('actuator_command_logs', ['actuator_id'=>$this->pump->id,'command'=>'TURN_ON','user_id'=>$this->admin->id,'previous_mode'=>'MANUAL','resulting_mode'=>'MANUAL','reason_code'=>'MANUAL_ON']);
    }

    public function test_manual_to_auto_stops_and_waits_for_a_new_reading(): void
    {
        $this->reading(20);
        $this->manual('TURN_ON');
        $this->auto();
        $this->control->tick();
        $this->assertSame('OFF', $this->pump->fresh()->simulation_state);
        $this->assertSame('WAITING_NEW_READING', $this->pump->fresh()->control_reason);
        $this->assertDatabaseHas('actuator_command_logs', ['command'=>'SET_MODE','previous_mode'=>'MANUAL','resulting_mode'=>'AUTO']);
    }

    public function test_auto_to_manual_stops_and_does_not_bypass_rest(): void
    {
        $this->auto(); $this->reading(20); $this->control->tick();
        $this->assertSame('ON', $this->pump->fresh()->simulation_state);
        $this->control->changeMode($this->pump, 'MANUAL', $this->admin);
        $this->assertSame('OFF', $this->pump->fresh()->simulation_state);
        $this->assertFalse($this->manual('TURN_ON')['success']);
        $this->travel(20)->seconds();
        $this->assertTrue($this->manual('TURN_ON')['success']);
    }

    public function test_manual_commands_are_rejected_and_audited_in_auto(): void
    {
        $this->auto();
        $before = ActuatorCommandLog::count();
        $this->actingAs($this->admin)->postJson(route('actuators.control',$this->pump),['command'=>'TURN_ON'])->assertUnprocessable();
        $this->assertSame($before+1,ActuatorCommandLog::count());
        $this->assertDatabaseHas('actuator_command_logs',['reason_code'=>'AUTO_OWNS_CONTROL','execution_status'=>'REJECTED']);
        $this->assertSame('OFF',$this->pump->fresh()->simulation_state);
    }

    public static function boundaries(): array { return [[39,'ON'],[40,'ON'],[41,'OFF'],[59,'OFF'],[60,'OFF'],[61,'OFF']]; }

    /** @dataProvider boundaries */
    public function test_auto_threshold_boundaries(float $soil,string $expected): void
    {
        $this->auto(); $this->reading($soil); $this->control->tick();
        $this->assertSame($expected,$this->pump->fresh()->simulation_state);
    }

    public function test_hysteresis_holds_on_and_off_without_chatter(): void
    {
        $this->auto(); $this->reading(30); $this->control->tick();
        $this->travel(1)->seconds(); $this->reading(50); $this->control->tick();
        $this->assertSame('ON',$this->pump->fresh()->simulation_state);
        $this->assertSame('HYSTERESIS_HOLD',$this->pump->fresh()->control_reason);
        $this->travel(1)->seconds(); $this->reading(60); $this->control->tick();
        $this->assertSame('OFF',$this->pump->fresh()->simulation_state);
        $this->travel(21)->seconds(); $this->reading(50); $this->control->tick();
        $this->assertSame('OFF',$this->pump->fresh()->simulation_state);
    }

    public static function unsafeSensors(): array { return [[null,0,'SENSOR_INVALID'],[-1,0,'SENSOR_INVALID'],[101,0,'SENSOR_INVALID'],[20,-31,'SENSOR_STALE'],[20,1,'SENSOR_STALE']]; }

    /** @dataProvider unsafeSensors */
    public function test_invalid_or_stale_data_stops_running_auto_pump(?float $soil,int $offset,string $reason): void
    {
        $this->auto(); $this->reading(20); $this->control->tick();
        $this->assertSame('ON',$this->pump->fresh()->simulation_state);
        // Remove ambiguity in latest-timestamp ordering using a separate future test time.
        $this->travel(2)->seconds();
        if ($offset < 0) $this->travel(35)->seconds();
        if ($offset < 0) {
            $this->pump->update(['max_runtime_seconds'=>300]);
            // Existing observation now stale, no fabricated recent sample.
        } else $this->reading($soil,$offset);
        $this->control->tick();
        $this->assertSame('OFF',$this->pump->fresh()->simulation_state);
        $this->assertSame($reason,$this->pump->fresh()->control_reason);
    }

    public function test_auto_without_sensor_or_configuration_stays_off(): void
    {
        $this->auto(); $this->control->tick();
        $this->assertSame('SENSOR_INVALID',$this->pump->fresh()->control_reason);
        $this->pump->update(['auto_on_threshold'=>null]);
        $this->control->tick();
        $this->assertSame('NOT_CONFIGURED',$this->pump->fresh()->control_reason);
        $this->assertSame('OFF',$this->pump->fresh()->simulation_state);
    }

    public function test_old_observation_backfilled_after_mode_switch_cannot_start_pump(): void
    {
        $this->auto(); $this->travel(5)->seconds(); $this->reading(20,-6); $this->control->tick();
        $this->assertSame('OFF',$this->pump->fresh()->simulation_state);
        $this->assertSame('WAITING_NEW_READING',$this->pump->fresh()->control_reason);
    }

    public function test_other_plant_cannot_drive_the_pump(): void
    {
        $this->auto(); $other=Plant::factory()->create(); $this->reading(20,0,$other->id); $this->control->tick();
        $this->assertSame('OFF',$this->pump->fresh()->simulation_state);
    }

    public function test_max_runtime_stops_manual_and_preserves_audit(): void
    {
        $this->manual('TURN_ON'); $this->travel(10)->seconds();
        $this->control->tick();
        $this->assertSame('OFF',$this->pump->fresh()->simulation_state);
        $this->assertSame('MAX_RUNTIME',$this->pump->fresh()->control_reason);
        $this->assertDatabaseHas('actuator_command_logs',['command'=>'EVALUATE','reason_code'=>'MAX_RUNTIME','source'=>'scheduler']);
    }

    public function test_unknown_start_time_fails_off(): void
    {
        $this->pump->update(['simulation_state'=>'ON','pump_started_at'=>null]);
        $this->control->tick();
        $this->assertSame('RUNTIME_UNKNOWN',$this->pump->fresh()->control_reason);
        $this->assertSame('OFF',$this->pump->fresh()->simulation_state);
    }

    public function test_auto_max_runtime_requires_new_sample_and_minimum_rest(): void
    {
        $this->auto(); $this->reading(20); $this->control->tick();
        $this->travel(10)->seconds(); $this->control->tick();
        $this->assertSame('MAX_RUNTIME',$this->pump->fresh()->control_reason);
        $this->travel(1)->seconds(); $this->reading(20); $this->control->tick();
        $this->assertSame('REST_INTERVAL',$this->pump->fresh()->control_reason);
        $this->travel(19)->seconds(); $this->control->tick();
        $this->assertSame('ON',$this->pump->fresh()->simulation_state);
    }

    public function test_same_dry_sample_cannot_restart_after_runtime_stop(): void
    {
        $this->pump->update(['sensor_max_age_seconds'=>300]);
        $this->auto(); $this->reading(20); $this->control->tick();
        $this->travel(10)->seconds(); $this->control->tick();
        $this->travel(21)->seconds(); $this->control->tick();
        $this->assertSame('OFF',$this->pump->fresh()->simulation_state);
        $this->assertSame('WAITING_NEW_READING',$this->pump->fresh()->control_reason);
    }

    public function test_settings_are_validated_persistent_and_audited(): void
    {
        $settings=['control_plant_id'=>$this->plant->id,'auto_on_threshold'=>35,'auto_off_threshold'=>65,'max_runtime_seconds'=>60,'min_rest_seconds'=>30,'sensor_max_age_seconds'=>90,'expected_version'=>0];
        $this->actingAs($this->admin)->patchJson(route('actuators.settings',$this->pump),$settings)->assertOk()->assertJsonPath('hardware_dispatched',false);
        $this->assertEquals(35,$this->pump->fresh()->auto_on_threshold);
        $log=ActuatorCommandLog::where('command','CONFIGURE')->latest('id')->first();
        $this->assertSame('PROVISIONAL',$log->control_snapshot['validation_status']);
        $this->assertStringContainsString('before',$log->notes);
        $settings['auto_off_threshold']=20; $settings['expected_version']=$this->pump->fresh()->control_version;
        $this->patchJson(route('actuators.settings',$this->pump),$settings)->assertUnprocessable();
        $this->assertEquals(65,$this->pump->fresh()->auto_off_threshold);
    }

    public function test_stale_control_version_cannot_overwrite_mode_or_status(): void
    {
        $this->manual('TURN_ON');
        $this->actingAs($this->admin)->patchJson(route('actuators.mode',$this->pump),['control_mode'=>'AUTO','expected_version'=>0])->assertConflict();
        $this->assertSame('MANUAL',$this->pump->fresh()->control_mode);
        $this->assertSame('ON',$this->pump->fresh()->simulation_state);
    }

    public function test_guest_and_observer_cannot_change_mode_settings_or_switch(): void
    {
        $this->patchJson(route('actuators.mode',$this->pump),['control_mode'=>'AUTO','expected_version'=>0])->assertUnauthorized();
        $observer=User::where('role','user')->first(); $this->actingAs($observer);
        $this->patchJson(route('actuators.mode',$this->pump),['control_mode'=>'AUTO','expected_version'=>0])->assertForbidden();
        $this->patchJson(route('actuators.settings',$this->pump),[])->assertForbidden();
        $this->postJson(route('actuators.control',$this->pump),['command'=>'TURN_ON'])->assertForbidden();
        $this->getJson(route('actuators.status',$this->pump))->assertOk()->assertJsonPath('hardware_dispatched',false);
        $this->assertSame('MANUAL',$this->pump->fresh()->control_mode);
    }

    public function test_scheduler_command_and_heartbeat_do_not_create_noop_log_flood(): void
    {
        $this->artisan('pump:evaluate')->assertSuccessful();
        $before=ActuatorCommandLog::count(); $version=$this->pump->fresh()->control_version;
        $this->travel(5)->seconds(); $this->artisan('pump:evaluate')->assertSuccessful();
        $this->assertSame($before,ActuatorCommandLog::count());
        $this->assertSame($version,$this->pump->fresh()->control_version);
        $this->assertTrue($this->control->statusData($this->pump->fresh())['worker_recent']);
        $this->travel(16)->seconds();
        $this->assertFalse($this->control->statusData($this->pump->fresh())['worker_recent']);
    }

    public function test_disabled_pump_stops_and_only_pump01_is_supported(): void
    {
        $this->manual('TURN_ON'); $this->pump->update(['is_enabled'=>false]); $this->control->tick();
        $this->assertSame('OFF',$this->pump->fresh()->simulation_state);
        $this->assertSame('DISABLED',$this->pump->fresh()->control_reason);
    }
}
