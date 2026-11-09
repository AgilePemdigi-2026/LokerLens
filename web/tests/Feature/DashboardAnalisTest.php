<?php

namespace Tests\Feature;

use App\Enums\HasilTinjauan;
use App\Enums\StatusKasus;
use App\Models\HasilKlasifikasi;
use App\Models\LowonganKerja;
use App\Models\Pengaduan;
use App\Models\Perusahaan;
use App\Models\TinjauanAnalis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard Analis (Sprint 2).
 * NFR-SEC-01: wajib login. NFR-SEC-02: hanya Analis.
 */
class DashboardAnalisTest extends TestCase
{
    use RefreshDatabase;

    private int $nomor = 1;

    protected function setUp(): void
    {
        parent::setUp();

        // Tes ini menguji hak akses dan data, bukan aset CSS/JS.
        $this->withoutVite();
    }

    private function analis(): User
    {
        return User::factory()->create(['role' => 'Analis']);
    }

    /** Buat satu kasus. $skor null = belum punya hasil klasifikasi. */
    private function buatKasus(StatusKasus $status, ?float $skor, int $hariLalu = 0, bool $prioritas = true): Pengaduan
    {
        $pelapor = User::factory()->create(['role' => 'Masyarakat']);
        $perusahaan = Perusahaan::create(['nama_perusahaan' => 'PT Uji ' . $this->nomor]);
        $lowongan = LowonganKerja::create([
            'id_perusahaan'      => $perusahaan->id_perusahaan,
            'posisi_lowongan'    => 'Posisi Uji ' . $this->nomor,
            'deskripsi_lowongan' => 'Lowongan untuk pengujian dashboard analis.',
        ]);

        $pengaduan = Pengaduan::create([
            'id_lowongan'       => $lowongan->id_lowongan,
            'id_pengguna'       => $pelapor->id,
            'nomor_tiket'       => '#UJI-' . $this->nomor++,
            'deskripsi_singkat' => 'Pengaduan untuk pengujian.',
            'status_pengaduan'  => $status->value,
            'tanggal_pengaduan' => now()->subDays($hariLalu),
        ]);

        if ($skor !== null) {
            HasilKlasifikasi::create([
                'id_pengaduan'     => $pengaduan->id_pengaduan,
                'confidence_score' => $skor,
                'kategori_hasil'   => 'Lowongan berpotensi penipuan',
                'status_prioritas' => $prioritas,
            ]);
        }

        return $pengaduan;
    }

    // ─── Hak akses ──────────────────────────────────────────────────────

    public function test_analis_bisa_membuka_dashboard(): void
    {
        $this->actingAs($this->analis())->get('/dashboard-analis')->assertOk();
    }

    public function test_peran_lain_ditolak(): void
    {
        foreach (['Masyarakat', 'Kepala Bidang'] as $peran) {
            $this->actingAs(User::factory()->create(['role' => $peran]))
                ->get('/dashboard-analis')
                ->assertForbidden();
        }
    }

    public function test_dashboard_wajib_login(): void
    {
        $this->get('/dashboard-analis')->assertRedirect('/login');
    }

    // ─── Kartu ringkasan ────────────────────────────────────────────────

    public function test_kartu_menghitung_status_dan_mengabaikan_kasus_tanpa_klasifikasi(): void
    {
        $this->buatKasus(StatusKasus::PerluTinjauan, 80);
        $this->buatKasus(StatusKasus::PerluTinjauan, 85);
        $this->buatKasus(StatusKasus::SedangDitinjau, 90);
        $this->buatKasus(StatusKasus::MenungguKeputusan, 95);
        // Belum terklasifikasi: tidak bisa ditinjau, jadi tidak dihitung di kartu.
        $this->buatKasus(StatusKasus::PerluTinjauan, null);

        $response = $this->actingAs($this->analis())->get('/dashboard-analis')->assertOk();

        $kartu = collect($response->viewData('ringkasan'))->pluck('jumlah', 'label');

        $this->assertSame(2, $kartu['Perlu Tinjauan']);
        $this->assertSame(1, $kartu['Sedang Ditinjau']);
        $this->assertSame(1, $kartu['Menunggu Keputusan']);
        $this->assertSame(0, $kartu['Selesai Ditinjau']);
    }

