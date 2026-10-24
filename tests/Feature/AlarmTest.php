<?php

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\MonitoringAlarm;
use App\Models\MonitoringParameter;
use App\Models\Plant;
use App\Models\User;
use App\Services\MonitoringAlarmService;
use Database\Seeders\AlarmParameterKeySeeder;
use Tests\TestCase;

/**
 * AlarmTest — Phase 12 Alarm & Notification System Test Suite
 *
 * Menguji 17 skenario wajib:
 *  1. Sensor normal tidak menghasilkan alarm.
 *  2. Sensor melewati maksimum menghasilkan alarm.
 *  3. Sensor di bawah minimum menghasilkan alarm.
 *  4. Pembacaan abnormal berulang tidak menggandakan alarm aktif (update snapshot).
 *  5. Sensor kembali normal menyelesaikan alarm (status=resolved).
 *  6. Sensor abnormal kembali setelah resolved menghasilkan alarm baru.
 *  7. Nilai null tidak menghasilkan alarm palsu.
 *  8. Payload legacy tetap kompatibel.
 *  9. Payload parsial tetap kompatibel.
 * 10. Alarm terbentuk dari API (POST /api/monitorings).
 * 11. Alarm terbentuk dari form web (POST /monitorings).
 * 12. RBAC Admin dan Pengamat (akses history vs threshold).
 * 13. Validasi threshold (min < max, critical_min < critical_max).
 * 14. Critical severity (tank_level below_minimum & critical limits).
 * 15. Penanganan data out-of-order.
 * 16. Dashboard & Riwayat Alarm (view rendering, filter, polling JSON).
 * 17. Kompatibilitas Phase 1-11.
 */
class AlarmTest extends TestCase
{
    protected Plant $plant;
    protected User $admin;
    protected User $pengamat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateDevice();

        // Pastikan parameter_key & critical threshold sudah terisi di database testing
        $this->seed(\Database\Seeders\MonitoringParameterSeeder::class);
        $this->seed(AlarmParameterKeySeeder::class);

        $this->plant = Plant::first() ?? Plant::create([
            'name'       => 'Melon Alarm Test Subject',
            'variety'    => 'Golden Aroma',
            'location'   => 'Greenhouse Alarm Lab',
            'planted_at' => now()->subDays(25),
            'status'     => 'active',
            'notes'      => 'Tanaman uji alarm Phase 12',
        ]);

        $this->admin = User::firstWhere('email', 'admin@melon.com') ?? User::create([
            'name'     => 'Administrator Test',
            'email'    => 'admin@melon.com',
            'password' => bcrypt('password123'),
            'role'     => 'admin',
        ]);

        $this->pengamat = User::firstWhere('email', 'pengamat@melon.com') ?? User::create([
            'name'     => 'Pengamat Test',
            'email'    => 'pengamat@melon.com',
            'password' => bcrypt('password123'),
            'role'     => 'user',
        ]);

