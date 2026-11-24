<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Integrasi Periksa Lowongan Kerja dengan API ML (KlasifikasiService).
 */
class KlasifikasiMlTest extends TestCase
{
    use RefreshDatabase;

    private User $masyarakat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masyarakat = User::factory()->create(['role' => 'Masyarakat']);
    }

    private function kirimForm()
    {
        return $this->actingAs($this->masyarakat)->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Contoh Abadi',
            'posisi_lowongan'    => 'Staff Administrasi',
            'deskripsi_lowongan' => 'Dibutuhkan staff administrasi, wajib transfer biaya pendaftaran sebelum wawancara.',
            'informasi_tambahan' => 'Hubungi hrd@gmail.com',
        ]);
    }

    public function test_skor_di_atas_ambang_masuk_daftar_analis_dengan_kata_pemicu(): void
    {
        $this->palsukanApiMl(0.92, ['biaya pendaftaran', 'transfer']);

        $this->kirimForm()->assertRedirectContains('/periksa-lowongan/hasil/');

        $pengaduan = Pengaduan::firstOrFail();
        $hasil = $pengaduan->hasilKlasifikasi;

        $this->assertSame(StatusKasus::PerluTinjauan, $pengaduan->status_pengaduan);
        $this->assertEquals(92, $hasil->confidence_score);
        $this->assertTrue($hasil->status_prioritas);
        $this->assertSame('Lowongan berpotensi penipuan', $hasil->kategori_hasil);
        $this->assertSame(['biaya pendaftaran', 'transfer'], $hasil->indikatorMencurigakan->pluck('nama_indikator')->all());

        // Hanya deskripsi yang dikirim ke model (docs/architecture.md §5.2)
        Http::assertSent(fn (Request $r) => $r->url() === config('services.ml.url') . '/predict'
            && $r->data() === ['deskripsi' => 'Dibutuhkan staff administrasi, wajib transfer biaya pendaftaran sebelum wawancara.']);

        // Halaman hasil menampilkan skor dan kata pemicu
        $this->actingAs($this->masyarakat)->get("/periksa-lowongan/hasil/{$pengaduan->id_pengaduan}")
            ->assertOk()->assertSee('92%')->assertSee('"biaya pendaftaran"', false);

        // Prioritas tinggi muncul di daftar tinjauan Analis
        $this->actingAs(User::factory()->create(['role' => 'Analis']))
            ->get('/tinjau-lowongan')->assertSee($pengaduan->nomor_tiket);
    }

    public function test_skor_palsu_di_bawah_ambang_70_tidak_masuk_daftar_analis(): void
    {
        $this->palsukanApiMl(0.65);

        $this->kirimForm();

        $pengaduan = Pengaduan::firstOrFail();
        $this->assertSame(StatusKasus::SelesaiRisikoRendah, $pengaduan->status_pengaduan);
        $this->assertFalse($pengaduan->hasilKlasifikasi->status_prioritas);

        $this->actingAs(User::factory()->create(['role' => 'Analis']))
            ->get('/tinjau-lowongan')->assertDontSee($pengaduan->nomor_tiket);
    }

    public function test_api_mati_atau_timeout_pengaduan_tetap_tersimpan_dengan_pesan_jelas(): void
    {
        // Timeout dan API mati sama-sama berupa ConnectionException
        Http::fake(['*/predict' => Http::failedConnection()]);

        $this->kirimForm()->assertRedirectContains('/periksa-lowongan/hasil/');

        $pengaduan = Pengaduan::firstOrFail();
        $this->assertNull($pengaduan->hasilKlasifikasi);
        $this->assertSame(1, Pengaduan::menungguKlasifikasi()->count());

        $this->actingAs($this->masyarakat)->get("/periksa-lowongan/hasil/{$pengaduan->id_pengaduan}")
            ->assertOk()
            ->assertSee(LokerLensCopy::MENUNGGU_KLASIFIKASI)
            ->assertSee(LokerLensCopy::KLASIFIKASI_TERTUNDA)
            ->assertSee($pengaduan->nomor_tiket);
    }

    public function test_api_menjawab_error_pengaduan_tetap_tersimpan(): void
    {
        Http::fake(['*/predict' => Http::response(['detail' => 'error'], 500)]);

        $this->kirimForm()->assertRedirectContains('/periksa-lowongan/hasil/');

        $this->assertSame(1, Pengaduan::menungguKlasifikasi()->count());
    }

    public function test_hasil_asli_tampil_hijau_dengan_kotak_info_dan_bar_sesuai_skor(): void
    {
        $this->palsukanApiMl(0.24);
        $this->kirimForm();
        $pengaduan = Pengaduan::firstOrFail();

        $this->actingAs($this->masyarakat)->get("/periksa-lowongan/hasil/{$pengaduan->id_pengaduan}")
            ->assertOk()
            ->assertSee('Lowongan kemungkinan asli')
            ->assertSee('Risiko rendah')
            ->assertSee('bg-risk-low')
            ->assertSee('style="width: 24%"', false)
            ->assertSee('tidak menunjukkan ciri penipuan yang kuat')
            ->assertSee('rekomendasi pendukung keputusan')
            ->assertDontSee('PERINGATAN SISTEM');
    }

    public function test_hasil_palsu_tetap_menampilkan_peringatan_merah(): void
    {
        $this->palsukanApiMl(0.92);
        $this->kirimForm();
        $pengaduan = Pengaduan::firstOrFail();

        $this->actingAs($this->masyarakat)->get("/periksa-lowongan/hasil/{$pengaduan->id_pengaduan}")
            ->assertOk()
            ->assertSee('Lowongan berpotensi penipuan')
            ->assertSee('bg-risk-high')
            ->assertSee('PERINGATAN SISTEM')
            ->assertSee('rekomendasi pendukung keputusan')
            ->assertDontSee('tidak menunjukkan ciri penipuan yang kuat');
    }

    public function test_detail_analis_menampilkan_data_pengaduan_asli_tanpa_lampiran_dummy(): void
    {
        $this->palsukanApiMl(0.92, ['biaya pendaftaran']);
        $this->kirimForm();
        $pengaduan = Pengaduan::firstOrFail();

        $this->actingAs(User::factory()->create(['role' => 'Analis']))
            ->get("/tinjau-lowongan/detail/{$pengaduan->hasilKlasifikasi->id_klasifikasi}")
            ->assertOk()
            ->assertSee('Data Pengaduan')
            ->assertSee($pengaduan->nomor_tiket)
            ->assertSee('wajib transfer biaya pendaftaran sebelum wawancara')
            ->assertSee('biaya pendaftaran')
            ->assertSee('20.00')                 // bobot 0.2 x 100
            ->assertDontSee('Tangkapan Layar Chat WA')
            ->assertDontSee('Salinan Surat Panggilan')
            ->assertDontSee('Log Ekstraksi Teks');
    }

    public function test_detail_analis_memakai_data_klasifikasi_asli_tanpa_teks_dummy(): void
    {
        $this->palsukanApiMl(0.83);
        $this->kirimForm();
        $pengaduan = Pengaduan::firstOrFail();

        $this->actingAs(User::factory()->create(['role' => 'Analis']))
            ->get("/tinjau-lowongan/detail/{$pengaduan->hasilKlasifikasi->id_klasifikasi}")
            ->assertOk()
            ->assertSee('83%')
            ->assertSee('83.00 / 100')
            ->assertSee('Risiko tinggi')
            ->assertSee('text-risk-high')
            ->assertSee('Lowongan berpotensi penipuan')
            ->assertSee('Model Uji')                      // metode_analisis dari API
            ->assertSee('Belum Diverifikasi')             // status_terdaftar perusahaan
            ->assertDontSee('KRITIS')
            ->assertDontSee('95/100')
            ->assertDontSee('LokerLens Sentinel')
            ->assertDontSee('Legalitas AHU')
            ->assertDontSee('Ditandai Laporan Penipuan')
            ->assertDontSee('Badan Hukum Belum Terverifikasi');
    }

    public function test_tingkat_risiko_mengikuti_batas_50_dan_ambang_70(): void
    {
        $tingkat = fn (float $skor) => (new \App\Models\HasilKlasifikasi(['confidence_score' => $skor]))->tingkat_risiko;

        $this->assertSame('rendah', $tingkat(49.99));
        $this->assertSame('sedang', $tingkat(50));
        $this->assertSame('sedang', $tingkat(69.99));
        $this->assertSame('tinggi', $tingkat(70));
    }

    public function test_perintah_klasifikasi_ulang_mengisi_pengaduan_yang_tertunda(): void
    {
        Http::fake(['*/predict' => Http::failedConnection()]);
        $this->kirimForm();

        // API masih mati: perintah gagal, pengaduan tetap menunggu
        $this->artisan('lokerlens:klasifikasi-ulang')->assertFailed();
        $this->assertSame(1, Pengaduan::menungguKlasifikasi()->count());

        // API menyala lagi
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        $this->palsukanApiMl(0.88);

        $this->artisan('lokerlens:klasifikasi-ulang')->assertSuccessful();

        $pengaduan = Pengaduan::firstOrFail();
        $this->assertSame(0, Pengaduan::menungguKlasifikasi()->count());
        $this->assertSame(StatusKasus::PerluTinjauan, $pengaduan->status_pengaduan);
        $this->assertTrue($pengaduan->hasilKlasifikasi->status_prioritas);
    }
}
