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

        $this->actingAs($user)->get('/monitorings')->assertRedirect('/dashboard#recent-table');
        $this->actingAs($user)->get('/plants')->assertRedirect('/dashboard#plant-summary');
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
}
