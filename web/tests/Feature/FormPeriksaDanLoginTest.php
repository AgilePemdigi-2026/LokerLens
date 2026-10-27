<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FormPeriksaDanLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kolom yang disabled tidak ikut terkirim saat form di-submit. Kalau isian
     * dikunci dengan disabled ketika "Periksa Sekarang" ditekan, server menerima
     * form kosong dan selalu membalas "Data belum lengkap".
     */
    public function test_isian_form_periksa_tidak_dikunci_dengan_disabled(): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => 'Masyarakat']))
            ->get('/periksa-lowongan')->assertOk()->getContent();

        preg_match_all('#<(?:input|textarea|select)\b[^>]*\bname="([^"]+)"[^>]*>#', $html, $kolom, PREG_SET_ORDER);
        $this->assertNotEmpty($kolom);

        foreach ($kolom as [$tag, $nama]) {
            $this->assertStringNotContainsString(':disabled', $tag, "Kolom {$nama} dikunci dengan :disabled sehingga tidak ikut terkirim");
            $this->assertDoesNotMatchRegularExpression('#\sdisabled[\s>=]#', $tag, "Kolom {$nama} disabled");
        }

        foreach (['nama_perusahaan', 'posisi_lowongan', 'deskripsi_lowongan'] as $wajib) {
            $this->assertMatchesRegularExpression('#name="' . $wajib . '"[^>]*:readonly="submitting"#', $html, "Kolom {$wajib} tidak dikunci dengan readonly");
        }
    }

    public function test_form_periksa_lengkap_berhasil_diperiksa(): void
    {
        $this->palsukanApiMl(0.9);

        $this->actingAs(User::factory()->create(['role' => 'Masyarakat']))
            ->post('/periksa-lowongan', [
                'nama_perusahaan'    => 'PT Maju Bersama Sejahtera',
                'posisi_lowongan'    => 'Staff Administrasi',
                'deskripsi_lowongan' => 'Dibutuhkan staff administrasi, wajib transfer biaya pendaftaran sebelum wawancara.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectContains('/periksa-lowongan/hasil/');
    }

    /** @return array<string, array{string, string}> */
    public static function peranDanHalamanAwal(): array
    {
        return [
            'Masyarakat'    => ['Masyarakat', '/beranda'],
            'Analis'        => ['Analis', '/tinjau-lowongan'],
            'Kepala Bidang' => ['Kepala Bidang', '/tindak-lanjut'],
        ];
    }

    #[DataProvider('peranDanHalamanAwal')]
    public function test_membuka_login_saat_sudah_login_diarahkan_ke_halaman_peran(string $peran, string $halamanAwal): void
    {
        $user = User::factory()->create(['role' => $peran]);

        $this->actingAs($user)->get('/login')->assertRedirect($halamanAwal);
        $this->actingAs($user)->get('/register')->assertRedirect($halamanAwal);
    }

    #[DataProvider('peranDanHalamanAwal')]
    public function test_alamat_awal_tidak_berputar_saat_sudah_login(string $peran, string $halamanAwal): void
    {
        $user = User::factory()->create(['role' => $peran]);

        // "/" → "/login" → halaman peran (sebelumnya berputar /login ⇄ / tanpa henti)
        $this->actingAs($user)->get('/')->assertRedirect('/login');
        $this->actingAs($user)->get('/login')->assertRedirect($halamanAwal);
        $this->actingAs($user)->get($halamanAwal)->assertOk();
    }

    public function test_login_tetap_tampil_untuk_tamu(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }

    public function test_buat_surat_tanpa_nomor_kasus_diarahkan_ke_daftar_laporan(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Kepala Bidang']))
            ->get('/buat-surat')->assertRedirect('/tindak-lanjut');

        // Hak akses tetap berlaku
        $this->actingAs(User::factory()->create(['role' => 'Masyarakat']))
            ->get('/buat-surat')->assertForbidden();
        auth()->logout();
        $this->get('/buat-surat')->assertRedirect('/login');
    }
}
