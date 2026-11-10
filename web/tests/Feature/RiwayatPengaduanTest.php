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

/**
 * Riwayat Pengaduan: hanya pengaduan yang statusnya sudah akhir (issue #25).
 * Pengaduan berjalan (termasuk Menunggu klasifikasi) dipantau di Status Pengaduan.
 */
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

    /** Skor null = belum ada hasil klasifikasi (Menunggu klasifikasi). */
    private function buatPengaduan(User $user, string $nomorTiket, string $perusahaan, string $posisi, StatusKasus $status, ?int $score = 80): Pengaduan
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

        if ($score !== null) {
            HasilKlasifikasi::create([
                'id_pengaduan'     => $pengaduan->id_pengaduan,
                'confidence_score' => $score,
                'kategori_hasil'   => 'Terindikasi Penipuan',
                'model_versi'      => 'v1.0.0',
                'tanggal_analisis' => now(),
            ]);
        }

        return $pengaduan;
    }

    public function test_halaman_riwayat_menampilkan_pesan_kosong_dan_tombol_ke_status(): void
    {
        // Pengaduan yang masih berjalan tidak dihitung sebagai riwayat
        $this->buatPengaduan($this->masyarakat, '#LKR-1001', 'PT Maju Bersama', 'Staff Finance', StatusKasus::PerluTinjauan);

        $response = $this->actingAs($this->masyarakat)->get('/riwayat-pengaduan');

        $response->assertOk()
            ->assertSee(LokerLensCopy::BELUM_ADA_PENGADUAN_SELESAI)
            ->assertSee(LokerLensCopy::BTN_PANTAU_STATUS)
            ->assertSee(route('status-pengaduan.index'), false)
            ->assertDontSee('#LKR-1001');
    }

    public function test_hanya_pengaduan_berstatus_akhir_yang_tampil(): void
    {
        // Status akhir → tampil
        $this->buatPengaduan($this->masyarakat, '#LKR-2001', 'PT Satu', 'Posisi Satu', StatusKasus::Selesai);
        $this->buatPengaduan($this->masyarakat, '#LKR-2002', 'PT Dua', 'Posisi Dua', StatusKasus::SelesaiRisikoRendah, 20);
        $this->buatPengaduan($this->masyarakat, '#LKR-2003', 'PT Tiga', 'Posisi Tiga', StatusKasus::TidakTerbukti);
        $this->buatPengaduan($this->masyarakat, '#LKR-2004', 'PT Empat', 'Posisi Empat', StatusKasus::TidakDitindaklanjuti);

        // Masih berjalan → tidak tampil, termasuk Menunggu klasifikasi (tanpa hasil klasifikasi)
        $this->buatPengaduan($this->masyarakat, '#LKR-3001', 'PT Lima', 'Posisi Lima', StatusKasus::PerluTinjauan);
        $this->buatPengaduan($this->masyarakat, '#LKR-3002', 'PT Enam', 'Posisi Enam', StatusKasus::SedangDitinjau);
        $this->buatPengaduan($this->masyarakat, '#LKR-3003', 'PT Tujuh', 'Posisi Tujuh', StatusKasus::MenungguKeputusan);
        $this->buatPengaduan($this->masyarakat, '#LKR-3004', 'PT Delapan', 'Posisi Delapan', StatusKasus::TindakLanjutDisetujui);
        $this->buatPengaduan($this->masyarakat, '#LKR-3005', 'PT Sembilan', 'Posisi Sembilan', StatusKasus::PerluTinjauan, null);

        $response = $this->actingAs($this->masyarakat)->get('/riwayat-pengaduan')->assertOk();

        foreach (['#LKR-2001', '#LKR-2002', '#LKR-2003', '#LKR-2004'] as $tiket) {
            $response->assertSee($tiket);
        }
        foreach (['#LKR-3001', '#LKR-3002', '#LKR-3003', '#LKR-3004', '#LKR-3005'] as $tiket) {
            $response->assertDontSee($tiket);
        }
        $response->assertDontSee(LokerLensCopy::MENUNGGU_KLASIFIKASI);
    }

    public function test_kotak_angka_total_selesai_ditindaklanjuti_dan_ditutup(): void
    {
        $this->buatPengaduan($this->masyarakat, '#LKR-2001', 'PT Satu', 'Posisi Satu', StatusKasus::Selesai);
        $this->buatPengaduan($this->masyarakat, '#LKR-2002', 'PT Dua', 'Posisi Dua', StatusKasus::Selesai);
        $this->buatPengaduan($this->masyarakat, '#LKR-2003', 'PT Tiga', 'Posisi Tiga', StatusKasus::TidakTerbukti);
        $this->buatPengaduan($this->masyarakat, '#LKR-3001', 'PT Lima', 'Posisi Lima', StatusKasus::PerluTinjauan);

        $response = $this->actingAs($this->masyarakat)->get('/riwayat-pengaduan')->assertOk();

        $response->assertViewHas('totalSelesai', 3)
            ->assertViewHas('ditindaklanjuti', 2)
            ->assertViewHas('ditutup', 1)
            ->assertSee(LokerLensCopy::RIWAYAT_STAT_TOTAL)
            ->assertSee(LokerLensCopy::RIWAYAT_STAT_DITINDAKLANJUTI)
            ->assertSee(LokerLensCopy::RIWAYAT_STAT_DITUTUP)
            ->assertDontSee('Sedang Berjalan');

        // Label jumlah menampilkan angkanya
        $response->assertSeeInOrder([LokerLensCopy::RIWAYAT_MENAMPILKAN_SEMUA, '3', LokerLensCopy::RIWAYAT_SATUAN_PENGADUAN]);
    }

    public function test_dropdown_status_hanya_berisi_status_akhir(): void
    {
        $response = $this->actingAs($this->masyarakat)->get('/riwayat-pengaduan')->assertOk();

        $response->assertSee('<option value="">' . LokerLensCopy::RIWAYAT_SEMUA_STATUS . '</option>', false);
        foreach ([StatusKasus::Selesai, StatusKasus::SelesaiRisikoRendah, StatusKasus::TidakTerbukti, StatusKasus::TidakDitindaklanjuti] as $status) {
            $response->assertSee('value="' . $status->value . '"', false);
        }
        foreach ([StatusKasus::PerluTinjauan, StatusKasus::SedangDitinjau, StatusKasus::MenungguKeputusan, StatusKasus::TindakLanjutDisetujui] as $status) {
            $response->assertDontSee('value="' . $status->value . '"', false);
        }
    }

    public function test_item_menampilkan_skor_risiko_dan_tombol_detail_serta_hasil(): void
    {
        $pengaduan = $this->buatPengaduan($this->masyarakat, '#LKR-2001', 'PT Satu', 'Posisi Satu', StatusKasus::Selesai, 85);

        $response = $this->actingAs($this->masyarakat)->get('/riwayat-pengaduan')->assertOk();

        $response->assertSee(LokerLensCopy::LABEL_SKOR_RISIKO)
            ->assertDontSee('KEYAKINAN SISTEM')
            ->assertSee(LokerLensCopy::BTN_LIHAT_DETAIL)
            ->assertSee(route('status-pengaduan.detail', $pengaduan->id_pengaduan), false)
            ->assertSee('Lihat Hasil')
            ->assertSee(route('periksa-lowongan.hasil', $pengaduan->id_pengaduan), false);

        // Halaman detail bisa dibuka
        $this->actingAs($this->masyarakat)->get(route('status-pengaduan.detail', $pengaduan->id_pengaduan))->assertOk();
    }

    public function test_halaman_riwayat_menampilkan_pengaduan_milik_sendiri(): void
    {
        $this->buatPengaduan($this->masyarakat, '#LKR-1001', 'PT Maju Bersama', 'Staff Finance', StatusKasus::Selesai, 85);
        $this->buatPengaduan($this->masyarakatLain, '#LKR-9999', 'PT Lain Makmur', 'Direktur Utama', StatusKasus::Selesai, 90);

        $response = $this->actingAs($this->masyarakat)->get('/riwayat-pengaduan');

        $response->assertOk();
        $response->assertSee('#LKR-1001');
        $response->assertSee('PT Maju Bersama');
        $response->assertSee('Staff Finance');
        $response->assertSee('85');
        // NFR-SEC-05: Tidak boleh melihat pengaduan milik orang lain
        $response->assertDontSee('#LKR-9999');
        $response->assertDontSee('PT Lain Makmur');
        $response->assertViewHas('totalSelesai', 1);
    }

    public function test_pencarian_berdasarkan_nomor_tiket_atau_nama_perusahaan(): void
    {
        $this->buatPengaduan($this->masyarakat, '#LKR-1001', 'PT Maju Bersama', 'Staff Finance', StatusKasus::Selesai);
        $this->buatPengaduan($this->masyarakat, '#LKR-1002', 'CV Sejahtera Abadi', 'Customer Service', StatusKasus::TidakTerbukti);

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
        $this->buatPengaduan($this->masyarakat, '#LKR-1001', 'PT Maju Bersama', 'Staff Finance', StatusKasus::TidakDitindaklanjuti);
        $this->buatPengaduan($this->masyarakat, '#LKR-1002', 'CV Sejahtera Abadi', 'Customer Service', StatusKasus::Selesai);

        $response = $this->actingAs($this->masyarakat)->get('/riwayat-pengaduan?status=' . urlencode(StatusKasus::Selesai->value));
        $response->assertOk();
        $response->assertSee('#LKR-1002');
        $response->assertDontSee('#LKR-1001');
    }

    public function test_filter_status_belum_akhir_tidak_menampilkan_pengaduan_berjalan(): void
    {
        $this->buatPengaduan($this->masyarakat, '#LKR-3001', 'PT Lima', 'Posisi Lima', StatusKasus::PerluTinjauan);
        $this->buatPengaduan($this->masyarakat, '#LKR-2001', 'PT Satu', 'Posisi Satu', StatusKasus::Selesai);

        // Status bukan akhir diabaikan: tetap hanya pengaduan selesai yang tampil
        $response = $this->actingAs($this->masyarakat)->get('/riwayat-pengaduan?status=' . urlencode(StatusKasus::PerluTinjauan->value));
        $response->assertOk();
        $response->assertSee('#LKR-2001');
        $response->assertDontSee('#LKR-3001');
    }

    public function test_beranda_tanpa_alur_proses_dan_teks_kartu_riwayat_baru(): void
    {
        $response = $this->actingAs($this->masyarakat)->get('/beranda')->assertOk();

        $response->assertSee('Satu tempat untuk melapor, memantau, dan meninjau.')
            ->assertSee(LokerLensCopy::BERANDA_KARTU_RIWAYAT)
            ->assertDontSee('ALUR PROSES PENGADUAN')
            ->assertDontSee('Pemeriksaan Laporan')
            ->assertDontSee('Pemantauan Status')
            ->assertDontSee('Hasil Penanganan');
    }
}
