<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Enums\HasilTinjauan;
use App\Enums\StatusKasus;
use App\Models\HasilKlasifikasi;
use App\Models\IndikatorMencurigakan;
use App\Models\LowonganKerja;
use App\Models\Pengaduan;
use App\Models\Perusahaan;
use App\Models\SuratTeguranLaporanPolisi;
use App\Models\TindakLanjutKasus;
use App\Models\TinjauanAnalis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Halaman Analis dan Kepala Bidang. Semua data dibuat di dalam test (tidak memakai seeder).
 */
class HalamanAnalisDanKepalaBidangTest extends TestCase
{
    use RefreshDatabase;

    private User $masyarakat;
    private User $analis;
    private User $kepalaBidang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masyarakat = User::factory()->create([
            'role'       => 'Masyarakat',
            'name'       => 'Pelapor Uji',
            'email'      => 'pelapor.uji@example.com',
            'no_telepon' => '081200001111',
        ]);
        $this->analis       = User::factory()->create(['role' => 'Analis']);
        $this->kepalaBidang = User::factory()->create(['role' => 'Kepala Bidang']);
    }

    /**
     * Buat satu kasus lengkap: perusahaan, lowongan, pengaduan, dan hasil klasifikasi.
     */
    private function buatKasus(string $nomorTiket, float $skor, StatusKasus $status, ?string $tanggal = null): Pengaduan
    {
        $perusahaan = Perusahaan::create(['nama_perusahaan' => 'PT Uji ' . $nomorTiket]);
        $lowongan = LowonganKerja::create([
            'id_perusahaan'      => $perusahaan->id_perusahaan,
            'posisi_lowongan'    => 'Staff Administrasi',
            'deskripsi_lowongan' => 'Gaji besar tanpa pengalaman. Wajib transfer biaya akomodasi sebelum wawancara di hotel.',
        ]);

        $pengaduan = Pengaduan::create([
            'id_lowongan'       => $lowongan->id_lowongan,
            'id_pengguna'       => $this->masyarakat->id,
            'nomor_tiket'       => $nomorTiket,
            'deskripsi_singkat' => 'Pengaduan uji ' . $nomorTiket,
            'status_pengaduan'  => $status,
            'tanggal_pengaduan' => $tanggal ? now()->parse($tanggal) : now(),
        ]);

        $klasifikasi = HasilKlasifikasi::create([
            'id_pengaduan'        => $pengaduan->id_pengaduan,
            'confidence_score'    => $skor,
            'kategori_hasil'      => 'Lowongan berpotensi penipuan',
            'status_prioritas'    => $skor >= 70,
            'metode_analisis'     => 'Model Uji',
            'tanggal_klasifikasi' => $tanggal ? now()->parse($tanggal) : now(),
        ]);

        IndikatorMencurigakan::create([
            'id_klasifikasi'     => $klasifikasi->id_klasifikasi,
            'nama_indikator'     => 'Biaya akomodasi',
            'kategori_indikator' => 'Biaya Fiktif',
            'bobot_indikator'    => 25,
        ]);

        return $pengaduan;
    }

    /**
     * Kasus yang sudah ditinjau Analis (Terkonfirmasi Mencurigakan) dan menunggu keputusan Kepala Bidang.
     */
    private function buatKasusMenungguKeputusan(string $nomorTiket): Pengaduan
    {
        $pengaduan = $this->buatKasus($nomorTiket, 88.0, StatusKasus::MenungguKeputusan);

        TinjauanAnalis::create([
            'id_klasifikasi'   => $pengaduan->hasilKlasifikasi->id_klasifikasi,
            'id_pengguna'      => $this->analis->id,
            'catatan_tinjauan' => 'Terindikasi sindikat penipuan.',
            'hasil_tinjauan'   => HasilTinjauan::TerkonfirmasiMencurigakan->value,
            'tanggal_tinjauan' => now(),
        ]);

        return $pengaduan;
    }

    /**
     * Kasus yang sudah diputuskan "Tindaklanjuti" oleh Kepala Bidang.
     */
    private function buatKasusTindaklanjuti(string $nomorTiket, StatusKasus $status): Pengaduan
    {
        $pengaduan = $this->buatKasusMenungguKeputusan($nomorTiket);
        $pengaduan->update(['status_pengaduan' => $status]);

        TindakLanjutKasus::create([
            'id_pengaduan'      => $pengaduan->id_pengaduan,
            'id_pengguna'       => $this->kepalaBidang->id,
            'keputusan'         => 'Tindaklanjuti',
            'catatan_keputusan' => 'Lanjutkan ke kepolisian.',
            'tanggal_keputusan' => now(),
        ]);

        return $pengaduan->fresh();
    }

    /**
     * Analis: daftar kasus, filter skor, pencarian nomor tiket, dan keadaan kosong (SRS Table B.3).
     */
    public function test_analis_daftar_kasus_filter_skor_pencarian_dan_data_kosong(): void
    {
        $this->buatKasus('#LKR-UJI-01', 92.0, StatusKasus::SedangDitinjau);
        $this->buatKasus('#LKR-UJI-02', 88.0, StatusKasus::PerluTinjauan);

        $response = $this->actingAs($this->analis)->get('/tinjau-lowongan');
        $response->assertOk()
            ->assertSee('Daftar Lowongan Prioritas Tinggi')
            ->assertSee('ID LOWONGAN')
            ->assertSee('CONFIDENCE SCORE')
            ->assertSee('#LKR-UJI-01')
            ->assertSee('#LKR-UJI-02');

        // Filter skor dan urutan prioritas
        $this->actingAs($this->analis)->get('/tinjau-lowongan?filter_skor=Skor+Tinggi&urutan=Skor+Tertinggi')
            ->assertOk()
            ->assertSee('Daftar Sesuai Kriteria Filter');

        // Pencarian nomor tiket terdaftar
        $this->actingAs($this->analis)->get('/tinjau-lowongan?nomor_tiket=%23LKR-UJI-01')
            ->assertOk()
            ->assertSee('Hasil Pencarian')
            ->assertSee('#LKR-UJI-01')
            ->assertDontSee('#LKR-UJI-02');

        // Keadaan kosong jika nomor tiket tidak cocok
        $this->actingAs($this->analis)->get('/tinjau-lowongan?nomor_tiket=TIDAK_ADA_123')
            ->assertOk()
            ->assertSee(LokerLensCopy::DATA_TIDAK_DITEMUKAN);
    }

    /**
     * Issue #6, FR-ML-03: urutan "Skor Terendah", "Skor Tertinggi", dan "Terbaru" berfungsi.
     */
    public function test_analis_urutan_skor_terendah_tertinggi_dan_terbaru(): void
    {
        $this->buatKasus('#LKR-UJI-79', 79.0, StatusKasus::PerluTinjauan, '2026-10-04 10:00:00');
        $this->buatKasus('#LKR-UJI-92', 92.0, StatusKasus::PerluTinjauan, '2026-10-01 10:00:00');
        $this->buatKasus('#LKR-UJI-85', 85.0, StatusKasus::PerluTinjauan, '2026-10-03 10:00:00');
        $this->buatKasus('#LKR-UJI-88', 88.0, StatusKasus::PerluTinjauan, '2026-10-02 10:00:00');

        $skor = fn (string $url) => $this->actingAs($this->analis)->get($url)
            ->assertOk()
            ->viewData('hasilKlasifikasi')
            ->pluck('confidence_score')
            ->map(fn ($nilai) => (float) $nilai)
            ->all();

        $this->assertSame([79.0, 85.0, 88.0, 92.0], $skor('/tinjau-lowongan?urutan=Skor+Terendah'));
        $this->assertSame([92.0, 88.0, 85.0, 79.0], $skor('/tinjau-lowongan?urutan=Skor+Tertinggi'));
        $this->assertSame([92.0, 88.0, 85.0, 79.0], $skor('/tinjau-lowongan'));
        // Terbaru = tanggal klasifikasi paling baru di atas
        $this->assertSame([79.0, 85.0, 88.0, 92.0], $skor('/tinjau-lowongan?urutan=Terbaru'));
    }

    /**
     * Detail dengan ID yang tidak ada menampilkan 404 (keputusan tim: 404 dipertahankan).
     */
    public function test_analis_detail_tidak_ditemukan_menampilkan_404(): void
    {
        $this->actingAs($this->analis)->get('/tinjau-lowongan/detail/999999')
            ->assertNotFound();
    }

    /**
     * NFR-PERF-01: daftar dengan 100 data tampil maksimal 3 detik.
     */
    public function test_analis_waktu_tampil_daftar_sesuai_nfr_perf_01(): void
    {
        $perusahaan = Perusahaan::create(['nama_perusahaan' => 'PT Uji Performa']);

        for ($i = 1; $i <= 100; $i++) {
            $lowongan = LowonganKerja::create([
                'id_perusahaan'      => $perusahaan->id_perusahaan,
                'posisi_lowongan'    => "Posisi Uji {$i}",
                'deskripsi_lowongan' => "Deskripsi pengujian performa nomor {$i}.",
            ]);

            $pengaduan = Pengaduan::create([
                'id_lowongan'       => $lowongan->id_lowongan,
                'id_pengguna'       => $this->masyarakat->id,
                'nomor_tiket'       => '#LKR-PERF-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'deskripsi_singkat' => "Uji performa {$i}",
                'status_pengaduan'  => StatusKasus::PerluTinjauan,
            ]);

            HasilKlasifikasi::create([
                'id_pengaduan'        => $pengaduan->id_pengaduan,
                'confidence_score'    => 75.0 + ($i % 25),
                'kategori_hasil'      => 'Lowongan berpotensi penipuan',
                'status_prioritas'    => true,
                'metode_analisis'     => 'Model Uji',
                'tanggal_klasifikasi' => now(),
            ]);
        }

        $mulai = microtime(true);
        $response = $this->actingAs($this->analis)->get('/tinjau-lowongan');
        $durasi = microtime(true) - $mulai;

        $response->assertOk();
        $this->assertLessThan(3.0, $durasi, "Daftar dengan 100 data harus tampil <= 3 detik (NFR-PERF-01). Aktual: {$durasi} detik.");
    }

    /**
     * Analis: detail kasus menampilkan data lowongan, skor, dan indikator mencurigakan (FR-ML-06).
     */
    public function test_analis_detail_kasus_menampilkan_data_klasifikasi(): void
    {
        $pengaduan = $this->buatKasus('#LKR-UJI-01', 92.0, StatusKasus::SedangDitinjau);

        $this->actingAs($this->analis)->get("/tinjau-lowongan/detail/{$pengaduan->hasilKlasifikasi->id_klasifikasi}")
            ->assertOk()
            ->assertSee('Detail Lowongan Terpilih')
            ->assertSee('Confidence Score')
            ->assertSee('92%')
            ->assertSee('Indikator Mencurigakan')
            ->assertSee('Biaya akomodasi');
    }

    /**
     * Analis: hasil_tinjauan wajib salah satu dari 2 pilihan (FR-ML-07, keputusan tim).
     */
    public function test_analis_hasil_tinjauan_hanya_dua_pilihan_dan_bisa_disimpan(): void
    {
        $pengaduan = $this->buatKasus('#LKR-UJI-01', 92.0, StatusKasus::SedangDitinjau);
        $url = "/tinjau-lowongan/detail/{$pengaduan->hasilKlasifikasi->id_klasifikasi}/simpan";

        // Teks bebas di luar 2 pilihan ditolak
        $this->actingAs($this->analis)->post($url, [
            'catatan_tinjauan' => 'Catatan hasil investigasi lengkap.',
            'hasil_tinjauan'   => 'Terkonfirmasi Mencurigakan - Ditemukan rekening penampung fiktif',
        ])->assertSessionHasErrors('hasil_tinjauan');

        // Pilihan resmi berhasil disimpan
        $this->actingAs($this->analis)->post($url, [
            'catatan_tinjauan' => 'Catatan audit ketenagakerjaan telah diverifikasi.',
            'hasil_tinjauan'   => HasilTinjauan::TerkonfirmasiMencurigakan->value,
        ])->assertRedirect('/tinjau-lowongan')
          ->assertSessionHas('success');

        $this->assertSame(StatusKasus::MenungguKeputusan, $pengaduan->fresh()->status_pengaduan);
        $this->assertSame(
            HasilTinjauan::TerkonfirmasiMencurigakan->value,
            $pengaduan->hasilKlasifikasi->tinjauanAnalis()->value('hasil_tinjauan')
        );
    }

    /**
     * Kepala Bidang: daftar laporan, detail, keputusan, lalu form surat terbuka.
     */
    public function test_kepala_bidang_daftar_detail_dan_keputusan(): void
    {
        $pengaduan = $this->buatKasusMenungguKeputusan('#LKR-UJI-0842');

        $this->actingAs($this->kepalaBidang)->get('/tindak-lanjut')
            ->assertOk()
            ->assertSee('Daftar Laporan')
            ->assertSee('Menunggu Keputusan')
            ->assertSee('#LKR-UJI-0842');

        $this->actingAs($this->kepalaBidang)->get("/tindak-lanjut/{$pengaduan->id_pengaduan}")
            ->assertOk()
            ->assertSee('Detail Laporan Lowongan Mencurigakan')
            ->assertSee('Informasi Lowongan')
            ->assertSee('Ringkasan Laporan')
            ->assertSee('Terindikasi Anomali Tinggi')
            ->assertSee('Biaya akomodasi')
            ->assertSee('Konfirmasi Keputusan')
            ->assertSee('Tindaklanjuti')
            ->assertSee('Tidak Ditindaklanjuti');

        $this->actingAs($this->kepalaBidang)->post("/tindak-lanjut/{$pengaduan->id_pengaduan}/keputusan", [
            'keputusan'         => 'Tindaklanjuti',
            'catatan_keputusan' => 'Setujui penerbitan surat laporan kepolisian.',
        ])->assertRedirect("/tindak-lanjut/{$pengaduan->id_pengaduan}/hasil?keputusan=Tindaklanjuti");

        $this->assertSame(StatusKasus::TindakLanjutDisetujui, $pengaduan->fresh()->status_pengaduan);

        $this->actingAs($this->kepalaBidang)->get("/buat-surat/{$pengaduan->id_pengaduan}")
            ->assertOk()
            ->assertSee('Buat Laporan Polisi')
            ->assertSee('Data Kasus');
    }

    /**
     * Kriteria Selesai SRS Table B.4:
     * 1. Detail menampilkan "Detail Laporan Lowongan Mencurigakan", "Informasi Lowongan", "Ringkasan Laporan", "Terindikasi Anomali Tinggi".
     * 2. "Tidak Ditindaklanjuti" menampilkan "Kasus ditandai tidak dilanjuti dan diarsipkan".
     * 3. "Tindaklanjuti" mengarah ke pembuatan surat, dan /buat-surat/ menampilkan "Buat Laporan Polisi".
     */
    public function test_kriteria_selesai_srs_tabel_b4_kepala_bidang(): void
    {
        $pengaduan = $this->buatKasusMenungguKeputusan('#LKR-BDD-004');

        // 1. Elemen halaman detail
        $this->actingAs($this->kepalaBidang)->get("/tindak-lanjut/{$pengaduan->id_pengaduan}")
            ->assertOk()
            ->assertSee('Detail Laporan Lowongan Mencurigakan')
            ->assertSee('Informasi Lowongan')
            ->assertSee('Ringkasan Laporan')
            ->assertSee('Terindikasi Anomali Tinggi');

        // 2. "Tidak Ditindaklanjuti" → kasus diarsipkan
        $pengaduanArsip = $this->buatKasusMenungguKeputusan('#LKR-BDD-005');
        $urlHasilTolak = route('tindak-lanjut.hasil', ['id' => $pengaduanArsip->id_pengaduan, 'keputusan' => 'Tidak Ditindaklanjuti']);

        $this->actingAs($this->kepalaBidang)->post("/tindak-lanjut/{$pengaduanArsip->id_pengaduan}/keputusan", [
            'keputusan'         => 'Tidak Ditindaklanjuti',
            'catatan_keputusan' => 'Arsipkan pengaduan.',
        ])->assertRedirect($urlHasilTolak);

        $this->actingAs($this->kepalaBidang)->get($urlHasilTolak)
            ->assertOk()
            ->assertSee(LokerLensCopy::KASUS_DIARSIPKAN);

        // 3. "Tindaklanjuti" → halaman hasil, lalu form surat
        $this->actingAs($this->kepalaBidang)->post("/tindak-lanjut/{$pengaduan->id_pengaduan}/keputusan", [
            'keputusan'         => 'Tindaklanjuti',
            'catatan_keputusan' => 'Lanjutkan ke polisi.',
        ])->assertRedirect("/tindak-lanjut/{$pengaduan->id_pengaduan}/hasil?keputusan=Tindaklanjuti");

        $this->actingAs($this->kepalaBidang)->get("/tindak-lanjut/{$pengaduan->id_pengaduan}/hasil?keputusan=Tindaklanjuti")
            ->assertOk()
            ->assertSee('Lanjut ke Pembuatan Surat');

        $this->actingAs($this->kepalaBidang)->get("/buat-surat/{$pengaduan->id_pengaduan}")
            ->assertOk()
            ->assertSee('Buat Laporan Polisi');
    }

    /**
     * "Cetak Tanda Terima" (Data Mockup Gambar 2.16): halaman terbit punya area cetak
     * (print-page) dan tombol yang tidak ikut tercetak (no-print).
     */
    public function test_halaman_surat_terbit_rapi_untuk_dicetak(): void
    {
        $pengaduan = $this->buatKasusTindaklanjuti('#LKR-UJI-8798', StatusKasus::Selesai);

        $surat = SuratTeguranLaporanPolisi::create([
            'id_tindaklanjut'    => $pengaduan->tindakLanjutKasus->id_tindaklanjut,
            'nomor_surat'        => SuratTeguranLaporanPolisi::generateNomorSurat($pengaduan->nomor_tiket),
            'jenis_dokumen'      => 'Laporan Polisi',
            'kronologi_kejadian' => 'Pelapor diminta membayar biaya seragam.',
            'tujuan_instansi'    => 'Subdit Siber Ditreskrimsus Polda Metro Jaya / Wilayah Terkait',
            'status_penerbitan'  => 'Terbit',
            'tanggal_terbit'     => now(),
        ]);

        $this->actingAs($this->kepalaBidang)->get("/buat-surat/{$pengaduan->id_pengaduan}/terbit/{$surat->id_surat}")
            ->assertOk()
            ->assertSee('Laporan Polisi Berhasil Diterbitkan')
            ->assertSee('Cetak Tanda Terima')
            ->assertSee('print-page')
            ->assertSee('no-print');
    }

    /**
     * Kriteria Selesai SRS Table B.5 (Buat Surat):
     * 1. Simpan Draft menampilkan "Draf berhasil disimpan".
     * 2. Penerbitan gagal menampilkan "Gagal menerbitkan dokumen, draf telah disimpan" dan tetap di "Buat Laporan Polisi".
     * 3. Terbitkan Laporan diarahkan ke halaman terbit yang menampilkan "Kasus terdokumentasi otomatis" dan "Kasus selesai".
     */
    public function test_kriteria_selesai_srs_tabel_b5_buat_surat(): void
    {
        Storage::fake('public');

        $pengaduan = $this->buatKasusTindaklanjuti('#LKR-UJI-8770', StatusKasus::TindakLanjutDisetujui);
        $urlForm = "/buat-surat/{$pengaduan->id_pengaduan}";
        $tujuan = 'Subdit Siber Ditreskrimsus Polda Metro Jaya';

        // 1. Simpan Draft (dengan bukti tambahan dari Kepala Bidang, FR-ST-04)
        $this->actingAs($this->kepalaBidang)->from($urlForm)->post("{$urlForm}/draft", [
            'kronologi'         => 'Kronologi awal draf oleh Kepala Bidang.',
            'tujuan_kepolisian' => $tujuan,
            'bukti_tambahan'    => [UploadedFile::fake()->create('Bukti_Chat_Baru.png', 50, 'image/png')],
        ])->assertRedirect($urlForm);

        $this->actingAs($this->kepalaBidang)->get($urlForm)
            ->assertOk()
            ->assertSee(LokerLensCopy::DRAF_TERSIMPAN)
            ->assertSee('Bukti_Chat_Baru.png');

        // 2. Penerbitan gagal. Sama seperti BDD (trigger MySQL), di sini trigger SQLite
        //    menolak perubahan status surat menjadi "Terbit".
        DB::statement("CREATE TRIGGER uji_gagal_terbit BEFORE UPDATE ON surat_teguran_laporan_polisi
            WHEN NEW.status_penerbitan = 'Terbit'
            BEGIN SELECT RAISE(ABORT, 'Simulasi penerbitan gagal'); END");

        $this->actingAs($this->kepalaBidang)->from($urlForm)->post("{$urlForm}/terbitkan", [
            'kronologi'         => 'Kronologi kejadian yang gagal diterbitkan.',
            'tujuan_kepolisian' => $tujuan,
        ])->assertRedirect($urlForm);

        $this->actingAs($this->kepalaBidang)->get($urlForm)
            ->assertOk()
            ->assertSee(LokerLensCopy::GAGAL_TERBITKAN)
            ->assertSee('Buat Laporan Polisi');

        // Draf tetap tersimpan dan kasus belum Selesai
        $pengaduan->refresh();
        $surat = $pengaduan->tindakLanjutKasus->suratTeguranLaporanPolisi;
        $this->assertSame(StatusKasus::TindakLanjutDisetujui, $pengaduan->status_pengaduan);
        $this->assertSame('Draft', $surat->status_penerbitan);
        $this->assertSame('Kronologi kejadian yang gagal diterbitkan.', $surat->kronologi_kejadian);

        DB::statement('DROP TRIGGER uji_gagal_terbit');

        // 3. Terbit berhasil → diarahkan ke halaman terbit
        $response = $this->actingAs($this->kepalaBidang)->from($urlForm)->post("{$urlForm}/terbitkan", [
            'kronologi'         => 'Pelapor telah mentransfer dana pelatihan ke sindikat penipuan.',
            'tujuan_kepolisian' => $tujuan,
        ]);

        $urlTerbit = route('buat-surat.terbit', ['pengaduanId' => $pengaduan->id_pengaduan, 'suratId' => $surat->id_surat]);
        $response->assertRedirect($urlTerbit);

        $this->actingAs($this->kepalaBidang)->get($urlTerbit)
            ->assertOk()
            ->assertSee(LokerLensCopy::KASUS_TERDOKUMENTASI)
            ->assertSee(LokerLensCopy::KASUS_SELESAI)
            ->assertSee($tujuan)
            ->assertSee('Bukti_Chat_Baru.png')
            ->assertSee('Lihat Arsip Kasus');

        $this->assertSame(StatusKasus::Selesai, $pengaduan->fresh()->status_pengaduan);
    }

    /**
     * NFR-SEC-02: halaman peran lain ditolak dengan 403.
     * SRS hanya meminta akses ditolak; tidak ada desain/teks khusus halaman 403 di SRS maupun Data Mockup.
     */
    public function test_middleware_role_menolak_dengan_403(): void
    {
        $this->actingAs($this->masyarakat)->get('/tinjau-lowongan')
            ->assertForbidden()
            ->assertSee('403');

        $this->actingAs($this->analis)->get('/tindak-lanjut')
            ->assertForbidden()
            ->assertSee('403');

        $this->actingAs($this->masyarakat)->get('/buat-surat')
            ->assertForbidden();
    }

    /**
     * NFR-SEC-05: email dan nomor telepon pelapor tidak tampil di halaman Analis dan Kepala Bidang.
     * Kepala Bidang hanya melihat nama pelapor (Data Mockup Gambar 2.11).
     */
    public function test_data_pribadi_pelapor_tidak_tampil_di_analis_dan_kepala_bidang(): void
    {
        $kasusAnalis = $this->buatKasus('#LKR-UJI-01', 92.0, StatusKasus::SedangDitinjau);

        $this->actingAs($this->analis)->get("/tinjau-lowongan/detail/{$kasusAnalis->hasilKlasifikasi->id_klasifikasi}")
            ->assertOk()
            ->assertDontSee($this->masyarakat->email)
            ->assertDontSee($this->masyarakat->no_telepon);

        $kasusKepalaBidang = $this->buatKasusMenungguKeputusan('#LKR-UJI-0842');

        $this->actingAs($this->kepalaBidang)->get("/tindak-lanjut/{$kasusKepalaBidang->id_pengaduan}")
            ->assertOk()
            ->assertSee($this->masyarakat->name)
            ->assertDontSee($this->masyarakat->email)
            ->assertDontSee($this->masyarakat->no_telepon);
    }
}
