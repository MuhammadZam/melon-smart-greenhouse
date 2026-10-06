<?php

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    /**
     * Helper: Mendapatkan atau membuat user dengan role tertentu.
     */
    protected function getUser(string $role = 'user'): User
    {
        $user = User::where('role', $role)->first();
        if (!$user) {
            $user = User::factory()->create(['role' => $role]);
        }
        return $user;
    }

    /**
     * Helper: Mendapatkan atau membuat tanaman melon.
     */
    protected function getPlant(): Plant
    {
        $plant = Plant::first();
        if (!$plant) {
            $plant = Plant::factory()->create([
                'name'     => 'Melon Golden Aroma - Test',
                'location' => 'Greenhouse Test A',
                'status'   => 'active',
            ]);
        }
        return $plant;
    }

    // ========================================================
    // 1. GUEST ACCESS TESTS
    // ========================================================

    public function test_guest_is_redirected_to_login_when_accessing_monitoring_routes(): void
    {
        $monitoring = Monitoring::first();
        $monitoringId = $monitoring ? $monitoring->id : 1;

        $this->get('/monitorings')->assertRedirect('/login');
        $this->get('/monitorings/create')->assertRedirect('/login');
        $this->post('/monitorings', [])->assertRedirect('/login');
        $this->get("/monitorings/{$monitoringId}")->assertRedirect('/login');
        $this->get("/monitorings/{$monitoringId}/edit")->assertRedirect('/login');
        $this->put("/monitorings/{$monitoringId}", [])->assertRedirect('/login');
        $this->delete("/monitorings/{$monitoringId}")->assertRedirect('/login');
    }

    // ========================================================
    // 2. INDEX & VIEW TESTS (Admin & Pengamat)
    // ========================================================

    public function test_pengamat_can_view_monitoring_index(): void
    {
        $pengamat = $this->getUser('user');

        $response = $this->actingAs($pengamat)->get('/monitorings');

        $response->assertStatus(200);
        $response->assertSee('Data Monitoring');
        $response->assertSee('Tanaman Melon');
        $response->assertSee('Tambah Monitoring');
        // Pengamat tidak boleh melihat tombol Edit atau Hapus di tabel
        $response->assertDontSee('Edit Data Monitoring (Admin)');
        $response->assertDontSee('Hapus Data Monitoring (Admin)');
    }

    public function test_admin_can_view_monitoring_index_with_action_buttons(): void
    {
        $admin = $this->getUser('admin');

        $response = $this->actingAs($admin)->get('/monitorings');

        $response->assertStatus(200);
        $response->assertSee('Data Monitoring');
        $response->assertSee('Tambah Monitoring');
        // Admin dapat melihat tombol Aksi Edit & Hapus jika ada data
        if (Monitoring::count() > 0) {
            $response->assertSee('Edit Data Monitoring (Admin)');
            $response->assertSee('Hapus Data Monitoring (Admin)');
        }
    }

    // ========================================================
    // 3. FILTER, SEARCH, & PAGINATION TESTS
    // ========================================================

    public function test_monitoring_index_can_filter_by_plant(): void
    {
        $user = $this->getUser('user');
        $plant = $this->getPlant();

        $response = $this->actingAs($user)->get("/monitorings?plant_id={$plant->id}");

        $response->assertStatus(200);
        $response->assertSee($plant->name);
    }

    public function test_monitoring_index_can_filter_by_condition(): void
    {
        $user = $this->getUser('user');

        $response = $this->actingAs($user)->get('/monitorings?condition=Subur');

        $response->assertStatus(200);
    }

    public function test_monitoring_index_can_search_by_keyword(): void
    {
        $user = $this->getUser('user');
        $plant = $this->getPlant();

        // Buat monitoring dengan catatan unik
        $uniqueNote = 'ObservasiSpesialUnik' . uniqid();
        Monitoring::factory()->create([
            'plant_id' => $plant->id,
            'notes'    => $uniqueNote,
        ]);

        $response = $this->actingAs($user)->get("/monitorings?search={$uniqueNote}");

        $response->assertStatus(200);
        $response->assertSee($plant->name);
    }

    public function test_monitoring_index_has_pagination(): void
    {
        $user = $this->getUser('user');

        $response = $this->actingAs($user)->get('/monitorings?page=1');

        $response->assertStatus(200);
        $response->assertViewHas('monitorings');
    }

    // ========================================================
    // 4. DETAIL / SHOW TESTS (Admin & Pengamat)
    // ========================================================

    public function test_pengamat_can_view_monitoring_detail(): void
    {
        $pengamat = $this->getUser('user');
        $monitoring = Monitoring::with('plant')->first();

        if (!$monitoring) {
            $plant = $this->getPlant();
            $monitoring = Monitoring::factory()->create(['plant_id' => $plant->id]);
        }

        $response = $this->actingAs($pengamat)->get("/monitorings/{$monitoring->id}");

        $response->assertStatus(200);
        $response->assertSee('Detail Pengamatan');
        $response->assertSee((string)$monitoring->id);
        $response->assertSee($monitoring->plant->name);
        // Pengamat tidak melihat tombol Edit/Hapus pada halaman detail
        $response->assertDontSee('Edit Data');
        $response->assertDontSee('Hapus');
    }

    public function test_admin_can_view_monitoring_detail_with_management_buttons(): void
    {
        $admin = $this->getUser('admin');
        $monitoring = Monitoring::with('plant')->first();

        if (!$monitoring) {
            $plant = $this->getPlant();
            $monitoring = Monitoring::factory()->create(['plant_id' => $plant->id]);
        }

        $response = $this->actingAs($admin)->get("/monitorings/{$monitoring->id}");

        $response->assertStatus(200);
        $response->assertSee('Detail Pengamatan');
        $response->assertSee('Edit Data');
        $response->assertSee('Hapus');
    }

    // ========================================================
    // 5. CREATE & STORE TESTS (Admin & Pengamat Boleh)
    // ========================================================

    public function test_pengamat_can_access_create_form(): void
    {
        $pengamat = $this->getUser('user');

        $response = $this->actingAs($pengamat)->get('/monitorings/create');

        $response->assertStatus(200);
        $response->assertSee('Tambah Data');
        $response->assertSee('Simpan Data Monitoring');
    }

    public function test_pengamat_can_store_valid_monitoring_data(): void
    {
        $pengamat = $this->getUser('user');
        $plant = $this->getPlant();

        $data = [
            'plant_id'        => $plant->id,
            'temperature'     => 27.50,
            'ph'              => 6.50,
            'humidity'        => 75.00,
            'plant_height'    => 45.0,
            'plant_density'   => 70.0,
            'plant_condition' => 'Subur & Baik',
            'plant_color'     => 'Hijau Segar',
            'notes'           => 'Observasi uji coba oleh pengamat lapangan.',
            'monitored_at'    => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($pengamat)->post('/monitorings', $data);

        $response->assertRedirect('/monitorings');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('monitorings', [
            'plant_id'        => $plant->id,
            'temperature'     => 27.50,
            'ph'              => 6.50,
            'plant_condition' => 'Subur & Baik',
        ]);
    }

    public function test_admin_can_store_valid_monitoring_data(): void
    {
        $admin = $this->getUser('admin');
        $plant = $this->getPlant();

        $data = [
            'plant_id'        => $plant->id,
            'temperature'     => 28.20,
            'ph'              => 6.70,
            'humidity'        => 70.00,
            'plant_height'    => 50.0,
            'plant_density'   => 75.0,
            'plant_condition' => 'Optimal',
            'plant_color'     => 'Hijau Segar',
            'notes'           => 'Input data oleh administrator.',
            'monitored_at'    => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($admin)->post('/monitorings', $data);

        $response->assertRedirect('/monitorings');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('monitorings', [
            'plant_id'        => $plant->id,
            'temperature'     => 28.20,
            'ph'              => 6.70,
        ]);
    }

    public function test_store_validation_errors_for_missing_required_fields(): void
    {
        $user = $this->getUser('user');

        $response = $this->actingAs($user)->post('/monitorings', []);

        $response->assertSessionHasErrors([
            'plant_id',
            'monitored_at',
            'temperature',
            'ph',
            'humidity',
            'plant_condition',
            'plant_color',
        ]);
    }

    public function test_store_validation_errors_for_invalid_numerical_ranges(): void
    {
        $user = $this->getUser('user');
        $plant = $this->getPlant();

        $response = $this->actingAs($user)->post('/monitorings', [
            'plant_id'        => $plant->id,
            'monitored_at'    => now()->format('Y-m-d H:i:s'),
            'temperature'     => 999.0, // di luar batas
            'ph'              => 25.0,  // di luar batas pH 0-14
            'humidity'        => 150.0, // di luar batas 0-100%
            'plant_condition' => 'Baik',
            'plant_color'     => 'Hijau',
        ]);

        $response->assertSessionHasErrors(['temperature', 'ph', 'humidity']);
    }

    // ========================================================
    // 6. EDIT & UPDATE TESTS (Admin ✅, Pengamat ❌)
    // ========================================================

    public function test_admin_can_access_edit_form(): void
    {
        $admin = $this->getUser('admin');
        $plant = $this->getPlant();
        $monitoring = Monitoring::factory()->create(['plant_id' => $plant->id]);

        $response = $this->actingAs($admin)->get("/monitorings/{$monitoring->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Koreksi Data');
        $response->assertSee('Perbarui Data Monitoring');
    }

    public function test_pengamat_cannot_access_edit_form(): void
    {
        $pengamat = $this->getUser('user');
        $plant = $this->getPlant();
        $monitoring = Monitoring::factory()->create(['plant_id' => $plant->id]);

        $response = $this->actingAs($pengamat)->get("/monitorings/{$monitoring->id}/edit");

        $response->assertStatus(403);
    }

    public function test_admin_can_update_monitoring_data(): void
    {
        $admin = $this->getUser('admin');
        $plant = $this->getPlant();
        $monitoring = Monitoring::factory()->create([
            'plant_id'    => $plant->id,
            'temperature' => 25.0,
            'ph'          => 6.0,
        ]);

        $updateData = [
            'plant_id'        => $plant->id,
            'temperature'     => 29.50,
            'ph'              => 6.85,
            'humidity'        => 78.00,
            'plant_height'    => 55.0,
            'plant_density'   => 80.0,
            'plant_condition' => 'Subur & Baik',
            'plant_color'     => 'Hijau Pekat',
            'notes'           => 'Data telah dikoreksi oleh admin.',
            'monitored_at'    => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($admin)->put("/monitorings/{$monitoring->id}", $updateData);

        $response->assertRedirect('/monitorings');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('monitorings', [
            'id'          => $monitoring->id,
            'temperature' => 29.50,
            'ph'              => 6.85,
            'notes'       => 'Data telah dikoreksi oleh admin.',
        ]);
    }

    public function test_pengamat_cannot_update_monitoring_data(): void
    {
        $pengamat = $this->getUser('user');
        $plant = $this->getPlant();
        $monitoring = Monitoring::factory()->create([
            'plant_id'    => $plant->id,
            'temperature' => 25.0,
        ]);

        $updateData = [
            'plant_id'        => $plant->id,
            'temperature'     => 30.00,
            'ph'              => 6.50,
            'humidity'        => 70.00,
            'plant_condition' => 'Baik',
            'plant_color'     => 'Hijau',
            'monitored_at'    => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($pengamat)->put("/monitorings/{$monitoring->id}", $updateData);

        $response->assertStatus(403);

        // Pastikan data tidak berubah
        $this->assertDatabaseHas('monitorings', [
            'id'          => $monitoring->id,
            'temperature' => 25.0,
        ]);
    }

    // ========================================================
    // 7. DELETE TESTS (Admin ✅, Pengamat ❌)
    // ========================================================

    public function test_admin_can_delete_monitoring_data(): void
    {
        $admin = $this->getUser('admin');
        $plant = $this->getPlant();
        $monitoring = Monitoring::factory()->create(['plant_id' => $plant->id]);

        $response = $this->actingAs($admin)->delete("/monitorings/{$monitoring->id}");

        $response->assertRedirect('/monitorings');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('monitorings', [
            'id' => $monitoring->id,
        ]);
    }

    public function test_pengamat_cannot_delete_monitoring_data(): void
    {
        $pengamat = $this->getUser('user');
        $plant = $this->getPlant();
        $monitoring = Monitoring::factory()->create(['plant_id' => $plant->id]);

        $response = $this->actingAs($pengamat)->delete("/monitorings/{$monitoring->id}");

        $response->assertStatus(403);

        // Pastikan data masih ada di database
        $this->assertDatabaseHas('monitorings', [
            'id' => $monitoring->id,
        ]);
    }
}
