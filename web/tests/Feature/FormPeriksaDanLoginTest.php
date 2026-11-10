<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
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

    /**
     * SRS B.2-2: form kosong harus menampilkan "Data belum lengkap, mohon lengkapi terlebih dahulu".
     * Form memakai novalidate supaya browser tidak menahan form kosong dengan tooltip bawaannya,
     * sehingga validasi dan pesan resminya datang dari server.
     */
    public function test_form_periksa_kosong_menampilkan_pesan_resmi_dari_server(): void
    {
        $masyarakat = User::factory()->create(['role' => 'Masyarakat']);

        $html = $this->actingAs($masyarakat)->get('/periksa-lowongan')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<form id="form-periksa"[^>]*\snovalidate[\s>]#', $html);

        $this->actingAs($masyarakat)->from('/periksa-lowongan')->followingRedirects()
            ->post('/periksa-lowongan', [])
            ->assertSee(LokerLensCopy::VALIDASI_BELUM_LENGKAP)
            ->assertDontSee('Hasil Pemeriksaan');
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
            // Issue #25: Analis diarahkan ke Dashboard Analis
            'Analis'        => ['Analis', '/dashboard-analis'],
            // Sejak PR #47: Kepala Bidang diarahkan ke Dashboard
            'Kepala Bidang' => ['Kepala Bidang', '/dashboard-kepala-bidang'],
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

    public function test_login_tidak_menampilkan_kotak_akun_demo(): void
    {
        // Dulu kotak ini hanya muncul di env local, jadi uji di local juga (issue #25)
        $this->app['env'] = 'local';

        $this->get('/login')->assertOk()
            ->assertDontSee('Akun Uji Coba Demo')
            ->assertDontSee('password123');
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
