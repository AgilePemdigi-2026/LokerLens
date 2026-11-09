<?php

namespace Tests\Feature;

use App\Enums\HasilTinjauan;
use App\Enums\StatusKasus;
use App\Models\HasilKlasifikasi;
use App\Models\LowonganKerja;
use App\Models\Pengaduan;
use App\Models\Perusahaan;
use App\Models\TinjauanAnalis;
use App\Models\User;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian Fitur Dashboard Analis (Sprint 2).
 * Kriteria Selesai:
 * 1. Menampilkan ringkasan jumlah kasus per status tahap Analis (Perlu Tinjauan, Sedang Ditinjau)
 *    dan hasil tinjauan (Terkonfirmasi Mencurigakan, Tidak Terbukti).
 * 2. Angka sesuai data di database.
 * 3. Hanya bisa diakses Analis (peran lain ditolak 403, tamu dialihkan ke login).
 * 4. Menu "Dashboard" di navbar Analis aktif.
 */
class DashboardAnalisTest extends TestCase
{
    use RefreshDatabase;

    private User $analis;
    private User $masyarakat;
    private User $kepalaBidang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LokerLensSeeder::class);

        $this->analis       = User::where('role', 'Analis')->firstOrFail();
        $this->masyarakat   = User::where('role', 'Masyarakat')->firstOrFail();
        $this->kepalaBidang = User::where('role', 'Kepala Bidang')->firstOrFail();
    }

    /**
     * Test 1: Halaman hanya bisa diakses Analis (NFR-SEC-02).
     */
    public function test_hanya_analis_yang_bisa_mengakses_dashboard_analis(): void
    {
        // 1. Analis bisa mengakses
        $this->actingAs($this->analis)
            ->get('/dashboard-analis')
            ->assertOk();

        // 2. Masyarakat ditolak (403 Forbidden)
        $this->actingAs($this->masyarakat)
            ->get('/dashboard-analis')
            ->assertForbidden();

        // 3. Kepala Bidang ditolak (403 Forbidden)
        $this->actingAs($this->kepalaBidang)
            ->get('/dashboard-analis')
            ->assertForbidden();
    }

    /**
     * Test 2: Pengguna belum login dialihkan ke halaman login (NFR-SEC-01).
     */
    public function test_halaman_dashboard_analis_wajib_login(): void
    {
        $this->get('/dashboard-analis')
            ->assertRedirect('/login');
    }

    /**
     * Test 2: Menampilkan ringkasan jumlah kasus sesuai data database.
     */
    public function test_dashboard_menampilkan_jumlah_kasus_dan_hasil_tinjauan_sesuai_database(): void
    {
        // Hitung ekspektasi dari seeder:
        // Seeder awal:
        // - Perlu Tinjauan: 3 kasus (#LKR-8819, #LKR-8790, #LKR-8765)
        // - Sedang Ditinjau: 1 kasus (#LKR-8821)
        // - Terkonfirmasi Mencurigakan: 1 kasus (#LKR-2025-0842)
        // - Tidak Terbukti: 0 kasus
        $expectedPerluTinjauan = Pengaduan::where('status_pengaduan', StatusKasus::PerluTinjauan)->count();
        $expectedSedangDitinjau = Pengaduan::where('status_pengaduan', StatusKasus::SedangDitinjau)->count();
        $expectedTerkonfirmasi = TinjauanAnalis::where('hasil_tinjauan', 'like', HasilTinjauan::TerkonfirmasiMencurigakan->value . '%')->count();
        $expectedTidakTerbukti = TinjauanAnalis::where('hasil_tinjauan', 'like', HasilTinjauan::TidakTerbukti->value . '%')->count();

        $response = $this->actingAs($this->analis)->get('/dashboard-analis');
        $response->assertOk();

        // Verifikasi view data yang dikirim controller
        $response->assertViewHas('jumlahPerluTinjauan', $expectedPerluTinjauan);
        $response->assertViewHas('jumlahSedangDitinjau', $expectedSedangDitinjau);
        $response->assertViewHas('jumlahTerkonfirmasi', $expectedTerkonfirmasi);
        $response->assertViewHas('jumlahTidakTerbukti', $expectedTidakTerbukti);

        // Verifikasi teks label dan angka ada di HTML
        $response->assertSee('Perlu Tinjauan');
        $response->assertSee('Sedang Ditinjau');
        $response->assertSee('Terkonfirmasi Mencurigakan');
        $response->assertSee('Tidak Terbukti');

        // Pastikan angka statistik tertera dalam HTML
        $response->assertSee((string) $expectedPerluTinjauan);
        $response->assertSee((string) $expectedSedangDitinjau);
        $response->assertSee((string) $expectedTerkonfirmasi);
    }

    /**
     * Test 3: Data dinamis berubah ketika ada pengaduan dan tinjauan baru.
     */
    public function test_angka_dashboard_berubah_dinamis_mengikuti_perubahan_data(): void
    {
        $perusahaan = Perusahaan::first();
        $initialTidakTerbukti = TinjauanAnalis::where('hasil_tinjauan', 'like', HasilTinjauan::TidakTerbukti->value . '%')->count();

        // Tambah 1 lowongan dan pengaduan baru dengan status Sedang Ditinjau
        $lowonganBaru = LowonganKerja::create([
            'id_perusahaan'      => $perusahaan->id_perusahaan,
            'posisi_lowongan'    => 'Customer Service Remote',
            'deskripsi_lowongan' => 'Pekerjaan CS penipuan dengan uang deposit Rp300.000.',
        ]);

        $pengaduanBaru = Pengaduan::create([
            'id_lowongan'      => $lowonganBaru->id_lowongan,
            'id_pengguna'      => $this->masyarakat->id,
            'nomor_tiket'      => '#LKR-TEST-DYN',
            'status_pengaduan' => StatusKasus::SedangDitinjau,
        ]);

        $klasifikasiBaru = HasilKlasifikasi::create([
            'id_pengaduan'     => $pengaduanBaru->id_pengaduan,
            'confidence_score' => 95.0,
            'kategori_hasil'   => 'Lowongan berpotensi penipuan',
            'status_prioritas' => true,
            'metode_analisis'  => 'ML Test',
        ]);

        // Tambah 1 tinjauan dengan hasil "Tidak Terbukti"
        TinjauanAnalis::create([
            'id_klasifikasi'   => $klasifikasiBaru->id_klasifikasi,
            'id_pengguna'      => $this->analis->id,
            'catatan_tinjauan' => 'Klarifikasi telah dilakukan ke HRD, lowongan ini sah.',
            'hasil_tinjauan'   => HasilTinjauan::TidakTerbukti->value,
            'tanggal_tinjauan' => now(),
        ]);

        $response = $this->actingAs($this->analis)->get('/dashboard-analis');
        $response->assertOk();

        // Tidak Terbukti bertambah 1 dari nilai awal
        $response->assertViewHas('jumlahTidakTerbukti', $initialTidakTerbukti + 1);
        $response->assertSee((string) ($initialTidakTerbukti + 1));
    }

    /**
     * Test 4: Menu "Dashboard" di navbar Analis aktif dan menaut ke route dashboard-analis.index.
     */
    public function test_menu_dashboard_di_navbar_analis_aktif(): void
    {
        $response = $this->actingAs($this->analis)->get('/dashboard-analis');
        $response->assertOk();

        // Link dashboard ada dan aktif
        $response->assertSee(route('dashboard-analis.index'));
        $response->assertSee('Dashboard');

        // Navigasi juga aktif saat Analis membuka halaman Pemantauan
        $resPemantauan = $this->actingAs($this->analis)->get('/tinjau-lowongan');
        $resPemantauan->assertOk();
        $resPemantauan->assertSee(route('dashboard-analis.index'));
    }
}
