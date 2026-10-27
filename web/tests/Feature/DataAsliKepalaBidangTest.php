<?php

namespace Tests\Feature;

use App\Enums\HasilTinjauan;
use App\Models\Pengaduan;
use App\Models\SuratTeguranLaporanPolisi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman Kepala Bidang (detail laporan, Buat Surat, surat terbit) memakai data asli,
 * bukan teks dummy (issue #7 dan #8).
 */
class DataAsliKepalaBidangTest extends TestCase
{
    use RefreshDatabase;

    private User $analis;
    private User $kepalaBidang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analis       = User::factory()->create(['role' => 'Analis', 'name' => 'Analis Uji Coba']);
        $this->kepalaBidang = User::factory()->create(['role' => 'Kepala Bidang', 'name' => 'Kabid Uji Coba']);
    }

    /** Periksa → tinjau (Terkonfirmasi Mencurigakan) sampai status Menunggu Keputusan. */
    private function kasusMenungguKeputusan(float $probabilitas = 0.72): Pengaduan
    {
        $this->palsukanApiMl($probabilitas, ['biaya pendaftaran', 'transfer']);

        $this->actingAs(User::factory()->create(['role' => 'Masyarakat']))->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Contoh Abadi',
            'posisi_lowongan'    => 'Staff Administrasi',
            'deskripsi_lowongan' => 'Dibutuhkan staff administrasi, wajib transfer biaya pendaftaran sebelum wawancara.',
        ]);
        $pengaduan = Pengaduan::latest('id_pengaduan')->firstOrFail();
        $idKlasifikasi = $pengaduan->hasilKlasifikasi->id_klasifikasi;

        $this->actingAs($this->analis)->get("/tinjau-lowongan/detail/{$idKlasifikasi}");
        $this->actingAs($this->analis)->post("/tinjau-lowongan/detail/{$idKlasifikasi}/simpan", [
            'catatan_tinjauan' => 'Catatan asli dari analis uji.',
            'hasil_tinjauan'   => HasilTinjauan::TerkonfirmasiMencurigakan->value,
        ]);

        return $pengaduan->fresh();
    }

    public function test_detail_kepala_bidang_memakai_data_asli_tanpa_dummy(): void
    {
        $pengaduan = $this->kasusMenungguKeputusan(0.72);

        $this->actingAs($this->kepalaBidang)->get("/tindak-lanjut/{$pengaduan->id_pengaduan}")
            ->assertOk()
            // Teks wajib BDD tetap ada
            ->assertSee('Detail Laporan Lowongan Mencurigakan')
            ->assertSee('Terindikasi Anomali Tinggi')
            // Data asli: analis, tinjauan, kata pemicu, tingkat risiko 72 = tinggi (ambang 70)
            ->assertSee('Analis Uji Coba')
            ->assertSee('Catatan asli dari analis uji.')
            ->assertSee(HasilTinjauan::TerkonfirmasiMencurigakan->value)
            ->assertSee('"biaya pendaftaran"', false)
            ->assertSee('Risiko tinggi')
            ->assertSee('Data Pengaduan')
            // Teks dummy sudah hilang
            ->assertDontSee('Budi Santoso')
            ->assertDontSee('Biaya akomodasi')
            ->assertDontSee('812-9901-4432')
            ->assertDontSee('Terindikasi Fiktif / Non-AHU')
            ->assertDontSee('Prioritas Tinggi (Kritis)')
            ->assertDontSee('Rekening Transfer Bank')
            ->assertDontSee('Ekstraksi OCR Teks');
    }

    public function test_prioritas_kepala_bidang_memakai_ambang_70(): void
    {
        $pengaduan = $this->kasusMenungguKeputusan(0.72);

        $this->actingAs($this->kepalaBidang)->get('/tindak-lanjut?prioritas=tinggi')->assertSee($pengaduan->nomor_tiket);
        $this->actingAs($this->kepalaBidang)->get('/tindak-lanjut?prioritas=sedang')->assertDontSee($pengaduan->nomor_tiket);
        $this->actingAs($this->kepalaBidang)->get('/tindak-lanjut')->assertSee('1 Prioritas tinggi · 0 Prioritas sedang');
    }

    public function test_pasal_tersimpan_di_draf_dan_tampil_di_surat_terbit(): void
    {
        $pengaduan = $this->kasusMenungguKeputusan();
        $this->actingAs($this->kepalaBidang)->post("/tindak-lanjut/{$pengaduan->id_pengaduan}/keputusan", ['keputusan' => 'Tindaklanjuti']);

        // Draf dengan satu pasal: pilihan tersimpan dan dicentang lagi di form
        $this->actingAs($this->kepalaBidang)->post("/buat-surat/{$pengaduan->id_pengaduan}/draft", [
            'kronologi'         => 'Kronologi uji.',
            'tujuan_kepolisian' => 'Satreskrim Polres Wilayah Setempat',
            'pasal'             => ['28_ite'],
        ])->assertSessionHasNoErrors();
        $this->assertSame(['28_ite'], SuratTeguranLaporanPolisi::sole()->pasal_dilanggar);

        $form = $this->actingAs($this->kepalaBidang)->get("/buat-surat/{$pengaduan->id_pengaduan}")->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#value="28_ite"\s+checked#', $form);
        $this->assertDoesNotMatchRegularExpression('#value="378_kuhp"\s+checked#', $form);
        $this->assertStringNotContainsString('KRITIS (Sindikat)', $form);
        $this->assertStringNotContainsString('Non-AHU', $form);
        $this->assertStringNotContainsString('TERVERIFIKASI TANDA TANGAN ELEKTRONIK', $form);
        $this->assertStringContainsString('Ditandatangani oleh Kabid Uji Coba', $form);

        // Terbitkan dengan dua pasal: tampil di halaman surat terbit
        $terbit = $this->actingAs($this->kepalaBidang)->post("/buat-surat/{$pengaduan->id_pengaduan}/terbitkan", [
            'kronologi'         => 'Kronologi uji.',
            'tujuan_kepolisian' => 'Satreskrim Polres Wilayah Setempat',
            'pasal'             => ['378_kuhp', '13_2003'],
        ]);
        $this->assertSame(['378_kuhp', '13_2003'], SuratTeguranLaporanPolisi::sole()->pasal_dilanggar);

        $this->actingAs($this->kepalaBidang)->get($terbit->headers->get('Location'))
            ->assertOk()
            ->assertSee('Dugaan Penipuan Rekrutmen Kerja (Pasal 378 KUHP)')
            ->assertSee('Pungutan Biaya Penempatan Ilegal bagi Calon Tenaga Kerja (UU No. 13/2003)')
            ->assertDontSee('Pasal 28 ayat 1 UU ITE')
            ->assertSee('Kabid Uji Coba (Kepala Bidang)')
            ->assertDontSee('Enkripsi SHA-256 Valid')
            ->assertDontSee('(Fiktif)')
            ->assertSee('siap diteruskan ke instansi kepolisian tujuan');
    }

    public function test_checkbox_notifikasi_darurat_disembunyikan_di_detail_analis(): void
    {
        $this->palsukanApiMl(0.9);
        $this->actingAs(User::factory()->create(['role' => 'Masyarakat']))->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Contoh Abadi',
            'posisi_lowongan'    => 'Staff Administrasi',
            'deskripsi_lowongan' => 'Dibutuhkan staff administrasi, wajib transfer biaya pendaftaran sebelum wawancara.',
        ]);
        $idKlasifikasi = Pengaduan::firstOrFail()->hasilKlasifikasi->id_klasifikasi;

        $this->actingAs($this->analis)->get("/tinjau-lowongan/detail/{$idKlasifikasi}")
            ->assertOk()
            ->assertSee('name="catatan_tinjauan"', false)
            ->assertDontSee('name="notifikasi_darurat"', false)
            ->assertDontSee('Kirimkan notifikasi darurat');
    }

    public function test_pasal_yang_tidak_dikenal_ditolak(): void
    {
        $pengaduan = $this->kasusMenungguKeputusan();
        $this->actingAs($this->kepalaBidang)->post("/tindak-lanjut/{$pengaduan->id_pengaduan}/keputusan", ['keputusan' => 'Tindaklanjuti']);

        $this->actingAs($this->kepalaBidang)->post("/buat-surat/{$pengaduan->id_pengaduan}/draft", [
            'kronologi'         => 'Kronologi uji.',
            'tujuan_kepolisian' => 'Satreskrim Polres Wilayah Setempat',
            'pasal'             => ['pasal_karangan'],
        ])->assertSessionHasErrors('pasal.0');
    }
}
