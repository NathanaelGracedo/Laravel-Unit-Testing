<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, modify the enum to include the new value
        DB::statement("ALTER TABLE m_status MODIFY status_nama ENUM('menunggu verifikasi', 'ditolak', 'diproses', 'selesai', 'disetujui', 'selesai diperiksa') DEFAULT 'menunggu verifikasi'");

        // Then insert the new status with ID 6 (use insertOrIgnore untuk testing)
        DB::table('m_status')->insertOrIgnore([
            'status_id' => 6,
            'status_nama' => 'selesai diperiksa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete the record with ID 6
        DB::table('m_status')->where('status_id', 6)->delete();

        // Change the enum back to original values
        DB::statement("ALTER TABLE m_status MODIFY status_nama ENUM('menunggu verifikasi', 'ditolak', 'diproses', 'selesai', 'disetujui') DEFAULT 'menunggu verifikasi'");
    }
};
