<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Enums\HasilTinjauan;
use App\Enums\StatusKasus;
use App\Models\BuktiPendukung;
use App\Models\HasilKlasifikasi;
use App\Models\Pengaduan;
use App\Models\User;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HalamanAnalisDanKepalaBidangTest extends TestCase
{
    use RefreshDatabase;

    private User $masyarakat;
    private User $analis;
    private User $kepalaBidang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LokerLensSeeder::class);

        $this->masyarakat   = User::where('role', 'Masyarakat')->firstOrFail();
        $this->analis       = User::where('role', 'Analis')->firstOrFail();
        $this->kepalaBidang = User::where('role', 'Kepala Bidang')->firstOrFail();
    }

    /**
     * Langkah 4: Analis - Daftar kasus dengan filter status dan urutan prioritas skor (>= 70 di atas).
     */
    public function test_analis_daftar_kasus_filter_status_dan_urutan_skor(): void
    {
        $response = $this->actingAs($this->analis)->get('/tinjau-lowongan');
        $response->assertOk()
            ->assertSee('Daftar Lowongan Prioritas Tinggi')
            ->assertSee('ID LOWONGAN')
            ->assertSee('CONFIDENCE SCORE');

        // Filter status "Perlu Tinjauan"
        $filterStatus = $this->actingAs($this->analis)->get('/tinjau-lowongan?status=Perlu+Tinjauan');
        $filterStatus->assertOk()
            ->assertSee('#LKR-8819')
            ->assertDontSee('#LKR-8821'); // status Sedang Ditinjau

        // Filter status "Sedang Ditinjau"
        $filterSedang = $this->actingAs($this->analis)->get('/tinjau-lowongan?status=Sedang+Ditinjau');
        $filterSedang->assertOk()
            ->assertSee('#LKR-8821')
            ->assertDontSee('#LKR-8819');

        // Filter skor dan urutan prioritas (SRS Table B.3)
        $filterSkor = $this->actingAs($this->analis)->get('/tinjau-lowongan?filter_skor=Skor+Tinggi&urutan=Skor+Tertinggi');
        $filterSkor->assertOk()
            ->assertSee('Daftar Sesuai Kriteria Filter');

        // Pencarian nomor tiket terdaftar (SRS Table B.3)
        $cariTerdaftar = $this->actingAs($this->analis)->get('/tinjau-lowongan?nomor_tiket=%23LKR-8821');
        $cariTerdaftar->assertOk()
            ->assertSee('Hasil Pencarian')
            ->assertSee('#LKR-8821');

        // Keadaan Kosong (empty state) jika filter / tiket tidak cocok (SRS Table B.3)
        $emptyResponse = $this->actingAs($this->analis)->get('/tinjau-lowongan?nomor_tiket=NONEXISTENT_TICKET_123');
        $emptyResponse->assertOk()
            ->assertSee('Data Lowongan Tidak Ditemukan');
    }

    /**
     * Issue #6: Urutan "Skor Terendah" dan "Terbaru" berfungsi dengan benar (FR-ML-03).
     */
    public function test_analis_urutan_skor_terendah_dan_terbaru(): void
    {
        // 1. Urutan Skor Terendah (ascending)
        $resAsc = $this->actingAs($this->analis)->get('/tinjau-lowongan?urutan=Skor+Terendah');
        $resAsc->assertOk();
        $itemsAsc = $resAsc->viewData('hasilKlasifikasi');
        $scoresAsc = $itemsAsc->pluck('confidence_score')->toArray();
        $sortedAsc = $scoresAsc;
        sort($sortedAsc);
        $this->assertSame($sortedAsc, $scoresAsc);

        // 2. Urutan Skor Tertinggi (descending - default)
        $resDesc = $this->actingAs($this->analis)->get('/tinjau-lowongan?urutan=Skor+Tertinggi');
        $resDesc->assertOk();
        $itemsDesc = $resDesc->viewData('hasilKlasifikasi');
        $scoresDesc = $itemsDesc->pluck('confidence_score')->toArray();
        $sortedDesc = $scoresDesc;
        rsort($sortedDesc);
        $this->assertSame($sortedDesc, $scoresDesc);
    }

    /**
     * Issue #6: Detail dengan ID yang tidak ditemukan redirect ke daftar dengan pesan "Data Lowongan Tidak Ditemukan" (UC-02, FR-ML-05).
     */
    public function test_analis_detail_tidak_ditemukan_redirect_notifikasi(): void
    {
        $response = $this->actingAs($this->analis)->get('/tinjau-lowongan/detail/999999');
        $response->assertRedirect('/tinjau-lowongan')
            ->assertSessionHas('error', \App\Constants\LokerLensCopy::DATA_TIDAK_DITEMUKAN);

        // Pesan error muncul di halaman antrean
        $this->actingAs($this->analis)->get('/tinjau-lowongan')
            ->assertOk()
            ->assertSee(\App\Constants\LokerLensCopy::DATA_TIDAK_DITEMUKAN);
    }

    /**
     * Issue #6: NFR-PERF-01 Waktu tampil daftar <= 3 detik.
     */
    public function test_analis_waktu_tampil_daftar_sesuai_nfr_perf_01(): void
    {
        // Siapkan 100 data kasus prioritas tinggi untuk pengujian NFR-PERF-01
        $perusahaan = \App\Models\Perusahaan::first();
        $pelapor = $this->masyarakat;

        $pengaduanData = [];
        $klasifikasiData = [];
        $now = now();

        for ($i = 1; $i <= 100; $i++) {
            $lowongan = \App\Models\LowonganKerja::create([
                'id_perusahaan'      => $perusahaan->id_perusahaan,
                'posisi_lowongan'    => "Posisi Uji {$i}",
                'deskripsi_lowongan' => "Deskripsi pengujian performa nomor {$i}.",
            ]);

            $pengaduan = \App\Models\Pengaduan::create([
                'id_lowongan'       => $lowongan->id_lowongan,
                'id_pengguna'       => $pelapor->id,
                'nomor_tiket'       => "#LKR-PERF-" . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'deskripsi_singkat' => "Uji performa {$i}",
                'status_pengaduan'  => StatusKasus::PerluTinjauan,
            ]);

            \App\Models\HasilKlasifikasi::create([
                'id_pengaduan'        => $pengaduan->id_pengaduan,
                'confidence_score'    => 75.0 + ($i % 25),
                'kategori_hasil'      => 'Lowongan berpotensi penipuan',
                'status_prioritas'    => true,
                'metode_analisis'     => 'Mock ML Test',
                'tanggal_klasifikasi' => $now,
            ]);
        }

        // Ukur waktu respon tampilan daftar untuk 100+ data
        $startTime = microtime(true);
        $response = $this->actingAs($this->analis)->get('/tinjau-lowongan');
        $duration = microtime(true) - $startTime;

        $response->assertOk();
        $this->assertLessThan(3.0, $duration, "Waktu muat halaman daftar lowongan dengan 100 data harus <= 3 detik (NFR-PERF-01). Aktual: {$duration} detik.");
    }

    /**
     * Langkah 4: Analis - Detail kasus menampilkan data lowongan, klasifikasi, kata pemicu, bukti pendukung.
     */
    public function test_analis_detail_kasus_tampilkan_data_dan_bukti_pendukung(): void
    {
        $klasifikasi = HasilKlasifikasi::whereHas('pengaduan', fn ($q) => $q->where('nomor_tiket', '#LKR-8821'))->firstOrFail();

        $response = $this->actingAs($this->analis)->get("/tinjau-lowongan/detail/{$klasifikasi->id_klasifikasi}");
        $response->assertOk()
            ->assertSee('Detail Lowongan Terpilih')
            ->assertSee('Confidence Score')
            ->assertSee('92%')
            ->assertSee('Indikator Mencurigakan')
            ->assertSee('Bukti Pendukung')
            ->assertSee('Tangkapan_Layar_Chat_HRD_Fiktif.png')
            ->assertSee('Surat_Panggilan_Interview_Hotel.pdf')
            ->assertSee('Pratinjau')
            ->assertSee('Unduh');
    }

    /**
     * Langkah 4: Analis - Form tinjauan hasil_tinjauan string bebas maksimal 100 karakter.
     */
    public function test_analis_form_tinjauan_maksimal_100_karakter_dan_bisa_disimpan(): void
    {
        $klasifikasi = HasilKlasifikasi::whereHas('pengaduan', fn ($q) => $q->where('nomor_tiket', '#LKR-8821'))->firstOrFail();

        // Validasi gagal jika hasil_tinjauan melebihi 100 karakter
        $string101 = str_repeat('A', 101);
        $this->actingAs($this->analis)->post("/tinjau-lowongan/detail/{$klasifikasi->id_klasifikasi}/simpan", [
            'catatan_tinjauan' => 'Catatan hasil investigasi lengkap.',
            'hasil_tinjauan'   => $string101,
        ])->assertSessionHasErrors('hasil_tinjauan');

        // Berhasil disimpan dengan string bebas hingga 100 karakter
        $stringValid = 'Terkonfirmasi Mencurigakan - Ditemukan rekening penampung fiktif';
        $response = $this->actingAs($this->analis)->post("/tinjau-lowongan/detail/{$klasifikasi->id_klasifikasi}/simpan", [
            'catatan_tinjauan' => 'Catatan audit ketenagakerjaan telah diverifikasi.',
            'hasil_tinjauan'   => $stringValid,
        ]);

        $response->assertRedirect('/tinjau-lowongan')
            ->assertSessionHas('success');

        $this->assertSame(StatusKasus::MenungguKeputusan, $klasifikasi->pengaduan->fresh()->status_pengaduan);
        $this->assertSame($stringValid, $klasifikasi->tinjauanAnalis()->value('hasil_tinjauan'));
    }

    /**
     * Langkah 5: Kepala Bidang - Daftar kasus yang sudah ditinjau, form keputusan, surat terbit.
     */
    public function test_kepala_bidang_daftar_detail_dan_keputusan(): void
    {
        // 1. Daftar kasus yang menunggu keputusan
        $responseDaftar = $this->actingAs($this->kepalaBidang)->get('/tindak-lanjut');
        $responseDaftar->assertOk()
            ->assertSee('Daftar Laporan')
            ->assertSee('Menunggu Keputusan')
            ->assertSee('#LKR-2025-0842');

        // 2. Detail kasus: Menampilkan Detail Laporan Lowongan Mencurigakan, Informasi Lowongan, Ringkasan Laporan, dan Terindikasi Anomali Tinggi (SRS Table B.4)
        $pengaduan = Pengaduan::where('nomor_tiket', '#LKR-2025-0842')->firstOrFail();
        $responseDetail = $this->actingAs($this->kepalaBidang)->get("/tindak-lanjut/{$pengaduan->id_pengaduan}");
        $responseDetail->assertOk()
            ->assertSee('Detail Laporan Lowongan Mencurigakan')
            ->assertSee('Informasi Lowongan')
            ->assertSee('Ringkasan Laporan')
            ->assertSee('Terindikasi Anomali Tinggi')
            ->assertSee('Biaya akomodasi')
            ->assertSee('Lihat Bukti')
            ->assertSee('Konfirmasi Keputusan')
            ->assertSee('Tindaklanjuti')
            ->assertSee('Tidak Ditindaklanjuti');

        // 3. Simpan keputusan tindak lanjut
        $responseKeputusan = $this->actingAs($this->kepalaBidang)->post("/tindak-lanjut/{$pengaduan->id_pengaduan}/keputusan", [
            'keputusan'         => 'Tindaklanjuti',
            'catatan_keputusan' => 'Setujui penerbitan surat laporan kepolisian.',
        ]);

        $responseKeputusan->assertRedirect("/tindak-lanjut/{$pengaduan->id_pengaduan}/hasil?keputusan=Tindaklanjuti");
        $this->assertSame(StatusKasus::TindakLanjutDisetujui, $pengaduan->fresh()->status_pengaduan);

        // 4. Form surat terbuka setelah Tindaklanjuti dan menampilkan "Buat Laporan Polisi"
        $this->actingAs($this->kepalaBidang)->get("/buat-surat/{$pengaduan->id_pengaduan}")
            ->assertOk()
            ->assertSee('Buat Laporan Polisi')
            ->assertSee('Data Kasus');
    }

    /**
     * Kriteria Selesai SRS Table B.4:
     * 1. Halaman detail menampilkan "Detail Laporan Lowongan Mencurigakan", "Informasi Lowongan", "Ringkasan Laporan", dan "Terindikasi Anomali Tinggi".
     * 2. Menekan "Tidak Ditindaklanjuti" (lalu konfirmasi) menampilkan "Kasus ditandai tidak dilanjuti dan diarsipkan".
     * 3. Menekan "Tindaklanjuti" (lalu konfirmasi) mengarah ke pembuatan surat, dan halaman /buat-surat/ menampilkan "Buat Laporan Polisi".
     */
    public function test_kriteria_selesai_srs_tabel_b4_kepala_bidang(): void
    {
        // Setup kasus baru menunggu keputusan
        $lowongan = \App\Models\LowonganKerja::create([
            'id_perusahaan'      => \App\Models\Perusahaan::first()->id_perusahaan,
            'posisi_lowongan'    => 'Finance Admin',
            'deskripsi_lowongan' => 'Staff admin keuangan dengan transfer biaya akomodasi interview hotel.',
        ]);

        $pengaduan = Pengaduan::create([
            'id_lowongan'       => $lowongan->id_lowongan,
            'id_pengguna'       => $this->analis->id,
            'nomor_tiket'       => '#LKR-BDD-004',
            'deskripsi_singkat' => 'Pengaduan uji BDD Table B.4',
            'status_pengaduan'  => StatusKasus::MenungguKeputusan,
        ]);

        $klasifikasi = \App\Models\HasilKlasifikasi::create([
            'id_pengaduan'     => $pengaduan->id_pengaduan,
            'confidence_score' => 88.0,
            'kategori_hasil'   => \App\Constants\LokerLensCopy::JUDUL_ANOMALI_TINGGI,
            'status_prioritas' => true,
            'metode_analisis'  => 'ML Model v1.0',
        ]);

        \App\Models\IndikatorMencurigakan::create([
            'id_klasifikasi'     => $klasifikasi->id_klasifikasi,
            'nama_indikator'     => 'biaya akomodasi',
            'kategori_indikator' => 'Biaya Fiktif',
            'bobot_indikator'    => 25.0,
        ]);

        \App\Models\BuktiPendukung::create([
            'id_pengaduan'   => $pengaduan->id_pengaduan,
            'nama_file'      => 'bukti_chat.pdf',
            'tipe_file'      => 'pdf',
            'ukuran_file'    => 102400,
            'path_file'      => 'bukti/bukti_chat.pdf',
            'tanggal_upload' => now(),
        ]);

        \App\Models\TinjauanAnalis::create([
            'id_klasifikasi'   => $klasifikasi->id_klasifikasi,
            'id_pengguna'      => $this->analis->id,
            'catatan_tinjauan' => 'Terindikasi sindikat penipuan.',
            'hasil_tinjauan'   => 'Terkonfirmasi Mencurigakan',
            'tanggal_tinjauan' => now(),
        ]);

        // 1. Verifikasi elemen halaman detail
        $resDetail = $this->actingAs($this->kepalaBidang)->get("/tindak-lanjut/{$pengaduan->id_pengaduan}");
        $resDetail->assertOk()
            ->assertSee('Detail Laporan Lowongan Mencurigakan')
            ->assertSee('Informasi Lowongan')
            ->assertSee('Ringkasan Laporan')
            ->assertSee('Terindikasi Anomali Tinggi')
            ->assertSee('biaya akomodasi')
            ->assertSee('Lihat Bukti');

        // 2. Skenario: Menekan "Tidak Ditindaklanjuti" -> menampilkan "Kasus ditandai tidak dilanjuti dan diarsipkan"
        $pengaduanArsip = Pengaduan::create([
            'id_lowongan'       => $lowongan->id_lowongan,
            'id_pengguna'       => $this->analis->id,
            'nomor_tiket'       => '#LKR-BDD-005',
            'deskripsi_singkat' => 'Pengaduan uji arsip',
            'status_pengaduan'  => StatusKasus::MenungguKeputusan,
        ]);

        $resTolak = $this->actingAs($this->kepalaBidang)->post("/tindak-lanjut/{$pengaduanArsip->id_pengaduan}/keputusan", [
            'keputusan'         => 'Tidak Ditindaklanjuti',
            'catatan_keputusan' => 'Arsipkan pengaduan.',
        ]);

        $resTolak->assertRedirect(route('tindak-lanjut.hasil', ['id' => $pengaduanArsip->id_pengaduan, 'keputusan' => 'Tidak Ditindaklanjuti']));
        $resHasilTolak = $this->actingAs($this->kepalaBidang)->get(route('tindak-lanjut.hasil', ['id' => $pengaduanArsip->id_pengaduan, 'keputusan' => 'Tidak Ditindaklanjuti']));
        $resHasilTolak->assertOk()
            ->assertSee('Kasus ditandai tidak dilanjuti dan diarsipkan');

        // 3. Skenario: Menekan "Tindaklanjuti" -> mengarah ke pembuatan surat, dan halaman /buat-surat/ menampilkan "Buat Laporan Polisi"
        $resSetuju = $this->actingAs($this->kepalaBidang)->post("/tindak-lanjut/{$pengaduan->id_pengaduan}/keputusan", [
            'keputusan'         => 'Tindaklanjuti',
            'catatan_keputusan' => 'Lanjutkan ke polisi.',
        ]);

        $resSetuju->assertRedirect("/tindak-lanjut/{$pengaduan->id_pengaduan}/hasil?keputusan=Tindaklanjuti");
        $resHasilSetuju = $this->actingAs($this->kepalaBidang)->get("/tindak-lanjut/{$pengaduan->id_pengaduan}/hasil?keputusan=Tindaklanjuti");
        $resHasilSetuju->assertOk()
            ->assertSee('Lanjut ke Pembuatan Surat');

        $resSurat = $this->actingAs($this->kepalaBidang)->get("/buat-surat/{$pengaduan->id_pengaduan}");
        $resSurat->assertOk()
            ->assertSee('Buat Laporan Polisi');
    }

    /**
     * Langkah 5 & 6: Halaman Pratinjau Surat memiliki kelas cetak dan no-print.
     */
    public function test_halaman_surat_terbit_rapi_untuk_dicetak(): void
    {
        $kasusSelesai = Pengaduan::where('status_pengaduan', StatusKasus::Selesai)->firstOrFail();
        $surat = $kasusSelesai->tindakLanjutKasus->suratTeguranLaporanPolisi()->where('status_penerbitan', 'Terbit')->firstOrFail();

        $response = $this->actingAs($this->kepalaBidang)->get("/buat-surat/{$kasusSelesai->id_pengaduan}/terbit/{$surat->id_surat}");
        $response->assertOk()
            ->assertSee('Laporan Polisi Berhasil Diterbitkan')
            ->assertSee('Cetak Tanda Terima')
            ->assertSee('print-page')
            ->assertSee('no-print');
    }

    /**
     * Kriteria selesai SRS Table B.5 (Buat Surat):
     * 1. Mengisi kronologi lalu menekan "Simpan Draft" menampilkan "Draf berhasil disimpan".
     * 2. Jika penerbitan gagal diproses sistem, muncul "Gagal menerbitkan dokumen, draf telah disimpan" dan halaman tetap menampilkan "Buat Laporan Polisi".
     * 3. Mengisi kronologi, memilih "Subdit Siber Ditreskrimsus Polda Metro Jaya" di tujuan_kepolisian, lalu menekan "Terbitkan Laporan" menampilkan "Kasus terdokumentasi otomatis" dan "Kasus selesai".
     * Serta bukti pendukung dinamis & tombol Lihat Arsip Kasus.
     */
    public function test_kriteria_selesai_srs_tabel_b5_buat_surat(): void
    {
        Storage::fake('public');

        // Gunakan kasus yang sudah Tindak Lanjut Disetujui dari Seeder (#LKR-8770)
        $pengaduan = Pengaduan::where('nomor_tiket', '#LKR-8770')->firstOrFail();

        // Tambah bukti pendukung awal untuk pengujian panel bukti
        BuktiPendukung::create([
            'id_pengaduan'   => $pengaduan->id_pengaduan,
            'nama_file'      => 'Bukti_Transfer_Awal.pdf',
            'tipe_file'      => 'pdf',
            'ukuran_file'    => 102400,
            'path_file'      => 'bukti/bukti_awal.pdf',
            'tanggal_upload' => now(),
        ]);

        // Pastikan halaman form menampilkan berkas dari tabel bukti_pendukung
        $resForm = $this->actingAs($this->kepalaBidang)->get("/buat-surat/{$pengaduan->id_pengaduan}");
        $resForm->assertOk()
            ->assertSee('Buat Laporan Polisi')
            ->assertSee('Bukti_Transfer_Awal.pdf');

        // 1. Skenario Simpan Draft: mengisi kronologi & upload berkas tambahan -> "Draf berhasil disimpan"
        $fileTambahan = UploadedFile::fake()->create('Bukti_Chat_Baru.png', 50, 'image/png');
        $resDraft = $this->actingAs($this->kepalaBidang)
            ->from("/buat-surat/{$pengaduan->id_pengaduan}")
            ->post("/buat-surat/{$pengaduan->id_pengaduan}/draft", [
                'kronologi'         => 'Kronologi awal draf oleh Kepala Bidang.',
                'tujuan_kepolisian' => 'Subdit Siber Ditreskrimsus Polda Metro Jaya',
                'bukti_tambahan'    => [$fileTambahan],
            ]);

        $resDraft->assertRedirect("/buat-surat/{$pengaduan->id_pengaduan}");
        $this->actingAs($this->kepalaBidang)->get("/buat-surat/{$pengaduan->id_pengaduan}")
            ->assertOk()
            ->assertSee(LokerLensCopy::DRAF_TERSIMPAN)
            ->assertSee('Bukti_Chat_Baru.png');

        // 2. Skenario Gagal Terbitkan: simulasi kegagalan sistem -> "Gagal menerbitkan dokumen, draf telah disimpan", halaman tetap "Buat Laporan Polisi"
        $resGagal = $this->actingAs($this->kepalaBidang)
            ->withHeader('X-Simulate-Terbit-Failure', '1')
            ->from("/buat-surat/{$pengaduan->id_pengaduan}")
            ->post("/buat-surat/{$pengaduan->id_pengaduan}/terbitkan", [
                'kronologi'         => 'Kronologi kejadian yang gagal diterbitkan.',
                'tujuan_kepolisian' => 'Subdit Siber Ditreskrimsus Polda Metro Jaya',
            ]);

        $resGagal->assertRedirect("/buat-surat/{$pengaduan->id_pengaduan}");
        $this->flushHeaders();
        $this->actingAs($this->kepalaBidang)->get("/buat-surat/{$pengaduan->id_pengaduan}")
            ->assertOk()
            ->assertSee(LokerLensCopy::GAGAL_TERBITKAN)
            ->assertSee('Buat Laporan Polisi');

        // Pastikan draf tetap tersimpan dan kasus belum berstatus Selesai
        $pengaduan->refresh();
        $this->assertSame(StatusKasus::TindakLanjutDisetujui, $pengaduan->status_pengaduan);
        $this->assertSame('Draft', $pengaduan->tindakLanjutKasus->suratTeguranLaporanPolisi->status_penerbitan);
        $this->assertSame('Kronologi kejadian yang gagal diterbitkan.', $pengaduan->tindakLanjutKasus->suratTeguranLaporanPolisi->kronologi_kejadian);

        // 3. Skenario Sukses Terbitkan: mengisi kronologi, pilih "Subdit Siber Ditreskrimsus Polda Metro Jaya", tekan "Terbitkan Laporan"
        $resTerbit = $this->actingAs($this->kepalaBidang)
            ->from("/buat-surat/{$pengaduan->id_pengaduan}")
            ->post("/buat-surat/{$pengaduan->id_pengaduan}/terbitkan", [
                'kronologi'         => 'Pelapor telah mentransfer dana pelatihan ke sindikat penipuan.',
                'tujuan_kepolisian' => 'Subdit Siber Ditreskrimsus Polda Metro Jaya',
            ]);

        $surat = $pengaduan->tindakLanjutKasus->suratTeguranLaporanPolisi()->where('status_penerbitan', 'Terbit')->firstOrFail();
        $resTerbit->assertRedirect(route('buat-surat.terbit', ['pengaduanId' => $pengaduan->id_pengaduan, 'suratId' => $surat->id_surat]));

        // Halaman hasil penerbitan:
        $resHasilTerbit = $this->actingAs($this->kepalaBidang)
            ->get(route('buat-surat.terbit', ['pengaduanId' => $pengaduan->id_pengaduan, 'suratId' => $surat->id_surat]));

        $resHasilTerbit->assertOk()
            ->assertSee(LokerLensCopy::KASUS_TERDOKUMENTASI)
            ->assertSee(LokerLensCopy::KASUS_SELESAI)
            ->assertSee('Subdit Siber Ditreskrimsus Polda Metro Jaya')
            ->assertSee('Bukti_Transfer_Awal.pdf')
            ->assertSee('Bukti_Chat_Baru.png')
            ->assertSee('Lihat Arsip Kasus')
            ->assertSee(route('tindak-lanjut.index', ['status' => StatusKasus::Selesai->value]));

        // Status kasus berubah menjadi Selesai
        $this->assertSame(StatusKasus::Selesai, $pengaduan->fresh()->status_pengaduan);
    }

    /**
     * Langkah 6: Hak Akses Middleware Server-Side & Halaman 403.
     */
    public function test_middleware_role_dan_halaman_403(): void
    {
        // Masyarakat ditolak saat coba akses halaman Analis
        $this->actingAs($this->masyarakat)->get('/tinjau-lowongan')
            ->assertForbidden()
            ->assertSee('403')
            ->assertSee('Akses Ditolak');

        // Analis ditolak saat coba akses halaman Kepala Bidang
        $this->actingAs($this->analis)->get('/tindak-lanjut')
            ->assertForbidden()
            ->assertSee('403')
            ->assertSee('Akses Ditolak');

        // Masyarakat ditolak saat coba akses halaman pembuatan surat
        $this->actingAs($this->masyarakat)->get('/buat-surat')
            ->assertForbidden();
    }

    /**
     * Langkah 6: Perlindungan Data Pribadi Pelapor (NFR-SEC-05).
     */
    public function test_data_pribadi_pelapor_tidak_tampil_di_analis_dan_kepala_bidang(): void
    {
        $klasifikasi = HasilKlasifikasi::whereHas('pengaduan', fn ($q) => $q->where('nomor_tiket', '#LKR-8821'))->firstOrFail();
        $emailPelapor = $klasifikasi->pengaduan->pengguna->email;

        // Analis tidak melihat email pelapor
        $this->actingAs($this->analis)->get("/tinjau-lowongan/detail/{$klasifikasi->id_klasifikasi}")
            ->assertOk()
            ->assertDontSee($emailPelapor);

        // Kepala Bidang tidak melihat email pelapor
        $pengaduan = Pengaduan::where('nomor_tiket', '#LKR-2025-0842')->firstOrFail();
        $emailPelapor2 = $pengaduan->pengguna->email;

        $this->actingAs($this->kepalaBidang)->get("/tindak-lanjut/{$pengaduan->id_pengaduan}")
            ->assertOk()
            ->assertDontSee($emailPelapor2);
    }
}
