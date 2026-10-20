<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel hasil_klasifikasi sesuai PDM LokerLens.
     * Satu per pengaduan. confidence_score 0-100 (angka besar = lebih mencurigakan).
     */
    public function up(): void
    {
        Schema::create('hasil_klasifikasi', function (Blueprint $table) {
            $table->id('id_klasifikasi');
            $table->foreignId('id_pengaduan')->unique()->constrained('pengaduan', 'id_pengaduan')->cascadeOnDelete();
            // Nilai 0-100. 92 = 92% probabilitas penipuan (semakin tinggi = semakin berbahaya).
            $table->decimal('confidence_score', 5, 2)->comment('0-100; makin tinggi makin berisiko');
            $table->string('kategori_hasil', 100)->comment('mis: Lowongan berpotensi penipuan, Lowongan Terverifikasi');
            $table->boolean('status_prioritas')->default(false)->comment('true = masuk antrean Analis');
            $table->string('metode_analisis', 255)->nullable();
            $table->timestamp('tanggal_klasifikasi')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_klasifikasi');
    }
};
