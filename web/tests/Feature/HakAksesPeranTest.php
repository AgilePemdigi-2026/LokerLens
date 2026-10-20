<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * NFR-SEC-01: fitur wajib login. NFR-SEC-02: tiap peran hanya mengakses fiturnya.
 */
class HakAksesPeranTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function halamanPerPeran(): array
    {
        return [
            'beranda'         => ['/beranda', 'Masyarakat'],
            'periksa lowongan' => ['/periksa-lowongan', 'Masyarakat'],
            'tinjau lowongan' => ['/tinjau-lowongan', 'Analis'],
            'tindak lanjut'   => ['/tindak-lanjut', 'Kepala Bidang'],
        ];
    }

    #[DataProvider('halamanPerPeran')]
    public function test_halaman_hanya_untuk_peran_yang_berwenang(string $url, string $peranBerwenang): void
    {
        foreach (['Masyarakat', 'Analis', 'Kepala Bidang'] as $peran) {
            $response = $this->actingAs(User::factory()->create(['role' => $peran]))->get($url);

            $peran === $peranBerwenang ? $response->assertOk() : $response->assertForbidden();
        }
    }

    #[DataProvider('halamanPerPeran')]
    public function test_halaman_wajib_login(string $url): void
    {
        $this->get($url)->assertRedirect('/login');
    }

    public function test_aksi_post_ditolak_untuk_peran_lain(): void
    {
        $masyarakat = User::factory()->create(['role' => 'Masyarakat']);
        $analis     = User::factory()->create(['role' => 'Analis']);

        $this->actingAs($masyarakat)->post('/tinjau-lowongan/detail/1/simpan')->assertForbidden();
        $this->actingAs($masyarakat)->post('/tindak-lanjut/1/keputusan')->assertForbidden();
        $this->actingAs($analis)->post('/tindak-lanjut/1/keputusan')->assertForbidden();
        $this->actingAs($analis)->post('/buat-surat/1/terbitkan')->assertForbidden();
        $this->actingAs($analis)->post('/periksa-lowongan')->assertForbidden();
    }
}
