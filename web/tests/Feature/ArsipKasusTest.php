<?php

namespace Tests\Feature;

use App\Enums\StatusKasus;
use App\Models\HasilKlasifikasi;
use App\Models\LowonganKerja;
use App\Models\Pengaduan;
use App\Models\Perusahaan;
use App\Models\RiwayatKasus;
use App\Models\SuratTeguranLaporanPolisi;
use App\Models\TindakLanjutKasus;
use App\Models\User;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian Fitur Arsip Kasus (Kepala Bidang) — Sprint 2.
 * Kriteria Selesai (Acceptance Criteria):
 * 1. Halaman arsip menampilkan kasus berstatus akhir beserta keputusan, tanggal, dan nomor surat (jika ada).
 * 2. Kasus "Menunggu Keputusan" dan status belum akhir lainnya tidak muncul di daftar.
 * 3. Filter status dan pencarian bekerja dengan benar.
 * 4. Detail menampilkan Riwayat Kasus dan nomor surat bila ada; kasus tanpa surat tetap tampil normal.
 * 5. Detail kasus yang belum berstatus akhir mengembalikan 404.
 * 6. Hanya Kepala Bidang yang dapat mengakses; peran lain ditolak 403, tamu dialihkan ke login.
 * 7. Tombol "Lihat Arsip Kasus" di halaman hasil terbit mengarah ke route arsip ini.
 * 8. Menu "Arsip Kasus" di navbar Kepala Bidang aktif saat halaman dibuka.
 */
class ArsipKasusTest extends TestCase
{
    use RefreshDatabase;

