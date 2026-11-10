<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman Masyarakat: form Periksa Lowongan (Data Mockup Gambar 2.5–2.6) dan Hasil Pemeriksaan (Gambar 2.7).
 * API ML selalu dipalsukan dengan palsukanApiMl() (lihat tests/TestCase.php).
 *
 * Catatan: Masyarakat tidak punya fitur upload bukti, jadi tidak ada test file_bukti.
 * Kegagalan API ML diuji di KlasifikasiMlTest (pengaduan tetap tersimpan dengan pesan jelas).
 */
class HalamanMasyarakatTest extends TestCase
{
    use RefreshDatabase;

    private User $masyarakat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->masyarakat = User::factory()->create(['role' => 'Masyarakat']);
    }

    public function test_form_periksa_memuat_semua_isian_dan_tombol(): void
    {
        $response = $this->actingAs($this->masyarakat)->get('/periksa-lowongan');

        $response->assertOk();
        $html = $response->getContent();

        // Isian sesuai Data Mockup Gambar 2.5
        $this->assertStringContainsString('name="link_lowongan"', $html);
        $this->assertStringContainsString('name="nama_perusahaan"', $html);
        $this->assertStringContainsString('name="posisi_lowongan"', $html);
        $this->assertStringContainsString('name="deskripsi_lowongan"', $html);
        $this->assertStringContainsString('name="informasi_tambahan"', $html);
        $this->assertStringContainsString('name="checklist[]"', $html);
        $this->assertStringContainsString('Minimal 50 karakter', $html);

        // Tombol dan status proses (Gambar 2.5–2.6)
        $response->assertSee(LokerLensCopy::BTN_RESET_FORM)
            ->assertSee(LokerLensCopy::BTN_PERIKSA_SEKARANG)
            ->assertSee('Sedang Memeriksa...');
    }

    public function test_halaman_hasil_menampilkan_nomor_tiket_label_skor_dan_kata_pemicu(): void
    {
        $this->palsukanApiMl(0.9, ['biaya pendaftaran', 'transfer']);

        $response = $this->actingAs($this->masyarakat)->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Penipu Digital',
            'posisi_lowongan'    => 'Data Entry',
            'deskripsi_lowongan' => 'Gaji tinggi tanpa pengalaman, wajib transfer biaya pendaftaran sebelum wawancara hotel.',
            'checklist'          => ['biaya', 'lokasi'],
        ]);

        $response->assertSessionHasNoErrors();
        $pengaduan = Pengaduan::latest('id_pengaduan')->firstOrFail();

        $this->actingAs($this->masyarakat)->get("/periksa-lowongan/hasil/{$pengaduan->id_pengaduan}")
            ->assertOk()
            ->assertSee($pengaduan->nomor_tiket)
            ->assertSee(LokerLensCopy::JUDUL_HASIL_PEMERIKSAAN)
            ->assertSee($pengaduan->hasilKlasifikasi->label_risiko)
            ->assertSee('Probabilitas penipuan')
            ->assertSee('id="kata-pemicu"', false)
            ->assertSee('biaya pendaftaran')
            ->assertSee('Gaji tinggi tanpa pengalaman');
    }

    /**
     * Kriteria Selesai SRS Table B.2:
     * 1. Isian lengkap → Hasil Pemeriksaan dengan kata-pemicu memuat "biaya".
     * 2. Field wajib kosong → "Data belum lengkap, mohon lengkapi terlebih dahulu" tanpa "Hasil Pemeriksaan".
     * 3. Tombol Reset Form tersedia.
     */
    public function test_kriteria_selesai_srs_tabel_b2(): void
    {
        $this->palsukanApiMl(0.9, ['biaya pendaftaran', 'transfer']);

        // 1. Isian lengkap sesuai SRS Table B.2
        $mulai = microtime(true);
        $response = $this->actingAs($this->masyarakat)->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Maju Bersama Sejahtera',
            'posisi_lowongan'    => 'Staff Administrasi',
            'deskripsi_lowongan' => 'Dibutuhkan staff administrasi, wajib transfer biaya pendaftaran sebelum wawancara.',
        ]);
        $durasi = microtime(true) - $mulai;

        // NFR-PERF-02: pemeriksaan <= 5 detik
        $this->assertLessThan(5.0, $durasi, "Pemeriksaan lowongan harus <= 5 detik (NFR-PERF-02). Aktual: {$durasi}s");

        $response->assertSessionHasNoErrors()->assertRedirect();

        $pengaduan = Pengaduan::where('deskripsi_singkat', 'like', '%PT Maju Bersama Sejahtera%')->latest('id_pengaduan')->firstOrFail();
        $html = $this->actingAs($this->masyarakat)->get("/periksa-lowongan/hasil/{$pengaduan->id_pengaduan}")
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(LokerLensCopy::JUDUL_HASIL_PEMERIKSAAN, $html);
        $this->assertMatchesRegularExpression('/id="kata-pemicu"[^>]*>[\s\S]*?biaya[\s\S]*?<\/div>/i', $html);

        // 2. Field wajib kosong
        $isianKosong = [
            'nama_perusahaan'    => '',
            'posisi_lowongan'    => '',
            'deskripsi_lowongan' => '',
        ];

        $this->actingAs($this->masyarakat)
            ->from('/periksa-lowongan')
            ->post('/periksa-lowongan', $isianKosong)
            ->assertRedirect('/periksa-lowongan')
            ->assertSessionHasErrors([
                'nama_perusahaan'    => LokerLensCopy::VALIDASI_BELUM_LENGKAP,
                'posisi_lowongan'    => LokerLensCopy::VALIDASI_BELUM_LENGKAP,
                'deskripsi_lowongan' => LokerLensCopy::VALIDASI_BELUM_LENGKAP,
            ]);

        // Halaman form setelah dikembalikan menampilkan pesan resmi
        $this->actingAs($this->masyarakat)
            ->from('/periksa-lowongan')
            ->followingRedirects()
            ->post('/periksa-lowongan', $isianKosong)
            ->assertOk()
            ->assertSee(LokerLensCopy::VALIDASI_BELUM_LENGKAP)
            ->assertDontSee(LokerLensCopy::JUDUL_HASIL_PEMERIKSAAN)
            // 3. Tombol Reset Form dengan fungsi reset
            ->assertSee(LokerLensCopy::BTN_RESET_FORM)
            ->assertSee('resetForm()', false);
    }

    /**
     * Integrasi API ML POST /predict (docs/architecture.md §5.1–§5.3).
     * NFR-QUA-05: Kategori hasil dan confidence score sama dengan nilai di Hasil_Klasifikasi.
     */
    public function test_integrasi_api_ml_post_predict_dan_kategori_hasil(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'http://127.0.0.1:8001/predict' => \Illuminate\Support\Facades\Http::response([
                'probabilitas_penipuan' => 0.92,
                'label'                 => 'palsu',
                'kata_pemicu'           => [
                    ['kata' => 'biaya administrasi', 'bobot' => 0.31],
                    ['kata' => 'tanpa pengalaman',   'bobot' => 0.18],
                ],
                'versi_model'           => 'logreg-tfidf-2026-10',
            ], 200),
        ]);

        config(['services.ml.url' => 'http://127.0.0.1:8001']);

        $response = $this->actingAs($this->masyarakat)->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Mandiri Sukses Global',
            'posisi_lowongan'    => 'Staff Administrasi',
            'deskripsi_lowongan' => 'PT Mandiri Sukses Global membuka lowongan Staff Administrasi gaji tinggi tanpa pengalaman biaya administrasi.',
        ]);

        $response->assertSessionHasNoErrors();
        $pengaduan = Pengaduan::latest('id_pengaduan')->firstOrFail();
        $klasifikasi = $pengaduan->hasilKlasifikasi;

        // Pastikan disimpan sesuai architecture.md §5.3
        $this->assertEquals(92.0, (float) $klasifikasi->confidence_score);
        $this->assertSame('Lowongan berpotensi penipuan', $klasifikasi->kategori_hasil);
        $this->assertSame('logreg-tfidf-2026-10', $klasifikasi->metode_analisis);

        // Pastikan kata pemicu asli disimpan ke indikator_mencurigakan
        $this->assertDatabaseHas('indikator_mencurigakan', [
            'id_klasifikasi' => $klasifikasi->id_klasifikasi,
            'nama_indikator' => 'biaya administrasi',
        ]);

        // Verifikasi halaman hasil menampilkan kategori_hasil dan kata pemicu asli
        $hasilResponse = $this->actingAs($this->masyarakat)->get("/periksa-lowongan/hasil/{$pengaduan->id_pengaduan}");
        $hasilResponse->assertOk();
        $html = $hasilResponse->getContent();
        $this->assertStringContainsString($klasifikasi->kategori_hasil, $html);
        $this->assertStringContainsString('biaya administrasi', $html);
    }
}
