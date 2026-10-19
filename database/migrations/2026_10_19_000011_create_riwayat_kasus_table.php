<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_kasus', function (Blueprint $table) {
            $table->id('id_riwayat');
            $table->foreignId('id_pengaduan')->constrained('pengaduan', 'id_pengaduan')->cascadeOnDelete();
            $table->string('status_kasus', 100);
            $table->text('keterangan')->nullable();
            $table->timestamp('tanggal_update')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kasus');
    }
};
