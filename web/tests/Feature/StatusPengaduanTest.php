<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Enums\HasilTinjauan;
use App\Enums\StatusKasus;
use App\Models\IndikatorMencurigakan;
use App\Models\Pengaduan;
use App\Models\User;
use App\Support\ProgresPengaduan;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Status Pengaduan (issue #11): hak akses, tampilan kosong, filter, pemetaan status ke 5 tahap,
 * konsistensi data seeder, link dari Beranda/Hasil, dan integrasi dengan alur Analis & Kepala Bidang.
 */
class StatusPengaduanTest extends TestCase
{
    use RefreshDatabase;

    private const SELESAI     = ProgresPengaduan::SELESAI;
    private const BERLANGSUNG = ProgresPengaduan::BERLANGSUNG;
    private const BELUM       = ProgresPengaduan::BELUM;
    private const DIHENTIKAN  = ProgresPengaduan::DIHENTIKAN;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LokerLensSeeder::class);
    }

    private function akunDemo(): User
    {
        return User::where('email', LokerLensSeeder::AKUN_DEMO['Masyarakat'])->firstOrFail();
    }

    private function akunBaru(): User
    {
        return User::where('email', 'masyarakat.baru@lokerlens.test')->firstOrFail();
    }

    private function akunPeran(string $peran): User
    {
        return User::where('email', LokerLensSeeder::AKUN_DEMO[$peran])->firstOrFail();
    }

    private function tiket(string $nomorTiket): Pengaduan
    {
        return Pengaduan::with(['hasilKlasifikasi', 'riwayatKasus'])->where('nomor_tiket', $nomorTiket)->firstOrFail();
    }

    /** Nomor tiket di daftar kiri (urut sesuai tampilan). */
    private function tiketDiDaftar($response): array
    {
        return $response->viewData('daftar')->pluck('nomor_tiket')->all();
    }

    // ─── Hak akses ───────────────────────────────────────────────────────

    public function test_wajib_login(): void
    {
        $id = $this->tiket('#LKR-8821')->id_pengaduan;

        $this->get('/status-pengaduan')->assertRedirect('/login');
        $this->get("/status-pengaduan/{$id}")->assertRedirect('/login');
    }

    public function test_hanya_masyarakat_yang_bisa_akses(): void
    {
        $id = $this->tiket('#LKR-8821')->id_pengaduan;

        foreach (['Analis', 'Kepala Bidang'] as $peran) {
            $this->actingAs($this->akunPeran($peran))->get('/status-pengaduan')->assertForbidden();
            $this->actingAs($this->akunPeran($peran))->get("/status-pengaduan/{$id}")->assertForbidden();
        }

        $this->actingAs($this->akunDemo())->get('/status-pengaduan')->assertOk();
        $this->actingAs($this->akunDemo())->get("/status-pengaduan/{$id}")->assertOk();
    }

    public function test_tidak_bisa_melihat_pengaduan_orang_lain(): void
    {
        $milikAkunDemo = $this->tiket('#LKR-8821')->id_pengaduan;

        $this->actingAs($this->akunBaru())->get("/status-pengaduan/{$milikAkunDemo}")->assertNotFound();
        $this->actingAs($this->akunDemo())->get('/status-pengaduan/999999')->assertNotFound();
    }

    // ─── Halaman daftar dan filter ───────────────────────────────────────

    public function test_tampilan_kosong_jika_tidak_ada_pengaduan_berjalan(): void
    {
        $this->actingAs($this->akunBaru())->get('/status-pengaduan')
            ->assertOk()
            ->assertSee(LokerLensCopy::STATUS_JUDUL_DAFTAR)
            ->assertSee(LokerLensCopy::BELUM_ADA_PENGADUAN_BERJALAN)
            ->assertSee(route('periksa-lowongan.index'), false)
            ->assertDontSee('id="filter-pengaduan"', false)
            // Riwayat Pengaduan (#12) belum ada, jadi tombolnya disembunyikan
            // (teks "Riwayat Pengaduan" tetap ada di navbar/footer sebagai menu nonaktif)
            ->assertDontSee('/riwayat-pengaduan', false);
    }

    public function test_daftar_hanya_berisi_pengaduan_belum_selesai_terbaru_di_atas(): void
    {
        $response = $this->actingAs($this->akunDemo())->get('/status-pengaduan')->assertOk();

        $this->assertSame(
            ['#LKR-8805', '#LKR-8765', '#LKR-8790', '#LKR-8819', '#LKR-8821', '#LKR-2025-0842', '#LKR-8770'],
            $this->tiketDiDaftar($response)
        );
        // Tidak ada panel detail di halaman daftar; setiap kartu menuju halaman detailnya
        $response->assertDontSee('id="timeline-progres"', false)
            ->assertSee(route('status-pengaduan.detail', $this->tiket('#LKR-8821')->id_pengaduan), false);
    }

    public function test_badge_kartu_mengikuti_kelompok_filter(): void
    {
        $response = $this->actingAs($this->akunDemo())->get('/status-pengaduan')->assertOk();

        // Badge hanya Menunggu / Aktif; nama status lengkap hanya di halaman detail
        $response->assertDontSee(StatusKasus::SedangDitinjau->value)
            ->assertDontSee(StatusKasus::MenungguKeputusan->value)
            // #LKR-8805 belum diklasifikasi: skor "Belum tersedia"
            ->assertSee(LokerLensCopy::SKOR_BELUM_TERSEDIA)
            ->assertDontSee(LokerLensCopy::MENUNGGU_KLASIFIKASI);

        foreach ($response->viewData('daftar') as $pengaduan) {
            $this->assertSame(ProgresPengaduan::kelompokFilter($pengaduan), $pengaduan->progres()->kelompok);
        }
        $this->assertSame('Aktif', $this->tiket('#LKR-8821')->progres()->labelKelompok);
        $this->assertSame('Menunggu', $this->tiket('#LKR-8819')->progres()->labelKelompok);
    }

    public function test_jumlah_di_tiap_pilihan_filter(): void
    {
        // Menunggu: Menunggu klasifikasi (#LKR-8805), Perlu Tinjauan (#LKR-8765, #LKR-8790, #LKR-8819)
        // Aktif: Sedang Ditinjau (#LKR-8821), Menunggu Keputusan (#LKR-2025-0842), Tindak Lanjut Disetujui (#LKR-8770)
        $this->actingAs($this->akunDemo())->get('/status-pengaduan')
            ->assertSee('Semua (7)')
            ->assertSee('Aktif (3)')
            ->assertSee('Menunggu (4)');
    }

    /** @return array<string, array{string, ?string}> */
    public static function kelompokPerStatus(): array
    {
        return [
            'Menunggu klasifikasi'    => ['#LKR-8805', ProgresPengaduan::FILTER_MENUNGGU],
            'Perlu Tinjauan'          => ['#LKR-8819', ProgresPengaduan::FILTER_MENUNGGU],
            'Sedang Ditinjau'         => ['#LKR-8821', ProgresPengaduan::FILTER_AKTIF],
            'Menunggu Keputusan'      => ['#LKR-2025-0842', ProgresPengaduan::FILTER_AKTIF],
            'Tindak Lanjut Disetujui' => ['#LKR-8770', ProgresPengaduan::FILTER_AKTIF],
            'Selesai'                 => ['#LKR-8798', null],
            'Tidak Terbukti'          => ['#LKR-8752', null],
        ];
    }

    #[DataProvider('kelompokPerStatus')]
    public function test_kelompok_filter_per_status(string $nomorTiket, ?string $kelompok): void
    {
        $this->assertSame($kelompok, ProgresPengaduan::kelompokFilter($this->tiket($nomorTiket)));
    }

    public function test_filter_aktif_dan_menunggu(): void
    {
        $aktif = $this->actingAs($this->akunDemo())->get('/status-pengaduan?filter=aktif')->assertOk();
        $this->assertSame(['#LKR-8821', '#LKR-2025-0842', '#LKR-8770'], $this->tiketDiDaftar($aktif));
        // Link kartu membawa filter supaya tombol kembali membuka daftar yang sama
        $aktif->assertSee(route('status-pengaduan.detail', ['id' => $this->tiket('#LKR-8770')->id_pengaduan, 'filter' => 'aktif']), false);

        $menunggu = $this->actingAs($this->akunDemo())->get('/status-pengaduan?filter=menunggu')->assertOk();
        $this->assertSame(['#LKR-8805', '#LKR-8765', '#LKR-8790', '#LKR-8819'], $this->tiketDiDaftar($menunggu));

        // Nilai filter tidak dikenal dianggap "Semua"
        $lain = $this->actingAs($this->akunDemo())->get('/status-pengaduan?filter=xyz')->assertOk();
        $this->assertSame('semua', $lain->viewData('filter'));
        $this->assertCount(7, $lain->viewData('daftar'));
    }

    public function test_filter_kosong_menampilkan_pesan(): void
    {
        // Akun baru memeriksa satu lowongan berisiko tinggi → status "Perlu Tinjauan" (kelompok Menunggu)
        $this->periksaSebagai($this->akunBaru());

        $this->actingAs($this->akunBaru())->get('/status-pengaduan?filter=aktif')
            ->assertOk()
            ->assertSee(LokerLensCopy::TIDAK_ADA_PENGADUAN_FILTER)
            ->assertSee('Menunggu (1)')
            ->assertDontSee('data-testid="kartu-pengaduan"', false);
    }

    // ─── Halaman detail ──────────────────────────────────────────────────

    public function test_detail_menampilkan_breadcrumb_status_lengkap_dan_tombol_kembali(): void
    {
        $pengaduan = $this->tiket('#LKR-8819');

        $this->actingAs($this->akunDemo())->get("/status-pengaduan/{$pengaduan->id_pengaduan}?filter=menunggu")
            ->assertOk()
            ->assertViewIs('status-pengaduan.detail')
            // Breadcrumb: Beranda / Status Pengaduan / #LKR-8819
            ->assertSeeInOrder(['Beranda', 'Status Pengaduan', '#LKR-8819'])
            // Tombol kembali membawa filter terakhir
            ->assertSee(route('status-pengaduan.index', ['filter' => 'menunggu']), false)
            ->assertSee(LokerLensCopy::BTN_KEMBALI_STATUS)
            // Badge kelompok + nama status lengkap + penjelasan
            ->assertSeeInOrder(['Menunggu', '·', StatusKasus::PerluTinjauan->value])
            ->assertSee(LokerLensCopy::PENJELASAN_STATUS[StatusKasus::PerluTinjauan->value])
            ->assertSee('id="timeline-progres"', false)
            ->assertSee(LokerLensCopy::CATATAN_TANPA_TINDAKAN)
            ->assertSee(route('periksa-lowongan.hasil', $pengaduan->id_pengaduan), false);

        // Tanpa filter → kembali ke daftar "Semua"
        $this->actingAs($this->akunDemo())->get("/status-pengaduan/{$pengaduan->id_pengaduan}")
            ->assertSee('href="' . route('status-pengaduan.index') . '" id="kembali-ke-daftar"', false);
    }

    /** @return array<string, array{string, string}> */
    public static function badgePengaduanSelesai(): array
    {
        return [
            'Selesai'                 => ['#LKR-8798', 'Selesai'],
            'Selesai - Risiko Rendah' => ['#LKR-8740', 'Selesai'],
            'Tidak Terbukti'          => ['#LKR-8752', 'Dihentikan'],
            'Tidak Ditindaklanjuti'   => ['#LKR-8781', 'Dihentikan'],
        ];
    }

    #[DataProvider('badgePengaduanSelesai')]
    public function test_pengaduan_selesai_bisa_dibuka_dengan_badge_sesuai(string $nomorTiket, string $badge): void
    {
        $pengaduan = $this->tiket($nomorTiket);

        $response = $this->actingAs($this->akunDemo())->get("/status-pengaduan/{$pengaduan->id_pengaduan}")->assertOk()
            ->assertSee(LokerLensCopy::CATATAN_PENGADUAN_DITUTUP)
            ->assertSee(LokerLensCopy::CATATAN_PENDUKUNG_KEPUTUSAN);

        $this->assertSame($badge, $response->viewData('progres')->labelKelompok);
        $response->assertSeeInOrder([$badge, '·', $pengaduan->status_pengaduan->value]);
    }

    public function test_tanggal_berbahasa_indonesia(): void
    {
        $this->assertSame('27 Okt 2026, 21:18 WIB', ProgresPengaduan::formatTanggal(Carbon::create(2026, 10, 27, 21, 18)));
        $this->assertSame('—', ProgresPengaduan::formatTanggal(null));
    }

    // ─── Pemetaan status → 5 tahap ───────────────────────────────────────

    /** @return array<string, array{string, int, array<int, string>, ?string}> */
    public static function pemetaanStatus(): array
    {
        return [
            'menunggu klasifikasi'    => ['#LKR-8805', 1, [self::BERLANGSUNG, self::BELUM, self::BELUM, self::BELUM, self::BELUM], null],
            'Selesai - Risiko Rendah' => ['#LKR-8740', 1, [self::SELESAI], LokerLensCopy::PESAN_BERHENTI_RISIKO_RENDAH],
            'Perlu Tinjauan'          => ['#LKR-8819', 2, [self::SELESAI, self::BERLANGSUNG, self::BELUM, self::BELUM, self::BELUM], null],
            'Sedang Ditinjau'         => ['#LKR-8821', 2, [self::SELESAI, self::BERLANGSUNG, self::BELUM, self::BELUM, self::BELUM], null],
            'Tidak Terbukti'          => ['#LKR-8752', 2, [self::SELESAI, self::DIHENTIKAN], LokerLensCopy::PESAN_BERHENTI_TIDAK_TERBUKTI],
            'Menunggu Keputusan'      => ['#LKR-2025-0842', 3, [self::SELESAI, self::SELESAI, self::BERLANGSUNG, self::BELUM, self::BELUM], null],
            'Tidak Ditindaklanjuti'   => ['#LKR-8781', 3, [self::SELESAI, self::SELESAI, self::DIHENTIKAN], LokerLensCopy::PESAN_BERHENTI_TIDAK_DITINDAKLANJUTI],
            'Tindak Lanjut Disetujui' => ['#LKR-8770', 4, [self::SELESAI, self::SELESAI, self::SELESAI, self::BERLANGSUNG, self::BELUM], null],
            'Selesai'                 => ['#LKR-8798', 5, [self::SELESAI, self::SELESAI, self::SELESAI, self::SELESAI, self::SELESAI], null],
        ];
    }

    /**
     * @param  array<int, string>  $keadaanTahap
     */
    #[DataProvider('pemetaanStatus')]
    public function test_status_menghasilkan_tahap_yang_benar(string $nomorTiket, int $tahapAktif, array $keadaanTahap, ?string $pesanBerhenti): void
    {
        $pengaduan = $this->tiket($nomorTiket);
        $progres = $pengaduan->progres();

        $this->assertSame($tahapAktif, $progres->tahapAktif);
        $this->assertSame($keadaanTahap, array_column($progres->tahap, 'keadaan'));
        $this->assertSame($pesanBerhenti, $progres->pesanBerhenti);

        // Tahap yang sudah dimulai selalu punya tanggal (dari Riwayat Kasus atau tanggal pengaduan)
        foreach ($progres->tahap as $tahap) {
            $tahap['keadaan'] === self::BELUM
                ? $this->assertNull($tahap['tanggal'])
                : $this->assertNotNull($tahap['tanggal'], "Tahap {$tahap['nomor']} {$nomorTiket} tanpa tanggal");
        }

        // Panel detail menampilkan stepper yang sama, tanpa error
        $response = $this->actingAs($this->akunDemo())->get("/status-pengaduan/{$pengaduan->id_pengaduan}")->assertOk();
        $this->assertTahapDiHalaman($response, $keadaanTahap);
        $pesanBerhenti ? $response->assertSee($pesanBerhenti) : $response->assertDontSee('id="pesan-berhenti"', false);
    }

    public function test_pengaduan_menunggu_klasifikasi_ditandai_jelas(): void
    {
        $pengaduan = $this->tiket('#LKR-8805');
        $progres = $pengaduan->progres();

        $this->assertSame(LokerLensCopy::MENUNGGU_KLASIFIKASI, $progres->labelStatus);
        $this->assertSame(LokerLensCopy::MENUNGGU_KLASIFIKASI, $progres->tahap[0]['label']);
        $this->assertSame(ProgresPengaduan::FILTER_MENUNGGU, ProgresPengaduan::kelompokFilter($pengaduan));
        $this->assertFalse($progres->ditutup);
    }

    // ─── Konsistensi data seeder ─────────────────────────────────────────

    public function test_tanggal_seeder_berurutan_dan_kata_pemicu_lengkap(): void
    {
        foreach (Pengaduan::with(['riwayatKasus', 'hasilKlasifikasi.indikatorMencurigakan', 'lowonganKerja'])->get() as $pengaduan) {
            $tiket = $pengaduan->nomor_tiket;
            $waktuRiwayat = $pengaduan->riwayatKasus->sortBy('id_riwayat')->pluck('tanggal_update');

            $this->assertNotEmpty($waktuRiwayat, "{$tiket} tanpa Riwayat Kasus");
            // Riwayat tidak lebih awal dari tanggal pengaduan, dan berurutan maju
            $this->assertTrue($waktuRiwayat->first()->gte($pengaduan->tanggal_pengaduan), "{$tiket}: riwayat sebelum tanggal pengaduan");
            $this->assertEquals($waktuRiwayat->sort()->values(), $waktuRiwayat->values(), "{$tiket}: riwayat tidak berurutan");
            $this->assertTrue($pengaduan->progres()->pembaruanTerakhir->gte($pengaduan->tanggal_pengaduan), "{$tiket}: pembaruan terakhir sebelum tanggal pengaduan");

            // Deskripsi lowongan berupa kalimat, bukan sekadar nama perusahaan
            $this->assertGreaterThanOrEqual(80, strlen($pengaduan->lowonganKerja->deskripsi_lowongan), "{$tiket}: deskripsi terlalu pendek");

            if ($pengaduan->hasilKlasifikasi) {
                $this->assertNotEmpty($pengaduan->hasilKlasifikasi->indikatorMencurigakan, "{$tiket} tanpa kata pemicu");
            }
        }
    }

    // ─── Integrasi dengan fitur lain (lewat alur controller) ─────────────

    /** Masyarakat memeriksa lowongan lewat form; API ML dipalsukan. */
    private function periksaSebagai(User $masyarakat, float $probabilitas = 0.9, array $kataPemicu = ['biaya pendaftaran', 'transfer']): Pengaduan
    {
        $this->palsukanApiMl($probabilitas, $kataPemicu);

        $this->actingAs($masyarakat)->post('/periksa-lowongan', [
            'nama_perusahaan'    => 'PT Uji Integrasi Sejahtera',
            'posisi_lowongan'    => 'Staf Gudang Uji',
            'deskripsi_lowongan' => 'Dibutuhkan staf gudang segera. Pelamar wajib transfer biaya pendaftaran Rp200.000 sebelum wawancara.',
        ])->assertRedirect();

        return Pengaduan::where('id_pengguna', $masyarakat->id)->latest('id_pengaduan')->firstOrFail();
    }

    /** Buka panel detail sebagai pemilik pengaduan dan cocokkan keadaan tiap tahap. */
    private function assertTahapMasyarakat(Pengaduan $pengaduan, array $keadaanTahap, string $labelStatus)
    {
        $response = $this->actingAs($pengaduan->pengguna)->get("/status-pengaduan/{$pengaduan->id_pengaduan}")->assertOk();
        $this->assertTahapDiHalaman($response, $keadaanTahap);
        $this->assertSame($labelStatus, $response->viewData('progres')->labelStatus);

        return $response;
    }

    private function assertTahapDiHalaman($response, array $keadaanTahap): void
    {
        foreach ($keadaanTahap as $i => $keadaan) {
            $response->assertSee('data-tahap="' . ($i + 1) . '" data-keadaan="' . $keadaan . '"', false);
        }
        $response->assertDontSee('data-tahap="' . (count($keadaanTahap) + 1) . '"', false);
    }

    public function test_integrasi_periksa_lowongan_langsung_muncul_di_status_pengaduan(): void
    {
        $pengaduan = $this->periksaSebagai($this->akunBaru());

        $response = $this->actingAs($this->akunBaru())->get('/status-pengaduan')->assertOk()->assertSee($pengaduan->nomor_tiket);
        $this->assertSame([$pengaduan->nomor_tiket], $this->tiketDiDaftar($response));
        $this->assertTahapMasyarakat($pengaduan, [self::SELESAI, self::BERLANGSUNG, self::BELUM, self::BELUM, self::BELUM], StatusKasus::PerluTinjauan->value);

        // Halaman Hasil Pemeriksaan menautkan ke Status Pengaduan
        $this->actingAs($this->akunBaru())->get("/periksa-lowongan/hasil/{$pengaduan->id_pengaduan}")
            ->assertSee(LokerLensCopy::BTN_PANTAU_STATUS)
            ->assertSee(route('status-pengaduan.detail', $pengaduan->id_pengaduan), false);
    }

    public function test_integrasi_analis_meninjau_mengubah_tahap(): void
    {
        $pengaduan = $this->periksaSebagai($this->akunBaru());
        $analis = $this->akunPeran('Analis');
        $idKlasifikasi = $pengaduan->hasilKlasifikasi->id_klasifikasi;

        // Analis membuka detail → "Sedang Ditinjau" (masuk filter Aktif)
        $this->actingAs($analis)->get("/tinjau-lowongan/detail/{$idKlasifikasi}")->assertOk();
        $this->assertTahapMasyarakat($pengaduan, [self::SELESAI, self::BERLANGSUNG, self::BELUM, self::BELUM, self::BELUM], StatusKasus::SedangDitinjau->value);
        $aktif = $this->actingAs($this->akunBaru())->get('/status-pengaduan?filter=aktif');
        $this->assertSame([$pengaduan->nomor_tiket], $this->tiketDiDaftar($aktif));

        // Analis menyimpan "Terkonfirmasi Mencurigakan" → tahap 3 Menunggu Keputusan
        $this->actingAs($analis)->post("/tinjau-lowongan/detail/{$idKlasifikasi}/simpan", [
            'catatan_tinjauan' => 'Ditemukan permintaan biaya pendaftaran.',
            'hasil_tinjauan'   => HasilTinjauan::TerkonfirmasiMencurigakan->value,
        ])->assertRedirect();
        $this->assertTahapMasyarakat($pengaduan, [self::SELESAI, self::SELESAI, self::BERLANGSUNG, self::BELUM, self::BELUM], StatusKasus::MenungguKeputusan->value);

        // Sudah diproses petugas → tetap di "Aktif", tidak kembali ke "Menunggu"
        $aktif = $this->actingAs($this->akunBaru())->get('/status-pengaduan?filter=aktif');
        $this->assertSame([$pengaduan->nomor_tiket], $this->tiketDiDaftar($aktif));
        $this->assertSame('Aktif', $pengaduan->fresh()->progres()->labelKelompok);
    }

    public function test_integrasi_analis_tidak_terbukti_menghentikan_di_tahap_2(): void
    {
        $pengaduan = $this->periksaSebagai($this->akunBaru());

        $this->actingAs($this->akunPeran('Analis'))->post("/tinjau-lowongan/detail/{$pengaduan->hasilKlasifikasi->id_klasifikasi}/simpan", [
            'catatan_tinjauan' => 'Perusahaan terverifikasi resmi.',
            'hasil_tinjauan'   => HasilTinjauan::TidakTerbukti->value,
        ])->assertRedirect();

        $this->assertTahapMasyarakat($pengaduan, [self::SELESAI, self::DIHENTIKAN], StatusKasus::TidakTerbukti->value)
            ->assertSee(LokerLensCopy::PESAN_BERHENTI_TIDAK_TERBUKTI);
        // Sudah selesai → hilang dari daftar
        $this->actingAs($this->akunBaru())->get('/status-pengaduan')->assertSee(LokerLensCopy::BELUM_ADA_PENGADUAN_BERJALAN);
    }

    /** Pengaduan baru yang sudah dikonfirmasi Analis (status Menunggu Keputusan). */
    private function kasusMenungguKeputusan(): Pengaduan
    {
        $pengaduan = $this->periksaSebagai($this->akunBaru());
        $this->actingAs($this->akunPeran('Analis'))->post("/tinjau-lowongan/detail/{$pengaduan->hasilKlasifikasi->id_klasifikasi}/simpan", [
            'catatan_tinjauan' => 'Ditemukan permintaan biaya pendaftaran.',
            'hasil_tinjauan'   => HasilTinjauan::TerkonfirmasiMencurigakan->value,
        ])->assertRedirect();

        return $pengaduan;
    }

    public function test_integrasi_kepala_bidang_tindaklanjuti_lalu_terbitkan_surat(): void
    {
        $pengaduan = $this->kasusMenungguKeputusan();
        $kepalaBidang = $this->akunPeran('Kepala Bidang');

        $this->actingAs($kepalaBidang)->post("/tindak-lanjut/{$pengaduan->id_pengaduan}/keputusan", ['keputusan' => 'Tindaklanjuti'])->assertRedirect();
        $this->assertTahapMasyarakat($pengaduan, [self::SELESAI, self::SELESAI, self::SELESAI, self::BERLANGSUNG, self::BELUM], StatusKasus::TindakLanjutDisetujui->value);

        $this->actingAs($kepalaBidang)->post("/buat-surat/{$pengaduan->id_pengaduan}/terbitkan", [
            'kronologi'         => 'Pelapor diminta transfer biaya pendaftaran.',
            'tujuan_kepolisian' => 'Satreskrim Polres Wilayah Setempat',
        ])->assertRedirect();
        $this->assertTahapMasyarakat($pengaduan, [self::SELESAI, self::SELESAI, self::SELESAI, self::SELESAI, self::SELESAI], StatusKasus::Selesai->value)
            ->assertSee(LokerLensCopy::CATATAN_PENGADUAN_DITUTUP);
    }

    public function test_integrasi_kepala_bidang_tidak_ditindaklanjuti_menghentikan_di_tahap_3(): void
    {
        $pengaduan = $this->kasusMenungguKeputusan();

        $this->actingAs($this->akunPeran('Kepala Bidang'))->post("/tindak-lanjut/{$pengaduan->id_pengaduan}/keputusan", ['keputusan' => 'Tidak Ditindaklanjuti'])->assertRedirect();

        $this->assertTahapMasyarakat($pengaduan, [self::SELESAI, self::SELESAI, self::DIHENTIKAN], StatusKasus::TidakDitindaklanjuti->value)
            ->assertSee(LokerLensCopy::PESAN_BERHENTI_TIDAK_DITINDAKLANJUTI);
    }

    // ─── Kata pemicu dirapikan (tampilan saja, data tetap) ───────────────

    public function test_rapikan_frasa_kata_pemicu(): void
    {
        $this->assertSame('nominal Rp', IndikatorMencurigakan::rapikanFrasa('nominal Rp nominal Rp'));
        $this->assertSame('biaya pendaftaran', IndikatorMencurigakan::rapikanFrasa('biaya biaya pendaftaran'));
        $this->assertSame('gaji nominal Rp', IndikatorMencurigakan::rapikanFrasa('gaji nominal Rp nominal rp'));
        $this->assertSame('transfer biaya', IndikatorMencurigakan::rapikanFrasa('transfer biaya'));
    }

    public function test_kata_pemicu_berulang_tampil_sekali_di_semua_halaman(): void
    {
        // API ML mengirim frasa dengan kata berulang dan dua frasa yang sama setelah dirapikan
        $pengaduan = $this->periksaSebagai($this->akunBaru(), 0.9, ['nominal Rp nominal Rp', 'nominal Rp', 'biaya pendaftaran']);
        $klasifikasi = $pengaduan->hasilKlasifikasi;

        // Data di database tidak diubah
        $this->assertSame(['nominal Rp nominal Rp', 'nominal Rp', 'biaya pendaftaran'], $klasifikasi->indikatorMencurigakan->pluck('nama_indikator')->all());
        $this->assertSame(['nominal Rp', 'biaya pendaftaran'], $klasifikasi->kata_pemicu_unik);
        $this->assertCount(2, $klasifikasi->indikator_unik);

        // Hasil Pemeriksaan: "nominal Rp" sekali, "biaya" tetap ada di #kata-pemicu (BDD B.2-1)
        $hasil = $this->actingAs($this->akunBaru())->get("/periksa-lowongan/hasil/{$pengaduan->id_pengaduan}")->assertOk()
            ->assertDontSee('nominal Rp nominal Rp')
            ->assertSee('2 Indikator');
        $isiKataPemicu = str($hasil->getContent())->after('id="kata-pemicu"')->before('Rekomendasi Tindak Lanjut');
        $this->assertSame(1, substr_count($isiKataPemicu, '"nominal Rp"'));
        $this->assertStringContainsString('biaya', $isiKataPemicu);

        // Status Pengaduan: chip unik
        $status = $this->actingAs($this->akunBaru())->get("/status-pengaduan/{$pengaduan->id_pengaduan}")->assertOk()
            ->assertDontSee('nominal Rp nominal Rp');
        $this->assertSame(1, substr_count($status->getContent(), '>nominal Rp</span>'));

        // Analis dan Kepala Bidang memakai fungsi yang sama
        $this->actingAs($this->akunPeran('Analis'))->get("/tinjau-lowongan/detail/{$klasifikasi->id_klasifikasi}")->assertOk()
            ->assertDontSee('nominal Rp nominal Rp')->assertSee('nominal Rp');
        $this->actingAs($this->akunPeran('Kepala Bidang'))->get("/tindak-lanjut/{$pengaduan->id_pengaduan}")
            ->assertDontSee('nominal Rp nominal Rp');
    }

    // ─── Link dari Beranda ───────────────────────────────────────────────

    public function test_navbar_dan_beranda_mengarah_ke_status_pengaduan(): void
    {
        $pengaduan = $this->tiket('#LKR-8821');

        $this->actingAs($this->akunDemo())->get('/beranda')
            ->assertOk()
            ->assertSee(route('status-pengaduan.index'), false)
            ->assertSee(route('status-pengaduan.detail', $pengaduan->id_pengaduan), false)
            // Link kedua ke hasil analisis tetap ada
            ->assertSee(route('periksa-lowongan.hasil', $pengaduan->id_pengaduan), false);
    }

    public function test_teks_catatan_di_halaman_hasil_tidak_berubah(): void
    {
        $pengaduan = $this->tiket('#LKR-8821');

        $this->actingAs($this->akunDemo())->get("/periksa-lowongan/hasil/{$pengaduan->id_pengaduan}")
            ->assertOk()
            ->assertSee('Hasil analisis sistem LokerLens merupakan rekomendasi pendukung keputusan berbasis kecerdasan buatan, bukan penetapan penipuan secara hukum.');
    }
}
