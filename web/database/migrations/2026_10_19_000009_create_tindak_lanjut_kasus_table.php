<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tindak_lanjut_kasus', function (Blueprint $table) {
            $table->id('id_tindaklanjut');
            $table->foreignId('id_pengaduan')->unique()->constrained('pengaduan', 'id_pengaduan')->cascadeOnDelete();
            $table->foreignId('id_pengguna')->constrained('users', 'id')->cascadeOnDelete(); // Kepala Bidang
            // BR-02: hanya dua keputusan
            $table->enum('keputusan', ['Tindaklanjuti', 'Tidak Ditindaklanjuti']);
            $table->text('catatan_keputusan')->nullable();
            $table->timestamp('tanggal_keputusan')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tindak_lanjut_kasus');
    }
};
