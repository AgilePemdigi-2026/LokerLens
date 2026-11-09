<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Enums\StatusKasus;
use App\Models\HasilKlasifikasi;
use App\Models\LowonganKerja;
use App\Models\Pengaduan;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiwayatPengaduanTest extends TestCase
{
    use RefreshDatabase;

    private User $masyarakat;
    private User $masyarakatLain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masyarakat = User::factory()->create(['role' => 'Masyarakat']);
        $this->masyarakatLain = User::factory()->create(['role' => 'Masyarakat']);
    }

    private function buatPengaduan(User $user, string $nomorTiket, string $perusahaan, string $posisi, StatusKasus $status, int $score = 80): Pengaduan
    {
        $per = Perusahaan::create(['nama_perusahaan' => $perusahaan]);
        $low = LowonganKerja::create([
            'id_perusahaan'      => $per->id_perusahaan,
            'posisi_lowongan'    => $posisi,
            'deskripsi_lowongan' => 'Deskripsi lowongan pekerjaan minimal lima puluh karakter untuk pengujian.',
        ]);

        $pengaduan = Pengaduan::create([
            'id_lowongan'       => $low->id_lowongan,
            'id_pengguna'       => $user->id,
            'nomor_tiket'       => $nomorTiket,
            'deskripsi_singkat' => 'Laporan uji',
            'status_pengaduan'  => $status,
            'tanggal_pengaduan' => now(),
        ]);

        HasilKlasifikasi::create([
            'id_pengaduan'     => $pengaduan->id_pengaduan,
            'confidence_score' => $score,
            'kategori_hasil'   => 'Terindikasi Penipuan',
            'model_versi'      => 'v1.0.0',
            'tanggal_analisis' => now(),
        ]);

        return $pengaduan;
    }

    public function test_halaman_riwayat_menampilkan_pesan_kosong_bila_belum_ada_pengaduan(): void
    {
        $response = $this->actingAs($this->masyarakat)->get('/riwayat-pengaduan');

        $response->assertOk();
        $response->assertSee(LokerLensCopy::BELUM_ADA_PENGADUAN);
    }

    public function test_halaman_riwayat_menampilkan_pengaduan_milik_sendiri(): void
    {
        $this->buatPengaduan($this->masyarakat, '#LKR-1001', 'PT Maju Bersama', 'Staff Finance', StatusKasus::PerluTinjauan, 85);
        $this->buatPengaduan($this->masyarakatLain, '#LKR-9999', 'PT Lain Makmur', 'Direktur Utama', StatusKasus::PerluTinjauan, 90);

        $response = $this->actingAs($this->masyarakat)->get('/riwayat-pengaduan');

        $response->assertOk();
        $response->assertSee('#LKR-1001');
        $response->assertSee('PT Maju Bersama');
        $response->assertSee('Staff Finance');
        $response->assertSee('85');
        // NFR-SEC-05: Tidak boleh melihat pengaduan milik orang lain
        $response->assertDontSee('#LKR-9999');
        $response->assertDontSee('PT Lain Makmur');
    }

    public function test_pencarian_berdasarkan_nomor_tiket_atau_nama_perusahaan(): void
    {
        $this->buatPengaduan($this->masyarakat, '#LKR-1001', 'PT Maju Bersama', 'Staff Finance', StatusKasus::PerluTinjauan);
        $this->buatPengaduan($this->masyarakat, '#LKR-1002', 'CV Sejahtera Abadi', 'Customer Service', StatusKasus::SedangDitinjau);

        // Cari nomor tiket
        $responseTiket = $this->actingAs($this->masyarakat)->get('/riwayat-pengaduan?cari=1001');
        $responseTiket->assertOk();
        $responseTiket->assertSee('#LKR-1001');
        $responseTiket->assertDontSee('#LKR-1002');

        // Cari nama perusahaan
        $responsePerusahaan = $this->actingAs($this->masyarakat)->get('/riwayat-pengaduan?cari=Sejahtera');
        $responsePerusahaan->assertOk();
        $responsePerusahaan->assertSee('CV Sejahtera Abadi');
        $responsePerusahaan->assertDontSee('PT Maju Bersama');
    }

    public function test_filter_berdasarkan_status(): void
    {
        $this->buatPengaduan($this->masyarakat, '#LKR-1001', 'PT Maju Bersama', 'Staff Finance', StatusKasus::PerluTinjauan);
        $this->buatPengaduan($this->masyarakat, '#LKR-1002', 'CV Sejahtera Abadi', 'Customer Service', StatusKasus::Selesai);

        $response = $this->actingAs($this->masyarakat)->get('/riwayat-pengaduan?status=' . urlencode(StatusKasus::Selesai->value));
        $response->assertOk();
        $response->assertSee('#LKR-1002');
        $response->assertDontSee('#LKR-1001');
    }
}

