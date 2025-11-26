<?php

namespace Tests\Unit;

use App\Models\FasilitasModel;
use App\Models\RuanganModel;
use App\Models\LantaiModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FasilitasModelTest extends TestCase
{
    use RefreshDatabase;

    protected $ruangan;

    /**
     * Setup method untuk membuat data test
     */
    protected function setUp(): void
    {
        parent::setUp();

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
     * Test: Fasilitas dapat dibuat dengan data yang valid
     */
    public function test_fasilitas_can_be_created(): void
    {
        $fasilitas = FasilitasModel::create([
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS001',
            'fasilitas_nama' => 'Proyektor LCD',
            'tingkat_urgensi' => 3
        ]);

        $this->assertInstanceOf(FasilitasModel::class, $fasilitas);
        $this->assertEquals('FAS001', $fasilitas->fasilitas_kode);
        $this->assertEquals('Proyektor LCD', $fasilitas->fasilitas_nama);
        $this->assertEquals(3, $fasilitas->tingkat_urgensi);
    }

    /**
     * Test: Fasilitas memiliki relasi dengan Ruangan
     */
    public function test_fasilitas_has_ruangan_relationship(): void
    {
        $fasilitas = FasilitasModel::create([
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS001',
            'fasilitas_nama' => 'Proyektor LCD',
            'tingkat_urgensi' => 3
        ]);

        $this->assertInstanceOf(RuanganModel::class, $fasilitas->ruangan);
        $this->assertEquals('Ruang 101', $fasilitas->ruangan->ruangan_nama);
        $this->assertEquals('R101', $fasilitas->ruangan->ruangan_kode);
    }

    /**
     * Test: Fasilitas dapat diupdate
     */
    public function test_fasilitas_can_be_updated(): void
    {
        $fasilitas = FasilitasModel::create([
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS001',
            'fasilitas_nama' => 'Proyektor LCD',
            'tingkat_urgensi' => 3
        ]);

        $fasilitas->update([
            'fasilitas_nama' => 'Proyektor LCD 4K',
            'tingkat_urgensi' => 5
        ]);

        $this->assertEquals('Proyektor LCD 4K', $fasilitas->fasilitas_nama);
        $this->assertEquals(5, $fasilitas->tingkat_urgensi);

        // Verifikasi di database
        $this->assertDatabaseHas('m_fasilitas', [
            'fasilitas_id' => $fasilitas->fasilitas_id,
            'fasilitas_nama' => 'Proyektor LCD 4K',
            'tingkat_urgensi' => 5
        ]);
    }

    /**
     * Test: Fasilitas dapat dihapus
     */
    public function test_fasilitas_can_be_deleted(): void
    {
        $fasilitas = FasilitasModel::create([
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS001',
            'fasilitas_nama' => 'Proyektor LCD',
            'tingkat_urgensi' => 3
        ]);

        $fasilitasId = $fasilitas->fasilitas_id;
        $fasilitas->delete();

        // Verifikasi data terhapus dari database
        $this->assertDatabaseMissing('m_fasilitas', [
            'fasilitas_id' => $fasilitasId
        ]);
    }

    /**
     * Test: Fasilitas menggunakan tabel yang benar
     */
    public function test_fasilitas_uses_correct_table(): void
    {
        $fasilitas = new FasilitasModel();
        $this->assertEquals('m_fasilitas', $fasilitas->getTable());
    }

    /**
     * Test: Fasilitas menggunakan primary key yang benar
     */
    public function test_fasilitas_uses_correct_primary_key(): void
    {
        $fasilitas = new FasilitasModel();
        $this->assertEquals('fasilitas_id', $fasilitas->getKeyName());
    }

    /**
     * Test: Fasilitas memiliki fillable attributes yang benar
     */
    public function test_fasilitas_has_correct_fillable_attributes(): void
    {
        $fasilitas = new FasilitasModel();
        $fillable = $fasilitas->getFillable();

        $this->assertContains('ruangan_id', $fillable);
        $this->assertContains('fasilitas_kode', $fillable);
        $this->assertContains('fasilitas_nama', $fillable);
        $this->assertContains('tingkat_urgensi', $fillable);
    }

    /**
     * Test: Dapat membuat beberapa fasilitas untuk ruangan yang sama
     */
    public function test_can_create_multiple_fasilitas_for_same_ruangan(): void
    {
        $fasilitas1 = FasilitasModel::create([
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS001',
            'fasilitas_nama' => 'Proyektor LCD',
            'tingkat_urgensi' => 3
        ]);

        $fasilitas2 = FasilitasModel::create([
            'ruangan_id' => $this->ruangan->ruangan_id,
            'fasilitas_kode' => 'FAS002',
            'fasilitas_nama' => 'AC',
            'tingkat_urgensi' => 4
        ]);

        $this->assertEquals($this->ruangan->ruangan_id, $fasilitas1->ruangan_id);
        $this->assertEquals($this->ruangan->ruangan_id, $fasilitas2->ruangan_id);
        
        // Pastikan keduanya berbeda
        $this->assertNotEquals($fasilitas1->fasilitas_id, $fasilitas2->fasilitas_id);
    }

    /**
     * Test: Tingkat urgensi disimpan dengan benar
     */
    public function test_tingkat_urgensi_is_stored_correctly(): void
    {
        foreach ([1, 2, 3, 4, 5] as $urgensi) {
            $fasilitas = FasilitasModel::create([
                'ruangan_id' => $this->ruangan->ruangan_id,
                'fasilitas_kode' => 'FAS00' . $urgensi,
                'fasilitas_nama' => 'Test Fasilitas ' . $urgensi,
                'tingkat_urgensi' => $urgensi
            ]);

            $this->assertEquals($urgensi, $fasilitas->tingkat_urgensi);
        }
    }
}
