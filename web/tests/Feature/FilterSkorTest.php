<?php

namespace Tests\Feature;

use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filter skor di Daftar Lowongan Prioritas Tinggi (FR-ML-03).
 * Batas "Skor Tinggi" = ambang prioritas di config/lokerlens.php (default 70).
 */
class FilterSkorTest extends TestCase
{
    use RefreshDatabase;

    /** Buat satu pengaduan lewat form Periksa dengan skor tertentu, kembalikan nomor tiketnya. */
    private function buatPengaduan(float $probabilitas): string
    {
        $this->palsukanApiMl($probabilitas);

        $this->actingAs(User::factory()->create(['role' => 'Masyarakat']))->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Contoh Abadi',
            'posisi_lowongan'    => 'Staff Administrasi',
            'deskripsi_lowongan' => 'Dibutuhkan staff administrasi, wajib transfer biaya pendaftaran sebelum wawancara.',
        ]);

        return Pengaduan::latest('id_pengaduan')->value('nomor_tiket');
    }

    private function filter(string $filter)
    {
        return $this->actingAs(User::factory()->create(['role' => 'Analis']))
            ->get('/tinjau-lowongan?filter_skor=' . urlencode($filter));
    }

    public function test_skor_tinggi_memakai_ambang_70_bukan_75(): void
    {
        $tiket72 = $this->buatPengaduan(0.72);
        $tiket95 = $this->buatPengaduan(0.95);

        $this->filter('Skor Tinggi')
            ->assertOk()
            ->assertSee('Skor Tinggi (≥ 70%)')
            ->assertSee('Skor Sedang (50% - 69%)')
            ->assertSee($tiket72)
            ->assertSee($tiket95);

        $this->filter('Skor Sedang')->assertDontSee($tiket72)->assertDontSee($tiket95);
    }

    public function test_batas_filter_mengikuti_config_ambang_prioritas(): void
    {
        // Diklasifikasi saat ambang 70 (masuk antrean), lalu ambang dinaikkan ke 80
        $tiket72 = $this->buatPengaduan(0.72);
        config(['lokerlens.ambang_prioritas' => 80]);

        $this->filter('Skor Tinggi')->assertDontSee($tiket72);
        $this->filter('Skor Sedang')->assertSee($tiket72)->assertSee('Skor Sedang (50% - 79%)');
    }
}
