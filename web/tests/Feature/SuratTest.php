<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Models\Pengaduan;
use App\Models\SuratTeguranLaporanPolisi;
use App\Models\User;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman Surat Kepala Bidang (issue #8, US-04).
 * Data seeder: draf untuk #LKR-8770 (PT Gemilang Karya Persada),
 * surat terbit untuk #LKR-8798 (PT Nusantara Cipta Mandiri).
 */
class SuratTest extends TestCase
{
    use RefreshDatabase;

    private User $kepalaBidang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LokerLensSeeder::class);
        $this->kepalaBidang = User::where('role', 'Kepala Bidang')->firstOrFail();
    }

    private function surat(string $nomorTiket): SuratTeguranLaporanPolisi
    {
        return Pengaduan::where('nomor_tiket', $nomorTiket)->firstOrFail()
            ->tindakLanjutKasus->suratTeguranLaporanPolisi;
    }

    public function test_hanya_kepala_bidang_yang_bisa_membuka_halaman_surat(): void
    {
        $this->actingAs($this->kepalaBidang)->get('/surat')->assertOk();

        foreach (['Analis', 'Masyarakat'] as $peran) {
            $this->actingAs(User::where('role', $peran)->firstOrFail())->get('/surat')->assertForbidden();
        }

        auth()->logout();
        $this->get('/surat')->assertRedirect('/login');
    }

    public function test_daftar_menampilkan_draf_dan_surat_terbit_beserta_tombol_aksi(): void
    {
        $draf = $this->surat('#LKR-8770');
        $terbit = $this->surat('#LKR-8798');

        $response = $this->actingAs($this->kepalaBidang)->get('/surat')->assertOk()
            ->assertSee(LokerLensCopy::SURAT_JUDUL)
            ->assertSee('#LKR-8770')
            ->assertSee('#LKR-8798')
            ->assertSee('PT Gemilang Karya Persada')
            ->assertSee($terbit->nomor_surat)
            ->assertSee('Laporan Polisi')
            ->assertSee(LokerLensCopy::BTN_LANJUTKAN_DRAF)
            ->assertSee(LokerLensCopy::BTN_LIHAT_SURAT)
            // Draf → Buat Surat; Terbit → halaman surat terbit
            ->assertSee(route('buat-surat.index', $draf->tindakLanjutKasus->id_pengaduan), false)
            ->assertSee(route('buat-surat.terbit', ['pengaduanId' => $terbit->tindakLanjutKasus->id_pengaduan, 'suratId' => $terbit->id_surat]), false)
            // Filter otomatis, tombol Cari tetap ada
            ->assertSee('x-data="filterOtomatis"', false)
            ->assertSeeText(LokerLensCopy::BTN_CARI);

        $this->assertSame(['total' => 2, 'draf' => 1, 'terbit' => 1], $response->viewData('statistik'));
    }

    public function test_filter_status_jenis_dan_pencarian(): void
    {
        $terbit = $this->surat('#LKR-8798');

        $this->actingAs($this->kepalaBidang)->get('/surat?status=Draft')->assertOk()
            ->assertSee('#LKR-8770')
            ->assertDontSee('#LKR-8798')
            ->assertSee('value="Draft" selected', false);

        $this->actingAs($this->kepalaBidang)->get('/surat?status=Terbit')->assertOk()
            ->assertSee('#LKR-8798')
            ->assertDontSee('#LKR-8770');

        // Pencarian nomor surat dan nama perusahaan
        $this->actingAs($this->kepalaBidang)->get('/surat?cari=' . urlencode($terbit->nomor_surat))->assertOk()
            ->assertSee('#LKR-8798')
            ->assertDontSee('#LKR-8770');
        $this->actingAs($this->kepalaBidang)->get('/surat?cari=Gemilang')->assertOk()
            ->assertSee('#LKR-8770')
            ->assertDontSee('#LKR-8798');

        // Belum ada Surat Teguran: tampilan "tidak ditemukan", bukan "belum ada surat"
        $this->actingAs($this->kepalaBidang)->get('/surat?jenis_dokumen=Surat+Teguran')->assertOk()
            ->assertSee(LokerLensCopy::SURAT_TIDAK_DITEMUKAN)
            ->assertDontSee(LokerLensCopy::BELUM_ADA_SURAT);

        // Nilai filter yang tidak dikenal diabaikan
        $this->actingAs($this->kepalaBidang)->get('/surat?status=Hapus&jenis_dokumen=Lain')->assertOk()
            ->assertSee('#LKR-8770')
            ->assertSee('#LKR-8798');
    }

    public function test_urutan_terbaru_dan_terlama_memakai_tanggal_terbit_atau_tanggal_simpan_draf(): void
    {
        // Draf disimpan lebih baru daripada tanggal terbit surat lain
        $this->surat('#LKR-8798')->update(['tanggal_terbit' => now()->subDays(3)]);
        $draf = $this->surat('#LKR-8770');
        $draf->timestamps = false;
        $draf->update(['updated_at' => now()->subDay()]);

        $this->actingAs($this->kepalaBidang)->get('/surat')->assertOk()
            ->assertSeeInOrder(['#LKR-8770', '#LKR-8798']);

        $this->actingAs($this->kepalaBidang)->get('/surat?urutan=Terlama')->assertOk()
            ->assertSeeInOrder(['#LKR-8798', '#LKR-8770']);
    }

    public function test_tampilan_kosong_jika_belum_ada_surat(): void
    {
        SuratTeguranLaporanPolisi::query()->delete();

        $response = $this->actingAs($this->kepalaBidang)->get('/surat')->assertOk()
            ->assertSee(LokerLensCopy::BELUM_ADA_SURAT);

        $this->assertSame(['total' => 0, 'draf' => 0, 'terbit' => 0], $response->viewData('statistik'));
    }

    public function test_pengingat_draf_di_dashboard_kepala_bidang(): void
    {
        $this->actingAs($this->kepalaBidang)->get('/dashboard-kepala-bidang')->assertOk()
            ->assertSee('id="pengingat-draf-surat"', false)
            ->assertSee(LokerLensCopy::PENGINGAT_DRAF_SURAT)
            ->assertSee(route('surat.index', ['status' => 'Draft']), false);

        // Tanpa draf, pengingat tidak tampil
        SuratTeguranLaporanPolisi::where('status_penerbitan', 'Draft')->delete();

        $this->actingAs($this->kepalaBidang)->get('/dashboard-kepala-bidang')->assertOk()
            ->assertDontSee('id="pengingat-draf-surat"', false);
    }

    public function test_tanda_ada_draf_di_daftar_tindak_lanjut(): void
    {
        $response = $this->actingAs($this->kepalaBidang)->get('/tindak-lanjut')->assertOk()
            ->assertSee(LokerLensCopy::TANDA_ADA_DRAF);

        // Hanya satu kasus (#LKR-8770) yang punya draf
        $this->assertSame(1, substr_count($response->getContent(), 'data-testid="tanda-ada-draf"'));
    }

    public function test_navbar_kepala_bidang_menandai_menu_surat_di_halaman_buat_surat(): void
    {
        $pengaduan = Pengaduan::where('nomor_tiket', '#LKR-8770')->firstOrFail();

        $this->actingAs($this->kepalaBidang)->get(route('buat-surat.index', $pengaduan->id_pengaduan))->assertOk()
            ->assertSee('<a href="' . route('surat.index') . '" class="px-3 py-2 rounded-md text-sm font-medium transition flex items-center gap-2 text-teal-400 bg-navy-800 font-semibold">', false);
    }
}
