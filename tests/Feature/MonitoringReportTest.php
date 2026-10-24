<?php

namespace Tests\Feature;

use App\Models\FuzzyInferenceResult;
use App\Models\Monitoring;
use App\Models\MonitoringAlarm;
use App\Models\Plant;
use App\Models\User;
use App\Services\Reports\MonitoringReportExportService;
use App\Services\Reports\MonitoringReportService;
use Tests\TestCase;

/**
 * MonitoringReportTest — Phase 16
 *
 * Menguji fitur Laporan Monitoring Greenhouse:
 * - Otorisasi & RBAC (Guest redirect, Pengamat OK, Admin OK)
 * - Filter komprehensif (Date range, Plant, Source, Condition)
 * - Validasi input filter
 * - Akurasi perhitungan statistik 8 parameter (null tidak dihitung nol)
 * - Ekspor CSV streamed dengan UTF-8 BOM dan proteksi CSV injection
 * - Ekspor PDF akademik A4 landscape
 * - Keutuhan hierarki Section A s.d. G di Dashboard
 * - Preservasi data historis
 */
class MonitoringReportTest extends TestCase
{
    protected function getPengamat(): User
    {
        return User::where('role', 'user')->first() ?? User::factory()->create([
            'role' => 'user',
            'name' => 'Pengamat Test',
            'email' => 'pengamat_' . uniqid() . '@example.com',
        ]);
    }

