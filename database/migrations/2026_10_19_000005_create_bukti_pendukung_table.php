<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel bukti_pendukung sesuai PDM LokerLens.
     * File bukti hanya menerima pdf/jpg/png (NFR-SEC-07).
     */
    public function up(): void
    {
        Schema::create('bukti_pendukung', function (Blueprint $table) {
            $table->id('id_bukti');
            $table->foreignId('id_pengaduan')->constrained('pengaduan', 'id_pengaduan')->cascadeOnDelete();
            $table->string('nama_file', 255);
            $table->enum('tipe_file', ['pdf', 'jpg', 'png']);
            $table->unsignedBigInteger('ukuran_file')->comment('dalam bytes');
            $table->string('path_file', 500);
            $table->timestamp('tanggal_upload')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bukti_pendukung');
    }
};
