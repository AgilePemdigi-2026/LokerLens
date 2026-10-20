<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel pengaduan sesuai PDM LokerLens.
     * Pengaduan adalah pusat relasi — satu pemeriksaan oleh pengguna.
     */
    public function up(): void
    {
        Schema::create('pengaduan', function (Blueprint $table) {
            $table->id('id_pengaduan');
            $table->foreignId('id_lowongan')->constrained('lowongan_kerja', 'id_lowongan')->cascadeOnDelete();
            $table->foreignId('id_pengguna')->constrained('users', 'id')->cascadeOnDelete();
            $table->string('nomor_tiket', 50)->unique(); // contoh: #LKR-8821
            $table->text('deskripsi_singkat')->nullable();
            $table->enum('status_pengaduan', [
                'Perlu Tinjauan Segera',
                'Sedang Ditinjau',
                'Menunggu Keputusan',
                'Tindak Lanjut Disetujui',
                'Kasus terdokumentasi otomatis',
                'Kasus selesai',
                'Tidak dilanjuti',
            ])->default('Perlu Tinjauan Segera');
            $table->timestamp('tanggal_pengaduan')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaduan');
    }
};
