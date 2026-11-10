<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Enums\StatusKasus;
use App\Models\BuktiPendukung;
use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HalamanMasyarakatTest extends TestCase
{
    use RefreshDatabase;

    private User $masyarakat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->masyarakat = User::factory()->create(['role' => 'Masyarakat']);
        Storage::fake('public');
    }

    public function test_form_periksa_memuat_semua_elemen_dan_fitur_alpine(): void
    {
        $response = $this->actingAs($this->masyarakat)->get('/periksa-lowongan');

        $response->assertOk();
        $html = $response->getContent();

        // 1. Memuat seluruh field yang diminta
        $this->assertStringContainsString('name="nama_perusahaan"', $html);
        $this->assertStringContainsString('name="posisi_lowongan"', $html);
        $this->assertStringContainsString('name="link_lowongan"', $html);
        $this->assertStringContainsString('name="deskripsi_lowongan"', $html);
        $this->assertStringContainsString('name="informasi_tambahan"', $html);
        $this->assertStringContainsString('name="checklist[]"', $html);
        $this->assertStringContainsString('name="file_bukti"', $html);

        // 2. Input file memuat accept pdf/jpg/jpeg/png
        $this->assertStringContainsString('accept=".pdf,.jpg,.jpeg,.png"', $html);
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);

        // 3. Alpine.js: pratinjau nama file, penghitung karakter, dan status loading Memeriksa...
        $this->assertStringContainsString('x-text="fileName"', $html);
        $this->assertStringContainsString('x-text="deskripsi.length"', $html);
        $this->assertStringContainsString('Memeriksa…', $html);
    }

    public function test_validasi_file_bukti_hanya_menerima_pdf_jpg_png(): void
    {
        // 1. File tidak valid (.txt / .exe) ditolak
        $invalidFile = UploadedFile::fake()->create('dokumen.txt', 100, 'text/plain');

        $responseInvalid = $this->actingAs($this->masyarakat)->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Contoh Aman',
            'posisi_lowongan'    => 'Staff HRD',
            'deskripsi_lowongan' => str_repeat('Deskripsi pekerjaan resmi dan lengkap untuk lowongan ini. ', 2),
            'file_bukti'         => $invalidFile,
        ]);

        $responseInvalid->assertSessionHasErrors(['file_bukti']);

        // 2. File valid (.pdf) diterima dan disimpan ke database bukti_pendukung
        $validFile = UploadedFile::fake()->create('bukti_loker.pdf', 500, 'application/pdf');

        $responseValid = $this->actingAs($this->masyarakat)->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Contoh Valid',
            'posisi_lowongan'    => 'Staff Administrasi',
            'deskripsi_lowongan' => str_repeat('Lowongan pekerjaan ini membutuhkan staf administrasi berpengalaman. ', 2),
            'file_bukti'         => $validFile,
        ]);

        $responseValid->assertSessionHasNoErrors();
        $responseValid->assertRedirect();

        $pengaduan = Pengaduan::latest('id_pengaduan')->firstOrFail();
        $this->assertDatabaseHas('bukti_pendukung', [
            'id_pengaduan' => $pengaduan->id_pengaduan,
            'nama_file'    => 'bukti_loker.pdf',
            'tipe_file'    => 'pdf',
        ]);
    }

    public function test_kegagalan_layanan_ml_menolak_pemeriksaan_dan_mempertahankan_isian(): void
    {
        $payload = [
            'nama_perusahaan'    => 'PT Sumber Berkah',
            'posisi_lowongan'    => 'Customer Service',
            'deskripsi_lowongan' => 'Lowongan kerja dengan transfer biaya reservasi hotel sebelum interview di hotel.',
            'informasi_tambahan' => 'Kontak WA 08123456789',
        ];

        // Header simulasi kegagalan ML
        $response = $this->actingAs($this->masyarakat)
            ->withHeader('X-Simulate-ML-Failure', '1')
            ->post('/periksa-lowongan', $payload);

        $response->assertRedirect();
        $response->assertSessionHas('error', LokerLensCopy::ERROR_API_ML);

        // Memastikan isian tetap dipertahankan via old()
        $this->assertSame($payload['nama_perusahaan'], session()->getOldInput('nama_perusahaan'));
        $this->assertSame($payload['posisi_lowongan'], session()->getOldInput('posisi_lowongan'));
        $this->assertSame($payload['deskripsi_lowongan'], session()->getOldInput('deskripsi_lowongan'));

        // Memastikan tidak ada pengaduan yang tersimpan sebagian
        $this->assertDatabaseMissing('pengaduan', [
            'deskripsi_singkat' => 'Periksa lowongan: Customer Service di PT Sumber Berkah',
        ]);
    }

    public function test_halaman_hasil_menampilkan_nomor_tiket_label_skor_kata_pemicu_dan_sorotan(): void
    {
        $response = $this->actingAs($this->masyarakat)->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Penipu Digital',
            'posisi_lowongan'    => 'Data Entry',
            'deskripsi_lowongan' => 'Gaji tinggi tanpa pengalaman, wajib transfer biaya pendaftaran sebelum wawancara hotel.',
            'checklist'          => ['biaya', 'lokasi'],
        ]);

        $response->assertSessionHasNoErrors();
        $pengaduan = Pengaduan::latest('id_pengaduan')->firstOrFail();

        $hasilResponse = $this->actingAs($this->masyarakat)->get("/periksa-lowongan/hasil/{$pengaduan->id_pengaduan}");
        $hasilResponse->assertOk();

        $html = $hasilResponse->getContent();

        // 1. Nomor Tiket
        $this->assertStringContainsString($pengaduan->nomor_tiket, $html);

        // 2. Label Asli / Palsu
        $this->assertStringContainsString('PALSU', $html);

        // 3. Skor & Hasil Pemeriksaan
        $this->assertStringContainsString('Hasil Pemeriksaan', $html);
        $this->assertStringContainsString('Probabilitas Penipuan', $html);

        // 4. Status Kasus
        $this->assertStringContainsString($pengaduan->status_pengaduan->value, $html);

        // 5. Daftar Kata Pemicu
        $this->assertStringContainsString('id="kata-pemicu"', $html);
        $this->assertStringContainsString('biaya', strtolower($html));

        // 6. Penyorotan kata pemicu dan deskripsi aman ter-escape
        $this->assertStringContainsString('Gaji tinggi tanpa pengalaman', $html);
        $this->assertStringContainsString('keywords', $html);
        $this->assertStringContainsString('biaya', $html);
    }

    /**
     * Kriteria Selesai SRS Table B.2:
     * 1. Mengisi PT Maju Bersama Sejahtera, Staff Administrasi, dan deskripsi wajib transfer biaya pendaftaran -> Hasil Pemeriksaan dengan kata-pemicu memuat "biaya".
     * 2. Menekan Periksa Sekarang dengan field wajib kosong -> "Data belum lengkap, mohon lengkapi terlebih dahulu" dan tidak menampilkan "Hasil Pemeriksaan".
     * 3. Reset form mengosongkan field.
     */
    public function test_kriteria_selesai_srs_tabel_b2(): void
    {
        // 1. Skenario Sukses: Isian lengkap sesuai SRS Tabel B.2
        $startTime = microtime(true);
        $response = $this->actingAs($this->masyarakat)->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Maju Bersama Sejahtera',
            'posisi_lowongan'    => 'Staff Administrasi',
            'deskripsi_lowongan' => 'Dibutuhkan staff administrasi, wajib transfer biaya pendaftaran sebelum wawancara.',
        ]);
        $duration = microtime(true) - $startTime;

        // NFR-PERF-02: Waktu pemeriksaan <= 5 detik
        $this->assertLessThan(5.0, $duration, "Waktu pemeriksaan lowongan harus <= 5 detik (NFR-PERF-02). Aktual: {$duration}s");

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $pengaduan = Pengaduan::where('deskripsi_singkat', 'like', '%PT Maju Bersama Sejahtera%')->latest('id_pengaduan')->firstOrFail();
        $hasilResponse = $this->actingAs($this->masyarakat)->get("/periksa-lowongan/hasil/{$pengaduan->id_pengaduan}");
        $hasilResponse->assertOk();

        $html = $hasilResponse->getContent();
        // Menampilkan "Hasil Pemeriksaan"
        $this->assertStringContainsString('Hasil Pemeriksaan', $html);
        // Ada elemen kata-pemicu dan "biaya" muncul di elemen tersebut
        $this->assertStringContainsString('id="kata-pemicu"', $html);
        $this->assertMatchesRegularExpression('/id="kata-pemicu"[^>]*>[\s\S]*?biaya[\s\S]*?<\/div>/i', $html);

        // 2. Skenario Field Wajib Kosong: Validasi gagal dan tidak menampilkan "Hasil Pemeriksaan"
        $responseEmpty = $this->actingAs($this->masyarakat)
            ->from('/periksa-lowongan')
            ->post('/periksa-lowongan', [
                'nama_perusahaan'    => '',
                'posisi_lowongan'    => '',
                'deskripsi_lowongan' => '',
            ]);

        $responseEmpty->assertSessionHasErrors([
            'nama_perusahaan'    => LokerLensCopy::VALIDASI_BELUM_LENGKAP,
            'posisi_lowongan'    => LokerLensCopy::VALIDASI_BELUM_LENGKAP,
            'deskripsi_lowongan' => LokerLensCopy::VALIDASI_BELUM_LENGKAP,
        ]);
        $responseEmpty->assertRedirect();

        // Render view periksa-lowongan.index dengan error validasi
        $errorBag = $responseEmpty->getSession()->get('errors');
        $view = $this->actingAs($this->masyarakat)->view('periksa-lowongan.index', [
            'errors' => $errorBag,
        ]);
        $view->assertSee('Data belum lengkap, mohon lengkapi terlebih dahulu');
        $view->assertDontSee('Hasil Pemeriksaan');

        // 3. Tombol Reset Form tersedia dan memiliki fungsi reset
        $view->assertSee('Reset Form');
        $view->assertSee('resetForm()', false);
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
