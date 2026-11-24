<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Enums\HasilTinjauan;
use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Bug: pengaduan baru dengan skor di bawah 70% tidak muncul di Analis.
 * Aturan yang benar: SEMUA pengaduan baru masuk antrean Analis. Skor >= 70% hanya
 * ditandai prioritas dan tampil paling atas.
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

        return $this->kirimForm('Staff Gudang');
    }

    /** Kirim form Periksa Lowongan (API ML harus sudah dipalsukan). */
    private function kirimForm(string $posisi): Pengaduan
    {
        $this->actingAs($this->masyarakat)->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Uji Alur',
            'posisi_lowongan'    => $posisi,
            'deskripsi_lowongan' => 'Dibutuhkan staff gudang, kirim CV lewat WhatsApp dan siapkan biaya pelatihan sebelum mulai bekerja.',
        ])->assertRedirect();

        return Pengaduan::latest('id_pengaduan')->firstOrFail();
    }

    public static function skorPengaduan(): array
    {
        return [
            'skor di bawah 70%' => [0.55, false],
            'skor di atas 70%'  => [0.85, true],
        ];
    }

    #[DataProvider('skorPengaduan')]
    public function test_alur_lengkap_masyarakat_analis_kepala_bidang_masyarakat(float $probabilitas, bool $prioritas): void
    {
        // 1. Masyarakat membuat pengaduan: selalu "Perlu Tinjauan", prioritas sesuai skor
        $pengaduan = $this->buatPengaduan($probabilitas);
        $klasifikasi = $pengaduan->hasilKlasifikasi;

        $this->assertSame(StatusKasus::PerluTinjauan, $pengaduan->status_pengaduan);
        $this->assertSame($prioritas, $klasifikasi->status_prioritas);

        // 2. Analis melihatnya di Dashboard dan Tinjau Lowongan
        $dashboard = $this->actingAs($this->analis)->get('/dashboard-analis')->assertOk()
            ->assertSee($pengaduan->nomor_tiket);
        $this->assertSame(1, $dashboard->viewData('ringkasan')[0]['jumlah']);
        $this->assertSame(1, $dashboard->viewData('totalAntrean'));

        $this->actingAs($this->analis)->get('/tinjau-lowongan')->assertOk()
            ->assertSee($pengaduan->nomor_tiket);

        // 3. Analis membuka detail dan menyimpan tinjauan
        $this->actingAs($this->analis)->get("/tinjau-lowongan/detail/{$klasifikasi->id_klasifikasi}")
            ->assertOk()
            ->assertSee($prioritas ? 'Prioritas tinggi' : 'Bukan prioritas');
        $this->assertSame(StatusKasus::SedangDitinjau, $pengaduan->fresh()->status_pengaduan);

        $this->actingAs($this->analis)->post("/tinjau-lowongan/detail/{$klasifikasi->id_klasifikasi}/simpan", [
            'catatan_tinjauan' => 'Ada permintaan biaya pelatihan.',
            'hasil_tinjauan'   => HasilTinjauan::TerkonfirmasiMencurigakan->value,
        ])->assertRedirect(route('tinjau-lowongan.index'))
          ->assertSessionHas('success', LokerLensCopy::TINJAUAN_BERHASIL);

        $this->assertSame(StatusKasus::MenungguKeputusan, $pengaduan->fresh()->status_pengaduan);

        // Sudah tidak di antrean Analis
        $this->actingAs($this->analis)->get('/tinjau-lowongan')->assertDontSee($pengaduan->nomor_tiket);

        // 4. Kepala Bidang melihatnya di Tindak Lanjut
        $this->actingAs($this->kepalaBidang)->get('/tindak-lanjut')->assertOk()
            ->assertSee($pengaduan->nomor_tiket);

        // 5. Masyarakat melihat statusnya berubah
        $this->actingAs($this->masyarakat)->get("/status-pengaduan/{$pengaduan->id_pengaduan}")->assertOk()
            ->assertSee(StatusKasus::MenungguKeputusan->value);
    }

    public function test_prioritas_tinggi_tampil_di_atas_skor_rendah(): void
    {
        // Http::fake hanya memakai jawaban pertama yang cocok, jadi tiga skor dipasang berurutan
        $jawaban = fn (float $p) => ['probabilitas_penipuan' => $p, 'label' => $p >= 0.5 ? 'palsu' : 'asli', 'kata_pemicu' => [], 'versi_model' => 'Model Uji'];
        Http::fake(['*/predict' => Http::sequence()
            ->push($jawaban(0.30))
            ->push($jawaban(0.55))
            ->push($jawaban(0.85))]);

        $rendah = $this->kirimForm('Kasir');
        $sedang = $this->kirimForm('Admin Toko');
        $tinggi = $this->kirimForm('Kurir');

        $this->actingAs($this->analis)->get('/tinjau-lowongan')->assertOk()
            ->assertSeeInOrder([$tinggi->nomor_tiket, $sedang->nomor_tiket, $rendah->nomor_tiket]);

        $antrean = $this->actingAs($this->analis)->get('/dashboard-analis')->viewData('antrean');
        $this->assertSame(
            [$tinggi->nomor_tiket, $sedang->nomor_tiket, $rendah->nomor_tiket],
            $antrean->map(fn ($hasil) => $hasil->pengaduan->nomor_tiket)->all()
        );
    }

    public function test_pengaduan_menunggu_klasifikasi_tetap_terlihat_oleh_analis(): void
    {
        // API ML error: pengaduan tersimpan tanpa skor
        Http::fake(['*/predict' => Http::response('error', 500)]);

        $pengaduan = $this->kirimForm('Staff Gudang');
        $this->assertNull($pengaduan->hasilKlasifikasi);
        $this->assertSame(StatusKasus::PerluTinjauan, $pengaduan->status_pengaduan);

        $this->actingAs($this->analis)->get('/tinjau-lowongan')->assertOk()
            ->assertSee(LokerLensCopy::JUDUL_MENUNGGU_KLASIFIKASI_ANALIS)
            ->assertSee($pengaduan->nomor_tiket);

        $dashboard = $this->actingAs($this->analis)->get('/dashboard-analis')->assertOk();
        $this->assertSame(1, $dashboard->viewData('ringkasan')[0]['jumlah']);
        $this->assertSame(1, $dashboard->viewData('jumlahMenungguKlasifikasi'));
    }
}
