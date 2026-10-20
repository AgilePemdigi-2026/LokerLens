<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel perusahaan sesuai PDM LokerLens.
     */
    public function up(): void
    {
        Schema::create('perusahaan', function (Blueprint $table) {
            $table->id('id_perusahaan');
            $table->string('nama_perusahaan', 255);
            $table->enum('status_terdaftar', ['Terdaftar', 'Tidak Terdaftar', 'Belum Diverifikasi'])->default('Belum Diverifikasi');
            $table->text('alamat')->nullable();
            $table->string('domain_email', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perusahaan');
    }
};
