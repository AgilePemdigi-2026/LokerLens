<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel indikator_mencurigakan sesuai PDM LokerLens.
     * Banyak per Hasil_Klasifikasi — berisi kata/frasa pemicu.
     */
    public function up(): void
    {
        Schema::create('indikator_mencurigakan', function (Blueprint $table) {
            $table->id('id_indikator');
            $table->foreignId('id_klasifikasi')->constrained('hasil_klasifikasi', 'id_klasifikasi')->cascadeOnDelete();
            $table->string('nama_indikator', 255)->comment('mis: biaya administrasi Rp150.000 sebelum wawancara');
            $table->string('kategori_indikator', 100)->comment('mis: Biaya Fiktif, Email Gratisan, Gaji Janggal');
            $table->decimal('bobot_indikator', 5, 2)->nullable()->comment('0-100; kontribusi indikator ke skor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indikator_mencurigakan');
    }
};
