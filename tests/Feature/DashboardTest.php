<?php

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\MonitoringParameter;
use App\Models\Plant;
use App\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    /**
     * Test 1: Tamu (Guest) yang belum login tidak dapat membuka dashboard dan dialihkan ke login.
     */
    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Test 2: Pengamat (User biasa) yang telah terautentikasi dapat membuka dashboard.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::where('role', 'user')->first();
        if (!$user) {
            $user = User::factory()->create(['role' => 'user']);
        }

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Ringkasan Pemantauan');
        $response->assertSee('Tanaman Melon');
        $response->assertSee('Mode Pengamat (User)');
        $response->assertSee('Total Tanaman');
        $response->assertSee('Total Monitoring');
        $response->assertSee('Monitoring Terakhir');
        $response->assertSee('Status Tanaman');
        $response->assertSee('tempChart');
        $response->assertSee('phChart');
        $response->assertSee('humidityChart');
        $response->assertSee('Data Pengamatan Terkini');
    }

    /**
     * Test 3: Pengamat (User biasa) TIDAK dapat melihat menu/panel administrasi.
     */
    public function test_regular_user_cannot_see_admin_section_or_menu(): void
    {
        $user = User::where('role', 'user')->first();
        if (!$user) {
            $user = User::factory()->create(['role' => 'user']);
        }

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee('Panel Administrasi (Khusus Admin)');
        $response->assertDontSee('ADMIN ONLY');
    }

    /**
     * Test 4: Administrator dapat melihat seluruh ringkasan serta panel administrasi dan menu admin.
     */
    public function test_admin_can_access_dashboard_and_see_admin_section(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::factory()->create(['role' => 'admin']);
        }

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Mode Administrator');
        $response->assertSee('Administrasi');
        $response->assertSee('Panel Administrasi (Khusus Admin)');
        $response->assertSee('ADMIN ONLY');
        $response->assertSee('Parameter');
    }

    /**
     * Test 5: Summary cards menampilkan data akurat yang bersumber langsung dari database MySQL.
     */
    public function test_dashboard_displays_accurate_counts_from_database(): void
    {
        $user = User::first();
        $totalPlants = Plant::count();
        $totalMonitorings = Monitoring::count();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee((string) $totalPlants);
        $response->assertSee((string) $totalMonitorings);
    }

    /**
     * Test 6: Tabel monitoring terbaru menampilkan baris observasi terkini dari database.
     */
    public function test_dashboard_displays_recent_monitoring_data(): void
    {
        $user = User::first();
        $latest = Monitoring::with('plant')->latest('monitored_at')->first();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        if ($latest) {
            $response->assertSee($latest->plant->name);
            $response->assertSee(number_format($latest->temperature, 1) . '°C');
        }
    }

    /**
     * Test 7: Filter dropdown tanaman berhasil memfilter data tanpa error.
     */
    public function test_dashboard_plant_filter_works_properly(): void
    {
        $user = User::first();
        $plant = Plant::first();

        $response = $this->actingAs($user)->get('/dashboard?plant_id=' . ($plant ? $plant->id : 1));

        $response->assertStatus(200);
        $response->assertSee('Ringkasan Pemantauan');
    }

    /**
     * Test 8: Rute navigasi terstruktur (/monitorings, /plants, /charts, /history) mengalihkan ke dashboard.
     */
    public function test_structured_navigation_routes_redirect_to_dashboard(): void
    {
        $user = User::first();

        $this->actingAs($user)->get('/monitorings')->assertStatus(200);
        $this->actingAs($user)->get('/plants')->assertStatus(200);
        $this->actingAs($user)->get('/charts')->assertRedirect('/dashboard#charts');
        $this->actingAs($user)->get('/history')->assertRedirect('/dashboard#recent-table');
    }

    /**
     * Test 9: Rute administrasi /admin/users dan /admin/parameters dilindungi oleh role:admin middleware.
     */
    public function test_admin_navigation_routes_protected_by_role_middleware(): void
    {
        $user = User::where('role', 'user')->first();
        $admin = User::where('role', 'admin')->first();

        // User biasa ditolak (403)
        $this->actingAs($user)->get('/admin/users')->assertStatus(403);
        $this->actingAs($user)->get('/admin/parameters')->assertStatus(403);

        // Admin diizinkan dan dialihkan ke bagian panel dashboard yang sesuai
        $this->actingAs($admin)->get('/admin/users')->assertRedirect('/dashboard#admin-section');
        $this->actingAs($admin)->get('/admin/parameters')->assertRedirect('/dashboard#parameters-list');
    }

    /**
     * Test 10: Phase 7 — Dashboard merender seluruh grafik pertumbuhan dan distribusi status.
     */
    public function test_dashboard_renders_growth_trends_and_distribution_charts(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('heightChart');
        $response->assertSee('densityChart');
        $response->assertSee('conditionChart');
        $response->assertSee('statusChart');
        $response->assertSee('frequencyChart');
        $response->assertSee('Configured Monitoring Threshold');
    }

    /**
     * Test 11: Phase 7 — Filter rentang tanggal (date_from & date_to) bekerja dengan benar.
     */
    public function test_dashboard_date_filter_works_properly(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->get('/dashboard?date_from=2020-01-01&date_to=2030-12-31');

        $response->assertStatus(200);
        $response->assertSee('Filter Visualisasi');
    }

    /**
     * Test 12: Phase 7 — Quick range filter (today, 7d, 30d, all) bekerja dengan benar.
     */
    public function test_dashboard_quick_range_filter_works(): void
    {
        $user = User::first();

        foreach (['today', '7d', '30d', 'all'] as $range) {
            $response = $this->actingAs($user)->get('/dashboard?range=' . $range);
            $response->assertStatus(200);
        }
    }

    /**
     * Test 13: Phase 7 — Empty state ditampilkan dengan ramah jika filter tidak menghasilkan data.
     */
    public function test_dashboard_handles_empty_filtered_data_gracefully(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->get('/dashboard?date_from=2099-01-01&date_to=2099-01-02');

        $response->assertStatus(200);
        $response->assertSee('Tidak ada data monitoring pada periode yang dipilih.');
    }

    /**
     * Test 14: Phase 7 — Statistik agregasi parameter (AVG, MIN, MAX) ditampilkan di dashboard.
     */
    public function test_dashboard_displays_parameter_aggregations(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Statistik Agregasi Parameter');
        $response->assertSee('Suhu Udara');
        $response->assertSee('pH Nutrisi');
        $response->assertSee('Kelembapan');
        $response->assertSee('Tinggi Tanaman');
        $response->assertSee('Kerapatan Tajuk');
        $response->assertSee('Rata-rata:');
        $response->assertSee('Minimum:');
        $response->assertSee('Maksimum:');
    }

    /**
     * Test 15: Phase 7 — Validasi keamanan filter menolak parameter yang tidak valid.
     */
    public function test_dashboard_rejects_invalid_filter_parameters(): void
    {
        $user = User::first();

        // Range tidak valid
        $responseRange = $this->actingAs($user)->get('/dashboard?range=invalid_range');
        $responseRange->assertSessionHasErrors(['range']);

        // Plant ID tidak valid
        $responsePlant = $this->actingAs($user)->get('/dashboard?plant_id=999999');
        $responsePlant->assertSessionHasErrors(['plant_id']);
    }

    /**
     * Test 16: Phase 7 — Halaman daftar monitoring memiliki tautan menuju analitik dashboard.
     */
    public function test_monitoring_index_has_link_to_dashboard_analytics(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->get('/monitorings');

        $response->assertStatus(200);
        $response->assertSee('Lihat Analytics');
        $response->assertSee(route('dashboard') . '#charts');
    }
}
