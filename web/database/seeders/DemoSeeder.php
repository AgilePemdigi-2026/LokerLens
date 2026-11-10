<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Data contoh untuk laptop pengembang, test BDD, dan demo: akun demo (kata sandi
 * password123) dan kasus fiktif dari LokerLensSeeder.
 *
 * Hanya dijalankan manual: php artisan db:seed --class=DemoSeeder
 * atau sekaligus reset: php artisan migrate:fresh --seeder=DemoSeeder
 *
 * Ditolak di server produksi (APP_ENV=production), lihat LokerLensSeeder::run().
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LokerLensSeeder::class);
    }
}
