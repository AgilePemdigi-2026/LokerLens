<?php

namespace Tests\Feature;

use App\Models\LowonganKerja;
use App\Models\Pengaduan;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NomorTiketTest extends TestCase
{
    use RefreshDatabase;

    public function test_nomor_tiket_pertama_saat_database_kosong(): void
    {
        $this->assertSame('#LKR-8821', Pengaduan::generateNomorTiket());
    }

    public function test_dua_pemeriksaan_berturut_turut_mendapat_nomor_tiket_berbeda(): void
    {
        $masyarakat = User::factory()->create(['role' => 'Masyarakat']);
        $this->palsukanApiMl(0.9);

        $data = [
            'nama_perusahaan'    => 'PT Contoh Abadi',
            'posisi_lowongan'    => 'Staff Administrasi',
            'deskripsi_lowongan' => 'Dibutuhkan staff administrasi untuk kantor pusat, wajib transfer biaya pendaftaran.',
        ];

        $this->actingAs($masyarakat)->post('/periksa-lowongan', $data)->assertRedirect();
        $this->actingAs($masyarakat)->post('/periksa-lowongan', $data)->assertRedirect();

        $tiket = Pengaduan::pluck('nomor_tiket');

        $this->assertCount(2, $tiket);
        $this->assertCount(2, $tiket->unique());
    }

    public function test_nomor_tiket_melewati_nomor_yang_sudah_dipakai(): void
    {
        $lowongan = LowonganKerja::create([
            'id_perusahaan'      => Perusahaan::create(['nama_perusahaan' => 'PT Contoh'])->id_perusahaan,
            'posisi_lowongan'    => 'Kurir',
            'deskripsi_lowongan' => 'Deskripsi lowongan contoh.',
        ]);

        // Nomor yang akan dihasilkan berikutnya (8821 + 1) sudah dipakai data lain.
        Pengaduan::create([
            'id_lowongan' => $lowongan->id_lowongan,
            'id_pengguna' => User::factory()->create()->id,
            'nomor_tiket' => '#LKR-8822',
        ]);

        $this->assertSame('#LKR-8823', Pengaduan::generateNomorTiket());
    }
}
