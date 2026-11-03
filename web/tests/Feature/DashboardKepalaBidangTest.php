<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard Kepala Bidang (issue #14).
 * NFR-SEC-01: wajib login. NFR-SEC-02: hanya Kepala Bidang.
 */
class DashboardKepalaBidangTest extends TestCase
{
    use RefreshDatabase;
        protected function setUp(): void
    {
        parent::setUp();

        // Tes ini hanya menguji hak akses, bukan aset CSS/JS,
        // jadi manifest Vite tidak perlu ada.
        $this->withoutVite();
    }

    public function test_kepala_bidang_bisa_membuka_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'Kepala Bidang']);

        $this->actingAs($user)
            ->get('/dashboard-kepala-bidang')
            ->assertOk();
    }

    public function test_peran_lain_ditolak(): void
    {
        foreach (['Masyarakat', 'Analis'] as $peran) {
            $user = User::factory()->create(['role' => $peran]);

            $this->actingAs($user)
                ->get('/dashboard-kepala-bidang')
                ->assertForbidden();
        }
    }

    public function test_dashboard_wajib_login(): void
    {
        $this->get('/dashboard-kepala-bidang')->assertRedirect('/login');
    }

    public function test_dashboard_menampilkan_jumlah_pengaduan_sesuai_status(): void
{
    $user = User::factory()->create(['role' => 'Kepala Bidang']);

    $perusahaan = \App\Models\Perusahaan::create([
        'nama_perusahaan' => 'PT Test Dashboard',
    ]);

    $lowongan = \App\Models\LowonganKerja::create([
        'id_perusahaan' => $perusahaan->id_perusahaan,
        'posisi_lowongan' => 'Staff Administrasi',
        'deskripsi_lowongan' => 'Lowongan untuk kebutuhan pengujian dashboard.',
    ]);

    $statusUji = [
        'Menunggu Keputusan' => 2,
        'Tindak Lanjut Disetujui' => 1,
        'Tidak Ditindaklanjuti' => 3,
        'Selesai' => 1,

        // Status ini tidak boleh masuk ringkasan Kepala Bidang
        'Perlu Tinjauan' => 2,
        'Sedang Ditinjau' => 1,
        'Tidak Terbukti' => 1,
        'Selesai - Risiko Rendah' => 2,
    ];

    $nomor = 1;

    foreach ($statusUji as $status => $jumlah) {
        for ($i = 0; $i < $jumlah; $i++) {
            \App\Models\Pengaduan::create([
                'id_lowongan' => $lowongan->id_lowongan,
                'id_pengguna' => $user->id,
                'nomor_tiket' => '#TEST-' . $nomor++,
                'deskripsi_singkat' => 'Pengaduan untuk pengujian dashboard.',
                'status_pengaduan' => $status,
            ]);
        }
    }

    $response = $this->actingAs($user)
        ->get('/dashboard-kepala-bidang');

    $response->assertOk();

    $ringkasan = collect($response->viewData('ringkasan'))
        ->mapWithKeys(fn ($item) => [
            $item['status']->value => $item['jumlah'],
        ]);

    $this->assertSame(2, $ringkasan['Menunggu Keputusan']);
    $this->assertSame(1, $ringkasan['Tindak Lanjut Disetujui']);
    $this->assertSame(3, $ringkasan['Tidak Ditindaklanjuti']);
    $this->assertSame(1, $ringkasan['Selesai']);

    $this->assertArrayNotHasKey('Perlu Tinjauan', $ringkasan->all());
    $this->assertArrayNotHasKey('Sedang Ditinjau', $ringkasan->all());
    $this->assertArrayNotHasKey('Tidak Terbukti', $ringkasan->all());
    $this->assertArrayNotHasKey('Selesai - Risiko Rendah', $ringkasan->all());
}
}