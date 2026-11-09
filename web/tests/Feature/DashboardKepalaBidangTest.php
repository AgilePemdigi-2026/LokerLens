<?php

namespace Tests\Feature;

use App\Enums\StatusKasus;
use App\Models\HasilKlasifikasi;
use App\Models\LowonganKerja;
use App\Models\Pengaduan;
use App\Models\Perusahaan;
use App\Models\SuratTeguranLaporanPolisi;
use App\Models\TindakLanjutKasus;
use App\Models\User;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian Fitur Dashboard Kepala Bidang (Sprint 2 - Issue #14).
 * Kriteria Selesai:
 * 1. Menampilkan ringkasan jumlah kasus per status tahap Kepala Bidang
 *    (Menunggu Keputusan, Tindak Lanjut Disetujui, Tidak Ditindaklanjuti, Selesai).
 * 2. Angka sesuai data di database.
 * 3. Hanya bisa diakses Kepala Bidang (peran lain ditolak 403, tamu dialihkan ke login).
 * 4. Menu "Dashboard" di navbar Kepala Bidang aktif.
 */
class DashboardKepalaBidangTest extends TestCase
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
     * Test 1: Halaman hanya bisa diakses Kepala Bidang (NFR-SEC-02).
     */
    public function test_hanya_kepala_bidang_yang_bisa_mengakses_dashboard_kepala_bidang(): void
    {
        // 1. Kepala Bidang bisa mengakses
        $this->actingAs($this->kepalaBidang)
            ->get('/dashboard-kepala-bidang')
            ->assertOk();

        // 2. Masyarakat ditolak (403 Forbidden)
        $this->actingAs($this->masyarakat)
            ->get('/dashboard-kepala-bidang')
            ->assertForbidden();

        // 3. Analis ditolak (403 Forbidden)
        $this->actingAs($this->analis)
            ->get('/dashboard-kepala-bidang')
            ->assertForbidden();
    }

    /**
     * Test 2: Pengguna belum login dialihkan ke halaman login (NFR-SEC-01).
     */
    public function test_halaman_dashboard_kepala_bidang_wajib_login(): void
    {
        $this->get('/dashboard-kepala-bidang')
            ->assertRedirect('/login');
    }

    /**
     * Test 2: Menampilkan ringkasan jumlah kasus sesuai data di database.
     */
    public function test_dashboard_kepala_bidang_menampilkan_ringkasan_sesuai_database(): void
    {
        $expectedMenunggu  = Pengaduan::where('status_pengaduan', StatusKasus::MenungguKeputusan)->count();
        $expectedDisetujui = Pengaduan::where('status_pengaduan', StatusKasus::TindakLanjutDisetujui)->count();
        $expectedDitolak   = Pengaduan::where('status_pengaduan', StatusKasus::TidakDitindaklanjuti)->count();
        $expectedSelesai   = Pengaduan::where('status_pengaduan', StatusKasus::Selesai)->count();
        $expectedSurat     = SuratTeguranLaporanPolisi::where('status_penerbitan', 'Terbit')->count();

        $response = $this->actingAs($this->kepalaBidang)
            ->get('/dashboard-kepala-bidang');

        $response->assertOk();
        $response->assertViewIs('dashboard-kepala-bidang.index');
        $response->assertViewHas('jumlahMenunggu', $expectedMenunggu);
        $response->assertViewHas('jumlahDisetujui', $expectedDisetujui);
        $response->assertViewHas('jumlahDitolak', $expectedDitolak);
        $response->assertViewHas('jumlahSelesai', $expectedSelesai);
        $response->assertViewHas('jumlahSuratTerbit', $expectedSurat);

        // Pastikan angka tampil di DOM
        $response->assertSee((string) $expectedMenunggu);
        $response->assertSee((string) $expectedDisetujui);
        $response->assertSee((string) $expectedDitolak);
        $response->assertSee((string) $expectedSelesai);
    }

    /**
     * Test 3: Angka ringkasan diperbarui secara dinamis saat kasus diputuskan.
     */
    public function test_dashboard_kepala_bidang_memperbarui_angka_secara_dinamis(): void
    {
        $initialMenunggu = Pengaduan::where('status_pengaduan', StatusKasus::MenungguKeputusan)->count();

        // Tambah satu pengaduan baru berstatus Menunggu Keputusan
        $perusahaan = Perusahaan::first();
        $lowongan = LowonganKerja::create([
            'id_perusahaan'     => $perusahaan->id_perusahaan,
            'posisi_lowongan'   => 'Staff Audit Finansial',
            'deskripsi_lowongan'=> 'Dibutuhkan segera staff audit internal.',
            'sumber_lowongan'   => 'LinkedIn',
        ]);

        $pengaduan = Pengaduan::create([
            'id_pengguna'        => $this->masyarakat->id,
            'id_lowongan'        => $lowongan->id_lowongan,
            'nomor_tiket'        => '#LKR-99999',
            'tanggal_pengaduan'  => now(),
            'status_pengaduan'   => StatusKasus::MenungguKeputusan,
        ]);

        HasilKlasifikasi::create([
            'id_pengaduan'     => $pengaduan->id_pengaduan,
            'confidence_score' => 92.5,
            'label_risiko'     => 'Tinggi',
            'kategori_hasil'   => 'Indikasi Penipuan Kuat',
            'tanggal_analisis' => now(),
        ]);

        $response = $this->actingAs($this->kepalaBidang)
            ->get('/dashboard-kepala-bidang');

        $response->assertOk();
        $response->assertViewHas('jumlahMenunggu', $initialMenunggu + 1);
        $response->assertSee('#LKR-99999');
        $response->assertSee('Staff Audit Finansial');
    }

    /**
     * Test 4: Menu "Dashboard" di navbar Kepala Bidang aktif dan menautkan rute yang benar.
     */
    public function test_menu_dashboard_di_navbar_kepala_bidang_aktif(): void
    {
        $response = $this->actingAs($this->kepalaBidang)
            ->get('/dashboard-kepala-bidang');

        $response->assertOk();
        // Memastikan navbar memuat link aktif ke dashboard-kepala-bidang
        $response->assertSee(route('dashboard-kepala-bidang.index'));
        $response->assertSee('text-teal-400 bg-navy-800 font-semibold', false);
    }
}
