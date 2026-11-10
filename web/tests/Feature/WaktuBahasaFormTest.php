<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Issue #25: zona waktu WIB, bahasa Indonesia, dan validasi form oleh server.
 * 1. Zona waktu Asia/Jakarta dan nama bulan bahasa Indonesia.
 * 2. Semua form ber-isian wajib memakai novalidate, sehingga pesan server
 *    bahasa Indonesia tampil di dekat isian (bukan tooltip browser).
 * 3. Pesan bawaan Laravel dari lang/id.
 * 4. Navbar "Keluar" dan tanggal Arsip Kasus dari Riwayat Kasus.
 */
class WaktuBahasaFormTest extends TestCase
{
    use RefreshDatabase;

    private User $masyarakat;
    private User $analis;
    private User $kepalaBidang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LokerLensSeeder::class);

        $this->masyarakat   = User::where('role', 'Masyarakat')->firstOrFail();
        $this->analis       = User::where('role', 'Analis')->firstOrFail();
        $this->kepalaBidang = User::where('role', 'Kepala Bidang')->firstOrFail();
    }

    private function kasus(string $tiket): Pengaduan
    {
        return Pengaduan::where('nomor_tiket', $tiket)->firstOrFail();
    }

    /**
     * Setiap <form> yang punya isian required wajib memakai novalidate.
     */
    private function assertFormRequiredPakaiNovalidate(TestResponse $response, string $halaman): void
    {
        preg_match_all('#<form\b([^>]*)>(.*?)</form>#s', $response->getContent(), $form, PREG_SET_ORDER);

        $jumlahDicek = 0;
        foreach ($form as [, $atribut, $isi]) {
            if (! preg_match('#\brequired\b#', $isi)) {
                continue;
            }
            $jumlahDicek++;
            $this->assertMatchesRegularExpression('#\bnovalidate\b#', $atribut, "Form di {$halaman} memakai required tanpa novalidate");
        }

        $this->assertGreaterThan(0, $jumlahDicek, "Tidak ada form ber-isian wajib di {$halaman}");
    }

    // ─── 1. Zona waktu & bahasa ──────────────────────────────────────────

    public function test_zona_waktu_dan_bahasa_aplikasi(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('id', app()->getLocale());
        $this->assertSame('Asia/Jakarta', now()->getTimezone()->getName());

        $tanggal = Carbon::create(2026, 10, 15, 9, 30);
        $this->assertSame('15 Okt 2026', $tanggal->translatedFormat('d M Y'));
        $this->assertSame('15 Oktober 2026', $tanggal->translatedFormat('d F Y'));
        $this->assertSame('Kamis', $tanggal->translatedFormat('l'));
    }

    public function test_riwayat_kasus_dicatat_dengan_jam_aplikasi(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 10, 21, 15, 0, 'Asia/Jakarta'));

        $kasus = $this->kasus('#LKR-2025-0842'); // Menunggu Keputusan
        $kasus->ubahStatus(StatusKasus::TidakDitindaklanjuti, LokerLensCopy::KASUS_DIARSIPKAN);

        $riwayat = $kasus->riwayatKasus()->first();
        $this->assertSame('2026-11-10 21:15', $riwayat->tanggal_update->format('Y-m-d H:i'));

        Carbon::setTestNow();
    }

    public function test_dashboard_kepala_bidang_memakai_nama_bulan_indonesia(): void
    {
        $kasus = $this->kasus('#LKR-2025-0842');
        $kasus->update(['tanggal_pengaduan' => Carbon::create(2026, 10, 5, 10, 0)]);

        $this->actingAs($this->kepalaBidang)
            ->get('/dashboard-kepala-bidang')
            ->assertOk()
            ->assertSee('05 Okt 2026')
            ->assertDontSee('05 Oct 2026');
    }

    // ─── 2. novalidate di semua form ber-isian wajib ─────────────────────

    public function test_form_tamu_memakai_novalidate(): void
    {
        foreach (['/login', '/register', '/forgot-password', '/reset-password/token-contoh?email=a@b.com'] as $url) {
            $this->assertFormRequiredPakaiNovalidate($this->get($url)->assertOk(), $url);
        }
    }

    public function test_form_pengguna_login_memakai_novalidate(): void
    {
        $this->actingAs($this->masyarakat);
        foreach (['/periksa-lowongan', '/profile', '/confirm-password'] as $url) {
            $this->assertFormRequiredPakaiNovalidate($this->get($url)->assertOk(), $url);
        }

        $klasifikasi = $this->kasus('#LKR-8819')->hasilKlasifikasi;
        $this->actingAs($this->analis);
        $this->assertFormRequiredPakaiNovalidate(
            $this->get('/tinjau-lowongan/detail/' . $klasifikasi->id_klasifikasi)->assertOk(), 'tinjau-lowongan detail');
        $this->assertFormRequiredPakaiNovalidate(
            $this->get('/dashboard-analis')->assertOk(), 'dashboard-analis');

        $this->actingAs($this->kepalaBidang);
        $this->assertFormRequiredPakaiNovalidate(
            $this->get('/buat-surat/' . $this->kasus('#LKR-8770')->id_pengaduan)->assertOk(), 'buat-surat');

        // Form keputusan tidak memakai required, tetapi tetap novalidate dan punya tempat pesan error
        $this->get('/tindak-lanjut/' . $this->kasus('#LKR-2025-0842')->id_pengaduan)
            ->assertOk()
            ->assertSee('id="form-keputusan" novalidate', false);
    }

    // ─── 3. Pesan server bahasa Indonesia di dekat isian ─────────────────

    public function test_login_kosong_menampilkan_pesan_di_bawah_isian(): void
    {
        $this->from('/login')->post('/login', ['email' => '', 'password' => ''])
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email'    => LokerLensCopy::LOGIN_EMAIL_WAJIB,
                'password' => LokerLensCopy::LOGIN_SANDI_WAJIB,
            ]);

        // Pesan isian kosong tampil di bawah isian, tanpa banner "Gagal masuk"
        $this->followingRedirects()->from('/login')->post('/login', ['email' => '', 'password' => ''])
            ->assertSee(LokerLensCopy::LOGIN_EMAIL_WAJIB)
            ->assertSee(LokerLensCopy::LOGIN_SANDI_WAJIB)
            ->assertDontSee(LokerLensCopy::GAGAL_LOGIN);
    }

    public function test_login_salah_tetap_menampilkan_banner_gagal_masuk(): void
    {
        $this->from('/login')->post('/login', ['email' => $this->masyarakat->email, 'password' => 'salah-sekali'])
            ->assertSessionHasErrors(['email' => LokerLensCopy::GAGAL_LOGIN]);

        $this->followingRedirects()->from('/login')
            ->post('/login', ['email' => $this->masyarakat->email, 'password' => 'salah-sekali'])
            ->assertSee(LokerLensCopy::GAGAL_LOGIN);
    }

    public function test_login_terlalu_sering_berbahasa_indonesia(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $this->masyarakat->email, 'password' => 'salah']);
        }

        // Pesan throttle dari lang/id/auth.php, tampil di banner
        $this->followingRedirects()->from('/login')
            ->post('/login', ['email' => $this->masyarakat->email, 'password' => 'salah'])
            ->assertSee('Terlalu banyak percobaan masuk. Silakan coba lagi dalam')
            ->assertDontSee('Too many login attempts');
    }

    public function test_daftar_kosong_menampilkan_semua_pesan_termasuk_persetujuan(): void
    {
        $this->from('/register')->post('/register', [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'persetujuan']);

        $this->followingRedirects()->from('/register')->post('/register', [])
            ->assertSee('Nama Lengkap wajib diisi.')
            ->assertSee('Anda harus menyetujui Kebijakan Privasi &amp; Syarat Layanan.', false);
    }

    public function test_pesan_bawaan_laravel_berbahasa_indonesia(): void
    {
        // Aturan max:255 tidak punya pesan khusus, jadi memakai lang/id/validation.php
        $this->from('/register')->post('/register', [
            'name' => str_repeat('a', 256),
            'email' => 'baru@contoh.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'persetujuan' => '1',
        ])->assertSessionHasErrors(['name' => 'Nama lengkap tidak boleh lebih dari 255 karakter.']);

        // Form profil (Breeze) juga memakai pesan bahasa Indonesia
        $this->actingAs($this->masyarakat)
            ->from('/profile')
            ->patch('/profile', ['name' => '', 'email' => 'bukan-email'])
            ->assertSessionHasErrors([
                'name'  => 'Nama lengkap wajib diisi.',
                'email' => 'Format email tidak valid.',
            ]);
    }

    public function test_reset_password_menampilkan_pesan_di_bawah_isian(): void
    {
        $url = '/reset-password/token-contoh?email=' . urlencode($this->masyarakat->email);

        $isian = [
            'token' => 'token-contoh',
            'email' => $this->masyarakat->email,
            'password' => 'sandiBaru123',
            'password_confirmation' => 'berbeda123',
        ];

        $this->from($url)->post('/reset-password', $isian)
            ->assertSessionHasErrors(['password' => LokerLensCopy::SANDI_BARU_TIDAK_COCOK]);

        $this->followingRedirects()->from($url)->post('/reset-password', $isian)
            ->assertSee(LokerLensCopy::SANDI_BARU_TIDAK_COCOK)
            ->assertDontSee(LokerLensCopy::TAUTAN_RESET_TIDAK_VALID);
    }

    public function test_terbitkan_surat_kosong_berbahasa_indonesia(): void
    {
        $id = $this->kasus('#LKR-8770')->id_pengaduan;

        // Sebelumnya hanya Simpan Draft yang punya pesan bahasa Indonesia
        $this->actingAs($this->kepalaBidang)
            ->from("/buat-surat/{$id}")
            ->post("/buat-surat/{$id}/terbitkan", ['kronologi' => '', 'tujuan_kepolisian' => ''])
            ->assertSessionHasErrors([
                'kronologi'         => LokerLensCopy::SURAT_KRONOLOGI_WAJIB,
                'tujuan_kepolisian' => LokerLensCopy::SURAT_TUJUAN_WAJIB,
            ]);

        $this->followingRedirects()->from("/buat-surat/{$id}")
            ->post("/buat-surat/{$id}/terbitkan", ['kronologi' => '', 'tujuan_kepolisian' => ''])
            ->assertSee(LokerLensCopy::SURAT_KRONOLOGI_WAJIB)
            ->assertSee(LokerLensCopy::SURAT_TUJUAN_WAJIB);
    }

    public function test_bukti_salah_tipe_menampilkan_pesan_di_bawah_isian_berkas(): void
    {
        Storage::fake('public');
        $id = $this->kasus('#LKR-8770')->id_pengaduan;

        $isian = fn () => [
            'kronologi' => 'Kronologi contoh',
            'tujuan_kepolisian' => 'Satreskrim Polres Wilayah Setempat',
            'bukti_tambahan' => [UploadedFile::fake()->create('catatan.txt', 1, 'text/plain')],
        ];

        $this->actingAs($this->kepalaBidang)
            ->from("/buat-surat/{$id}")
            ->post("/buat-surat/{$id}/draft", $isian())
            ->assertSessionHasErrors(['bukti_tambahan.0' => LokerLensCopy::SURAT_BUKTI_TIPE]);

        $this->followingRedirects()->from("/buat-surat/{$id}")
            ->post("/buat-surat/{$id}/draft", $isian())
            ->assertSee(LokerLensCopy::SURAT_BUKTI_TIPE);
    }

    public function test_keputusan_kosong_menampilkan_pesan(): void
    {
        $id = $this->kasus('#LKR-2025-0842')->id_pengaduan;

        $this->actingAs($this->kepalaBidang)
            ->from("/tindak-lanjut/{$id}")
            ->post("/tindak-lanjut/{$id}/keputusan", ['keputusan' => ''])
            ->assertSessionHasErrors(['keputusan' => LokerLensCopy::KEPUTUSAN_WAJIB]);

        $this->followingRedirects()->from("/tindak-lanjut/{$id}")
            ->post("/tindak-lanjut/{$id}/keputusan", ['keputusan' => ''])
            ->assertSee(LokerLensCopy::KEPUTUSAN_WAJIB);
        $this->assertSame(StatusKasus::MenungguKeputusan, $this->kasus('#LKR-2025-0842')->status_pengaduan);
    }

    public function test_periode_kustom_kosong_menampilkan_pesan(): void
    {
        $this->actingAs($this->analis)
            ->get('/dashboard-analis?periode=kustom&dari=&sampai=')
            ->assertOk()
            ->assertSee(LokerLensCopy::PERIODE_KUSTOM_TIDAK_VALID);

        $this->get('/dashboard-analis?periode=7')
            ->assertDontSee(LokerLensCopy::PERIODE_KUSTOM_TIDAK_VALID);
    }

    // ─── 4. Navbar & Arsip Kasus ─────────────────────────────────────────

    public function test_navbar_menampilkan_keluar(): void
    {
        $this->actingAs($this->analis)
            ->get('/tinjau-lowongan')
            ->assertSee('<span>' . LokerLensCopy::NAV_KELUAR . '</span>', false)
            ->assertDontSee('Log Out');
    }

    public function test_arsip_kasus_memakai_tanggal_selesai_bukan_updated_at(): void
    {
        $kasus = $this->kasus('#LKR-8798'); // Selesai

        // Tanggal selesai dari Riwayat Kasus; updated_at sengaja dibuat berbeda
        $kasus->riwayatKasus()->where('status_kasus', StatusKasus::Selesai->value)
            ->update(['tanggal_update' => Carbon::create(2026, 8, 17, 10, 0)]);
        Pengaduan::whereKey($kasus->id_pengaduan)->toBase()
            ->update(['updated_at' => Carbon::create(2026, 12, 1, 10, 0)]);

        $this->actingAs($this->kepalaBidang)
            ->get('/arsip-kasus')
            ->assertSee('17 Agt 2026')
            ->assertDontSee('01 Des 2026');

        $this->get('/arsip-kasus/' . $kasus->id_pengaduan)
            ->assertSee('17 Agt 2026')
            ->assertDontSee('01 Des 2026');
    }

    public function test_arsip_kasus_diurutkan_dari_yang_terakhir_selesai(): void
    {
        $lama = $this->kasus('#LKR-8798');
        $baru = $this->kasus('#LKR-8752');

        $lama->riwayatKasus()->where('status_kasus', StatusKasus::Selesai->value)
            ->update(['tanggal_update' => Carbon::create(2026, 1, 5)]);
        $baru->riwayatKasus()->where('status_kasus', StatusKasus::TidakTerbukti->value)
            ->update(['tanggal_update' => Carbon::create(2026, 9, 5)]);

        $this->actingAs($this->kepalaBidang)
            ->get('/arsip-kasus')
            ->assertSeeInOrder([$baru->nomor_tiket, $lama->nomor_tiket]);
    }
}
