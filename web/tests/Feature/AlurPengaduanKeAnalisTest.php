<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Enums\HasilTinjauan;
use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur pengaduan antar aktor sesuai SRS BR-05:
 * hanya skor >= ambang prioritas (70%) yang masuk ke Analis. Skor di bawahnya
 * otomatis "Selesai - Risiko Rendah" dan Masyarakat mendapat penjelasannya.
 */
class AlurPengaduanKeAnalisTest extends TestCase
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

    /** Masyarakat mengirim form Periksa Lowongan, API ML dipalsukan dengan probabilitas 0–1. */
    private function buatPengaduan(float $probabilitas): Pengaduan
    {
        $this->palsukanApiMl($probabilitas);

        $this->actingAs($this->masyarakat)->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Uji Alur',
            'posisi_lowongan'    => 'Staff Gudang',
            'deskripsi_lowongan' => 'Dibutuhkan staff gudang, kirim CV lewat WhatsApp dan siapkan biaya pelatihan sebelum mulai bekerja.',
        ])->assertRedirect();

        return Pengaduan::latest('id_pengaduan')->firstOrFail();
    }

    public function test_skor_85_masuk_analis_lalu_kepala_bidang_dan_status_terlihat_masyarakat(): void
    {
        // 1. Masyarakat membuat pengaduan: prioritas tinggi, "Perlu Tinjauan"
        $pengaduan = $this->buatPengaduan(0.85);
        $klasifikasi = $pengaduan->hasilKlasifikasi;

        $this->assertSame(StatusKasus::PerluTinjauan, $pengaduan->status_pengaduan);
        $this->assertTrue($klasifikasi->status_prioritas);

        // 2. Analis melihatnya di Dashboard dan Tinjau Lowongan
        $dashboard = $this->actingAs($this->analis)->get('/dashboard-analis')->assertOk()
            ->assertSee($pengaduan->nomor_tiket);
        $this->assertSame(1, $dashboard->viewData('totalAntrean'));

        $this->actingAs($this->analis)->get('/tinjau-lowongan')->assertOk()
            ->assertSee($pengaduan->nomor_tiket);

        // 3. Analis membuka detail dan menyimpan tinjauan
        $this->actingAs($this->analis)->get("/tinjau-lowongan/detail/{$klasifikasi->id_klasifikasi}")
            ->assertOk()->assertSee('Prioritas tinggi');
        $this->assertSame(StatusKasus::SedangDitinjau, $pengaduan->fresh()->status_pengaduan);

        $this->actingAs($this->analis)->post("/tinjau-lowongan/detail/{$klasifikasi->id_klasifikasi}/simpan", [
            'catatan_tinjauan' => 'Ada permintaan biaya pelatihan.',
            'hasil_tinjauan'   => HasilTinjauan::TerkonfirmasiMencurigakan->value,
        ])->assertRedirect(route('tinjau-lowongan.index'))
          ->assertSessionHas('success', LokerLensCopy::TINJAUAN_BERHASIL);

        $this->assertSame(StatusKasus::MenungguKeputusan, $pengaduan->fresh()->status_pengaduan);
        $this->actingAs($this->analis)->get('/tinjau-lowongan')->assertDontSee($pengaduan->nomor_tiket);

        // 4. Kepala Bidang melihatnya di Tindak Lanjut
        $this->actingAs($this->kepalaBidang)->get('/tindak-lanjut')->assertOk()
            ->assertSee($pengaduan->nomor_tiket);

        // 5. Masyarakat melihat statusnya berubah
        $this->actingAs($this->masyarakat)->get("/status-pengaduan/{$pengaduan->id_pengaduan}")->assertOk()
            ->assertSee(StatusKasus::MenungguKeputusan->value);
    }

    public function test_skor_55_tidak_masuk_analis_dan_masyarakat_melihat_selesai_risiko_rendah(): void
    {
        $pengaduan = $this->buatPengaduan(0.55);

        $this->assertSame(StatusKasus::SelesaiRisikoRendah, $pengaduan->status_pengaduan);
        $this->assertFalse($pengaduan->hasilKlasifikasi->status_prioritas);

        // Tidak muncul di Analis maupun Kepala Bidang
        $this->actingAs($this->analis)->get('/tinjau-lowongan')->assertOk()
            ->assertDontSee($pengaduan->nomor_tiket);
        $this->actingAs($this->analis)->get('/dashboard-analis')->assertOk()
            ->assertDontSee($pengaduan->nomor_tiket);
        $this->actingAs($this->analis)->get("/tinjau-lowongan/detail/{$pengaduan->hasilKlasifikasi->id_klasifikasi}")
            ->assertNotFound();
        $this->actingAs($this->kepalaBidang)->get('/tindak-lanjut')->assertOk()
            ->assertDontSee($pengaduan->nomor_tiket);

        // Masyarakat melihat status akhir beserta alasannya
        $this->actingAs($this->masyarakat)->get("/status-pengaduan/{$pengaduan->id_pengaduan}")->assertOk()
            ->assertSee(StatusKasus::SelesaiRisikoRendah->value)
            ->assertSee(str_replace(':ambang', '70', LokerLensCopy::ALASAN_RISIKO_RENDAH));

        $this->actingAs($this->masyarakat)->get('/riwayat-pengaduan')->assertOk()
            ->assertSee($pengaduan->nomor_tiket);
    }

    public function test_alasan_risiko_rendah_tidak_tampil_untuk_status_lain(): void
    {
        $pengaduan = $this->buatPengaduan(0.85);

        $this->actingAs($this->masyarakat)->get("/status-pengaduan/{$pengaduan->id_pengaduan}")->assertOk()
            ->assertDontSee(str_replace(':ambang', '70', LokerLensCopy::ALASAN_RISIKO_RENDAH));
    }
}
