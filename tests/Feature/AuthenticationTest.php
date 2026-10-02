<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    /**
     * Test: Halaman login dapat dirender dengan sukses (HTTP 200).
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Selamat Datang');
        $response->assertSee('Masuk ke Dashboard');
    }

    /**
     * Test: Login berhasil menggunakan akun Administrator.
     */
    public function test_admin_can_authenticate_and_redirected_to_dashboard(): void
    {
        $response = $this->post('/login', [
            'email'    => 'admin@azolla.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    /**
     * Test: Login berhasil menggunakan akun Pengamat (User biasa).
     */
    public function test_regular_user_can_authenticate_and_redirected_to_dashboard(): void
    {
        $response = $this->post('/login', [
            'email'    => 'pengamat@azolla.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    /**
     * Test: Login gagal jika kata sandi salah.
     */
    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'email'    => 'admin@azolla.com',
            'password' => 'wrong-password-999',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test: Login gagal jika email tidak terdaftar.
     */
    public function test_users_cannot_authenticate_with_non_existent_email(): void
    {
        $response = $this->post('/login', [
            'email'    => 'unknown@notfound.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test: Logout berhasil dan menginvalasi sesi autentikasi.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::where('email', 'admin@azolla.com')->first();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    /**
     * Test: Pengunjung tanpa autentikasi (guest) dialihkan ke halaman login saat mencoba membuka dashboard.
     */
    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Test: Administrator memiliki akses ke rute berproteksi role:admin.
     */
    public function test_admin_user_can_access_admin_route(): void
    {
        $admin = User::where('email', 'admin@azolla.com')->first();

        $response = $this->actingAs($admin)->get('/admin/management-check');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    /**
     * Test: Pengamat (User biasa) ditolak (HTTP 403 Forbidden) saat mengakses rute khusus admin.
     */
    public function test_regular_user_cannot_access_admin_route(): void
    {
        $user = User::where('email', 'pengamat@azolla.com')->first();

        $response = $this->actingAs($user)->get('/admin/management-check');

        $response->assertStatus(403);
    }
}
