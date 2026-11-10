<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Enums\HasilTinjauan;
use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use App\Models\TinjauanAnalis;
use App\Models\User;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Riwayat Tinjauan Analis (issue #25): /riwayat-tinjauan dan detail hanya-baca.
 *
 * Memakai data seeder. Kasus yang sudah ditinjau Analis:
 *   #LKR-2025-0842  Terkonfirmasi, Menunggu Keputusan          (skor 84)
 *   #LKR-8770       Terkonfirmasi, Tindak Lanjut Disetujui     (skor 90, surat masih draf)
 *   #LKR-8798       Terkonfirmasi, Selesai                     (skor 95, surat terbit)
 *   #LKR-8781       Terkonfirmasi, Tidak Ditindaklanjuti       (skor 76)
 *   #LKR-8752       Tidak Terbukti                             (skor 73)
 * Belum ditinjau (tetap di Pemantauan): #LKR-8821 (Sedang Ditinjau), #LKR-8819, #LKR-8790, #LKR-8765 (Perlu Tinjauan).
 */
class RiwayatTinjauanTest extends TestCase
{
    use RefreshDatabase;

    private const SUDAH_DITINJAU = ['#LKR-2025-0842', '#LKR-8770', '#LKR-8798', '#LKR-8781', '#LKR-8752'];
    private const BELUM_DITINJAU = ['#LKR-8821', '#LKR-8819', '#LKR-8790', '#LKR-8765'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LokerLensSeeder::class);
    }

    private function akun(string $peran): User
    {
        return User::where('email', LokerLensSeeder::AKUN_DEMO[$peran])->firstOrFail();
    }

    private function analis(): User
    {
        return $this->akun('Analis');
    }

    /** id_klasifikasi untuk satu nomor tiket (parameter route detail). */
    private function idKlasifikasi(string $tiket): int
    {
        return Pengaduan::where('nomor_tiket', $tiket)->firstOrFail()->hasilKlasifikasi->id_klasifikasi;
    }

    private function detail(string $tiket)
    {
        return $this->actingAs($this->analis())->get(route('riwayat-tinjauan.detail', $this->idKlasifikasi($tiket)));
    }

    // ─── Halaman benar-benar bisa dirender ─────────────────────────────────

    /** Menangkap error seperti "View [riwayat-tinjauan.index] not found". */
    public function test_analis_bisa_membuka_daftar_dan_semua_detail_riwayat_tinjauan(): void
    {
        $this->actingAs($this->analis())->get('/riwayat-tinjauan')
            ->assertStatus(200)
            ->assertViewIs('riwayat-tinjauan.index')
            ->assertSee(LokerLensCopy::RIWAYAT_TINJAUAN_JUDUL);

        foreach (self::SUDAH_DITINJAU as $tiket) {
            $this->actingAs($this->analis())->get('/riwayat-tinjauan/' . $this->idKlasifikasi($tiket))
                ->assertStatus(200)
                ->assertViewIs('tinjau-lowongan.detail')
                ->assertViewHas('modeBaca', true)
                ->assertSee(LokerLensCopy::JUDUL_HASIL_AKHIR_KASUS);
        }
    }

    // ─── Hak akses & navbar ───────────────────────────────────────────────

    public function test_hanya_analis_yang_bisa_membuka_riwayat_tinjauan(): void
    {
        $id = $this->idKlasifikasi('#LKR-8798');

        foreach (['Masyarakat', 'Kepala Bidang'] as $peran) {
            $this->actingAs($this->akun($peran))->get('/riwayat-tinjauan')->assertForbidden();
            $this->actingAs($this->akun($peran))->get("/riwayat-tinjauan/{$id}")->assertForbidden();
        }

        auth()->logout();
        $this->get('/riwayat-tinjauan')->assertRedirect('/login');

        $this->actingAs($this->analis())->get('/riwayat-tinjauan')->assertOk();
        $this->actingAs($this->analis())->get("/riwayat-tinjauan/{$id}")->assertOk();
    }

    public function test_navbar_analis_dashboard_pemantauan_riwayat_tanpa_laporan_dan_arsip(): void
    {
        $response = $this->actingAs($this->analis())->get('/riwayat-tinjauan')->assertOk();

        $response->assertSeeInOrder(['Dashboard', 'Pemantauan', LokerLensCopy::NAV_RIWAYAT])
            ->assertSee(route('riwayat-tinjauan.index'), false)
            ->assertDontSee('Arsip Kasus')
            ->assertDontSee('Sprint 2');

        // Navbar Kepala Bidang tidak berubah: Arsip Kasus tetap ada
        $this->actingAs($this->akun('Kepala Bidang'))->get('/tindak-lanjut')
            ->assertSee(route('arsip-kasus.index'), false);
    }

    // ─── Daftar ───────────────────────────────────────────────────────────

    public function test_hanya_kasus_yang_sudah_ditinjau_yang_tampil(): void
    {
        $response = $this->actingAs($this->analis())->get('/riwayat-tinjauan')->assertOk();

        foreach (self::SUDAH_DITINJAU as $tiket) {
            $response->assertSee($tiket);
        }
        foreach (self::BELUM_DITINJAU as $tiket) {
            $response->assertDontSee($tiket);
        }

        // Kolom yang diminta
        $response->assertSee('PT Nusantara Cipta Mandiri')
            ->assertSee('Customer Service')
            ->assertSee('95%')
            ->assertSee('Budi Santoso')
            ->assertSee(LokerLensCopy::LABEL_SKOR_RISIKO)
            ->assertSee(LokerLensCopy::BTN_LIHAT_DETAIL)
            ->assertSee(route('riwayat-tinjauan.detail', $this->idKlasifikasi('#LKR-8798')), false);
    }

    public function test_kotak_angka_total_terkonfirmasi_dan_tidak_terbukti(): void
    {
        $this->actingAs($this->analis())->get('/riwayat-tinjauan')->assertOk()
            ->assertViewHas('statistik', ['total' => 5, 'terkonfirmasi' => 4, 'tidakTerbukti' => 1])
            ->assertSee(LokerLensCopy::RIWAYAT_TINJAUAN_STAT_TOTAL);
    }

    public function test_hasil_akhir_tiap_kasus(): void
    {
        $response = $this->actingAs($this->analis())->get('/riwayat-tinjauan?urutan=' . urlencode(LokerLensCopy::URUTAN_SKOR_TERTINGGI));

        // Urutan skor: 8798 (95) Selesai, 8770 (90) Disetujui, 0842 (84), 8781 (76), 8752 (73)
        $response->assertSeeInOrder([
            '#LKR-8798', LokerLensCopy::HASIL_AKHIR_DITINDAKLANJUTI,
            '#LKR-8770', LokerLensCopy::HASIL_AKHIR_DITINDAKLANJUTI,
            '#LKR-2025-0842', LokerLensCopy::HASIL_AKHIR_MENUNGGU,
            '#LKR-8781', LokerLensCopy::HASIL_AKHIR_TIDAK_DITINDAKLANJUTI,
            '#LKR-8752', LokerLensCopy::HASIL_AKHIR_TIDAK_TERBUKTI,
        ]);
    }

    public function test_pencarian_nomor_tiket_perusahaan_dan_posisi(): void
    {
        $analis = $this->analis();

        $this->actingAs($analis)->get('/riwayat-tinjauan?cari=8781')
            ->assertSee('#LKR-8781')->assertDontSee('#LKR-8798');

        $this->actingAs($analis)->get('/riwayat-tinjauan?cari=' . urlencode('Nusantara Cipta'))
            ->assertSee('#LKR-8798')->assertDontSee('#LKR-8781');

        $this->actingAs($analis)->get('/riwayat-tinjauan?cari=' . urlencode('Sales Lapangan'))
            ->assertSee('#LKR-8752')->assertDontSee('#LKR-8798');

        // Kasus yang belum ditinjau tidak muncul walaupun dicari
        $this->actingAs($analis)->get('/riwayat-tinjauan?cari=8821')
            ->assertDontSee('#LKR-8821')
            ->assertSee(LokerLensCopy::DATA_TIDAK_DITEMUKAN);
    }

    public function test_filter_hasil_tinjauan(): void
    {
        $tidakTerbukti = $this->actingAs($this->analis())
            ->get('/riwayat-tinjauan?filter_hasil=' . urlencode(HasilTinjauan::TidakTerbukti->value));
        $tidakTerbukti->assertSee('#LKR-8752');
        foreach (['#LKR-2025-0842', '#LKR-8770', '#LKR-8798', '#LKR-8781'] as $tiket) {
            $tidakTerbukti->assertDontSee($tiket);
        }

        $terkonfirmasi = $this->actingAs($this->analis())
            ->get('/riwayat-tinjauan?filter_hasil=' . urlencode(HasilTinjauan::TerkonfirmasiMencurigakan->value));
        $terkonfirmasi->assertDontSee('#LKR-8752');
        foreach (['#LKR-2025-0842', '#LKR-8770', '#LKR-8798', '#LKR-8781'] as $tiket) {
            $terkonfirmasi->assertSee($tiket);
        }
    }

    public function test_filter_hasil_akhir(): void
    {
        $harapan = [
            LokerLensCopy::HASIL_AKHIR_MENUNGGU              => ['#LKR-2025-0842'],
            LokerLensCopy::HASIL_AKHIR_DITINDAKLANJUTI       => ['#LKR-8770', '#LKR-8798'],
            LokerLensCopy::HASIL_AKHIR_TIDAK_DITINDAKLANJUTI => ['#LKR-8781'],
        ];

        foreach ($harapan as $hasilAkhir => $tampil) {
            $response = $this->actingAs($this->analis())->get('/riwayat-tinjauan?filter_hasil_akhir=' . urlencode($hasilAkhir))->assertOk();

            foreach (self::SUDAH_DITINJAU as $tiket) {
                in_array($tiket, $tampil, true) ? $response->assertSee($tiket) : $response->assertDontSee($tiket);
            }
        }

        // Dropdown Hasil Akhir hanya berisi Semua + 3 pilihan
        $html = $this->actingAs($this->analis())->get('/riwayat-tinjauan')->getContent();
        preg_match('#<select id="filter_hasil_akhir".*?</select>#s', $html, $cocok);
        preg_match_all('#<option value="([^"]*)"#', $cocok[0], $opsi);
        $this->assertSame([
            '',
            LokerLensCopy::HASIL_AKHIR_MENUNGGU,
            LokerLensCopy::HASIL_AKHIR_DITINDAKLANJUTI,
            LokerLensCopy::HASIL_AKHIR_TIDAK_DITINDAKLANJUTI,
        ], $opsi[1]);
    }

    public function test_urutan_terbaru_skor_tertinggi_dan_skor_terendah(): void
    {
        $analis = $this->analis();
        $skorTurun = ['#LKR-8798', '#LKR-8770', '#LKR-2025-0842', '#LKR-8781', '#LKR-8752'];

        $this->actingAs($analis)->get('/riwayat-tinjauan?urutan=' . urlencode(LokerLensCopy::URUTAN_SKOR_TERTINGGI))
            ->assertSeeInOrder($skorTurun);
        $this->actingAs($analis)->get('/riwayat-tinjauan?urutan=' . urlencode(LokerLensCopy::URUTAN_SKOR_TERENDAH))
            ->assertSeeInOrder(array_reverse($skorTurun));

        // Terbaru = tanggal tinjauan terbaru di atas (juga urutan bawaan)
        $terbaru = TinjauanAnalis::with('hasilKlasifikasi.pengaduan')->orderByDesc('tanggal_tinjauan')->get()
            ->map(fn (TinjauanAnalis $t) => $t->hasilKlasifikasi->pengaduan->nomor_tiket)->all();

        $this->actingAs($analis)->get('/riwayat-tinjauan?urutan=' . urlencode(LokerLensCopy::URUTAN_TERBARU))
            ->assertSeeInOrder($terbaru);
        $this->actingAs($analis)->get('/riwayat-tinjauan')->assertSeeInOrder($terbaru);
    }

    public function test_tampilan_kosong_belum_ada_tinjauan(): void
    {
        TinjauanAnalis::query()->delete();

        $this->actingAs($this->analis())->get('/riwayat-tinjauan')->assertOk()
            ->assertSee(LokerLensCopy::BELUM_ADA_TINJAUAN)
            ->assertViewHas('statistik', ['total' => 0, 'terkonfirmasi' => 0, 'tidakTerbukti' => 0]);
    }

    public function test_data_pribadi_pelapor_tidak_tampil(): void
    {
        $email = LokerLensSeeder::AKUN_DEMO['Masyarakat'];

        $this->actingAs($this->analis())->get('/riwayat-tinjauan')->assertDontSee($email);
        foreach (self::SUDAH_DITINJAU as $tiket) {
            $this->detail($tiket)->assertOk()->assertDontSee($email);
        }
    }

    // ─── Detail hanya-baca ────────────────────────────────────────────────

    public function test_detail_hanya_baca_tanpa_form_tinjauan(): void
    {
        $response = $this->detail('#LKR-2025-0842')->assertOk();

        // Data lowongan, skor, kata pemicu, dan hasil tinjauan
        $response->assertSee('Detail Lowongan Terpilih')
            ->assertSee('84%')
            ->assertSee('Biaya akomodasi')
            ->assertSee(LokerLensCopy::LABEL_MODE_HANYA_BACA)
            ->assertSee(LokerLensCopy::JUDUL_HASIL_TINJAUAN)
            ->assertSee(HasilTinjauan::TerkonfirmasiMencurigakan->value)
            ->assertSee('ditemukan pola permintaan biaya yang tidak wajar')
            ->assertSee('Budi Santoso')
            ->assertSee(LokerLensCopy::LABEL_STATUS_KASUS_SEKARANG)
            ->assertSee(StatusKasus::MenungguKeputusan->value);

        // Form tinjauan tidak dirender
        $response->assertDontSee('name="catatan_tinjauan"', false)
            ->assertDontSee('name="hasil_tinjauan"', false)
            ->assertDontSee(route('tinjau-lowongan.simpan', $this->idKlasifikasi('#LKR-2025-0842')), false)
            ->assertDontSee('Lanjutkan');
    }

    public function test_detail_tidak_mengubah_status_dan_kasus_belum_ditinjau_404(): void
    {
        $this->detail('#LKR-2025-0842')->assertOk();
        $this->assertSame(StatusKasus::MenungguKeputusan, Pengaduan::where('nomor_tiket', '#LKR-2025-0842')->first()->status_pengaduan);

        // Kasus yang masih di Pemantauan tidak bisa dibuka lewat Riwayat, dan statusnya tidak berubah
        foreach (self::BELUM_DITINJAU as $tiket) {
            $sebelum = Pengaduan::where('nomor_tiket', $tiket)->first()->status_pengaduan;
            $this->detail($tiket)->assertNotFound();
            $this->assertSame($sebelum, Pengaduan::where('nomor_tiket', $tiket)->first()->status_pengaduan);
        }
    }

    public function test_tinjauan_yang_sudah_selesai_tidak_bisa_dikirim_ulang(): void
    {
        foreach (self::SUDAH_DITINJAU as $tiket) {
            $pengaduan = Pengaduan::where('nomor_tiket', $tiket)->first();
            $tinjauan = $pengaduan->hasilKlasifikasi->tinjauanAnalis;
            $jumlahRiwayat = $pengaduan->riwayatKasus()->count();

            $this->actingAs($this->analis())
                ->from(route('riwayat-tinjauan.detail', $tinjauan->id_klasifikasi))
                ->post(route('tinjau-lowongan.simpan', $tinjauan->id_klasifikasi), [
                    'catatan_tinjauan' => 'Coba ubah tinjauan',
                    'hasil_tinjauan'   => HasilTinjauan::TidakTerbukti->value,
                ])
                ->assertRedirect(route('riwayat-tinjauan.detail', $tinjauan->id_klasifikasi))
                ->assertSessionHas('error');

            // Tinjauan, status, dan riwayat kasus tidak berubah
            $this->assertSame($tinjauan->catatan_tinjauan, $tinjauan->fresh()->catatan_tinjauan, $tiket);
            $this->assertSame($tinjauan->hasil_tinjauan, $tinjauan->fresh()->hasil_tinjauan, $tiket);
            $this->assertSame($pengaduan->status_pengaduan, $pengaduan->fresh()->status_pengaduan, $tiket);
            $this->assertSame($jumlahRiwayat, $pengaduan->riwayatKasus()->count(), $tiket);
        }
    }

    // ─── Bagian Hasil Akhir Kasus ─────────────────────────────────────────

    public function test_hasil_akhir_menunggu_keputusan(): void
    {
        $this->detail('#LKR-2025-0842')
            ->assertSee(LokerLensCopy::JUDUL_HASIL_AKHIR_KASUS)
            ->assertSee(LokerLensCopy::MENUNGGU_KEPUTUSAN_KABID)
            ->assertDontSee(LokerLensCopy::LABEL_KEPUTUSAN . ':')
            ->assertDontSee(LokerLensCopy::KASUS_DITUTUP_TAHAP_ANALIS);
    }

    public function test_hasil_akhir_tidak_terbukti(): void
    {
        $this->detail('#LKR-8752')
            ->assertSee(LokerLensCopy::KASUS_DITUTUP_TAHAP_ANALIS)
            ->assertDontSee(LokerLensCopy::MENUNGGU_KEPUTUSAN_KABID)
            ->assertDontSee(LokerLensCopy::LABEL_KEPUTUSAN . ':');
    }

    public function test_hasil_akhir_tidak_ditindaklanjuti(): void
    {
        $this->detail('#LKR-8781')
            ->assertSee(LokerLensCopy::LABEL_KEPUTUSAN . ':')
            ->assertSee('Tidak Ditindaklanjuti')
            ->assertSee('Rizky Pratama')
            ->assertSee(LokerLensCopy::LABEL_TANGGAL_KEPUTUSAN)
            ->assertSee('Data contoh seeder.')
            ->assertDontSee(LokerLensCopy::LABEL_NOMOR_SURAT)
            ->assertDontSee(LokerLensCopy::MENUNGGU_KEPUTUSAN_KABID);
    }

    public function test_hasil_akhir_ditindaklanjuti_surat_draf_tidak_ditampilkan(): void
    {
        $this->detail('#LKR-8770')
            ->assertSee('Tindaklanjuti')
            ->assertSee('Rizky Pratama')
            ->assertDontSee(LokerLensCopy::LABEL_NOMOR_SURAT)
            ->assertDontSee('Draf: pelapor diminta mentransfer biaya pelatihan');
    }

    public function test_hasil_akhir_surat_terbit_hanya_identitas_surat(): void
    {
        $response = $this->detail('#LKR-8798');

        $response->assertSee('Tindaklanjuti')
            ->assertSee('Rizky Pratama')
            ->assertSee(LokerLensCopy::LABEL_JENIS_DOKUMEN)
            ->assertSee('Laporan Polisi')
            ->assertSee(LokerLensCopy::LABEL_NOMOR_SURAT)
            ->assertSee('/POLRI/8798')
            ->assertSee(LokerLensCopy::LABEL_TANGGAL_TERBIT);

        // Isi surat (kronologi, tujuan instansi) dan lampiran tidak ditampilkan
        $response->assertDontSee('Pelapor diminta membayar biaya seragam Rp300.000 dan diundang interview di hotel.')
            ->assertDontSee('Subdit Siber Ditreskrimsus Polda Metro Jaya')
            ->assertDontSee('Lampiran');
    }
}
