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

class TinjauLowonganTest extends TestCase
{
    use RefreshDatabase;

    private User $analis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analis = User::factory()->create(['role' => 'Analis']);
    }

    private function buatKasus(float $skor, string $nomorTiket, ?string $tanggal = null): HasilKlasifikasi
    {
        $perusahaan = Perusahaan::create(['nama_perusahaan' => 'PT ' . $nomorTiket]);
        $lowongan = LowonganKerja::create([
            'id_perusahaan' => $perusahaan->id_perusahaan,
            'posisi_lowongan' => 'Staff',
            'deskripsi_lowongan' => 'Deskripsi lowongan pekerjaan minimal 50 karakter untuk keperluan pengujian sistem.',
        ]);
        $pengaduan = Pengaduan::create([
            'id_pengguna' => User::factory()->create(['role' => 'Masyarakat'])->id,
            'id_lowongan' => $lowongan->id_lowongan,
            'nomor_tiket' => $nomorTiket,
            'status_pengaduan' => 'Perlu Tinjauan',
            'tanggal_pengaduan' => now(),
        ]);
        return HasilKlasifikasi::create([
            'id_pengaduan' => $pengaduan->id_pengaduan,
            'confidence_score' => $skor,
            'status_prioritas' => true,
            'kategori_hasil' => 'Terindikasi Penipuan',
            'label_klasifikasi' => 'Palsu',
            'tanggal_klasifikasi' => $tanggal ? \Carbon\Carbon::parse($tanggal) : now(),
        ]);
    }

    public function test_urutan_skor_terendah_dan_terbaru_berfungsi(): void
    {
        $kasus1 = $this->buatKasus(75.0, '#TIKET-75', '2026-10-01 10:00:00');
        $kasus2 = $this->buatKasus(90.0, '#TIKET-90', '2026-10-05 10:00:00');
        $kasus3 = $this->buatKasus(80.0, '#TIKET-80', '2026-10-03 10:00:00');

        // 1. Urutan Skor Terendah
        $responseTerendah = $this->actingAs($this->analis)->get('/tinjau-lowongan?urutan=Skor+Terendah');
        $responseTerendah->assertOk();
        $responseTerendah->assertSeeInOrder(['#TIKET-75', '#TIKET-80', '#TIKET-90']);

        // 2. Urutan Skor Tertinggi
        $responseTertinggi = $this->actingAs($this->analis)->get('/tinjau-lowongan?urutan=Skor+Tertinggi');
        $responseTertinggi->assertOk();
        $responseTertinggi->assertSeeInOrder(['#TIKET-90', '#TIKET-80', '#TIKET-75']);

        // 3. Urutan Terbaru
        $responseTerbaru = $this->actingAs($this->analis)->get('/tinjau-lowongan?urutan=Terbaru');
        $responseTerbaru->assertOk();
        $responseTerbaru->assertSeeInOrder(['#TIKET-90', '#TIKET-80', '#TIKET-75']);
    }

    /**
     * Issue #25: tombol aksi tidak sama dengan teks status, dan skor tampil sebagai tingkat risiko.
     */
    public function test_tombol_aksi_dan_badge_skor_di_tabel_pemantauan(): void
    {
        $this->buatKasus(83.0, '#TIKET-83');
        $sedang = $this->buatKasus(65.0, '#TIKET-65');
        $sedang->pengaduan->update(['status_pengaduan' => StatusKasus::SedangDitinjau]);

        $this->actingAs($this->analis)->get('/tinjau-lowongan')
            ->assertOk()
            ->assertSee(LokerLensCopy::KOLOM_NOMOR_TIKET)
            ->assertSee(LokerLensCopy::BTN_MULAI_TINJAU)
            ->assertSee(LokerLensCopy::BTN_LANJUTKAN_TINJAUAN)
            // Title "Lihat Detail ..." dipakai langkah BDD 'I follow "Lihat Detail"'
            ->assertSee('title="' . LokerLensCopy::BTN_LIHAT_DETAIL . ' #TIKET-83"', false)
            ->assertSee('83%')
            ->assertSee('Risiko tinggi')
            ->assertSee('Risiko sedang')
            ->assertDontSee('Indikasi Penipuan')
            ->assertDontSee('Skor Kritis')
            ->assertDontSee('bg-amber-500');
    }

    /**
     * Issue #25: form filter memakai filterOtomatis, tombol "Cari" tetap ada,
     * dan pilihan filter tetap terpilih setelah halaman dimuat ulang.
     */
    public function test_form_filter_otomatis_dan_pilihan_tetap_terpilih(): void
    {
        $this->buatKasus(83.0, '#TIKET-83');

        $this->actingAs($this->analis)->get('/tinjau-lowongan?filter_skor=Skor+Tinggi&urutan=Terbaru&nomor_tiket=TIKET')
            ->assertOk()
            ->assertSee('x-data="filterOtomatis"', false)
            ->assertSee('type="submit"', false)
            ->assertSeeText(LokerLensCopy::BTN_CARI)
            ->assertSee('value="Skor Tinggi" selected', false)
            ->assertSee('value="Terbaru" selected', false)
            ->assertSee('value="TIKET"', false);
    }

    // Keputusan tim: ID yang tidak ada tetap menampilkan 404 (tidak diarahkan ke daftar).
    public function test_detail_id_salah_menampilkan_404(): void
    {
        $this->actingAs($this->analis)->get('/tinjau-lowongan/detail/99999')
            ->assertNotFound();
    }

    public function test_simpan_tinjauan_id_salah_menampilkan_404(): void
    {
        $this->actingAs($this->analis)->post('/tinjau-lowongan/detail/99999/simpan', [
            'catatan_tinjauan' => 'Catatan audit validasi pengujian',
            'hasil_tinjauan' => 'Terkonfirmasi Mencurigakan',
        ])->assertNotFound();
    }
}
