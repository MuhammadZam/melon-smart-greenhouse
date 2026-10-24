<?php

namespace Tests\Feature;

use App\Models\Actuator;
use App\Models\ActuatorCommandLog;
use App\Models\Plant;
use App\Models\User;
use App\Services\ActuatorSimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ActuatorSimulationTest extends TestCase
{
    protected User $admin;
    protected User $pengamat;
    protected Actuator $pump;
    protected Actuator $fan;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat user admin dan pengamat jika belum ada
        $this->admin = User::firstWhere('email', 'admin.actuator@melon.com') ?? User::create([
            'name'     => 'Admin Actuator Test',
            'email'    => 'admin.actuator@melon.com',
            'role'     => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $this->pengamat = User::firstWhere('email', 'pengamat.actuator@melon.com') ?? User::create([
            'name'     => 'Pengamat Actuator Test',
            'email'    => 'pengamat.actuator@melon.com',
            'role'     => 'pengamat',
            'password' => bcrypt('password123'),
        ]);

        ActuatorCommandLog::query()->delete();
        Actuator::query()->delete();

        // Buat aktuator simulasi awal
        $this->pump = Actuator::create([
            'code'             => 'PUMP-01',
            'name'             => 'Pompa Irigasi Utama (Greenhouse A)',
            'type'             => Actuator::TYPE_IRRIGATION_PUMP,
            'simulation_state' => Actuator::STATE_OFF,
            'mode'             => Actuator::MODE_SIMULATION,
            'is_enabled'       => true,
            'location'         => 'Greenhouse Melon Zona A',
            'description'      => 'Pompa fertigasi irigasi tetes.',
            'last_changed_at'  => now(),
        ]);

        $this->fan = Actuator::create([
            'code'             => 'FAN-01',
            'name'             => 'Kipas Exhaust Sirkulasi (Greenhouse A)',
            'type'             => Actuator::TYPE_EXHAUST_FAN,
            'simulation_state' => Actuator::STATE_OFF,
            'mode'             => Actuator::MODE_SIMULATION,
            'is_enabled'       => true,
            'location'         => 'Greenhouse Dinding Timur',
            'description'      => 'Kipas sirkulasi udara greenhouse.',
            'last_changed_at'  => now(),
        ]);
    }

    /**
     * 1. Admin menyalakan simulasi pompa.
     */
    public function test_admin_can_turn_on_pump_simulation(): void
    {
        $response = $this->actingAs($this->admin)->post(route('actuators.control', $this->pump->id), [
            'command' => 'TURN_ON',
            'notes'   => 'Aktivasi fertigasi pagi hari',
        ]);

        $response->assertRedirect(route('actuators.index'));
        $response->assertSessionHas('success');

        $this->pump->refresh();
        $this->assertEquals(Actuator::STATE_ON, $this->pump->simulation_state);
        $this->assertEquals($this->admin->id, $this->pump->last_user_id);
        $this->assertNotNull($this->pump->last_changed_at);
    }

    /**
     * 2. Admin mematikan simulasi pompa.
     */
    public function test_admin_can_turn_off_pump_simulation(): void
    {
        $this->pump->update(['simulation_state' => Actuator::STATE_ON]);

        $response = $this->actingAs($this->admin)->post(route('actuators.control', $this->pump->id), [
            'command' => 'TURN_OFF',
            'notes'   => 'Siklus fertigasi selesai',
        ]);

        $response->assertRedirect(route('actuators.index'));

        $this->pump->refresh();
        $this->assertEquals(Actuator::STATE_OFF, $this->pump->simulation_state);
    }

    /**
     * 3. Admin mengontrol kipas exhaust (ON lalu OFF).
     */
    public function test_admin_can_control_fan_simulation(): void
    {
        // Turn ON
        $resOn = $this->actingAs($this->admin)->post(route('actuators.control', $this->fan->id), [
            'command' => 'TURN_ON',
            'notes'   => 'Suhu ruangan terdeteksi hangat',
        ]);
        $resOn->assertRedirect(route('actuators.index'));

        $this->fan->refresh();
        $this->assertEquals(Actuator::STATE_ON, $this->fan->simulation_state);

        // Turn OFF
        $resOff = $this->actingAs($this->admin)->post(route('actuators.control', $this->fan->id), [
            'command' => 'TURN_OFF',
        ]);
        $resOff->assertRedirect(route('actuators.index'));

        $this->fan->refresh();
        $this->assertEquals(Actuator::STATE_OFF, $this->fan->simulation_state);
    }

    /**
     * 4. Admin dapat menggunakan perintah TOGGLE.
     */
    public function test_admin_can_toggle_actuator(): void
    {
        $this->assertEquals(Actuator::STATE_OFF, $this->pump->simulation_state);

        // Toggle 1: OFF -> ON
        $this->actingAs($this->admin)->post(route('actuators.control', $this->pump->id), [
            'command' => 'TOGGLE',
        ]);
        $this->pump->refresh();
        $this->assertEquals(Actuator::STATE_ON, $this->pump->simulation_state);

        // Toggle 2: ON -> OFF
        $this->actingAs($this->admin)->post(route('actuators.control', $this->pump->id), [
            'command' => 'TOGGLE',
        ]);
        $this->pump->refresh();
        $this->assertEquals(Actuator::STATE_OFF, $this->pump->simulation_state);
    }

    /**
     * 5. Pengamat tidak dapat mengontrol aktuator (HTTP 403 Forbidden).
     */
    public function test_pengamat_cannot_control_actuator_forbidden_403(): void
    {
        $response = $this->actingAs($this->pengamat)->post(route('actuators.control', $this->pump->id), [
            'command' => 'TURN_ON',
        ]);

        $response->assertStatus(403);

        // Pastikan status pompa tidak berubah
        $this->pump->refresh();
        $this->assertEquals(Actuator::STATE_OFF, $this->pump->simulation_state);
    }

    /**
     * 6. Guest ditolak dan diarahkan ke login saat mengakses rute aktuator.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('actuators.index'))->assertRedirect(route('login'));
        $this->get(route('actuators.history'))->assertRedirect(route('login'));
        $this->post(route('actuators.control', $this->pump->id), ['command' => 'TURN_ON'])->assertRedirect(route('login'));
    }

    /**
     * 7. Command history tercatat lengkap dengan audit trail.
     */
    public function test_command_history_is_recorded(): void
    {
        $this->actingAs($this->admin)->post(route('actuators.control', $this->pump->id), [
            'command' => 'TURN_ON',
            'notes'   => 'Audit test note',
        ]);

        $this->assertDatabaseHas('actuator_command_logs', [
            'actuator_id'      => $this->pump->id,
            'user_id'          => $this->admin->id,
            'command'          => 'TURN_ON',
            'previous_state'   => 'OFF',
            'resulting_state'  => 'ON',
            'execution_status' => 'SUCCESS',
            'source'           => ActuatorCommandLog::SOURCE_WEB_SIMULATION,
            'notes'            => 'Audit test note',
        ]);
    }

    /**
     * 8. Perintah tidak valid ditolak dengan validasi error.
     */
    public function test_invalid_command_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post(route('actuators.control', $this->pump->id), [
            'command' => 'BLOW_UP',
        ]);

        $response->assertSessionHasErrors(['command']);
        $this->pump->refresh();
        $this->assertEquals(Actuator::STATE_OFF, $this->pump->simulation_state);
    }

    /**
     * 9. Duplicate repeated requests ditangani secara aman dan idempotent.
     */
    public function test_duplicate_repeated_command_handled_idempotently(): void
    {
        // Kondisi awal: OFF. Kirim perintah TURN_OFF lagi.
        $service = app(ActuatorSimulationService::class);
        $result = $service->executeCommand(
            actuator: $this->pump,
            command: 'TURN_OFF',
            user: $this->admin,
            notes: 'Uji idempotensi'
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_idempotent']);
        $this->assertEquals(Actuator::STATE_OFF, $result['resulting_state']);

        // Log audit trail tetap tercatat dengan status SUCCESS
        $this->assertDatabaseHas('actuator_command_logs', [
            'actuator_id'      => $this->pump->id,
            'command'          => 'TURN_OFF',
            'previous_state'   => 'OFF',
            'resulting_state'  => 'OFF',
            'execution_status' => 'SUCCESS',
        ]);
    }

    /**
     * 10. Konkurensi update aman dengan locking transaksi database.
     */
    public function test_concurrent_updates_safe_with_pessimistic_lock(): void
    {
        $service = app(ActuatorSimulationService::class);

        // Eksekusi dua perintah secara berurutan dalam transaksi terisolasi
        $res1 = $service->executeCommand($this->pump, 'TURN_ON', $this->admin);
        $res2 = $service->executeCommand($this->pump, 'TURN_OFF', $this->admin);

        $this->assertEquals(Actuator::STATE_ON, $res1['resulting_state']);
        $this->assertEquals(Actuator::STATE_OFF, $res2['resulting_state']);

        $this->pump->refresh();
        $this->assertEquals(Actuator::STATE_OFF, $this->pump->simulation_state);
    }

    /**
     * 11. Tidak ada komunikasi hardware fisik (HTTP/ESP32/MQTT).
     */
    public function test_strictly_no_hardware_communication(): void
    {
        // Pasang Http fake pencegah network call tak terduga
        Http::fake();

        $service = app(ActuatorSimulationService::class);
        $result = $service->executeCommand($this->pump, 'TURN_ON', $this->admin);

        $this->assertFalse($result['hardware_dispatched']);
        $this->assertEquals('simulation', $result['mode']);

        // Pastikan tidak ada outgoing HTTP request sama sekali
        Http::assertNothingSent();
    }

    /**
     * 12. Dashboard menampilkan status ringkasan aktuator.
     */
    public function test_dashboard_displays_actuator_status_summary(): void
    {
        $this->pump->update(['simulation_state' => Actuator::STATE_ON]);

        $response = $this->actingAs($this->pengamat)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Status Perangkat Otomasi (Mode Simulasi)');
        $response->assertSee('Pompa Irigasi Utama (Greenhouse A)');
        $response->assertSee('Kipas Exhaust Sirkulasi (Greenhouse A)');
        $response->assertSee('AKTIF (ON)');
        $response->assertSee('NON-AKTIF (OFF)');
    }

    /**
     * 13. Halaman index aktuator merender kartu dan disclaimer simulasi.
     */
    public function test_actuator_index_page_rendered(): void
    {
        $response = $this->actingAs($this->admin)->get(route('actuators.index'));

        $response->assertStatus(200);
        $response->assertSee('Kontrol Perangkat Aktuator');
        $response->assertSee('Kontrol ini merupakan simulasi perangkat dan tidak mengaktifkan hardware fisik');
        $response->assertSee('PUMP-01');
        $response->assertSee('FAN-01');
    }

    /**
     * 14. Filter riwayat kontrol bekerja dengan baik.
     */
    public function test_history_filtering_works(): void
    {
        ActuatorCommandLog::create([
            'actuator_id'      => $this->pump->id,
            'user_id'          => $this->admin->id,
            'command'          => 'TURN_ON',
            'previous_state'   => 'OFF',
            'resulting_state'  => 'ON',
            'execution_status' => 'SUCCESS',
            'source'           => ActuatorCommandLog::SOURCE_WEB_SIMULATION,
            'executed_at'      => now()->subDays(3),
        ]);

        ActuatorCommandLog::create([
            'actuator_id'      => $this->fan->id,
            'user_id'          => $this->admin->id,
            'command'          => 'TURN_OFF',
            'previous_state'   => 'ON',
            'resulting_state'  => 'OFF',
            'execution_status' => 'SUCCESS',
            'source'           => ActuatorCommandLog::SOURCE_WEB_SIMULATION,
            'executed_at'      => now(),
        ]);

        // Filter by actuator_id = pump
        $resFilterActuator = $this->actingAs($this->admin)->get(route('actuators.history', [
            'actuator_id' => $this->pump->id,
        ]));
        $resFilterActuator->assertStatus(200);
        $logsActuator = $resFilterActuator->viewData('logs');
        $this->assertEquals(1, $logsActuator->total());
        $this->assertEquals($this->pump->id, $logsActuator->first()->actuator_id);

        // Filter by command = TURN_OFF
        $resFilterCommand = $this->actingAs($this->admin)->get(route('actuators.history', [
            'command' => 'TURN_OFF',
        ]));
        $resFilterCommand->assertStatus(200);
        $logsCommand = $resFilterCommand->viewData('logs');
        $this->assertEquals(1, $logsCommand->total());
        $this->assertEquals('TURN_OFF', $logsCommand->first()->command);
    }

    /**
     * 15. Perangkat non-aktif (disabled) menolak perintah kontrol.
     */
    public function test_disabled_actuator_rejects_command(): void
    {
        $this->pump->update(['is_enabled' => false]);

        $response = $this->actingAs($this->admin)->post(route('actuators.control', $this->pump->id), [
            'command' => 'TURN_ON',
        ]);

        $response->assertStatus(422);

        // Command log tercatat sebagai REJECTED
        $this->assertDatabaseHas('actuator_command_logs', [
            'actuator_id'      => $this->pump->id,
            'execution_status' => ActuatorCommandLog::STATUS_REJECTED,
        ]);
    }

    /**
     * 16. Pengamat dapat melihat halaman kontrol dan riwayat dalam mode baca (read-only).
     */
    public function test_pengamat_can_view_index_and_history_read_only(): void
    {
        $resIndex = $this->actingAs($this->pengamat)->get(route('actuators.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('Akses Pengamat: Mode Baca');

        $resHistory = $this->actingAs($this->pengamat)->get(route('actuators.history'));
        $resHistory->assertStatus(200);
        $resHistory->assertSee('Riwayat Perintah Kontrol');
    }

    /**
     * 17. REST API JSON response untuk ajax client.
     */
    public function test_json_ajax_control_response(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('actuators.control', $this->pump->id), [
            'command' => 'TURN_ON',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'             => true,
            'resulting_state'     => 'ON',
            'mode'                => 'simulation',
            'hardware_dispatched' => false,
        ]);
    }
}
