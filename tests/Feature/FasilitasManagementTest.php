<?php

namespace Tests\Feature;

use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\FasilitasModel;
use App\Models\RuanganModel;
use App\Models\LantaiModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FasilitasManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $ruangan;

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

        // Buat user admin untuk testing
        $this->adminUser = UserModel::create([
            'roles_id' => 1,
            'username' => 'admin_test',
            'name' => 'Admin Testing',
            'password' => Hash::make('password123'),
            'NIP' => '123456789'
        ]);

        // Buat lantai untuk testing
        $lantai = LantaiModel::create([
            'lantai_kode' => 'LT1',
            'lantai_nama' => 'Lantai 1'
        ]);

        // Buat ruangan untuk testing
        $this->ruangan = RuanganModel::create([
            'lantai_id' => $lantai->lantai_id,
            'ruangan_kode' => 'R101',
            'ruangan_nama' => 'Ruang 101'
        ]);
    }

    /**
     * Test: Admin dapat mengakses halaman daftar fasilitas
     */
    public function test_admin_can_access_fasilitas_index_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.fasilitas.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.fasilitas.index');
        $response->assertViewHas('active_menu', 'fasilitas');
    }

    /**
     * Test: Admin dapat mengakses halaman create fasilitas
     */
    public function test_admin_can_access_create_fasilitas_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.fasilitas.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.fasilitas.create');
        $response->assertViewHas('ruangan');
    }

    /**
     * Test: Admin dapat menambahkan fasilitas baru dengan data yang valid
     */
    public function test_admin_can_create_new_fasilitas(): void
    {
        $fasilitasData = [
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS001',
            'fasilitas_nama' => 'Proyektor',
            'tingkat_urgensi' => 3
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.fasilitas.store'), $fasilitasData);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Data Fasilitas berhasil disimpan'
        ]);

        // Verifikasi data tersimpan di database
        $this->assertDatabaseHas('m_fasilitas', [
            'fasilitas_kode' => 'FAS001',
            'fasilitas_nama' => 'Proyektor',
            'tingkat_urgensi' => 3
        ]);
    }

    /**
     * Test: Validasi fasilitas_kode wajib diisi
     */
    public function test_fasilitas_kode_is_required(): void
    {
        $fasilitasData = [
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_nama' => 'Proyektor',
            'tingkat_urgensi' => 3
            // fasilitas_kode tidak diisi
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.fasilitas.store'), $fasilitasData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('fasilitas_kode');
    }

    /**
     * Test: Validasi fasilitas_nama wajib diisi
     */
    public function test_fasilitas_nama_is_required(): void
    {
        $fasilitasData = [
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS001',
            'tingkat_urgensi' => 3
            // fasilitas_nama tidak diisi
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.fasilitas.store'), $fasilitasData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('fasilitas_nama');
    }

    /**
     * Test: Validasi tingkat_urgensi wajib diisi dan harus antara 1-5
     */
    public function test_tingkat_urgensi_must_be_between_1_and_5(): void
    {
        $fasilitasData = [
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS001',
            'fasilitas_nama' => 'Proyektor',
            'tingkat_urgensi' => 10 // Invalid value
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.fasilitas.store'), $fasilitasData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tingkat_urgensi');
    }

    /**
     * Test: Validasi ruangan_id wajib diisi
     */
    public function test_ruangan_id_is_required(): void
    {
        $fasilitasData = [
            'fasilitas_kode' => 'FAS001',
            'fasilitas_nama' => 'Proyektor',
            'tingkat_urgensi' => 3
            // ruangan_id tidak diisi
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.fasilitas.store'), $fasilitasData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ruangan_id');
    }

    /**
     * Test: Admin dapat mengakses halaman edit fasilitas
     */
    public function test_admin_can_access_edit_fasilitas_page(): void
    {
        $fasilitas = FasilitasModel::create([
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS001',
            'fasilitas_nama' => 'Proyektor',
            'tingkat_urgensi' => 3
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.fasilitas.edit', $fasilitas->fasilitas_id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.fasilitas.edit');
        $response->assertViewHas('fasilitas');
    }

    /**
     * Test: Admin dapat mengupdate fasilitas
     */
    public function test_admin_can_update_fasilitas(): void
    {
        $fasilitas = FasilitasModel::create([
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS001',
            'fasilitas_nama' => 'Proyektor',
            'tingkat_urgensi' => 3
        ]);

        $updatedData = [
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS001-UPDATED',
            'fasilitas_nama' => 'Proyektor LCD',
            'tingkat_urgensi' => 5
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('admin.fasilitas.update', $fasilitas->fasilitas_id), $updatedData);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Data berhasil diupdate'
        ]);

        // Verifikasi data terupdate di database
        $this->assertDatabaseHas('m_fasilitas', [
            'fasilitas_id' => $fasilitas->fasilitas_id,
            'fasilitas_kode' => 'FAS001-UPDATED',
            'fasilitas_nama' => 'Proyektor LCD',
            'tingkat_urgensi' => 5
        ]);
    }

    /**
     * Test: Admin dapat melihat detail fasilitas
     */
    public function test_admin_can_view_fasilitas_details(): void
    {
        $fasilitas = FasilitasModel::create([
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS001',
            'fasilitas_nama' => 'Proyektor',
            'tingkat_urgensi' => 3
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.fasilitas.show', $fasilitas->fasilitas_id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.fasilitas.show');
        $response->assertViewHas('fasilitas');
    }

    /**
     * Test: Admin dapat menghapus fasilitas
     */
    public function test_admin_can_delete_fasilitas(): void
    {
        $fasilitas = FasilitasModel::create([
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS001',
            'fasilitas_nama' => 'Proyektor',
            'tingkat_urgensi' => 3
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('admin.fasilitas.delete', $fasilitas->fasilitas_id));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Data fasilitas berhasil dihapus'
        ]);

        // Verifikasi data terhapus dari database
        $this->assertDatabaseMissing('m_fasilitas', [
            'fasilitas_id' => $fasilitas->fasilitas_id
        ]);
    }

    /**
     * Test: User yang tidak login tidak dapat mengakses halaman fasilitas
     */
    public function test_guest_cannot_access_fasilitas_pages(): void
    {
        $response = $this->get(route('admin.fasilitas.index'));
        
        // Harus redirect ke login
        $response->assertRedirect(route('login'));
    }

    /**
     * Test: Tidak dapat menghapus fasilitas yang tidak ada
     */
    public function test_cannot_delete_non_existent_fasilitas(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('admin.fasilitas.delete', 99999));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => false,
            'message' => 'Data tidak ditemukan'
        ]);
    }

    /**
     * Test: Admin dapat mengakses halaman list fasilitas (DataTables)
     */
    public function test_admin_can_get_fasilitas_list_data(): void
    {
        // Buat beberapa data fasilitas
        FasilitasModel::create([
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS001',
            'fasilitas_nama' => 'Proyektor',
            'tingkat_urgensi' => 3
        ]);

        FasilitasModel::create([
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS002',
            'fasilitas_nama' => 'AC',
            'tingkat_urgensi' => 4
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.fasilitas.list'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data'
        ]);
    }
}
