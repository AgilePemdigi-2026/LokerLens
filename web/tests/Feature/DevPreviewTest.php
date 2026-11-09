<?php

namespace Tests\Feature;

use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use App\Models\User;
use App\Support\DevPreview;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DevPreviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Muat ulang routes/web.php dengan APP_ENV dan DEV_PREVIEW tertentu,
     * supaya yang diuji adalah kode pendaftaran route yang sebenarnya.
     * APP_ENV dikembalikan ke "testing" setelahnya, karena syarat hanya dicek
     * saat route didaftarkan (dan agar perilaku test lain, misalnya CSRF, tetap normal).
     *
     * @return bool hasil DevPreview::aktif() saat route didaftarkan
     */
    private function muatUlangRoute(string $env, bool $devPreview): bool
    {
        $envAsli = $this->app['env'];
        $this->app['env'] = $env;
        config(['lokerlens.dev_preview' => $devPreview]);
        $aktif = DevPreview::aktif();

        $router = $this->app['router'];
        $router->setRoutes(new RouteCollection);
        $router->middleware('web')->group(base_path('routes/web.php'));
        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();

        $this->app['env'] = $envAsli;

        return $aktif;
    }

    /** @return array<string, array{string, bool, bool}> */
    public static function kombinasiPengaturan(): array
    {
        return [
            'local + DEV_PREVIEW=true'       => ['local', true, true],
            'local + DEV_PREVIEW=false'      => ['local', false, false],
            'production + DEV_PREVIEW=true'  => ['production', true, false],
            'production + DEV_PREVIEW=false' => ['production', false, false],
            'testing + DEV_PREVIEW=true'     => ['testing', true, false],
        ];
    }

    #[DataProvider('kombinasiPengaturan')]
    public function test_route_dev_hanya_terdaftar_jika_local_dan_dev_preview_true(string $env, bool $devPreview, bool $harusAktif): void
    {
        $this->assertSame($harusAktif, $this->muatUlangRoute($env, $devPreview));
        $this->assertSame($harusAktif, Route::has('dev.index'));
        $this->assertSame($harusAktif, Route::has('dev.masuk'));
    }

    public function test_dev_tidak_bisa_diakses_jika_dev_preview_false(): void
    {
        $this->seed(LokerLensSeeder::class);
        $this->muatUlangRoute('local', false);

        $this->get('/dev')->assertNotFound();
        $this->post('/dev/masuk/Analis')->assertNotFound();
        $this->assertGuest();
    }

    public function test_dev_tidak_terdaftar_dengan_pengaturan_bawaan(): void
    {
        // .env.example dan phpunit.xml tidak mengaktifkan DEV_PREVIEW
        $this->assertFalse(config('lokerlens.dev_preview'));
        $this->get('/dev')->assertNotFound();
    }

    public function test_tombol_masuk_login_sebagai_akun_demo_lalu_kembali_ke_dev(): void
    {
        $this->seed(LokerLensSeeder::class);
        $this->muatUlangRoute('local', true);

        $this->get('/dev')->assertOk()
            ->assertSee('Masuk sebagai Masyarakat')
            ->assertSee('Masuk sebagai Analis')
            ->assertSee('Masuk sebagai Kepala Bidang');

        foreach (LokerLensSeeder::AKUN_DEMO as $peran => $email) {
            $this->post('/dev/masuk/' . rawurlencode($peran))->assertRedirect(route('dev.index'));
            $this->assertAuthenticatedAs(User::where('email', $email)->firstOrFail());
        }

        $this->post('/dev/masuk/Admin')->assertNotFound();
    }

    public function test_aturan_hak_akses_tetap_berlaku_setelah_masuk_lewat_dev(): void
    {
        $this->seed(LokerLensSeeder::class);
        $this->muatUlangRoute('local', true);

        $this->post('/dev/masuk/Masyarakat');

        $this->get('/beranda')->assertOk();
        $this->get('/tinjau-lowongan')->assertForbidden();
        $this->get('/tindak-lanjut')->assertForbidden();
    }

    public function test_seeder_menyediakan_kasus_untuk_setiap_status(): void
    {
        $this->seed(LokerLensSeeder::class);

        foreach (StatusKasus::cases() as $status) {
            $this->assertTrue(Pengaduan::where('status_pengaduan', $status)->exists(), "Tidak ada kasus berstatus {$status->value}");
        }
    }

    public function test_semua_tautan_di_dev_bisa_dibuka_dengan_peran_yang_tertulis(): void
    {
        $this->seed(LokerLensSeeder::class);
        $this->muatUlangRoute('local', true);

        $akun = collect(LokerLensSeeder::AKUN_DEMO)->map(fn ($email) => User::where('email', $email)->firstOrFail());

        foreach (DevPreview::tautan() as $kelompok => $daftar) {
            foreach ($daftar as $item) {
                $this->assertNotNull($item['url'], "{$kelompok}: {$item['label']} tidak punya tautan");

                if ($item['peran'] === 'Tamu') {
                    auth()->logout();
                } else {
                    $this->actingAs($akun[$item['peran']] ?? $akun['Masyarakat']);
                }

                $this->get($item['url'])->assertOk();
            }
        }
    }
}