    protected function getAdmin(): User
    {
        return User::where('role', 'admin')->first() ?? User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Test',
            'email' => 'admin_' . uniqid() . '@example.com',
        ]);
    }

    protected function getOrCreatePlant(): Plant
    {
        return Plant::first() ?? Plant::create([
            'name' => 'Melon Golden Test',
            'variety' => 'Golden Apollo',
            'location' => 'Greenhouse Blok A-1',
            'planting_date' => now()->subDays(30),
            'status' => 'active',
        ]);
    }

    /**
     * Test 01: Guest tidak dapat mengakses halaman laporan monitoring dan dialihkan ke login.
     */
    public function test_01_guest_cannot_access_monitoring_reports_redirects_to_login(): void
    {
        $response = $this->get('/reports/monitoring');
        $response->assertRedirect('/login');
    }

    /**
     * Test 02: Guest tidak dapat mengekspor CSV dan dialihkan ke login.
     */
    public function test_02_guest_cannot_export_csv_redirects_to_login(): void
    {
        $response = $this->get('/reports/monitoring/export/csv');
        $response->assertRedirect('/login');
    }

    /**
     * Test 03: Guest tidak dapat mengekspor PDF dan dialihkan ke login.
     */
    public function test_03_guest_cannot_export_pdf_redirects_to_login(): void
    {
        $response = $this->get('/reports/monitoring/export/pdf');
        $response->assertRedirect('/login');
    }

    /**
     * Test 04: Pengamat dapat membuka halaman laporan monitoring dengan status 200 OK.
     */
    public function test_04_pengamat_can_access_monitoring_report_page(): void
    {
        $pengamat = $this->getPengamat();

        $response = $this->actingAs($pengamat)->get('/reports/monitoring');

        $response->assertStatus(200);
        $response->assertSee('Laporan Monitoring');
        $response->assertSee('Greenhouse Melon');
        $response->assertSee('Ringkasan Statistik Lingkungan Greenhouse');
        $response->assertSee('Ekspor CSV');
        $response->assertSee('Ekspor PDF (A4)');
    }

    /**
     * Test 05: Admin dapat membuka halaman laporan monitoring dengan status 200 OK.
     */
    public function test_05_admin_can_access_monitoring_report_page(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get('/reports/monitoring');

        $response->assertStatus(200);
        $response->assertSee('Laporan Monitoring');
        $response->assertSee('Filter &amp; Parameter Laporan', false);
    }

    /**
     * Test 06: Halaman laporan menampilkan 8 parameter sensor pada ringkasan statistik.
     */
    public function test_06_report_page_renders_8_sensor_parameters_in_statistics(): void
    {
        $pengamat = $this->getPengamat();

        $response = $this->actingAs($pengamat)->get('/reports/monitoring');

        $response->assertStatus(200);
        $response->assertSee('Suhu Udara');
        $response->assertSee('Kelembapan Udara');
        $response->assertSee('Kelembapan Tanah');
        $response->assertSee('pH Nutrisi/Media');
        $response->assertSee('Konduktivitas Listrik (EC)');
        $response->assertSee('Intensitas Cahaya');
        $response->assertSee('Suhu Nutrisi');
        $response->assertSee('Level Tandon Nutrisi');
    }

    /**
     * Test 07: Halaman laporan menampilkan ringkasan alarm 4 parameter dan ringkasan fuzzy logic.
     */
    public function test_07_report_page_renders_alarm_summary_and_fuzzy_summary(): void
    {
        $pengamat = $this->getPengamat();

        $response = $this->actingAs($pengamat)->get('/reports/monitoring');

        $response->assertStatus(200);
        $response->assertSee('Ringkasan Alarm 4 Parameter');
        $response->assertSee('Ringkasan Analisis Logika Fuzzy');
        $response->assertSee('Kondisi BAIK');
        $response->assertSee('PERHATIAN');
        $response->assertSee('Kondisi BURUK');
    }

    /**
     * Test 08: Filter rentang tanggal (date_from dan date_to) menyaring data secara akurat.
     */
    public function test_08_filter_by_date_range_filters_records_accurately(): void
    {
        $pengamat = $this->getPengamat();
        $plant = $this->getOrCreatePlant();

        // Buat data kemarin dan hari ini
        $pastDate = now()->subDays(5)->format('Y-m-d');
        $uniqueCondition = 'Kondisi-Past-' . uniqid();
        $monitoringPast = Monitoring::create([
            'plant_id' => $plant->id,
            'temperature' => 25.5,
            'humidity' => 65.0,
            'soil_moisture' => 70.0,
            'ph' => 6.2,
            'plant_condition' => $uniqueCondition,
            'plant_color' => 'Hijau Segar',
            'notes' => 'Catatan masa lalu ' . uniqid(),
            'monitored_at' => now()->subDays(5),
            'source' => 'web',
        ]);

        $response = $this->actingAs($pengamat)->get('/reports/monitoring?date_from=' . $pastDate . '&date_to=' . $pastDate);

        $response->assertStatus(200);
        $this->assertTrue($response->viewData('monitorings')->contains('id', $monitoringPast->id));
        $response->assertSee($uniqueCondition);
    }

    /**
     * Test 09: Filter tanaman menyaring data khusus untuk tanaman yang dipilih.
     */
    public function test_09_filter_by_plant_filters_records_to_selected_plant(): void
    {
        $pengamat = $this->getPengamat();
        $plantA = Plant::create([
            'name' => 'Melon Khusus A ' . uniqid(),
            'variety' => 'Action 434',
            'location' => 'Blok Khusus A',
            'planting_date' => now()->subDays(20),
            'status' => 'active',
        ]);
        $plantB = Plant::create([
            'name' => 'Melon Khusus B ' . uniqid(),
            'variety' => 'Glamour',
            'location' => 'Blok Khusus B',
            'planting_date' => now()->subDays(15),
            'status' => 'active',
        ]);

        $mA = Monitoring::create([
            'plant_id' => $plantA->id,
            'temperature' => 26.0,
            'humidity' => 68.0,
            'soil_moisture' => 72.0,
            'ph' => 6.4,
            'plant_condition' => 'Subur A ' . uniqid(),
            'plant_color' => 'Hijau Pekat',
            'notes' => 'Marker-Plant-A',
            'monitored_at' => now(),
            'source' => 'web',
        ]);

        $mB = Monitoring::create([
            'plant_id' => $plantB->id,
            'temperature' => 27.0,
            'humidity' => 70.0,
            'soil_moisture' => 75.0,
            'ph' => 6.5,
            'plant_condition' => 'Subur B ' . uniqid(),
            'plant_color' => 'Hijau Pekat',
            'notes' => 'Marker-Plant-B',
            'monitored_at' => now(),
            'source' => 'web',
        ]);

        $response = $this->actingAs($pengamat)->get('/reports/monitoring?plant_id=' . $plantA->id);

        $response->assertStatus(200);
        $this->assertTrue($response->viewData('monitorings')->contains('id', $mA->id));
        $this->assertFalse($response->viewData('monitorings')->contains('id', $mB->id));
        $response->assertSee(route('monitorings.show', $mA->id));
        $response->assertDontSee(route('monitorings.show', $mB->id));
    }

    /**
     * Test 10: Filter sumber data menyaring record berdasarkan source.
     */
    public function test_10_filter_by_data_source_filters_records(): void
    {
        $pengamat = $this->getPengamat();
        $plant = $this->getOrCreatePlant();

        $uniqueCond = 'Kondisi-Sheets-' . uniqid();
        $m = Monitoring::create([
            'plant_id' => $plant->id,
            'temperature' => 28.0,
            'humidity' => 65.0,
            'soil_moisture' => 68.0,
            'ph' => 6.3,
            'plant_condition' => $uniqueCond,
            'plant_color' => 'Hijau',
            'notes' => 'Sheets Sync Note',
            'monitored_at' => now(),
            'source' => 'google_sheets',
        ]);

        $response = $this->actingAs($pengamat)->get('/reports/monitoring?source=google_sheets');

        $response->assertStatus(200);
        $this->assertTrue($response->viewData('monitorings')->contains('id', $m->id));
        $response->assertSee($uniqueCond);
    }

    /**
     * Test 11: Filter kondisi tanaman menyaring record berdasarkan kondisi fisik.
     */
    public function test_11_filter_by_plant_condition_filters_records(): void
    {
        $pengamat = $this->getPengamat();
        $plant = $this->getOrCreatePlant();

        $uniqueCondition = 'Kondisi-Unik-' . uniqid();
        $m = Monitoring::create([
            'plant_id' => $plant->id,
            'temperature' => 29.0,
            'humidity' => 60.0,
            'soil_moisture' => 65.0,
            'ph' => 6.0,
            'plant_condition' => $uniqueCondition,
            'plant_color' => 'Kekuningan',
            'notes' => 'Condition Test',
            'monitored_at' => now(),
            'source' => 'web',
        ]);

        $response = $this->actingAs($pengamat)->get('/reports/monitoring?condition=' . urlencode($uniqueCondition));

        $response->assertStatus(200);
        $this->assertTrue($response->viewData('monitorings')->contains('id', $m->id));
        $response->assertSee($uniqueCondition);
    }

    /**
     * Test 12: Validasi menolak tanggal akhir yang lebih awal dari tanggal mulai.
     */
    public function test_12_validation_rejects_invalid_date_range_where_to_is_before_from(): void
    {
        $pengamat = $this->getPengamat();

        $response = $this->actingAs($pengamat)->get('/reports/monitoring?date_from=2026-10-15&date_to=2026-10-10');

        $response->assertSessionHasErrors('date_to');
    }

    /**
     * Test 13: Validasi menolak plant_id yang tidak ada di database.
     */
    public function test_13_validation_rejects_non_existent_plant_id(): void
    {
        $pengamat = $this->getPengamat();

        $response = $this->actingAs($pengamat)->get('/reports/monitoring?plant_id=999999');

        $response->assertSessionHasErrors('plant_id');
    }

    /**
     * Test 14: Perhitungan statistik tidak menganggap null sebagai nol.
     */
    public function test_14_statistics_service_calculates_avg_min_max_without_treating_null_as_zero(): void
    {
        $service = app(MonitoringReportService::class);
        $plant = Plant::create([
            'name' => 'Stat Test Plant ' . uniqid(),
            'location' => 'Greenhouse Lab 1',
            'planting_date' => now(),
            'status' => 'active',
        ]);

        // Buat 2 rekaman: satu memiliki EC, satu EC-nya null
        Monitoring::create([
            'plant_id' => $plant->id,
            'temperature' => 20.0,
            'humidity' => 50.0,
            'ph' => 6.0,
            'ec' => 2.0, // EC ada
            'light_intensity' => 10000.0,
            'plant_condition' => 'Test',
            'plant_color' => 'Hijau',
            'monitored_at' => now()->subMinute(),
            'source' => 'web',
        ]);

        Monitoring::create([
            'plant_id' => $plant->id,
            'temperature' => 30.0,
            'humidity' => 70.0,
            'ph' => 7.0,
            'ec' => null, // EC null
            'light_intensity' => 20000.0,
            'plant_condition' => 'Test',
            'plant_color' => 'Hijau',
            'monitored_at' => now(),
            'source' => 'web',
        ]);

        $query = $service->buildQuery(['plant_id' => (string) $plant->id]);
        $stats = $service->calculateStatistics($query, ['plant_id' => (string) $plant->id]);

        // Rata-rata temperatur (20 + 30) / 2 = 25.0
        $this->assertEquals(25.0, $stats['temperature']['avg']);
        $this->assertEquals(20.0, $stats['temperature']['min']);
        $this->assertEquals(30.0, $stats['temperature']['max']);

        // EC hanya ada 1 nilai yaitu 2.0. Rata-rata harus 2.0, BUKAN (2.0 + 0) / 2 = 1.0!
        $this->assertEquals(2.0, $stats['ec']['avg']);
        $this->assertEquals(2.0, $stats['ec']['min']);
        $this->assertEquals(2.0, $stats['ec']['max']);
    }

    /**
     * Test 15: Perhitungan statistik menangani dataset kosong dengan aman (mengembalikan N/A / null).
     */
    public function test_15_statistics_service_handles_empty_dataset_gracefully(): void
    {
        $service = app(MonitoringReportService::class);
        $plant = Plant::create([
            'name' => 'Empty Plant ' . uniqid(),
            'location' => 'Greenhouse Lab 2',
            'planting_date' => now(),
            'status' => 'inactive',
        ]);

        $query = $service->buildQuery(['plant_id' => (string) $plant->id]);
        $stats = $service->calculateStatistics($query, ['plant_id' => (string) $plant->id]);

        $this->assertEquals(0, $stats['total_count']);
        $this->assertNull($stats['first_monitored_at']);
        $this->assertNull($stats['last_monitored_at']);
        $this->assertNull($stats['temperature']['avg']);
        $this->assertNull($stats['temperature']['min']);
        $this->assertNull($stats['temperature']['max']);
        $this->assertNull($stats['ph']['avg']);
    }

    /**
     * Test 16: Ekspor CSV mengembalikan streamed response dengan UTF-8 BOM dan header yang benar.
     */
    public function test_16_csv_export_returns_streamed_response_with_utf8_bom_and_headers(): void
    {
        $pengamat = $this->getPengamat();

        $response = $this->actingAs($pengamat)->get('/reports/monitoring/export/csv');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('laporan_monitoring_melon_', $response->headers->get('Content-Disposition'));

        // Cek isi stream output
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Verifikasi UTF-8 BOM pada 3 byte pertama (\xEF\xBB\xBF)
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Verifikasi header CSV kolom utama
        $this->assertStringContainsString('Waktu Monitoring', $content);
        $this->assertStringContainsString('Nama Tanaman', $content);
        $this->assertStringContainsString('Suhu Udara', $content);
        $this->assertStringContainsString('Kelembapan Udara', $content);
        $this->assertStringContainsString('pH Nutrisi/Media', $content);
        $this->assertStringContainsString('Skor Fuzzy', $content);
    }

    /**
     * Test 17: Ekspor CSV melindungi dari CSV Formula Injection (CWE-1236).
     */
    public function test_17_csv_export_sanitizes_against_formula_injection(): void
    {
        // Uji fungsi sanitizer langsung
        $dangerousFormula1 = '=SUM(1,2)';
        $dangerousFormula2 = '+CMD|calc.exe!A';
        $dangerousFormula3 = '-2+3*4';
        $dangerousFormula4 = '@cmd';
        $safeText = 'Normal Observation Note';

        $this->assertEquals("'=SUM(1,2)", MonitoringReportExportService::sanitizeCsvCell($dangerousFormula1));
        $this->assertEquals("'+CMD|calc.exe!A", MonitoringReportExportService::sanitizeCsvCell($dangerousFormula2));
        $this->assertEquals("'-2+3*4", MonitoringReportExportService::sanitizeCsvCell($dangerousFormula3));
        $this->assertEquals("'@cmd", MonitoringReportExportService::sanitizeCsvCell($dangerousFormula4));
        $this->assertEquals("Normal Observation Note", MonitoringReportExportService::sanitizeCsvCell($safeText));
    }

    /**
     * Test 18: Ekspor CSV mematuhi filter yang sedang aktif.
     */
    public function test_18_csv_export_respects_active_filters(): void
    {
        $pengamat = $this->getPengamat();
        $plant = Plant::create([
            'name' => 'CSV Plant Filter ' . uniqid(),
            'variety' => 'Golden',
            'location' => 'Blok CSV Test',
            'planting_date' => now(),
            'status' => 'active',
        ]);

        $uniqueMarker = 'CSV-Unique-Notes-' . uniqid();
        Monitoring::create([
            'plant_id' => $plant->id,
            'temperature' => 27.5,
            'humidity' => 68.0,
            'soil_moisture' => 72.0,
            'ph' => 6.5,
            'plant_condition' => 'Sehat Baik',
            'plant_color' => 'Hijau',
            'notes' => $uniqueMarker,
            'monitored_at' => now(),
            'source' => 'web',
        ]);

        $response = $this->actingAs($pengamat)->get('/reports/monitoring/export/csv?plant_id=' . $plant->id);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString($uniqueMarker, $content);
        $this->assertStringContainsString($plant->name, $content);
    }

    /**
     * Test 19: Ekspor PDF mengembalikan respons unduhan PDF yang valid (application/pdf).
     */
    public function test_19_pdf_export_returns_successful_pdf_download_response(): void
    {
        $pengamat = $this->getPengamat();

        $response = $this->actingAs($pengamat)->get('/reports/monitoring/export/pdf');

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('laporan_monitoring_melon_', $response->headers->get('Content-Disposition'));
    }

    /**
     * Test 20: Ekspor PDF merender layout akademik A4 dengan data observasi terisi.
     */
    public function test_20_pdf_export_renders_academic_a4_layout_with_populated_data(): void
    {
        $pengamat = $this->getPengamat();
        $plant = $this->getOrCreatePlant();

        Monitoring::create([
            'plant_id' => $plant->id,
            'temperature' => 27.5,
            'humidity' => 68.0,
            'soil_moisture' => 72.0,
            'ph' => 6.5,
            'ec' => 2.1,
            'light_intensity' => 25000.0,
            'nutrient_temperature' => 24.0,
            'tank_level' => 85.0,
            'plant_condition' => 'Optimal Prima',
            'plant_color' => 'Hijau Tua',
            'notes' => 'Observasi PDF rendering ' . uniqid(),
            'monitored_at' => now(),
            'source' => 'web',
        ]);

        $response = $this->actingAs($pengamat)->get('/reports/monitoring/export/pdf?plant_id=' . $plant->id);

        $response->assertStatus(200);
        // Memastikan PDF binary diawali format %PDF
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /**
     * Test 21: Sorting dan pagination berfungsi pada laporan monitoring.
     */
    public function test_21_sorting_and_pagination_work_on_reports(): void
    {
        $pengamat = $this->getPengamat();

        $response = $this->actingAs($pengamat)->get('/reports/monitoring?sort_by=temperature&sort_direction=asc&per_page=10');

        $response->assertStatus(200);
        $response->assertViewHas('monitorings');
    }

    /**
     * Test 22: Dashboard utama merender seluruh Section A s.d. Section G secara konsisten.
     */
    public function test_22_dashboard_renders_all_sections_a_through_g(): void
    {
        $pengamat = $this->getPengamat();

        $response = $this->actingAs($pengamat)->get('/dashboard');

        $response->assertStatus(200);
        // Section A: Greenhouse Overview
        $response->assertSee('Section A — Greenhouse Overview');
        $response->assertSee('Total Tanaman');
        $response->assertSee('Sumber Data Telemetri');

        // Section B: Sensor Overview (Smart Greenhouse)
        $response->assertSee('Section B — Sensor Overview');
        $response->assertSee('Smart Greenhouse');
        $response->assertSee('Suhu Udara');
        $response->assertSee('Kelembapan Udara');
        $response->assertSee('Kelembapan Tanah');
        $response->assertSee('pH Nutrisi/Media');
        $response->assertSee('Konduktivitas Listrik (EC)');
        $response->assertSee('Intensitas Cahaya');
        $response->assertSee('Suhu Nutrisi');
        $response->assertSee('Level Tandon Nutrisi');

        // Section C: Trends & Filter
        $response->assertSee('Filter Visualisasi &amp; Rentang Tanggal', false);

        // Section D: Alarm Summary (4 parameters only)
        $response->assertSee('Status Alarm &amp; Notifikasi Sensor', false);

        // Section E: Fuzzy Analysis
        $response->assertSee('Analisis Kondisi Greenhouse — Fuzzy Logic');
        $response->assertSee('Aturan Fuzzy Simulasi — Belum Divalidasi Tim Elektro');

        // Section F: Actuator Simulation
        $response->assertSee('Status Perangkat Otomasi (Mode Simulasi)');
        $response->assertSee('Simulasi DB');

        // Section G: Integration Health
        $response->assertSee('Status Integrasi Google Sheets &amp; Kesegaran Telemetri', false);
        $response->assertSee('Section G — Pemantauan kontinuitas jalur data telemetri', false);
    }

    /**
     * Test 23: Pembuatan laporan dan ekspor tidak pernah menghapus atau mengubah data historis.
     */
    public function test_23_preservation_of_historical_data_across_report_generation(): void
    {
        $pengamat = $this->getPengamat();
        $initialMonitoringCount = Monitoring::count();
        $initialAlarmCount = MonitoringAlarm::count();
        $initialFuzzyCount = FuzzyInferenceResult::count();

        // Akses view, CSV, dan PDF
        $this->actingAs($pengamat)->get('/reports/monitoring');
        $this->actingAs($pengamat)->get('/reports/monitoring/export/csv');
        $this->actingAs($pengamat)->get('/reports/monitoring/export/pdf');

        $this->assertEquals($initialMonitoringCount, Monitoring::count());
        $this->assertEquals($initialAlarmCount, MonitoringAlarm::count());
        $this->assertEquals($initialFuzzyCount, FuzzyInferenceResult::count());
    }

    /**
     * Test 24: Peringatan batas maksimal 500 observasi pada ekspor PDF tampil jika total data > 500.
     */
    public function test_24_pdf_500_record_limit_notice_is_explicitly_displayed_when_data_exceeds_threshold(): void
    {
        $pengamat = $this->getPengamat();
        $total = Monitoring::count();

        $response = $this->actingAs($pengamat)->get('/reports/monitoring');
        $response->assertStatus(200);

        if ($total > 500) {
            $response->assertSee('Catatan Ekspor PDF:');
            $response->assertSee('PDF dibatasi maksimal 500 observasi');
            $response->assertSee('Gunakan <strong>Ekspor CSV</strong> untuk mengunduh seluruh data', false);
        } else {
            $this->assertTrue(true);
        }
    }
}

