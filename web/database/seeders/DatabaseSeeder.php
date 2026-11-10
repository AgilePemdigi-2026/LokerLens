<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder bawaan (php artisan db:seed / migrate --seed). Aman untuk server produksi.
 *
 * Saat ini LokerLens tidak butuh data awal di produksi: status kasus ada di kode
 * (App\Enums\StatusKasus) dan akun petugas dibuat dengan perintah
 * php artisan lokerlens:buat-petugas. Jika nanti ada data referensi yang wajib ada
 * (mis. daftar instansi), tambahkan di sini.
 *
 * Data contoh (akun demo dan kasus fiktif) ada di DemoSeeder dan hanya dijalankan manual:
 * php artisan db:seed --class=DemoSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        //
    }
}