    private User $kepalaBidang;
    private User $analis;
    private User $masyarakat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LokerLensSeeder::class);

        $this->kepalaBidang = User::where('role', 'Kepala Bidang')->firstOrFail();
        $this->analis       = User::where('role', 'Analis')->firstOrFail();
        $this->masyarakat   = User::where('role', 'Masyarakat')->firstOrFail();
    }

    /**
     * Test 1: Hak akses — Hanya Kepala Bidang yang dapat mengakses (NFR-SEC-02).
     */
    public function test_hanya_kepala_bidang_yang_dapat_mengakses_arsip_kasus(): void
    {
        // 1. Kepala Bidang bisa mengakses daftar arsip
        $this->actingAs($this->kepalaBidang)
            ->get('/arsip-kasus')
            ->assertOk();

        // 2. Analis ditolak (403 Forbidden)
        $this->actingAs($this->analis)
            ->get('/arsip-kasus')
            ->assertForbidden();

        // 3. Masyarakat ditolak (403 Forbidden)
        $this->actingAs($this->masyarakat)
            ->get('/arsip-kasus')
            ->assertForbidden();
    }

    /**
     * Test 2: Pengguna belum login dialihkan ke halaman login (NFR-SEC-01).
     */
    public function test_pengguna_belum_login_dialihkan_ke_login(): void
    {
        $this->get('/arsip-kasus')
            ->assertRedirect('/login');

        $kasusSelesai = Pengaduan::where('status_pengaduan', StatusKasus::Selesai)->firstOrFail();
        $this->get('/arsip-kasus/' . $kasusSelesai->id_pengaduan)
            ->assertRedirect('/login');
    }

    /**
     * Test 3: Daftar arsip hanya menampilkan kasus berstatus akhir.
     * Kasus "Menunggu Keputusan" atau "Tindak Lanjut Disetujui" TIDAK boleh muncul.
     */
    public function test_daftar_arsip_hanya_menampilkan_kasus_berstatus_akhir(): void
    {
        // Kasus-kasus dari seeder
        $kasusMenunggu = Pengaduan::where('status_pengaduan', StatusKasus::MenungguKeputusan)->first();
        $kasusDisetujui = Pengaduan::where('status_pengaduan', StatusKasus::TindakLanjutDisetujui)->first();
        $kasusSelesai = Pengaduan::where('status_pengaduan', StatusKasus::Selesai)->firstOrFail();
        $kasusDiarsipkan = Pengaduan::where('status_pengaduan', StatusKasus::TidakDitindaklanjuti)->firstOrFail();

        $response = $this->actingAs($this->kepalaBidang)
            ->get('/arsip-kasus');

        $response->assertOk();
        $response->assertViewIs('arsip-kasus.index');

        // Kasus akhir harus muncul
        $response->assertSee($kasusSelesai->nomor_tiket);
        $response->assertSee($kasusDiarsipkan->nomor_tiket);

        // Kasus yang belum berstatus akhir TIDAK boleh muncul
        if ($kasusMenunggu) {
            $response->assertDontSee($kasusMenunggu->nomor_tiket);
        }
        if ($kasusDisetujui) {
            $response->assertDontSee($kasusDisetujui->nomor_tiket);
        }
    }

    /**
     * Test 4: Filter status dan pencarian pada daftar arsip bekerja.
     */
    public function test_filter_status_dan_pencarian_arsip_bekerja(): void
    {
        $kasusSelesai = Pengaduan::where('status_pengaduan', StatusKasus::Selesai)->firstOrFail();
        $kasusDiarsipkan = Pengaduan::where('status_pengaduan', StatusKasus::TidakDitindaklanjuti)->firstOrFail();

        // 1. Filter status = Selesai
        $responseSelesai = $this->actingAs($this->kepalaBidang)
            ->get('/arsip-kasus?status=' . urlencode(StatusKasus::Selesai->value));

        $responseSelesai->assertOk();
        $responseSelesai->assertSee($kasusSelesai->nomor_tiket);
        $responseSelesai->assertDontSee($kasusDiarsipkan->nomor_tiket);

        // 2. Filter status = Tidak Ditindaklanjuti
        $responseArsip = $this->actingAs($this->kepalaBidang)
            ->get('/arsip-kasus?status=' . urlencode(StatusKasus::TidakDitindaklanjuti->value));

        $responseArsip->assertOk();
        $responseArsip->assertSee($kasusDiarsipkan->nomor_tiket);
        $responseArsip->assertDontSee($kasusSelesai->nomor_tiket);

        // 3. Pencarian berdasarkan nomor tiket
        $responseCari = $this->actingAs($this->kepalaBidang)
            ->get('/arsip-kasus?cari=' . urlencode($kasusSelesai->nomor_tiket));

        $responseCari->assertOk();
        $responseCari->assertSee($kasusSelesai->nomor_tiket);
        $responseCari->assertDontSee($kasusDiarsipkan->nomor_tiket);
    }

    /**
     * Test 5: Detail arsip menampilkan Riwayat Kasus dan nomor surat (bila ada).
     */
    public function test_detail_arsip_menampilkan_riwayat_kasus_dan_surat_terbit(): void
    {
        // Kasus Selesai dengan surat terbit
        $kasusSelesai = Pengaduan::with(['tindakLanjutKasus.suratTeguranLaporanPolisi', 'riwayatKasus'])
            ->where('status_pengaduan', StatusKasus::Selesai)
            ->firstOrFail();

        $surat = $kasusSelesai->tindakLanjutKasus->suratTeguranLaporanPolisi;

        $response = $this->actingAs($this->kepalaBidang)
            ->get('/arsip-kasus/' . $kasusSelesai->id_pengaduan);

        $response->assertOk();
        $response->assertViewIs('arsip-kasus.show');
        $response->assertSee($kasusSelesai->nomor_tiket);
        $response->assertSee($surat->nomor_surat);
        $response->assertSee('Garis Waktu Riwayat Kasus');

        // Pastikan entri riwayat kasus ada di halaman
        foreach ($kasusSelesai->riwayatKasus as $riwayat) {
            $response->assertSee($riwayat->status_kasus);
        }
    }

    /**
     * Test 6: Detail arsip untuk kasus tanpa surat (Tidak Ditindaklanjuti / Tidak Terbukti) tetap tampil benar.
     */
    public function test_detail_arsip_tanpa_surat_tetap_tampil_benar(): void
    {
        $kasusDiarsipkan = Pengaduan::where('status_pengaduan', StatusKasus::TidakDitindaklanjuti)->firstOrFail();

        $response = $this->actingAs($this->kepalaBidang)
            ->get('/arsip-kasus/' . $kasusDiarsipkan->id_pengaduan);

        $response->assertOk();
        $response->assertViewIs('arsip-kasus.show');
        $response->assertSee($kasusDiarsipkan->nomor_tiket);
        $response->assertSee('Garis Waktu Riwayat Kasus');
        $response->assertSee('Tidak Diterbitkan');
    }

    /**
     * Test 7: Detail kasus yang belum berstatus akhir mengembalikan 404 (Not Found).
     */
    public function test_detail_kasus_bukan_status_akhir_mengembalikan_404(): void
    {
        $kasusMenunggu = Pengaduan::where('status_pengaduan', StatusKasus::MenungguKeputusan)->firstOrFail();

        $response = $this->actingAs($this->kepalaBidang)
            ->get('/arsip-kasus/' . $kasusMenunggu->id_pengaduan);

        $response->assertNotFound();
    }

    /**
     * Test 8: Tombol "Lihat Arsip Kasus" di halaman hasil terbit mengarah ke route arsip.
     */
    public function test_tombol_lihat_arsip_di_halaman_terbit_mengarah_ke_route_arsip(): void
    {
        $kasusSelesai = Pengaduan::with('tindakLanjutKasus.suratTeguranLaporanPolisi')
            ->where('status_pengaduan', StatusKasus::Selesai)
            ->firstOrFail();

        $surat = $kasusSelesai->tindakLanjutKasus->suratTeguranLaporanPolisi;

        $response = $this->actingAs($this->kepalaBidang)
            ->get(route('buat-surat.terbit', [
                'pengaduanId' => $kasusSelesai->id_pengaduan,
                'suratId'     => $surat->id_surat,
            ]));

        $response->assertOk();
        $response->assertSee(route('arsip-kasus.show', $kasusSelesai->id_pengaduan));
    }

    /**
     * Test 9: Menu "Arsip Kasus" di navigasi Kepala Bidang aktif.
     */
    public function test_menu_arsip_kasus_di_navigasi_kepala_bidang_aktif(): void
    {
        $response = $this->actingAs($this->kepalaBidang)
            ->get('/arsip-kasus');

        $response->assertOk();
        $response->assertSee(route('arsip-kasus.index'));
        $response->assertSee('text-teal-400 bg-navy-800 font-semibold', false);
    }
}
