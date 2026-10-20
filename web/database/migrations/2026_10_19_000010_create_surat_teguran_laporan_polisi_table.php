<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_teguran_laporan_polisi', function (Blueprint $table) {
            $table->id('id_surat');
            $table->foreignId('id_tindaklanjut')->constrained('tindak_lanjut_kasus', 'id_tindaklanjut')->cascadeOnDelete();
            $table->string('nomor_surat', 100)->nullable();
            $table->enum('jenis_dokumen', ['Surat Teguran', 'Laporan Polisi'])->default('Laporan Polisi');
            $table->text('kronologi_kejadian')->nullable();
            $table->string('tujuan_instansi', 255)->nullable();
            // BR-09: Draft belum terbit; resmi setelah diterbitkan
            $table->enum('status_penerbitan', ['Draft', 'Terbit'])->default('Draft');
            $table->timestamp('tanggal_terbit')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_teguran_laporan_polisi');
    }
};
