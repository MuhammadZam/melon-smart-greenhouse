<?php

namespace Tests\Feature;

use App\Models\IotDevice;
use App\Models\Monitoring;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase16_2ApiSecurityTest extends TestCase
{
    public static function privateReads(): array
    {
        return array_map(fn ($url) => [$url], ['/api/plants','/api/plants/1','/api/monitorings','/api/monitorings/1','/api/monitorings/statistics','/api/dashboard/summary','/api/notifications/status']);
    }

    /** @dataProvider privateReads */
    public function test_guest_cannot_read_private_endpoints(string $url): void
    {
        $this->getJson($url)->assertUnauthorized();
    }

    /** @dataProvider privateReads */
    public function test_authorized_observer_can_read_internal_data(string $url): void
    {
        $this->actingAs(User::where('role', 'user')->first())->getJson($url)->assertOk();
    }

    public function test_token_only_device_cannot_read_private_data(): void
    {
        $this->authenticateDevice();
        foreach (self::privateReads() as [$url]) {
            $this->getJson($url)->assertForbidden()->assertJsonMissingPath('data');
        }
    }

    public function test_unknown_user_role_has_no_read_permission(): void
    {
        // The users.role DB enum is strict; use an authenticated unsaved role mutation.
        $user = User::where('role', 'user')->first();
        $user->role = 'unauthorized';
        $this->actingAs($user)->getJson('/api/monitorings')->assertForbidden();
    }

    public function test_authentication_runs_before_private_model_binding(): void
    {
        $this->getJson('/api/plants/999999')->assertUnauthorized();
        $this->getJson('/api/monitorings/999999')->assertUnauthorized();
        $this->actingAs(User::where('role', 'user')->first())
            ->postJson('/admin/integrations/google-sheets/devices/999999/revoke')->assertForbidden();
    }

    public function test_observer_cannot_use_admin_endpoints_but_admin_can(): void
    {
        IotDevice::createWithToken('ADMIN-TARGET', 'Existing protected target');
        $observer = User::where('role', 'user')->first();
        foreach (['/admin/thresholds','/admin/fuzzy/config','/admin/integrations/google-sheets','/admin/users','/admin/parameters','/admin/management-check'] as $url) {
            $this->actingAs($observer)->getJson($url)->assertForbidden();
        }
        foreach (['/admin/fuzzy/toggle','/admin/integrations/google-sheets/sync','/admin/integrations/google-sheets/devices','/admin/integrations/google-sheets/devices/1/revoke','/admin/integrations/google-sheets/devices/1/activate'] as $url) {
            $this->postJson($url)->assertForbidden();
        }
        $this->patchJson('/admin/thresholds/1', [])->assertForbidden();
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->getJson('/admin/management-check')->assertOk()->assertJsonPath('user.role', 'admin');
        $this->get('/admin/thresholds')->assertOk();
    }

    public function test_admin_session_cannot_substitute_for_device_token(): void
    {
        $this->actingAs(User::where('role', 'admin')->first())->postJson('/api/monitorings', $this->payload())
            ->assertUnauthorized()->assertJsonPath('error_code', 'DEVICE_TOKEN_REQUIRED');
    }

    public function test_valid_invalid_missing_and_revoked_device_auth(): void
    {
        $payload = $this->payload();
        $before = Monitoring::count();
        $this->postJson('/api/monitorings', $payload)->assertUnauthorized();
        $this->postJson('/api/monitorings', $payload, ['X-Device-Token' => 'invalid'])->assertUnauthorized();
        [$device, $token] = IotDevice::createWithToken('PHASE162-DEVICE', 'Authorized device');
        $this->postJson('/api/monitorings', $payload + ['device_id' => 'SPOOFED', 'source' => 'google_sheets'], ['Authorization' => 'Bearer '.$token])
            ->assertCreated()->assertJsonPath('data.device_id', $device->device_id)->assertJsonPath('data.source', 'api');
        $this->assertSame($before + 1, Monitoring::count());
        $device->revoke('Isolated test');
        $this->postJson('/api/monitorings', $payload, ['X-Device-Token' => $token])->assertForbidden();
        $this->assertSame($before + 1, Monitoring::count());
    }

    public function test_expired_database_session_is_unauthorized(): void
    {
        config(['session.driver' => 'database', 'session.lifetime' => 1]);
        $user = User::where('role', 'user')->first();
        $id = Str::random(40);
        DB::table('sessions')->insert([
            'id' => $id, 'user_id' => $user->id,
            'payload' => base64_encode(serialize([Auth::guard('web')->getName() => $user->id])),
            'last_activity' => now()->subMinutes(2)->timestamp,
        ]);
        $this->assertSame('', app('session')->driver()->getHandler()->read($id));
        $this->withCredentials()->withCookie(config('session.cookie'), $id)->getJson('/api/monitorings')->assertUnauthorized();
    }

    public function test_valid_encrypted_session_cookie_can_read_api_without_acting_as(): void
    {
        config(['session.driver' => 'database', 'session.lifetime' => 60]);
        $user = User::where('role', 'user')->first();
        $id = Str::random(40);
        DB::table('sessions')->insert([
            'id' => $id, 'user_id' => $user->id,
            'payload' => base64_encode(serialize([Auth::guard('web')->getName() => $user->id])),
            'last_activity' => now()->timestamp,
        ]);
        $this->assertGuest();
        $this->withCredentials()->withCookie(config('session.cookie'), $id)->getJson('/api/monitorings')
            ->assertOk()->assertJsonPath('success', true)->assertJsonStructure(['data' => [['id','plant_id']]]);
    }

    private function payload(): array
    {
        return ['plant_id' => Plant::first()->id, 'monitored_at' => now()->toDateTimeString(), 'temperature' => 27, 'humidity' => 70, 'soil_moisture' => 70, 'ph' => 6.5, 'plant_height' => 40, 'plant_density' => 60, 'condition' => 'optimal'];
    }
}
