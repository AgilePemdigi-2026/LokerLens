<?php

namespace Tests\Feature;

use App\Enums\StatusKasus;
use App\Models\HasilKlasifikasi;
use App\Models\Pengaduan;
use App\Models\User;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Memastikan halaman utama tiap peran tampil tanpa error dengan data seeder.
 */
class HalamanSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LokerLensSeeder::class);
    }

    private function akun(string $peran): User
    {
        return User::where('role', $peran)->firstOrFail();
    }

    public function test_halaman_masyarakat_tampil(): void
    {
        $masyarakat = $this->akun('Masyarakat');
        $pengaduan = Pengaduan::where('id_pengguna', $masyarakat->id)->firstOrFail();

        $this->actingAs($masyarakat)->get('/beranda')->assertOk();
        $this->actingAs($masyarakat)->get('/periksa-lowongan')->assertOk();
        $this->actingAs($masyarakat)->get("/periksa-lowongan/hasil/{$pengaduan->id_pengaduan}")
            ->assertOk()->assertSee('Hasil Pemeriksaan');
    }

    public function test_halaman_analis_tampil(): void
    {
        $analis = $this->akun('Analis');
        $klasifikasi = HasilKlasifikasi::whereHas('pengaduan', fn ($q) => $q->where('nomor_tiket', '#LKR-8821'))->firstOrFail();

        $this->actingAs($analis)->get('/tinjau-lowongan')
            ->assertOk()->assertSee('Daftar Lowongan Prioritas Tinggi')->assertSee('#LKR-8821')
            ->assertDontSee('#LKR-2025-0842'); // sudah Menunggu Keputusan, bukan antrean Analis

        $this->actingAs($analis)->get("/tinjau-lowongan/detail/{$klasifikasi->id_klasifikasi}")
            ->assertOk()->assertSee('Detail Lowongan Terpilih')->assertSee('Terkonfirmasi Mencurigakan');

        // Kasus yang sudah lewat tahap tinjauan tampil tanpa formulir
        $sudahDitinjau = HasilKlasifikasi::whereHas('pengaduan', fn ($q) => $q->where('nomor_tiket', '#LKR-2025-0842'))->firstOrFail();
        $this->actingAs($analis)->get("/tinjau-lowongan/detail/{$sudahDitinjau->id_klasifikasi}")
            ->assertOk()->assertSee('Tinjauan untuk kasus ini sudah selesai')->assertDontSee('name="catatan_tinjauan"', false);
    }

    public function test_halaman_kepala_bidang_tampil(): void
    {
        $kepalaBidang = $this->akun('Kepala Bidang');
        $pengaduan = Pengaduan::where('status_pengaduan', StatusKasus::MenungguKeputusan)->firstOrFail();

        $this->actingAs($kepalaBidang)->get('/tindak-lanjut')
            ->assertOk()->assertSee('Daftar Laporan')->assertSee($pengaduan->nomor_tiket);
        $this->actingAs($kepalaBidang)->get("/tindak-lanjut/{$pengaduan->id_pengaduan}")
            ->assertOk()->assertSee('Detail Laporan Lowongan Mencurigakan')->assertSee('Konfirmasi Keputusan');
    }
}
