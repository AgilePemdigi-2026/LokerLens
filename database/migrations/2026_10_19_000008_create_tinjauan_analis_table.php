<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel tinjauan_analis sesuai PDM LokerLens.
     * 1 per Hasil_Klasifikasi. Analis hanya memberi catatan & hasil tinjauan (BR-06).
     */
    public function up(): void
    {
        Schema::create('tinjauan_analis', function (Blueprint $table) {
            $table->id('id_tinjauan');
            $table->foreignId('id_klasifikasi')->unique()->constrained('hasil_klasifikasi', 'id_klasifikasi')->cascadeOnDelete();
            $table->foreignId('id_pengguna')->constrained('users', 'id')->cascadeOnDelete(); // Analis
            $table->text('catatan_tinjauan');
            // Nilai dari select hasil_tinjauan. Contoh BDD: "Mencurigakan". Opsi lain TBD.
            $table->string('hasil_tinjauan', 100);
            $table->timestamp('tanggal_tinjauan')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tinjauan_analis');
    }
};