    public function test_selesai_ditinjau_hanya_menghitung_tinjauan_dalam_periode(): void
    {
        $analis = $this->analis();
        $baru = $this->buatKasus(StatusKasus::MenungguKeputusan, 90);
        $lama = $this->buatKasus(StatusKasus::MenungguKeputusan, 91, 20);

        foreach ([[$baru, 0], [$lama, 20]] as [$kasus, $hariLalu]) {
            TinjauanAnalis::create([
                'id_klasifikasi'   => $kasus->hasilKlasifikasi->id_klasifikasi,
                'id_pengguna'      => $analis->id,
                'catatan_tinjauan' => 'Catatan uji.',
                'hasil_tinjauan'   => HasilTinjauan::TerkonfirmasiMencurigakan->value,
                'tanggal_tinjauan' => now()->subDays($hariLalu),
            ]);
        }

        $kartu7 = collect($this->actingAs($analis)->get('/dashboard-analis')->viewData('ringkasan'))->pluck('jumlah', 'label');
        $kartu30 = collect($this->actingAs($analis)->get('/dashboard-analis?periode=30')->viewData('ringkasan'))->pluck('jumlah', 'label');

        $this->assertSame(1, $kartu7['Selesai Ditinjau']);
        $this->assertSame(2, $kartu30['Selesai Ditinjau']);
    }

    // ─── Distribusi skor ────────────────────────────────────────────────

    public function test_distribusi_skor_memakai_batas_50_dan_ambang_prioritas(): void
    {
        $ambang = (float) config('lokerlens.ambang_prioritas');

        $this->buatKasus(StatusKasus::SelesaiRisikoRendah, 30, 0, false);
        $this->buatKasus(StatusKasus::SelesaiRisikoRendah, 50, 0, false);
        $this->buatKasus(StatusKasus::SelesaiRisikoRendah, $ambang - 1, 0, false);
        $this->buatKasus(StatusKasus::PerluTinjauan, $ambang);
        $this->buatKasus(StatusKasus::PerluTinjauan, 95);

        $response = $this->actingAs($this->analis())->get('/dashboard-analis')->assertOk();

        $this->assertSame(['rendah' => 1, 'sedang' => 2, 'tinggi' => 2], $response->viewData('distribusi'));
        $this->assertSame(5, $response->viewData('totalSkor'));
    }

    // ─── Antrean prioritas ──────────────────────────────────────────────

    public function test_antrean_maksimal_empat_urut_skor_tertinggi_dan_hanya_tahap_analis(): void
    {
        foreach ([71, 72, 73, 74, 75, 76] as $skor) {
            $this->buatKasus(StatusKasus::PerluTinjauan, $skor);
        }
        // Sudah lewat tahap Analis: tidak boleh masuk antrean.
        $this->buatKasus(StatusKasus::MenungguKeputusan, 99);

        $response = $this->actingAs($this->analis())->get('/dashboard-analis')->assertOk();

        $antrean = $response->viewData('antrean');

        $this->assertCount(4, $antrean);
        $this->assertSame(6, $response->viewData('totalAntrean'));
        $this->assertEquals(76, (float) $antrean->first()->confidence_score);
    }

    public function test_antrean_kosong_menampilkan_pesan(): void
    {
        $this->actingAs($this->analis())
            ->get('/dashboard-analis')
            ->assertOk()
            ->assertSee('Belum ada antrean pada periode ini');
    }

    // ─── Filter periode ─────────────────────────────────────────────────

    public function test_filter_periode_7_hari_30_hari_dan_kustom(): void
    {
        $analis = $this->analis();
        $this->buatKasus(StatusKasus::PerluTinjauan, 80, 10);

        $this->assertSame(0, $this->actingAs($analis)->get('/dashboard-analis')->viewData('totalSkor'));
        $this->assertSame(1, $this->actingAs($analis)->get('/dashboard-analis?periode=30')->viewData('totalSkor'));

        $dari = now()->subDays(12)->format('Y-m-d');
        $sampai = now()->subDays(8)->format('Y-m-d');
        $kustom = $this->actingAs($analis)->get("/dashboard-analis?periode=kustom&dari={$dari}&sampai={$sampai}");

        $this->assertSame('kustom', $kustom->viewData('periode'));
        $this->assertSame(1, $kustom->viewData('totalSkor'));
    }

    public function test_periode_kustom_tidak_valid_kembali_ke_7_hari(): void
    {
        $analis = $this->analis();

        $this->assertSame('7', $this->actingAs($analis)->get('/dashboard-analis?periode=kustom&dari=bukan&sampai=tanggal')->viewData('periode'));
        $this->assertSame('7', $this->actingAs($analis)->get('/dashboard-analis?periode=kustom&dari=2026-11-09&sampai=2026-11-01')->viewData('periode'));
        $this->assertSame('7', $this->actingAs($analis)->get('/dashboard-analis?periode=999')->viewData('periode'));
    }

    // ─── Tren ───────────────────────────────────────────────────────────

    public function test_tren_menghitung_semua_pengaduan_termasuk_yang_belum_terklasifikasi(): void
    {
        $this->buatKasus(StatusKasus::PerluTinjauan, 80);
        $this->buatKasus(StatusKasus::PerluTinjauan, null);

        $response = $this->actingAs($this->analis())->get('/dashboard-analis')->assertOk();

        $this->assertSame(2, (int) $response->viewData('tren')->sum('jumlah'));
        $this->assertSame(1, $response->viewData('totalSkor'));
    }
}