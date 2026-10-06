<?php

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantTest extends TestCase
{
    /**
     * Helper: Mendapatkan atau membuat user berdasarkan role.
     */
    protected function getUser(string $role = 'user'): User
    {
        $user = User::where('role', $role)->first();
        if (!$user) {
            $user = User::factory()->create(['role' => $role]);
        }
        return $user;
    }

    // ========================================================
    // 1. GUEST ACCESS TESTS
    // ========================================================

    public function test_guest_cannot_access_plant_routes(): void
    {
        $plant = Plant::first() ?? Plant::factory()->create();

        $this->get('/plants')->assertRedirect('/login');
        $this->get('/plants/create')->assertRedirect('/login');
        $this->post('/plants', [])->assertRedirect('/login');
        $this->get("/plants/{$plant->id}")->assertRedirect('/login');
        $this->get("/plants/{$plant->id}/edit")->assertRedirect('/login');
        $this->put("/plants/{$plant->id}", [])->assertRedirect('/login');
        $this->delete("/plants/{$plant->id}")->assertRedirect('/login');
    }

    // ========================================================
    // 2. PENGAMAT ACCESS & AUTHORIZATION TESTS
    // ========================================================

    public function test_pengamat_can_view_plants(): void
    {
        $pengamat = $this->getUser('user');

        $response = $this->actingAs($pengamat)->get('/plants');

        $response->assertStatus(200);
        $response->assertSee('Data Tanaman');
        $response->assertSee('Melon');
        // Pengamat tidak boleh melihat tombol Tambah Tanaman
        $response->assertDontSee('Tambah Tanaman');
    }

    public function test_pengamat_can_view_plant_detail(): void
    {
        $pengamat = $this->getUser('user');
        $plant = Plant::first() ?? Plant::factory()->create();

        $response = $this->actingAs($pengamat)->get("/plants/{$plant->id}");

        $response->assertStatus(200);
        $response->assertSee($plant->name);
        $response->assertSee($plant->location);
        // Pengamat tidak boleh melihat tombol Edit Tanaman atau Hapus Tanaman
        $response->assertDontSee('Edit Tanaman');
        $response->assertDontSee('Hapus Tanaman');
    }

    public function test_pengamat_cannot_create_plant(): void
    {
        $pengamat = $this->getUser('user');

        $response = $this->actingAs($pengamat)->get('/plants/create');

        $response->assertStatus(403);
    }

    public function test_pengamat_cannot_store_plant(): void
    {
        $pengamat = $this->getUser('user');

        $data = [
            'name'     => 'Melon Percobaan Pengamat',
            'location' => 'Greenhouse 99',
            'status'   => 'active',
        ];

        $response = $this->actingAs($pengamat)->post('/plants', $data);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('plants', ['name' => 'Melon Percobaan Pengamat']);
    }

    public function test_pengamat_cannot_edit_plant(): void
    {
        $pengamat = $this->getUser('user');
        $plant = Plant::first() ?? Plant::factory()->create();

        $response = $this->actingAs($pengamat)->get("/plants/{$plant->id}/edit");

        $response->assertStatus(403);
    }

    public function test_pengamat_cannot_update_plant(): void
    {
        $pengamat = $this->getUser('user');
        $plant = Plant::factory()->create(['name' => 'Original Plant Name']);

        $response = $this->actingAs($pengamat)->put("/plants/{$plant->id}", [
            'name'     => 'Updated By Pengamat',
            'location' => 'Location Updated',
            'status'   => 'active',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('plants', ['id' => $plant->id, 'name' => 'Original Plant Name']);
    }

    public function test_pengamat_cannot_delete_plant(): void
    {
        $pengamat = $this->getUser('user');
        $plant = Plant::factory()->create();

        $response = $this->actingAs($pengamat)->delete("/plants/{$plant->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('plants', ['id' => $plant->id]);
    }

    // ========================================================
    // 3. ADMIN ACCESS & CRUD TESTS
    // ========================================================

    public function test_admin_can_view_plants_with_management_controls(): void
    {
        $admin = $this->getUser('admin');

        $response = $this->actingAs($admin)->get('/plants');

        $response->assertStatus(200);
        $response->assertSee('Data Tanaman');
        $response->assertSee('Tambah Tanaman');
    }

    public function test_admin_can_view_plant_detail(): void
    {
        $admin = $this->getUser('admin');
        $plant = Plant::first() ?? Plant::factory()->create();

        $response = $this->actingAs($admin)->get("/plants/{$plant->id}");

        $response->assertStatus(200);
        $response->assertSee($plant->name);
        $response->assertSee('Edit Tanaman');
    }

    public function test_admin_can_open_create_form(): void
    {
        $admin = $this->getUser('admin');

        $response = $this->actingAs($admin)->get('/plants/create');

        $response->assertStatus(200);
        $response->assertSee('Tambah Plot');
        $response->assertSee('Simpan Data Tanaman');
    }

    public function test_admin_can_create_plant(): void
    {
        $admin = $this->getUser('admin');

        $uniqueName = 'Melon Intanon Greenhouse ' . uniqid();
        $data = [
            'name'        => $uniqueName,
            'location'    => 'Greenhouse 3 - Sektor Timur',
            'description' => 'Plot melon premium varietas Intanon dengan fertigasi tetes.',
            'status'      => 'active',
        ];

        $response = $this->actingAs($admin)->post('/plants', $data);

        $response->assertRedirect('/plants');
        $response->assertSessionHas('success', 'Data tanaman berhasil ditambahkan.');

        $this->assertDatabaseHas('plants', [
            'name'     => $uniqueName,
            'location' => 'Greenhouse 3 - Sektor Timur',
            'status'   => 'active',
        ]);
    }

    public function test_admin_can_open_edit_form(): void
    {
        $admin = $this->getUser('admin');
        $plant = Plant::first() ?? Plant::factory()->create();

        $response = $this->actingAs($admin)->get("/plants/{$plant->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Edit Tanaman');
        $response->assertSee('Perbarui Data Tanaman');
    }

    public function test_admin_can_update_plant(): void
    {
        $admin = $this->getUser('admin');
        $plant = Plant::factory()->create(['name' => 'Melon Sebelum Update ' . uniqid()]);

        $updatedName = 'Melon Sweet Net - Greenhouse 4 ' . uniqid();
        $updateData = [
            'name'        => $updatedName,
            'location'    => 'Greenhouse 4 - Sektor Selatan',
            'description' => 'Keterangan plot setelah diperbarui.',
            'status'      => 'harvested',
        ];

        $response = $this->actingAs($admin)->put("/plants/{$plant->id}", $updateData);

        $response->assertRedirect('/plants');
        $response->assertSessionHas('success', 'Data tanaman berhasil diperbarui.');

        $this->assertDatabaseHas('plants', [
            'id'       => $plant->id,
            'name'     => $updatedName,
            'location' => 'Greenhouse 4 - Sektor Selatan',
            'status'   => 'harvested',
        ]);
    }

    public function test_admin_can_delete_plant_without_monitoring(): void
    {
        $admin = $this->getUser('admin');
        // Buat tanaman baru yang belum memiliki record monitoring apapun
        $emptyPlant = Plant::factory()->create();

        $response = $this->actingAs($admin)->delete("/plants/{$emptyPlant->id}");

        $response->assertRedirect('/plants');
        $response->assertSessionHas('success', 'Data tanaman berhasil dihapus.');

        $this->assertDatabaseMissing('plants', [
            'id' => $emptyPlant->id,
        ]);
    }

    // ========================================================
    // 4. VALIDATION TESTS
    // ========================================================

    public function test_invalid_plant_data_rejected(): void
    {
        $admin = $this->getUser('admin');

        $response = $this->actingAs($admin)->post('/plants', [
            'name'     => '',
            'location' => '',
            'status'   => 'invalid-status',
        ]);

        $response->assertSessionHasErrors(['name', 'location', 'status']);
    }

    // ========================================================
    // 5. SEARCH & FILTER TESTS
    // ========================================================

    public function test_plant_search_works(): void
    {
        $user = $this->getUser('user');

        $uniqueSearchKey = 'VarietasLangka' . uniqid();
        Plant::factory()->create([
            'name'     => "Melon {$uniqueSearchKey}",
            'location' => 'Greenhouse Khusus',
        ]);

        $response = $this->actingAs($user)->get("/plants?search={$uniqueSearchKey}");

        $response->assertStatus(200);
        $response->assertSee($uniqueSearchKey);
    }

    public function test_plant_filter_by_status_works(): void
    {
        $user = $this->getUser('user');

        $response = $this->actingAs($user)->get('/plants?status=active');

        $response->assertStatus(200);
        $response->assertViewHas('plants');
    }

    // ========================================================
    // 6. PAGINATION TESTS
    // ========================================================

    public function test_plant_pagination_works(): void
    {
        $user = $this->getUser('user');

        $response = $this->actingAs($user)->get('/plants?page=1');

        $response->assertStatus(200);
        $response->assertViewHas('plants');
    }

    // ========================================================
    // 7. RELATIONSHIP & DELETE PROTECTION TESTS
    // ========================================================

    public function test_plant_shows_monitoring_count(): void
    {
        $user = $this->getUser('user');
        $plant = Plant::first();

        if (!$plant) {
            $plant = Plant::factory()->create();
            Monitoring::factory()->create(['plant_id' => $plant->id]);
        }

        $response = $this->actingAs($user)->get('/plants');

        $response->assertStatus(200);
        $response->assertSee('Pengamatan');
    }

    public function test_plant_with_monitoring_cannot_be_deleted(): void
    {
        $admin = $this->getUser('admin');

        // Tanaman yang memiliki data riwayat monitoring
        $plant = Plant::factory()->create();
        Monitoring::factory()->create(['plant_id' => $plant->id]);

        $response = $this->actingAs($admin)->delete("/plants/{$plant->id}");

        $response->assertRedirect('/plants');
        $response->assertSessionHas('error', 'Tanaman tidak dapat dihapus karena masih memiliki data monitoring.');

        // Pastikan tanaman TIDAK terhapus dari database
        $this->assertDatabaseHas('plants', [
            'id' => $plant->id,
        ]);
    }
}
