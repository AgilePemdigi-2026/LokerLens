<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use App\Models\SuratTeguranLaporanPolisi;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pilihan jenis dokumen di Buat Surat (issue #8, US-04): Laporan Polisi ATAU Surat Teguran.
 * Data seeder: #LKR-8770 (PT Gemilang Karya Persada) sudah Tindaklanjuti dan punya draf Laporan Polisi.
 */
class JenisDokumenSuratTest extends TestCase
{
    use RefreshDatabase;

    private const TIKET = '#LKR-8770';
    private const TUJUAN_POLISI = 'Satreskrim Polres Wilayah Setempat';

    private User $kepalaBidang;
    private Pengaduan $pengaduan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LokerLensSeeder::class);
        $this->kepalaBidang = User::where('role', 'Kepala Bidang')->firstOrFail();
        $this->pengaduan = Pengaduan::where('nomor_tiket', self::TIKET)->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function surat(): ?SuratTeguranLaporanPolisi
    {
        return $this->pengaduan->tindakLanjutKasus->fresh()->suratTeguranLaporanPolisi;
    }

    private function kirim(string $aksi, array $isian)
    {
        return $this->actingAs($this->kepalaBidang)
            ->from(route('buat-surat.index', $this->pengaduan->id_pengaduan))
            ->post(route("buat-surat.{$aksi}", $this->pengaduan->id_pengaduan), $isian + ['kronologi' => 'Kronologi uji.']);
    }

    public function test_form_menampilkan_pilihan_jenis_dengan_laporan_polisi_terpilih(): void
    {
        $this->actingAs($this->kepalaBidang)->get(route('buat-surat.index', $this->pengaduan->id_pengaduan))
            ->assertOk()
            ->assertSee(LokerLensCopy::LABEL_JENIS_DOKUMEN)
            ->assertSee('value="Laporan Polisi" x-model="jenis" checked', false)
            ->assertDontSee('value="Surat Teguran" x-model="jenis" checked', false)
            ->assertSee(LokerLensCopy::JUDUL_BUAT_LAPORAN)
            // Tujuan teguran terisi otomatis dengan nama perusahaan
            ->assertSee('value="Pimpinan PT Gemilang Karya Persada"', false);
    }

    public function test_kronologi_bawaan_netral_untuk_kedua_jenis(): void
    {
        SuratTeguranLaporanPolisi::query()->delete();

        $this->actingAs($this->kepalaBidang)->get(route('buat-surat.index', $this->pengaduan->id_pengaduan))
            ->assertOk()
            ->assertSee('kasus ini ditindaklanjuti sesuai kewenangan instansi yang dituju')
            ->assertDontSee('pihak berwajib');
    }

    public function test_simpan_draf_surat_teguran_hanya_menyimpan_tujuan_teguran(): void
    {
        $this->kirim('draft', [
            'jenis_dokumen'     => LokerLensCopy::JENIS_SURAT_TEGURAN,
            'tujuan_teguran'    => 'Direktur PT Gemilang Karya Persada',
            // Isian panel tersembunyi ikut terkirim, tetapi tidak boleh tersimpan
            'tujuan_kepolisian' => self::TUJUAN_POLISI,
        ])->assertRedirect()->assertSessionHas('success', LokerLensCopy::DRAF_TERSIMPAN);

        $surat = $this->surat();
        $this->assertSame(LokerLensCopy::JENIS_SURAT_TEGURAN, $surat->jenis_dokumen);
        $this->assertSame('Direktur PT Gemilang Karya Persada', $surat->tujuan_instansi);
        $this->assertSame('Draft', $surat->status_penerbitan);

        // Draf dibuka lagi: Surat Teguran tetap terpilih
        $this->actingAs($this->kepalaBidang)->get(route('buat-surat.index', $this->pengaduan->id_pengaduan))
            ->assertOk()
            ->assertSee('value="Surat Teguran" x-model="jenis" checked', false)
            ->assertSee('value="Direktur PT Gemilang Karya Persada"', false);
    }

    public function test_terbitkan_surat_teguran_memakai_nomor_disnaker_tahun_berjalan(): void
    {
        Carbon::setTestNow(Carbon::create(2027, 3, 5, 10, 0, 0));

        $this->kirim('terbitkan', [
            'jenis_dokumen'  => LokerLensCopy::JENIS_SURAT_TEGURAN,
            'tujuan_teguran' => 'Pimpinan PT Gemilang Karya Persada',
        ])->assertRedirect();

        $surat = $this->surat();
        $this->assertSame('#ST-2027/DISNAKER/8770', $surat->nomor_surat);
        $this->assertSame('Terbit', $surat->status_penerbitan);
        $this->assertSame(StatusKasus::Selesai, $this->pengaduan->fresh()->status_pengaduan);
        $this->assertStringStartsWith('Surat Teguran #ST-2027/DISNAKER/8770 berhasil diterbitkan', $this->pengaduan->riwayatKasus()->first()->keterangan);

        $this->actingAs($this->kepalaBidang)
            ->get(route('buat-surat.terbit', ['pengaduanId' => $this->pengaduan->id_pengaduan, 'suratId' => $surat->id_surat]))
            ->assertOk()
            ->assertSee(LokerLensCopy::JUDUL_TERBIT_TEGURAN)
            ->assertSee(LokerLensCopy::LABEL_DITUJUKAN_KEPADA)
            ->assertDontSee(LokerLensCopy::LABEL_TUJUAN_KEPOLISIAN)
            ->assertSee(LokerLensCopy::KASUS_SELESAI);

        // Muncul di halaman Surat dengan filter Surat Teguran
        $this->actingAs($this->kepalaBidang)->get('/surat?jenis_dokumen=Surat+Teguran')
            ->assertOk()
            ->assertSee('#ST-2027/DISNAKER/8770');
    }

    public function test_terbitkan_laporan_polisi_memakai_nomor_polri_tahun_berjalan(): void
    {
        Carbon::setTestNow(Carbon::create(2028, 1, 10, 9, 0, 0));

        $this->kirim('terbitkan', [
            'jenis_dokumen'     => LokerLensCopy::JENIS_LAPORAN_POLISI,
            'tujuan_kepolisian' => self::TUJUAN_POLISI,
            'tujuan_teguran'    => 'Tidak boleh tersimpan',
        ])->assertRedirect();

        $surat = $this->surat();
        $this->assertSame('#LP-2028/POLRI/8770', $surat->nomor_surat);
        $this->assertSame(self::TUJUAN_POLISI, $surat->tujuan_instansi);

        $this->actingAs($this->kepalaBidang)
            ->get(route('buat-surat.terbit', ['pengaduanId' => $this->pengaduan->id_pengaduan, 'suratId' => $surat->id_surat]))
            ->assertOk()
            ->assertSee(LokerLensCopy::JUDUL_TERBIT_LAPORAN)
            ->assertSee(LokerLensCopy::LABEL_TUJUAN_KEPOLISIAN);
    }

    public function test_tanpa_jenis_dokumen_dianggap_laporan_polisi(): void
    {
        $this->kirim('draft', ['tujuan_kepolisian' => self::TUJUAN_POLISI])->assertSessionHasNoErrors();

        $this->assertSame(LokerLensCopy::JENIS_LAPORAN_POLISI, $this->surat()->jenis_dokumen);
    }

    public function test_validasi_tujuan_mengikuti_jenis_dokumen(): void
    {
        // Surat Teguran: tujuan_teguran wajib, tujuan_kepolisian tidak diperiksa
        $this->kirim('terbitkan', [
            'jenis_dokumen'     => LokerLensCopy::JENIS_SURAT_TEGURAN,
            'tujuan_teguran'    => '',
            'tujuan_kepolisian' => '',
        ])->assertSessionHasErrors(['tujuan_teguran' => LokerLensCopy::SURAT_TUJUAN_TEGURAN_WAJIB])
          ->assertSessionDoesntHaveErrors('tujuan_kepolisian');

        // Laporan Polisi: tujuan_kepolisian wajib, tujuan_teguran tidak diperiksa
        $this->kirim('draft', [
            'jenis_dokumen'     => LokerLensCopy::JENIS_LAPORAN_POLISI,
            'tujuan_kepolisian' => '',
            'tujuan_teguran'    => '',
        ])->assertSessionHasErrors(['tujuan_kepolisian' => LokerLensCopy::SURAT_TUJUAN_WAJIB])
          ->assertSessionDoesntHaveErrors('tujuan_teguran');

        // Draf seeder tidak berubah
        $this->assertSame(LokerLensCopy::JENIS_LAPORAN_POLISI, $this->surat()->jenis_dokumen);
        $this->assertSame('Draft', $this->surat()->status_penerbitan);
    }

    public function test_jenis_dokumen_tidak_dikenal_ditolak(): void
    {
        $this->kirim('terbitkan', [
            'jenis_dokumen'     => 'Surat Peringatan',
            'tujuan_kepolisian' => self::TUJUAN_POLISI,
        ])->assertSessionHasErrors(['jenis_dokumen' => LokerLensCopy::SURAT_JENIS_TIDAK_DIKENAL]);

        $this->assertSame('Draft', $this->surat()->status_penerbitan);
        $this->assertSame(StatusKasus::TindakLanjutDisetujui, $this->pengaduan->fresh()->status_pengaduan);
    }

    public function test_gagal_validasi_mempertahankan_pilihan_surat_teguran(): void
    {
        $this->followingRedirects()->kirim('draft', [
            'jenis_dokumen'  => LokerLensCopy::JENIS_SURAT_TEGURAN,
            'tujuan_teguran' => '',
        ])->assertOk()
          ->assertSee(LokerLensCopy::SURAT_TUJUAN_TEGURAN_WAJIB)
          ->assertSee('value="Surat Teguran" x-model="jenis" checked', false);
    }
}
