<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Enums\HasilTinjauan;
use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use App\Models\SuratTeguranLaporanPolisi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlurKasusTest extends TestCase
{
    use RefreshDatabase;

    private User $masyarakat;
    private User $analis;
    private User $kepalaBidang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masyarakat   = User::factory()->create(['role' => 'Masyarakat']);
        $this->analis       = User::factory()->create(['role' => 'Analis']);
        $this->kepalaBidang = User::factory()->create(['role' => 'Kepala Bidang']);
    }

    /** Skor simulasi: 20 + biaya (25) + gmail (20) + gaji (15) + hotel (15) = 95. */
    private function periksa(string $deskripsi): Pengaduan
    {
        $this->actingAs($this->masyarakat)->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Contoh Abadi',
            'posisi_lowongan'    => 'Staff Administrasi',
            'deskripsi_lowongan' => $deskripsi,
        ])->assertRedirect();

        return Pengaduan::latest('id_pengaduan')->firstOrFail();
    }

    private function periksaRisikoTinggi(): Pengaduan
    {
        return $this->periksa('Gaji tinggi tanpa pengalaman, wajib bayar biaya seragam, kirim CV ke hrd@gmail.com, interview di hotel.');
    }

    private function tinjau(Pengaduan $pengaduan, HasilTinjauan $hasil)
    {
        return $this->actingAs($this->analis)->post("/tinjau-lowongan/detail/{$pengaduan->hasilKlasifikasi->id_klasifikasi}/simpan", [
            'catatan_tinjauan' => 'Catatan hasil audit.',
            'hasil_tinjauan'   => $hasil->value,
        ]);
    }

    private function putuskan(Pengaduan $pengaduan, string $keputusan)
    {
        return $this->actingAs($this->kepalaBidang)->post("/tindak-lanjut/{$pengaduan->id_pengaduan}/keputusan", [
            'keputusan' => $keputusan,
        ]);
    }

    private function terbitkan(Pengaduan $pengaduan)
    {
        return $this->actingAs($this->kepalaBidang)->post("/buat-surat/{$pengaduan->id_pengaduan}/terbitkan", [
            'kronologi'         => 'Kronologi kejadian.',
            'tujuan_kepolisian' => 'Satreskrim Polres Wilayah Setempat',
        ]);
    }

    /** @return array<int, string> */
    private function riwayat(Pengaduan $pengaduan): array
    {
        return $pengaduan->riwayatKasus()->reorder('id_riwayat')->pluck('status_kasus')->all();
    }

    public function test_skor_di_bawah_ambang_langsung_selesai_risiko_rendah(): void
    {
        $pengaduan = $this->periksa('Dibutuhkan staff administrasi untuk kantor pusat dengan jam kerja normal dan kontrak resmi.');

        $this->assertSame(StatusKasus::SelesaiRisikoRendah, $pengaduan->status_pengaduan);
        $this->assertFalse($pengaduan->hasilKlasifikasi->status_prioritas);
        $this->assertSame([StatusKasus::SelesaiRisikoRendah->value], $this->riwayat($pengaduan));

        $this->actingAs($this->analis)->get('/tinjau-lowongan')->assertDontSee($pengaduan->nomor_tiket);
    }

    public function test_alur_lengkap_sampai_surat_terbit_dan_tercatat_di_riwayat(): void
    {
        $pengaduan = $this->periksaRisikoTinggi();
        $this->assertSame(StatusKasus::PerluTinjauan, $pengaduan->status_pengaduan);
        $this->assertTrue($pengaduan->hasilKlasifikasi->status_prioritas);

        $this->actingAs($this->analis)->get("/tinjau-lowongan/detail/{$pengaduan->hasilKlasifikasi->id_klasifikasi}")->assertOk();
        $this->assertSame(StatusKasus::SedangDitinjau, $pengaduan->fresh()->status_pengaduan);

        $this->tinjau($pengaduan, HasilTinjauan::TerkonfirmasiMencurigakan)
            ->assertRedirect(route('tinjau-lowongan.index'))
            ->assertSessionHas('success', LokerLensCopy::TINJAUAN_BERHASIL);
        $this->assertSame(StatusKasus::MenungguKeputusan, $pengaduan->fresh()->status_pengaduan);

        $this->putuskan($pengaduan, 'Tindaklanjuti')->assertRedirect();
        $this->assertSame(StatusKasus::TindakLanjutDisetujui, $pengaduan->fresh()->status_pengaduan);
        $this->actingAs($this->kepalaBidang)->get("/tindak-lanjut/{$pengaduan->id_pengaduan}/hasil")
            ->assertOk()->assertSee('Keputusan Berhasil Disimpan')->assertSee('Tindak Lanjut Disetujui');

        $terbit = $this->terbitkan($pengaduan)->assertRedirect();
        $this->assertSame(StatusKasus::Selesai, $pengaduan->fresh()->status_pengaduan);
        $this->assertSame('Terbit', SuratTeguranLaporanPolisi::sole()->status_penerbitan);
        $this->actingAs($this->kepalaBidang)->get($terbit->headers->get('Location'))
            ->assertOk()->assertSee('Kasus terdokumentasi otomatis')->assertSee('Kasus selesai');

        $this->assertSame([
            StatusKasus::PerluTinjauan->value,
            StatusKasus::SedangDitinjau->value,
            StatusKasus::MenungguKeputusan->value,
            StatusKasus::TindakLanjutDisetujui->value,
            StatusKasus::Selesai->value,
        ], $this->riwayat($pengaduan));
    }

    public function test_tidak_terbukti_menutup_kasus_dan_tidak_masuk_kepala_bidang(): void
    {
        $pengaduan = $this->periksaRisikoTinggi();

        $this->tinjau($pengaduan, HasilTinjauan::TidakTerbukti)->assertRedirect(route('tinjau-lowongan.index'));

        $this->assertSame(StatusKasus::TidakTerbukti, $pengaduan->fresh()->status_pengaduan);
        $this->assertSame('Tidak Terbukti', $pengaduan->hasilKlasifikasi->tinjauanAnalis()->value('hasil_tinjauan'));
        $this->assertSame([
            StatusKasus::PerluTinjauan->value,
            StatusKasus::SedangDitinjau->value,
            StatusKasus::TidakTerbukti->value,
        ], $this->riwayat($pengaduan));

        $this->actingAs($this->kepalaBidang)->get('/tindak-lanjut')->assertDontSee($pengaduan->nomor_tiket);
        $this->actingAs($this->kepalaBidang)->get("/tindak-lanjut/{$pengaduan->id_pengaduan}")->assertNotFound();
        $this->putuskan($pengaduan, 'Tindaklanjuti')->assertSessionHas('error');
        $this->assertSame(StatusKasus::TidakTerbukti, $pengaduan->fresh()->status_pengaduan);
    }

    public function test_hasil_tinjauan_lama_ditolak_validasi(): void
    {
        $pengaduan = $this->periksaRisikoTinggi();

        $this->actingAs($this->analis)->post("/tinjau-lowongan/detail/{$pengaduan->hasilKlasifikasi->id_klasifikasi}/simpan", [
            'catatan_tinjauan' => 'Catatan.',
            'hasil_tinjauan'   => 'Mencurigakan',
        ])->assertSessionHasErrors('hasil_tinjauan');

        $this->assertSame(StatusKasus::PerluTinjauan, $pengaduan->fresh()->status_pengaduan);
    }

    public function test_analis_tidak_bisa_meninjau_ulang_kasus_yang_sudah_lewat_tahap_tinjauan(): void
    {
        $pengaduan = $this->periksaRisikoTinggi();
        $this->tinjau($pengaduan, HasilTinjauan::TerkonfirmasiMencurigakan);
        $this->putuskan($pengaduan, 'Tidak Ditindaklanjuti');

        $this->tinjau($pengaduan, HasilTinjauan::TidakTerbukti)->assertSessionHas('error');

        $this->assertSame(StatusKasus::TidakDitindaklanjuti, $pengaduan->fresh()->status_pengaduan);
        $this->assertSame('Terkonfirmasi Mencurigakan', $pengaduan->hasilKlasifikasi->tinjauanAnalis()->value('hasil_tinjauan'));
    }

    public function test_kepala_bidang_tidak_bisa_memutuskan_kasus_yang_belum_ditinjau(): void
    {
        $pengaduan = $this->periksaRisikoTinggi();

        $this->putuskan($pengaduan, 'Tindaklanjuti')->assertSessionHas('error');

        $this->assertSame(StatusKasus::PerluTinjauan, $pengaduan->fresh()->status_pengaduan);
        $this->assertNull($pengaduan->fresh()->tindakLanjutKasus);
    }

    public function test_keputusan_tidak_bisa_diubah_setelah_ditetapkan(): void
    {
        $pengaduan = $this->periksaRisikoTinggi();
        $this->tinjau($pengaduan, HasilTinjauan::TerkonfirmasiMencurigakan);
        $this->putuskan($pengaduan, 'Tidak Ditindaklanjuti');

        $this->putuskan($pengaduan, 'Tindaklanjuti')->assertSessionHas('error');

        $this->assertSame(StatusKasus::TidakDitindaklanjuti, $pengaduan->fresh()->status_pengaduan);
        $this->assertSame('Tidak Ditindaklanjuti', $pengaduan->fresh()->tindakLanjutKasus->keputusan);
    }

    public function test_surat_hanya_bisa_terbit_satu_kali(): void
    {
        $pengaduan = $this->periksaRisikoTinggi();
        $this->tinjau($pengaduan, HasilTinjauan::TerkonfirmasiMencurigakan);
        $this->putuskan($pengaduan, 'Tindaklanjuti');
        $this->terbitkan($pengaduan);

        $surat = SuratTeguranLaporanPolisi::sole();
        $tanggalTerbit = $surat->tanggal_terbit;
        $jumlahRiwayat = count($this->riwayat($pengaduan));

        $this->terbitkan($pengaduan)->assertSessionHas('error', LokerLensCopy::LAPORAN_SUDAH_TERBIT);
        $this->actingAs($this->kepalaBidang)->post("/buat-surat/{$pengaduan->id_pengaduan}/draft", [
            'kronologi'         => 'Mencoba mengubah surat yang sudah terbit.',
            'tujuan_kepolisian' => 'Satreskrim Polres Wilayah Setempat',
        ])->assertSessionHas('error', LokerLensCopy::LAPORAN_SUDAH_TERBIT);

        $surat->refresh();
        $this->assertSame(1, SuratTeguranLaporanPolisi::count());
        $this->assertSame('Terbit', $surat->status_penerbitan);
        $this->assertSame('Kronologi kejadian.', $surat->kronologi_kejadian);
        $this->assertEquals($tanggalTerbit, $surat->tanggal_terbit);
        $this->assertCount($jumlahRiwayat, $this->riwayat($pengaduan));

        // Form tidak bisa dibuka lagi, langsung diarahkan ke halaman hasil terbit
        $this->actingAs($this->kepalaBidang)->get("/buat-surat/{$pengaduan->id_pengaduan}")
            ->assertRedirect(route('buat-surat.terbit', ['pengaduanId' => $pengaduan->id_pengaduan, 'suratId' => $surat->id_surat]));
    }

    public function test_halaman_terbit_menolak_surat_milik_kasus_lain(): void
    {
        $kasusA = $this->periksaRisikoTinggi();
        $kasusB = $this->periksaRisikoTinggi();
        foreach ([$kasusA, $kasusB] as $kasus) {
            $this->tinjau($kasus, HasilTinjauan::TerkonfirmasiMencurigakan);
            $this->putuskan($kasus, 'Tindaklanjuti');
            $this->terbitkan($kasus);
        }
        $suratB = $kasusB->fresh()->tindakLanjutKasus->suratTeguranLaporanPolisi;

        $this->actingAs($this->kepalaBidang)
            ->get("/buat-surat/{$kasusA->id_pengaduan}/terbit/{$suratB->id_surat}")
            ->assertNotFound();
    }
}
