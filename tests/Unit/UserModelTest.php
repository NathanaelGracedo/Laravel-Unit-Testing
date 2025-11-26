<?php

namespace Tests\Unit;

use App\Models\UserModel;
use App\Models\RoleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserModelTest extends TestCase
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
     * Test: User dapat dibuat dengan data yang valid
     */
    public function test_user_can_be_created(): void
    {
        $user = UserModel::create([
            'roles_id' => 1,
            'username' => 'testuser',
            'name' => 'Test User',
            'password' => 'password123',
            'NIP' => '123456789'
        ]);

        $this->assertDatabaseHas('m_user', [
            'username' => 'testuser',
            'name' => 'Test User',
            'NIP' => '123456789'
        ]);
    }

    /**
     * Test: Password user otomatis di-hash
     */
    public function test_user_password_is_hashed(): void
    {
        $user = UserModel::create([
            'roles_id' => 1,
            'username' => 'testuser',
            'name' => 'Test User',
            'password' => 'plainpassword',
            'NIP' => '123456789'
        ]);

        // Password di database harus berbeda dengan plain text
        $this->assertNotEquals('plainpassword', $user->password);
        
        // Tapi bisa diverifikasi dengan Hash::check
        $this->assertTrue(Hash::check('plainpassword', $user->password));
    }

    /**
     * Test: User memiliki relasi dengan Role
     */
    public function test_user_has_role_relationship(): void
    {
        $user = UserModel::create([
            'roles_id' => 1,
            'username' => 'testuser',
            'name' => 'Test User',
            'password' => 'password123',
            'NIP' => '123456789'
        ]);

        $this->assertInstanceOf(RoleModel::class, $user->role);
        $this->assertEquals('Admin', $user->role->roles_nama);
    }

    /**
     * Test: Method getRoleName() mengembalikan nama role yang benar
     */
    public function test_get_role_name_returns_correct_role(): void
    {
        $user = UserModel::create([
            'roles_id' => 1,
            'username' => 'testuser',
            'name' => 'Test User',
            'password' => 'password123',
            'NIP' => '123456789'
        ]);

        $this->assertEquals('Admin', $user->getRoleName());
    }

    /**
     * Test: Password disembunyikan saat serialisasi
     */
    public function test_password_is_hidden_in_serialization(): void
    {
        $user = UserModel::create([
            'roles_id' => 1,
            'username' => 'testuser',
            'name' => 'Test User',
            'password' => 'password123',
            'NIP' => '123456789'
        ]);

        $userArray = $user->toArray();

        $this->assertArrayNotHasKey('password', $userArray);
    }

    /**
     * Test: Username harus unique
     */
    public function test_username_must_be_unique(): void
    {
        UserModel::create([
            'roles_id' => 1,
            'username' => 'testuser',
            'name' => 'Test User 1',
            'password' => 'password123',
            'NIP' => '123456789'
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Coba buat user dengan username sama
        UserModel::create([
            'roles_id' => 1,
            'username' => 'testuser', // username sama
            'name' => 'Test User 2',
            'password' => 'password123',
            'NIP' => '987654321'
        ]);
    }

    /**
     * Test: User dapat dihapus
     */
    public function test_user_can_be_deleted(): void
    {
        $user = UserModel::create([
            'roles_id' => 1,
            'username' => 'testuser',
            'name' => 'Test User',
            'password' => 'password123',
            'NIP' => '123456789'
        ]);

        $userId = $user->user_id;
        $user->delete();

        $this->assertDatabaseMissing('m_user', [
            'user_id' => $userId
        ]);
    }

    /**
     * Test: User dapat diupdate
     */
    public function test_user_can_be_updated(): void
    {
        $user = UserModel::create([
            'roles_id' => 1,
            'username' => 'testuser',
            'name' => 'Test User',
            'password' => 'password123',
            'NIP' => '123456789'
        ]);

        $user->update([
            'name' => 'Updated Name',
            'NIP' => '987654321'
        ]);

        $this->assertDatabaseHas('m_user', [
            'user_id' => $user->user_id,
            'name' => 'Updated Name',
            'NIP' => '987654321'
        ]);
    }
}
