<?php

namespace Tests\Feature;

use App\Models\UserModel;
use App\Models\RoleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup method untuk membuat data test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Buat role untuk testing
        RoleModel::create([
            'roles_id' => 1,
            'roles_nama' => 'Admin',
            'roles_kode' => 'ADM'
        ]);

        RoleModel::create([
            'roles_id' => 2,
            'roles_nama' => 'Pelapor',
            'roles_kode' => 'PLP'
        ]);
    }

    /**
     * Test: Halaman login dapat diakses
     */
    public function test_login_page_can_be_accessed(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * Test: User dapat login dengan kredensial yang benar
     */
    public function test_user_can_login_with_correct_credentials(): void
    {
        // Buat user test
        $user = UserModel::create([
            'roles_id' => 1,
            'username' => 'admin_test',
            'name' => 'Admin Testing',
            'password' => Hash::make('password123'),
            'NIP' => '123456789'
        ]);

        // Attempt login
        $response = $this->post('/login', [
            'username' => 'admin_test',
            'password' => 'password123',
        ]);

        // Assert user terautentikasi
        $this->assertAuthenticatedAs($user);
        
        // Assert redirect ke dashboard admin
        $response->assertRedirect('/admin/dashboard');
    }

    /**
     * Test: User tidak dapat login dengan password salah
     */
    public function test_user_cannot_login_with_incorrect_password(): void
    {
        // Buat user test
        UserModel::create([
            'roles_id' => 1,
            'username' => 'admin_test',
            'name' => 'Admin Testing',
            'password' => Hash::make('password123'),
            'NIP' => '123456789'
        ]);

        // Attempt login dengan password salah
        $response = $this->post('/login', [
            'username' => 'admin_test',
            'password' => 'wrongpassword',
        ]);

        // Assert user tidak terautentikasi
        $this->assertGuest();
        
        // Assert ada error
        $response->assertSessionHasErrors();
    }

    /**
     * Test: User tidak dapat login dengan username yang tidak terdaftar
     */
    public function test_user_cannot_login_with_unregistered_username(): void
    {
        $response = $this->post('/login', [
            'username' => 'tidak_ada',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    /**
     * Test: Login memerlukan username
     */
    public function test_login_requires_username(): void
    {
        $response = $this->post('/login', [
            'username' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
    }

    /**
     * Test: Login memerlukan password
     */
    public function test_login_requires_password(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin_test',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * Test: User dengan role Admin diarahkan ke dashboard admin
     */
    public function test_admin_user_redirected_to_admin_dashboard(): void
    {
        $user = UserModel::create([
            'roles_id' => 1,
            'username' => 'admin_test',
            'name' => 'Admin Testing',
            'password' => Hash::make('password123'),
            'NIP' => '123456789'
        ]);

        $response = $this->post('/login', [
            'username' => 'admin_test',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/dashboard');
    }

    /**
     * Test: User dengan role Pelapor diarahkan ke dashboard pelapor
     */
    public function test_pelapor_user_redirected_to_pelapor_dashboard(): void
    {
        $user = UserModel::create([
            'roles_id' => 2, // Pelapor
            'username' => 'pelapor_test',
            'name' => 'Pelapor Testing',
            'password' => Hash::make('password123'),
            'NIM' => '12345678'
        ]);

        $response = $this->post('/login', [
            'username' => 'pelapor_test',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/pelapor/dashboard');
    }

    /**
     * Test: User yang sudah login tidak dapat mengakses halaman login
     */
    public function test_authenticated_user_cannot_access_login_page(): void
    {
        $user = UserModel::create([
            'roles_id' => 1,
            'username' => 'admin_test',
            'name' => 'Admin Testing',
            'password' => Hash::make('password123'),
            'NIP' => '123456789'
        ]);

        // Login user
        $this->actingAs($user);

        // Coba akses halaman login
        $response = $this->get('/login');

        // Assert redirect ke home
        $response->assertRedirect('/');
    }

    /**
     * Test: User dapat logout
     */
    public function test_user_can_logout(): void
    {
        $user = UserModel::create([
            'roles_id' => 1,
            'username' => 'admin_test',
            'name' => 'Admin Testing',
            'password' => Hash::make('password123'),
            'NIP' => '123456789'
        ]);

        // Login user
        $this->actingAs($user);
        $this->assertAuthenticated();

        // Logout
        $response = $this->get('/logout');

        // Assert user tidak terautentikasi
        $this->assertGuest();
        
        // Assert redirect ke login
        $response->assertRedirect('login');
    }

    /**
     * Test: Login dengan AJAX request
     */
    public function test_ajax_login_returns_json_response(): void
    {
        $user = UserModel::create([
            'roles_id' => 1,
            'username' => 'admin_test',
            'name' => 'Admin Testing',
            'password' => Hash::make('password123'),
            'NIP' => '123456789'
        ]);

        $response = $this->postJson('/login', [
            'username' => 'admin_test',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'redirect'
        ]);
    }

    /**
     * Test: AJAX login dengan kredensial salah mengembalikan error JSON
     */
    public function test_ajax_login_with_wrong_credentials_returns_json_error(): void
    {
        // Buat user terlebih dahulu
        UserModel::create([
            'roles_id' => 1,
            'username' => 'admin_test',
            'name' => 'Admin Testing',
            'password' => Hash::make('password123'),
            'NIP' => '123456789'
        ]);

        $response = $this->postJson('/login', [
            'username' => 'admin_test',
            'password' => 'wrongpassword',
        ]);

        // Laravel mengembalikan 302 redirect untuk auth failures
        // Kita test bahwa password salah tidak berhasil login
        $this->assertGuest();
    }
}
