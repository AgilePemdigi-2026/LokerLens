<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Akun demo dari seeder harus bisa login lewat form login biasa,
 * dan petunjuk akun di halaman login harus sama dengan seeder.
 */
class LoginAkunDemoTest extends TestCase
{
    use RefreshDatabase;

    private const HALAMAN_TUJUAN = [
        'Masyarakat'    => 'beranda',
        'Analis'        => 'tinjau-lowongan.index',
        'Kepala Bidang' => 'tindak-lanjut.index',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LokerLensSeeder::class);
    }

    private function loginBerhasil(string $email, string $password, string $peran): void
    {
        $this->post('/login', ['email' => $email, 'password' => $password])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route(self::HALAMAN_TUJUAN[$peran]));

        $this->assertAuthenticatedAs(User::where('email', $email)->firstOrFail());
        $this->post('/logout');
        $this->assertGuest();
    }

    public function test_akun_demo_bisa_login_untuk_ketiga_peran(): void
    {
        foreach (LokerLensSeeder::AKUN_DEMO as $peran => $email) {
            $this->loginBerhasil($email, LokerLensSeeder::PASSWORD_DEMO, $peran);
        }
    }

    public function test_akun_di_petunjuk_halaman_login_sesuai_seeder(): void
    {
        // Kotak petunjuk hanya tampil di APP_ENV=local
        $this->app['env'] = 'local';
        $html = $this->get('/login')->assertOk()->getContent();
        $this->app['env'] = 'testing';

        preg_match_all('#<strong[^>]*>(Masyarakat|Analis|Kepala Bidang):</strong><br>(\S+) / (\S+)</div>#', $html, $petunjuk, PREG_SET_ORDER);
        $this->assertCount(3, $petunjuk, 'Kotak petunjuk akun demo tidak ditemukan di halaman login');

        foreach ($petunjuk as [, $peran, $email, $password]) {
            $this->assertSame(LokerLensSeeder::AKUN_DEMO[$peran], $email, "Email petunjuk untuk {$peran} tidak sama dengan seeder");
            $this->loginBerhasil($email, $password, $peran);
        }
    }

    public function test_akun_yang_tidak_ada_tetap_gagal_dengan_pesan_resmi(): void
    {
        $this->post('/login', ['email' => 'budi@gmail.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => \App\Constants\LokerLensCopy::GAGAL_LOGIN]);

        $this->assertGuest();
    }
}