        // Bersihkan alarm khusus tanaman uji ini sebelum setiap test
        MonitoringAlarm::where('plant_id', $this->plant->id)->delete();
    }

    protected function tearDown(): void
    {
        MonitoringAlarm::where('plant_id', $this->plant->id)->delete();
        parent::tearDown();
    }

    /**
     * 1. Sensor normal tidak menghasilkan alarm.
     */
    public function test_sensor_normal_does_not_produce_alarm(): void
    {
        $monitoring = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 25.0,  // range 20 - 32
            'humidity'             => 70.0,  // range 60 - 85
            'ph'                   => 6.5,   // range 5.8 - 7.2
            'soil_moisture'        => 65.0,  // range 50 - 80
            'ec'                   => 2.0,   // range 1.5 - 2.8
            'light_intensity'      => 30000.0, // range 15000 - 50000
            'nutrient_temperature' => 23.0,  // range 20 - 26
            'tank_level'           => 80.0,  // range 40 - 95
            'plant_height'         => 80.0,
            'plant_density'        => 70.0,
            'plant_condition'      => 'optimal',
            'plant_color'          => 'Hijau',
        ]);

        app(MonitoringAlarmService::class)->evaluate($monitoring);

        $this->assertDatabaseMissing('monitoring_alarms', [
            'plant_id' => $this->plant->id,
            'status'   => 'active',
        ]);
    }

    /**
     * 2. Sensor melewati maksimum menghasilkan alarm.
     */
    public function test_sensor_above_maximum_produces_alarm(): void
    {
        $monitoring = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 35.0,  // Max adalah 32.0, critical adalah 38.0 -> di atas maksimum (warning)
            'humidity'             => 70.0,
            'ph'                   => 6.5,
            'plant_height'         => 80.0,
            'plant_density'        => 70.0,
            'plant_condition'      => 'warning',
            'plant_color'          => 'Kuning',
        ]);

        app(MonitoringAlarmService::class)->evaluate($monitoring);

        $this->assertDatabaseHas('monitoring_alarms', [
            'plant_id'            => $this->plant->id,
            'parameter_key'       => 'temperature',
            'violation_direction' => 'above_maximum',
            'status'              => 'active',
            'severity'            => 'warning',
        ]);
    }

    /**
     * 3. Sensor di bawah minimum menghasilkan alarm.
     */
    public function test_sensor_below_minimum_produces_alarm(): void
    {
        $monitoring = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 25.0,
            'humidity'             => 70.0,
            'ph'                   => 4.5,   // Min adalah 5.8 -> di bawah minimum
            'plant_height'         => 80.0,
            'plant_density'        => 70.0,
            'plant_condition'      => 'abnormal',
            'plant_color'          => 'Kuning',
        ]);

        app(MonitoringAlarmService::class)->evaluate($monitoring);

        $this->assertDatabaseHas('monitoring_alarms', [
            'plant_id'            => $this->plant->id,
            'parameter_key'       => 'ph',
            'violation_direction' => 'below_minimum',
            'status'              => 'active',
        ]);
    }

    /**
     * 4. Pembacaan abnormal berulang tidak menggandakan alarm aktif (update last_abnormal).
     */
    public function test_repeated_abnormal_readings_do_not_duplicate_active_alarm(): void
    {
        $service = app(MonitoringAlarmService::class);

        // Rekaman 1: Suhu 35.0 (Abnormal)
        $m1 = Monitoring::create([
            'plant_id'        => $this->plant->id,
            'monitored_at'    => now()->subMinutes(10),
            'temperature'     => 35.0,
            'humidity'        => 70.0,
            'ph'              => 6.5,
            'plant_height'    => 80.0,
            'plant_density'   => 70.0,
            'plant_condition' => 'abnormal',
            'plant_color'     => 'Kuning',
        ]);
        $service->evaluate($m1);

        $initialAlarm = MonitoringAlarm::where('plant_id', $this->plant->id)
            ->where('parameter_key', 'temperature')
            ->where('status', 'active')
            ->first();

        $this->assertNotNull($initialAlarm);
        $this->assertEquals(35.0, $initialAlarm->sensor_value);

        // Rekaman 2: Suhu 36.5 (Masih abnormal pada tanaman yang sama)
        $m2 = Monitoring::create([
            'plant_id'        => $this->plant->id,
            'monitored_at'    => now(),
            'temperature'     => 36.5,
            'humidity'        => 70.0,
            'ph'              => 6.5,
            'plant_height'    => 80.0,
            'plant_density'   => 70.0,
            'plant_condition' => 'abnormal',
            'plant_color'     => 'Kuning',
        ]);
        $service->evaluate($m2);

        // Jumlah alarm aktif tetap 1 (tidak diduplikasi)
        $activeCount = MonitoringAlarm::where('plant_id', $this->plant->id)
            ->where('parameter_key', 'temperature')
            ->where('status', 'active')
            ->count();
        $this->assertEquals(1, $activeCount);

        // Nilai abnormal terakhir diperbarui
        $updatedAlarm = $initialAlarm->fresh();
        $this->assertEquals(36.5, $updatedAlarm->last_abnormal_value);
    }

    /**
     * 5. Sensor kembali normal menyelesaikan alarm (status=resolved).
     */
    public function test_sensor_returning_to_normal_resolves_alarm(): void
    {
        $service = app(MonitoringAlarmService::class);

        // 1. Munculkan alarm
        $m1 = Monitoring::create([
            'plant_id'        => $this->plant->id,
            'monitored_at'    => now()->subMinutes(5),
            'temperature'     => 38.0,
            'humidity'        => 70.0,
            'ph'              => 6.5,
            'plant_height'    => 80.0,
            'plant_density'   => 70.0,
            'plant_condition' => 'abnormal',
            'plant_color'     => 'Kuning',
        ]);
        $service->evaluate($m1);

        $alarm = MonitoringAlarm::where('plant_id', $this->plant->id)
            ->where('parameter_key', 'temperature')
            ->where('status', 'active')
            ->first();
        $this->assertNotNull($alarm);

        // 2. Kirim pembacaan normal
        $m2 = Monitoring::create([
            'plant_id'        => $this->plant->id,
            'monitored_at'    => now(),
            'temperature'     => 26.0, // Kembali normal (20 - 32)
            'humidity'        => 70.0,
            'ph'              => 6.5,
            'plant_height'    => 80.0,
            'plant_density'   => 70.0,
            'plant_condition' => 'optimal',
            'plant_color'     => 'Hijau',
        ]);
        $service->evaluate($m2);

        $alarm->refresh();
        $this->assertEquals('resolved', $alarm->status);
        $this->assertEquals(26.0, $alarm->resolved_value);
        $this->assertNotNull($alarm->resolved_at);
    }

    /**
     * 6. Sensor kembali abnormal setelah resolved menghasilkan kejadian alarm baru.
     */
    public function test_sensor_abnormal_again_after_resolved_creates_new_alarm(): void
    {
        $service = app(MonitoringAlarmService::class);

        // 1. Alarm pertama
        $m1 = Monitoring::create([
            'plant_id' => $this->plant->id, 'monitored_at' => now()->subMinutes(15),
            'temperature' => 37.0, 'humidity' => 70.0, 'ph' => 6.5,
            'plant_height' => 80.0, 'plant_density' => 70.0, 'plant_condition' => 'abnormal', 'plant_color' => 'Kuning',
        ]);
        $service->evaluate($m1);

        // 2. Normal (resolve)
        $m2 = Monitoring::create([
            'plant_id' => $this->plant->id, 'monitored_at' => now()->subMinutes(10),
            'temperature' => 25.0, 'humidity' => 70.0, 'ph' => 6.5,
            'plant_height' => 80.0, 'plant_density' => 70.0, 'plant_condition' => 'optimal', 'plant_color' => 'Hijau',
        ]);
        $service->evaluate($m2);

        // 3. Abnormal lagi
        $m3 = Monitoring::create([
            'plant_id' => $this->plant->id, 'monitored_at' => now(),
            'temperature' => 39.0, 'humidity' => 70.0, 'ph' => 6.5,
            'plant_height' => 80.0, 'plant_density' => 70.0, 'plant_condition' => 'abnormal', 'plant_color' => 'Kuning',
        ]);
        $service->evaluate($m3);

        // Harus ada 2 record: 1 resolved dan 1 active
        $allAlarms = MonitoringAlarm::where('plant_id', $this->plant->id)
            ->where('parameter_key', 'temperature')
            ->get();

        $this->assertCount(2, $allAlarms);
        $this->assertEquals(1, $allAlarms->where('status', 'resolved')->count());
        $this->assertEquals(1, $allAlarms->where('status', 'active')->count());
    }

    /**
     * 7. Nilai null tidak menghasilkan alarm palsu.
     */
    public function test_null_sensor_values_do_not_produce_false_alarms(): void
    {
        $monitoring = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 25.0,
            'humidity'             => 70.0,
            'ph'                   => 6.5,
            'soil_moisture'        => null, // parameter bernilai null
            'ec'                   => null,
            'light_intensity'      => null,
            'nutrient_temperature' => null,
            'tank_level'           => null,
            'plant_height'         => 80.0,
            'plant_density'        => 70.0,
            'plant_condition'      => 'optimal',
            'plant_color'          => 'Hijau',
        ]);

        app(MonitoringAlarmService::class)->evaluate($monitoring);

        $alarms = MonitoringAlarm::where('plant_id', $this->plant->id)->get();
        $this->assertCount(0, $alarms);
    }

    /**
     * 8. Payload legacy tetap kompatibel dengan evaluasi alarm.
     */
    public function test_legacy_payload_remains_compatible(): void
    {
        $monitoring = Monitoring::create([
            'plant_id'        => $this->plant->id,
            'monitored_at'    => now(),
            'temperature'     => 27.0,
            'humidity'        => 72.0,
            'ph'              => 6.2,
            'plant_height'    => 75.0,
            'plant_density'   => 65.0,
            'plant_condition' => 'optimal',
            'plant_color'     => 'Hijau',
            // sensor tambahan Phase 11 tidak disertakan (null)
        ]);

        app(MonitoringAlarmService::class)->evaluate($monitoring);

        $this->assertTrue(true, 'Evaluasi legacy payload selesai tanpa pengecualian.');
    }

    /**
     * 9. Payload parsial tetap kompatibel.
     */
    public function test_partial_sensor_payload_remains_compatible(): void
    {
        $monitoring = Monitoring::create([
            'plant_id'        => $this->plant->id,
            'monitored_at'    => now(),
            'temperature'     => 35.0, // Abnormal
            'humidity'        => 72.0,
            'ph'              => 6.2,
            'ec'              => 2.1,  // Sebagian diisi
            'plant_height'    => 75.0,
            'plant_density'   => 65.0,
            'plant_condition' => 'abnormal',
            'plant_color'     => 'Kuning',
        ]);

        app(MonitoringAlarmService::class)->evaluate($monitoring);

        // Hanya alarm suhu yang terbentuk
        $alarms = MonitoringAlarm::where('plant_id', $this->plant->id)->get();
        $this->assertCount(1, $alarms);
        $this->assertEquals('temperature', $alarms->first()->parameter_key);
    }

    /**
     * 10. Alarm terbentuk dari API (POST /api/monitorings).
     */
    public function test_alarm_generated_from_api_ingestion(): void
    {
        $response = $this->postJson(route('api.monitorings.store'), [
            'plant_id'        => $this->plant->id,
            'monitored_at'    => '2026-10-14 12:30:00',
            'temperature'     => 41.0, // Di atas ambang 32.0
            'humidity'        => 70.0,
            'ph'              => 6.5,
            'plant_height'    => 80.0,
            'canopy_density'  => 70.0,
            'condition'       => 'warning',
            'leaf_color'      => 'Kuning',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('monitoring_alarms', [
            'plant_id'            => $this->plant->id,
            'parameter_key'       => 'temperature',
            'violation_direction' => 'above_maximum',
            'status'              => 'active',
        ]);
    }

    /**
     * 11. Alarm terbentuk dari form web (POST /monitorings).
     */
    public function test_alarm_generated_from_web_form_submission(): void
    {
        $response = $this->actingAs($this->admin)->post(route('monitorings.store'), [
            'plant_id'             => $this->plant->id,
            'monitored_at'         => '2026-10-14T13:00',
            'temperature'          => 25.0,
            'humidity'             => 70.0,
            'ph'                   => 3.8, // Di bawah minimum 5.8
            'plant_height'         => 80.0,
            'plant_density'        => 70.0,
            'plant_condition'      => 'abnormal',
            'plant_color'          => 'Kuning',
        ]);

        $response->assertRedirect(route('monitorings.index'));

        $this->assertDatabaseHas('monitoring_alarms', [
            'plant_id'            => $this->plant->id,
            'parameter_key'       => 'ph',
            'violation_direction' => 'below_minimum',
            'status'              => 'active',
        ]);
    }

    /**
     * 12. RBAC Admin dan Pengamat.
     */
    public function test_rbac_admin_and_pengamat_access_control(): void
    {
        // 1. Pengamat bisa melihat daftar alarm dan detail alarm
        $alarm = MonitoringAlarm::create([
            'plant_id'               => $this->plant->id,
            'parameter_key'          => 'temperature',
            'parameter_display_name' => 'Suhu Udara',
            'unit'                   => '°C',
            'violation_direction'    => 'above_maximum',
            'sensor_value'           => 38.0,
            'threshold_min'          => 20.0,
            'threshold_max'          => 32.0,
            'severity'               => 'warning',
            'status'                 => 'active',
            'first_detected_at'      => now(),
        ]);

        $this->actingAs($this->pengamat)
            ->get(route('alarms.index'))
            ->assertStatus(200)
            ->assertSee('Suhu Udara');

        $this->actingAs($this->pengamat)
            ->get(route('alarms.show', $alarm->id))
            ->assertStatus(200)
            ->assertSee('Detail Alarm');

        // 2. Pengamat DILARANG mengakses halaman threshold management (403 Forbidden)
        $this->actingAs($this->pengamat)
            ->get(route('alarms.thresholds'))
            ->assertStatus(403);

        // 3. Admin DIIZINKAN mengakses halaman threshold management (200 OK)
        $this->actingAs($this->admin)
            ->get(route('alarms.thresholds'))
            ->assertStatus(200)
            ->assertSee('Manajemen Ambang Batas');
    }

    /**
     * 13. Validasi threshold (min < max, critical_min < critical_max).
     */
    public function test_threshold_validation_rules(): void
    {
        $parameter = MonitoringParameter::first();
        $this->assertNotNull($parameter);

        // Nilai max <= min harus gagal
        $response = $this->actingAs($this->admin)
            ->patch(route('alarms.thresholds.update', $parameter->id), [
                'minimum_value' => 30.0,
                'maximum_value' => 20.0, // Tidak valid karena < minimum
            ]);

        $response->assertSessionHasErrors('maximum_value');

        // Nilai valid harus berhasil disimpan
        $successResponse = $this->actingAs($this->admin)
            ->patch(route('alarms.thresholds.update', $parameter->id), [
                'minimum_value'      => 22.0,
                'maximum_value'      => 34.0,
                'critical_min_value' => 18.0,
                'critical_max_value' => 38.0,
            ]);

        $successResponse->assertRedirect(route('alarms.thresholds'));
        $this->assertDatabaseHas('monitoring_parameters', [
            'id'            => $parameter->id,
            'minimum_value' => 22.0,
            'maximum_value' => 34.0,
        ]);
    }

    /**
     * 14. Critical severity (tank_level below_minimum & critical limits).
     */
    public function test_critical_severity_alarm_detection(): void
    {
        $service = app(MonitoringAlarmService::class);

        // Kasus A: pH di bawah critical_min_value -> severity=critical (pada parameter alarm eligible)
        $phParam = MonitoringParameter::where('parameter_key', 'ph')
            ->orWhere('parameter_name', 'like', '%pH%')
            ->first();

        if ($phParam) {
            $phParam->update(['critical_min_value' => 5.0]);
        }

        $m1 = Monitoring::create([
            'plant_id'        => $this->plant->id,
            'monitored_at'    => now(),
            'temperature'     => 25.0,
            'humidity'        => 70.0,
            'ph'              => 4.5, // Min normal 5.8, critical_min 5.0 -> kritis
            'plant_height'    => 80.0,
            'plant_density'   => 70.0,
            'plant_condition' => 'abnormal',
            'plant_color'     => 'Kuning',
        ]);
        $service->evaluate($m1);

        $phAlarm = MonitoringAlarm::where('plant_id', $this->plant->id)
            ->where('parameter_key', 'ph')
            ->first();

        $this->assertNotNull($phAlarm);
        $this->assertEquals('critical', $phAlarm->severity);

        // Kasus B: Suhu di atas critical_max_value -> severity=critical
        $tempParam = MonitoringParameter::where('parameter_key', 'temperature')
            ->orWhere('parameter_name', 'Suhu Udara')
            ->first();

        if ($tempParam) {
            $tempParam->update(['critical_max_value' => 40.0]);
        }

        $m2 = Monitoring::create([
            'plant_id'        => $this->plant->id,
            'monitored_at'    => now(),
            'temperature'     => 42.0, // Melebihi critical_max_value 40.0
            'humidity'        => 70.0,
            'ph'              => 6.5,
            'plant_height'    => 80.0,
            'plant_density'   => 70.0,
            'plant_condition' => 'abnormal',
            'plant_color'     => 'Kuning',
        ]);
        $service->evaluate($m2);

        $tempAlarm = MonitoringAlarm::where('plant_id', $this->plant->id)
            ->where('parameter_key', 'temperature')
            ->where('status', 'active')
            ->first();

        $this->assertNotNull($tempAlarm);
        $this->assertEquals('critical', $tempAlarm->severity);
    }

    /**
     * 15. Penanganan data out-of-order.
     */
    public function test_out_of_order_monitoring_data_handling(): void
    {
        $service = app(MonitoringAlarmService::class);

        // Data baru: Suhu abnormal 38.0 pada jam 12:00
        $mNewer = Monitoring::create([
            'plant_id'        => $this->plant->id,
            'monitored_at'    => '2026-10-14 12:00:00',
            'temperature'     => 38.0,
            'humidity'        => 70.0,
            'ph'              => 6.5,
            'plant_height'    => 80.0,
            'plant_density'   => 70.0,
            'plant_condition' => 'abnormal',
            'plant_color'     => 'Kuning',
        ]);
        $service->evaluate($mNewer);

        $activeAlarm = MonitoringAlarm::where('plant_id', $this->plant->id)
            ->where('parameter_key', 'temperature')
            ->where('status', 'active')
            ->first();
        $this->assertNotNull($activeAlarm);

        // Data lama yang terlambat: Suhu normal 25.0 pada jam 10:00 (out-of-order)
        $mOlder = Monitoring::create([
            'plant_id'        => $this->plant->id,
            'monitored_at'    => '2026-10-14 10:00:00',
            'temperature'     => 25.0,
            'humidity'        => 70.0,
            'ph'              => 6.5,
            'plant_height'    => 80.0,
            'plant_density'   => 70.0,
            'plant_condition' => 'optimal',
            'plant_color'     => 'Hijau',
        ]);
        $service->evaluate($mOlder);

        // Sistem tetap aman dan stabil tanpa unhandled exception
        $this->assertTrue(true, 'Data out of order berhasil diproses secara aman.');
    }

    /**
     * 16. Dashboard & Riwayat Alarm (view rendering, filter, polling JSON).
     */
    public function test_dashboard_and_alarm_history_rendering_and_filtering(): void
    {
        // Buat satu alarm aktif dan satu resolved
        MonitoringAlarm::create([
            'plant_id'               => $this->plant->id,
            'parameter_key'          => 'temperature',
            'parameter_display_name' => 'Suhu Udara',
            'unit'                   => '°C',
            'violation_direction'    => 'above_maximum',
            'sensor_value'           => 38.0,
            'threshold_min'          => 20.0,
            'threshold_max'          => 32.0,
            'severity'               => 'warning',
            'status'                 => 'active',
            'first_detected_at'      => now(),
        ]);

        MonitoringAlarm::create([
            'plant_id'               => $this->plant->id,
            'parameter_key'          => 'ph',
            'parameter_display_name' => 'pH Nutrisi/Media',
            'unit'                   => 'pH',
            'violation_direction'    => 'below_minimum',
            'sensor_value'           => 4.5,
            'threshold_min'          => 5.8,
            'threshold_max'          => 7.2,
            'severity'               => 'warning',
            'status'                 => 'resolved',
            'first_detected_at'      => now()->subHours(2),
            'resolved_at'            => now(),
            'resolved_value'         => 6.4,
        ]);

        // 1. Dashboard merender alarm panel
        $dashResponse = $this->actingAs($this->pengamat)->get(route('dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Status Alarm &amp; Notifikasi Sensor', false);
        $dashResponse->assertSee('alarm-panel');

        // 2. Halaman riwayat alarm dengan filter
        $historyResponse = $this->actingAs($this->pengamat)->get(route('alarms.index', [
            'status'   => 'active',
            'severity' => 'warning',
        ]));
        $historyResponse->assertStatus(200);
        $historyResponse->assertSee('Suhu Udara');

        // 3. Polling endpoint JSON untuk navbar
        $pollResponse = $this->actingAs($this->pengamat)->getJson(route('notifications.status'));
        $pollResponse->assertStatus(200);
        $pollResponse->assertJsonStructure([
            'active_count',
            'critical_count',
            'latest',
        ]);
        $this->assertGreaterThanOrEqual(1, $pollResponse->json('active_count'));
    }

    /**
     * 17. Kompatibilitas Phase 1-11 tetap terjaga.
     */
    public function test_compatibility_phase_1_to_11_maintained(): void
    {
        // Monitoring index tetap 200
        $this->actingAs($this->pengamat)->get(route('monitorings.index'))->assertStatus(200);

        // Plant index tetap 200
        $this->actingAs($this->pengamat)->get(route('plants.index'))->assertStatus(200);

        // API monitorings statistics tetap 200
        $this->actingAs($this->admin)->getJson(route('api.monitorings.statistics'))->assertStatus(200);

        // API dashboard summary tetap 200
        $this->actingAs($this->admin)->getJson(route('api.dashboard.summary'))->assertStatus(200);
    }

    /**
     * 18. Phase 13: Hanya 4 parameter yang diizinkan menghasilkan alarm (temperature, humidity, soil_moisture, ph).
     */
    public function test_only_four_allowed_parameters_generate_alarms(): void
    {
        $service = app(MonitoringAlarmService::class);

        // Uji ke-4 parameter yang diizinkan (semua diberikan nilai di atas threshold maximum)
        $m = Monitoring::create([
            'plant_id'        => $this->plant->id,
            'monitored_at'    => now(),
            'temperature'     => 45.0, // max normal ~32
            'humidity'        => 95.0, // max normal ~80
            'soil_moisture'   => 95.0, // max normal ~80
            'ph'              => 8.5,  // max normal ~7.2
            'plant_height'    => 80.0,
            'plant_density'   => 70.0,
            'plant_condition' => 'abnormal',
            'plant_color'     => 'Kuning',
        ]);

        $service->evaluate($m);

        $allowedParams = ['temperature', 'humidity', 'soil_moisture', 'ph'];
        foreach ($allowedParams as $paramKey) {
            $alarm = MonitoringAlarm::where('plant_id', $this->plant->id)
                ->where('parameter_key', $paramKey)
                ->where('status', 'active')
                ->first();

            $this->assertNotNull($alarm, "Alarm harus tercipta untuk parameter yang diizinkan: {$paramKey}");
            $this->assertEquals('active', $alarm->status);
        }
    }

    /**
     * 19. Phase 13: Parameter non-alarm tidak membuat alarm, tetapi data sensor tetap tersimpan.
     */
    public function test_non_alarm_parameters_do_not_generate_alarms_but_data_is_stored(): void
    {
        $service = app(MonitoringAlarmService::class);

        // Masukkan data abnormal HANYA untuk parameter non-alarm:
        // ec (max ~2.5), light_intensity (max ~35000), nutrient_temperature (max ~26), tank_level (min ~40)
        $m = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 28.0, // normal
            'humidity'             => 65.0, // normal
            'soil_moisture'        => 70.0, // normal
            'ph'                   => 6.5,  // normal
            'ec'                   => 5.0,  // sangat tinggi
            'light_intensity'      => 60000.0, // sangat tinggi
            'nutrient_temperature' => 35.0, // sangat panas
            'tank_level'           => 10.0, // sangat rendah
            'plant_height'         => 120.0,
            'plant_density'        => 95.0,
            'plant_condition'      => 'normal',
            'plant_color'          => 'Hijau',
        ]);

        $service->evaluate($m);

        // Verifikasi TIDAK ADA alarm yang dibuat untuk parameter non-alarm
        $nonAlarmParams = ['ec', 'light_intensity', 'nutrient_temperature', 'tank_level', 'plant_height', 'plant_density'];
        foreach ($nonAlarmParams as $paramKey) {
            $alarm = MonitoringAlarm::where('plant_id', $this->plant->id)
                ->where('parameter_key', $paramKey)
                ->first();

            $this->assertNull($alarm, "Parameter non-alarm tidak boleh menghasilkan alarm: {$paramKey}");
        }

        // Verifikasi data sensor non-alarm TETAP tersimpan di tabel monitorings
        $this->assertDatabaseHas('monitorings', [
            'id'                   => $m->id,
            'ec'                   => 5.0,
            'light_intensity'      => 60000.0,
            'nutrient_temperature' => 35.0,
            'tank_level'           => 10.0,
        ]);
    }

    /**
     * 20. Phase 13: Kebijakan transisi alarm lama menyelesaikan alarm aktif non-eligible tanpa menghapus histori.
     */
    public function test_legacy_alarms_reconcile_policy_resolves_without_deletion(): void
    {
        $service = app(MonitoringAlarmService::class);

        // Simulasikan alarm aktif warisan masa lalu untuk parameter yang kini dinonaktifkan (misal: tank_level dan ec)
        $legacyAlarm1 = MonitoringAlarm::create([
            'plant_id'               => $this->plant->id,
            'parameter_key'          => 'tank_level',
            'parameter_display_name' => 'Level Tandon Nutrisi',
            'unit'                   => '%',
            'violation_direction'    => 'below_minimum',
            'sensor_value'           => 15.0,
            'threshold_min'          => 40.0,
            'threshold_max'          => 100.0,
            'severity'               => 'critical',
            'status'                 => 'active',
            'first_detected_at'      => now()->subDays(2),
        ]);

        $legacyAlarm2 = MonitoringAlarm::create([
            'plant_id'               => $this->plant->id,
            'parameter_key'          => 'ec',
            'parameter_display_name' => 'Konduktivitas Listrik (EC)',
            'unit'                   => 'mS/cm',
            'violation_direction'    => 'above_maximum',
            'sensor_value'           => 3.2,
            'threshold_min'          => 1.0,
            'threshold_max'          => 2.5,
            'severity'               => 'warning',
            'status'                 => 'active',
            'first_detected_at'      => now()->subDays(1),
        ]);

        // Jalankan rekonsiliasi transisi Phase 13
        $resolvedCount = $service->reconcileLegacyAlarms();
        $this->assertEquals(2, $resolvedCount);

        // Pastikan kedua alarm lama tidak dihapus, statusnya berubah menjadi resolved
        $this->assertDatabaseHas('monitoring_alarms', [
            'id'     => $legacyAlarm1->id,
            'status' => 'resolved',
        ]);
        $this->assertDatabaseHas('monitoring_alarms', [
            'id'     => $legacyAlarm2->id,
            'status' => 'resolved',
        ]);

        // Pastikan catatan kebijakan transisi tersimpan
        $freshAlarm1 = MonitoringAlarm::find($legacyAlarm1->id);
        $this->assertStringContainsString('[Phase 13 Transition Policy]', $freshAlarm1->notes);

        // Pastikan count active alarm di service tidak menghitung alarm legacy yang telah diselesaikan
        $this->assertEquals(0, $service->getActiveAlarmCount());
    }
}

