<?php

namespace Database\Seeders;

use App\Enums\HasilTinjauan;
use App\Enums\StatusKasus;
use App\Models\HasilKlasifikasi;
use App\Models\IndikatorMencurigakan;
use App\Models\LowonganKerja;
use App\Models\Pengaduan;
use App\Models\Perusahaan;
use App\Models\RiwayatKasus;
use App\Models\SuratTeguranLaporanPolisi;
use App\Models\TindakLanjutKasus;
use App\Models\TinjauanAnalis;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder data mock LokerLens sesuai Bagian 11 panduan.
 * Semua data FIKTIF untuk keperluan pengembangan dan demo.
 * Master Mock Data masih ❓ TBD (Appendix C no. 20).
 */
class LokerLensSeeder extends Seeder
{
    /** Email akun demo per peran (dipakai juga oleh halaman /dev). */
    public const AKUN_DEMO = [
        'Masyarakat'    => 'adelulinn@gmail.com',
        'Analis'        => 'budi.analis@lokerlens.go.id',
        'Kepala Bidang' => 'rizky.kb@lokerlens.go.id',
    ];

    /** Kata sandi semua akun demo. Jangan gunakan di produksi. */
    public const PASSWORD_DEMO = 'password123';

    public function run(): void
    {
        // ─── Pengguna ─────────────────────────────────────────────────────
        $masyarakat = User::create([
            'name'     => 'Adela Ulin Nadhroh',
            'email'    => self::AKUN_DEMO['Masyarakat'],
            'password' => Hash::make(self::PASSWORD_DEMO), // Jangan gunakan password demo di produksi
            'role'     => 'Masyarakat',
            'no_telepon' => '081234567890',
        ]);

        $analis = User::create([
            'name'     => 'Budi Santoso',
            'email'    => self::AKUN_DEMO['Analis'],
            'password' => Hash::make(self::PASSWORD_DEMO),
            'role'     => 'Analis',
            'no_telepon' => '081234567891',
        ]);

        $kepalaBidang = User::create([
            'name'     => 'Rizky Pratama',
            'email'    => self::AKUN_DEMO['Kepala Bidang'],
            'password' => Hash::make(self::PASSWORD_DEMO),
            'role'     => 'Kepala Bidang',
            'no_telepon' => '081234567892',
        ]);

        // ─── Perusahaan (fiktif) ─────────────────────────────────────────
        $perusahaanUtama = Perusahaan::create([
            'nama_perusahaan'  => 'PT Mandiri Sukses Global',
            'status_terdaftar' => 'Tidak Terdaftar',
            'alamat'           => 'Jl. Sudirman No. 123, Jakarta (tidak terverifikasi)',
            'domain_email'     => 'gmail.com',
        ]);

        $perusahaan2 = Perusahaan::create([
            'nama_perusahaan'  => 'PT Sejahtera Abadi Tours',
            'status_terdaftar' => 'Belum Diverifikasi',
        ]);

        $perusahaan3 = Perusahaan::create([
            'nama_perusahaan'  => 'CV Inti Nusantara Karya',
            'status_terdaftar' => 'Belum Diverifikasi',
        ]);

        $perusahaan4 = Perusahaan::create([
            'nama_perusahaan'  => 'PT Mega Buana Logistik Perkasa',
            'status_terdaftar' => 'Belum Diverifikasi',
        ]);

        // Perusahaan untuk kasus Kepala Bidang (LKR-2025-0842)
        $perusahaan5 = Perusahaan::create([
            'nama_perusahaan'  => 'PT Maju Sejahtera Energi Nusantara',
            'status_terdaftar' => 'Tidak Terdaftar',
        ]);

        // ─── Lowongan Kerja (fiktif) ─────────────────────────────────────
        $lowongan1 = LowonganKerja::create([
            'id_perusahaan'      => $perusahaanUtama->id_perusahaan,
            'posisi_lowongan'    => 'Staff Administrasi',
            'link_lowongan'      => 'https://wa.me/6281299014432',
            'deskripsi_lowongan' => 'Dibutuhkan Staff Administrasi untuk perusahaan berkembang. Gaji Rp8.000.000-Rp12.000.000. Tidak dibutuhkan pengalaman. Biaya administrasi Rp150.000 sebelum wawancara. Hubungi mandirisuksesglobal@gmail.com.',
            'informasi_tambahan' => 'WhatsApp: +62 812-9901-4432. Interview di Hotel Grand Mercure lt. 5.',
        ]);

        $lowongan2 = LowonganKerja::create([
            'id_perusahaan'   => $perusahaan2->id_perusahaan,
            'posisi_lowongan' => 'Tour Guide Internasional',
            'deskripsi_lowongan' => 'Lowongan tour guide internasional. Gaji sangat tinggi. Transfer biaya seragam Rp500.000 dulu. Proses cepat 1 hari.',
        ]);

        $lowongan3 = LowonganKerja::create([
            'id_perusahaan'   => $perusahaan3->id_perusahaan,
            'posisi_lowongan' => 'Admin Typing Staff',
            'deskripsi_lowongan' => 'Dibutuhkan admin. Kirim CV ke cv.intinusantara@yahoo.com. Wawancara di kost area Sudirman.',
        ]);

        $lowongan4 = LowonganKerja::create([
            'id_perusahaan'   => $perusahaan4->id_perusahaan,
            'posisi_lowongan' => 'Driver & Kurir Ekspres',
            'deskripsi_lowongan' => 'Butuh driver. Gaji Rp10 juta. Tidak perlu pengalaman. DP biaya BPJS Rp200.000.',
        ]);

        // Lowongan kasus Kepala Bidang
        $lowongan5 = LowonganKerja::create([
            'id_perusahaan'   => $perusahaan5->id_perusahaan,
            'posisi_lowongan' => 'Staff Administrasi Operasional',
            'deskripsi_lowongan' => 'Biaya akomodasi, transfer sebelum wawancara, proses cepat. Gaji besar tanpa pengalaman.',
        ]);

        // ─── Pengaduan & Klasifikasi (kasus utama #LKR-8821) ────────────
        $pengaduan1 = Pengaduan::create([
            'id_lowongan'      => $lowongan1->id_lowongan,
            'id_pengguna'      => $masyarakat->id,
            'nomor_tiket'      => '#LKR-8821',
            'deskripsi_singkat' => 'Lowongan Staff Administrasi PT Mandiri Sukses Global terindikasi penipuan',
            'status_pengaduan' => StatusKasus::SedangDitinjau,
        ]);

        $klasifikasi1 = HasilKlasifikasi::create([
            'id_pengaduan'     => $pengaduan1->id_pengaduan,
            'confidence_score' => 92.00,
            'kategori_hasil'   => 'Lowongan berpotensi penipuan',
            'status_prioritas' => true,
            'metode_analisis'  => 'Mock ML v1.0 (TF-IDF + Rule-based)',
        ]);

        IndikatorMencurigakan::insert([
            ['id_klasifikasi' => $klasifikasi1->id_klasifikasi, 'nama_indikator' => 'biaya administrasi Rp150.000 sebelum wawancara', 'kategori_indikator' => 'Biaya Fiktif', 'bobot_indikator' => 25, 'created_at' => now(), 'updated_at' => now()],
            ['id_klasifikasi' => $klasifikasi1->id_klasifikasi, 'nama_indikator' => 'Gaji Rp8.000.000–Rp12.000.000 tanpa pengalaman', 'kategori_indikator' => 'Gaji Janggal', 'bobot_indikator' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['id_klasifikasi' => $klasifikasi1->id_klasifikasi, 'nama_indikator' => 'mandirisuksesglobal@gmail.com (email gratisan)', 'kategori_indikator' => 'Email Tidak Resmi', 'bobot_indikator' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['id_klasifikasi' => $klasifikasi1->id_klasifikasi, 'nama_indikator' => 'Interview di Hotel, bukan kantor resmi', 'kategori_indikator' => 'Lokasi Mencurigakan', 'bobot_indikator' => 15, 'created_at' => now(), 'updated_at' => now()],
        ]);

        RiwayatKasus::insert([
            ['id_pengaduan' => $pengaduan1->id_pengaduan, 'status_kasus' => StatusKasus::PerluTinjauan->value, 'keterangan' => 'Tiket diterima sistem', 'tanggal_update' => now()->subDays(3), 'created_at' => now(), 'updated_at' => now()],
            ['id_pengaduan' => $pengaduan1->id_pengaduan, 'status_kasus' => StatusKasus::SedangDitinjau->value, 'keterangan' => 'Laporan masuk ke Analis', 'tanggal_update' => now()->subDays(2), 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── #LKR-8819 ──────────────────────────────────────────────────
        $pengaduan2 = Pengaduan::create([
            'id_lowongan' => $lowongan2->id_lowongan, 'id_pengguna' => $masyarakat->id,
            'nomor_tiket' => '#LKR-8819', 'status_pengaduan' => StatusKasus::PerluTinjauan,
        ]);
        $klasifikasi2 = HasilKlasifikasi::create(['id_pengaduan' => $pengaduan2->id_pengaduan, 'confidence_score' => 88.00, 'kategori_hasil' => 'Lowongan berpotensi penipuan', 'status_prioritas' => true, 'metode_analisis' => 'Mock ML v1.0']);
        IndikatorMencurigakan::create(['id_klasifikasi' => $klasifikasi2->id_klasifikasi, 'nama_indikator' => 'Transfer biaya seragam sebelum kerja', 'kategori_indikator' => 'Biaya Fiktif', 'bobot_indikator' => 25]);

        // ─── #LKR-8790 ──────────────────────────────────────────────────
        $pengaduan3 = Pengaduan::create([
            'id_lowongan' => $lowongan3->id_lowongan, 'id_pengguna' => $masyarakat->id,
            'nomor_tiket' => '#LKR-8790', 'status_pengaduan' => StatusKasus::PerluTinjauan,
        ]);
        HasilKlasifikasi::create(['id_pengaduan' => $pengaduan3->id_pengaduan, 'confidence_score' => 85.00, 'kategori_hasil' => 'Lowongan berpotensi penipuan', 'status_prioritas' => true, 'metode_analisis' => 'Mock ML v1.0']);

        // ─── #LKR-8765 ──────────────────────────────────────────────────
        $pengaduan4 = Pengaduan::create([
            'id_lowongan' => $lowongan4->id_lowongan, 'id_pengguna' => $masyarakat->id,
            'nomor_tiket' => '#LKR-8765', 'status_pengaduan' => StatusKasus::PerluTinjauan,
        ]);
        HasilKlasifikasi::create(['id_pengaduan' => $pengaduan4->id_pengaduan, 'confidence_score' => 79.00, 'kategori_hasil' => 'Lowongan berpotensi penipuan', 'status_prioritas' => true, 'metode_analisis' => 'Mock ML v1.0']);

        // ─── Kasus Kepala Bidang (LKR-2025-0842, sudah ditinjau Analis) ─
        $pengaduan5 = Pengaduan::create([
            'id_lowongan'      => $lowongan5->id_lowongan,
            'id_pengguna'      => $masyarakat->id,
            'nomor_tiket'      => '#LKR-2025-0842',
            'deskripsi_singkat' => 'Staff Administrasi Operasional PT Maju Sejahtera Energi Nusantara',
            'status_pengaduan' => StatusKasus::MenungguKeputusan,
        ]);

        $klasifikasi5 = HasilKlasifikasi::create([
            'id_pengaduan'     => $pengaduan5->id_pengaduan,
            'confidence_score' => 84.00,
            'kategori_hasil'   => 'Lowongan berpotensi penipuan',
            'status_prioritas' => true,
            'metode_analisis'  => 'Mock ML v1.0',
        ]);

        IndikatorMencurigakan::insert([
            ['id_klasifikasi' => $klasifikasi5->id_klasifikasi, 'nama_indikator' => 'Biaya akomodasi', 'kategori_indikator' => 'Biaya Fiktif', 'bobot_indikator' => 25, 'created_at' => now(), 'updated_at' => now()],
            ['id_klasifikasi' => $klasifikasi5->id_klasifikasi, 'nama_indikator' => 'Transfer sebelum wawancara', 'kategori_indikator' => 'Biaya Fiktif', 'bobot_indikator' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['id_klasifikasi' => $klasifikasi5->id_klasifikasi, 'nama_indikator' => 'Proses cepat 1 hari', 'kategori_indikator' => 'Pola Mencurigakan', 'bobot_indikator' => 15, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Tinjauan Analis sudah dibuat untuk kasus ini
        TinjauanAnalis::create([
            'id_klasifikasi'   => $klasifikasi5->id_klasifikasi,
            'id_pengguna'      => $analis->id,
            'catatan_tinjauan' => 'Berdasarkan investigasi, ditemukan pola permintaan biaya yang tidak wajar. Lowongan ini sangat mencurigakan dan perlu ditindaklanjuti oleh pihak berwajib.',
            'hasil_tinjauan'   => HasilTinjauan::TerkonfirmasiMencurigakan->value,
            'tanggal_tinjauan' => now()->subDay(),
        ]);

        RiwayatKasus::insert([
            ['id_pengaduan' => $pengaduan5->id_pengaduan, 'status_kasus' => StatusKasus::PerluTinjauan->value, 'keterangan' => 'Tiket diterima', 'tanggal_update' => now()->subDays(5), 'created_at' => now(), 'updated_at' => now()],
            ['id_pengaduan' => $pengaduan5->id_pengaduan, 'status_kasus' => StatusKasus::SedangDitinjau->value, 'keterangan' => 'Laporan masuk ke Analis', 'tanggal_update' => now()->subDays(3), 'created_at' => now(), 'updated_at' => now()],
            ['id_pengaduan' => $pengaduan5->id_pengaduan, 'status_kasus' => StatusKasus::MenungguKeputusan->value, 'keterangan' => 'Tinjauan Analis selesai: Terkonfirmasi Mencurigakan. Diteruskan ke Kepala Bidang.', 'tanggal_update' => now()->subDay(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->kasusUntukStatusLain($masyarakat, $analis, $kepalaBidang);
    }

    /**
     * Kasus tambahan supaya setiap status kasus punya minimal satu contoh
     * (semua halaman bisa dibuka, termasuk Buat Surat dan hasil terbit).
     */
    private function kasusUntukStatusLain(User $masyarakat, User $analis, User $kepalaBidang): void
    {
        // Selesai - Risiko Rendah: skor di bawah ambang, tidak masuk antrean Analis
        $this->buatKasus($masyarakat, 'PT Cahaya Prima Logistik', 'Staff Gudang', '#LKR-8740', 32.00,
            'Dibutuhkan staff gudang untuk kantor cabang Surabaya. Kontrak kerja resmi, gaji sesuai UMR, seleksi di kantor perusahaan.',
            [[StatusKasus::SelesaiRisikoRendah, 'Pengaduan diterima dan klasifikasi dilakukan. Skor di bawah ambang prioritas, kasus selesai.']]);

        // Tidak Terbukti: ditutup Analis
        $tidakTerbukti = $this->buatKasus($masyarakat, 'CV Berkah Mitra Niaga', 'Sales Lapangan', '#LKR-8752', 73.00,
            'Lowongan sales lapangan dengan komisi besar. Pelamar diminta datang ke kantor untuk wawancara, tanpa biaya.',
            [
                [StatusKasus::PerluTinjauan, 'Tiket diterima sistem'],
                [StatusKasus::SedangDitinjau, 'Analis mulai meninjau kasus.'],
                [StatusKasus::TidakTerbukti, 'Tinjauan Analis selesai: Tidak Terbukti. Kasus ditutup oleh Analis.'],
            ]);
        $this->buatTinjauan($tidakTerbukti, $analis, HasilTinjauan::TidakTerbukti,
            'Perusahaan terdaftar dan alamat kantor dapat diverifikasi. Tidak ditemukan permintaan biaya.');

        // Tindak Lanjut Disetujui: siap dibuatkan surat (ada draf)
        $disetujui = $this->buatKasus($masyarakat, 'PT Gemilang Karya Persada', 'Admin Data Entry', '#LKR-8770', 90.00,
            'Kerja dari rumah sebagai admin data entry, gaji Rp9 juta tanpa pengalaman. Wajib transfer biaya pelatihan Rp250.000.',
            [
                [StatusKasus::PerluTinjauan, 'Tiket diterima sistem'],
                [StatusKasus::SedangDitinjau, 'Analis mulai meninjau kasus.'],
                [StatusKasus::MenungguKeputusan, 'Tinjauan Analis selesai: Terkonfirmasi Mencurigakan. Diteruskan ke Kepala Bidang.'],
                [StatusKasus::TindakLanjutDisetujui, 'Keputusan Kepala Bidang: Tindaklanjuti. Kasus dilanjutkan ke tahap pembuatan surat.'],
            ]);
        $this->buatTinjauan($disetujui, $analis, HasilTinjauan::TerkonfirmasiMencurigakan,
            'Ditemukan permintaan biaya pelatihan sebelum kerja dan rekening penampung pribadi.');
        $tindakLanjut = $this->buatTindakLanjut($disetujui, $kepalaBidang, 'Tindaklanjuti');
        SuratTeguranLaporanPolisi::create([
            'id_tindaklanjut'    => $tindakLanjut->id_tindaklanjut,
            'jenis_dokumen'      => 'Laporan Polisi',
            'kronologi_kejadian' => 'Draf: pelapor diminta mentransfer biaya pelatihan Rp250.000 sebelum mulai bekerja.',
            'tujuan_instansi'    => 'Satreskrim Polres Wilayah Setempat',
            'status_penerbitan'  => 'Draft',
        ]);

        // Tidak Ditindaklanjuti: diarsipkan Kepala Bidang
        $diarsipkan = $this->buatKasus($masyarakat, 'PT Sinar Harapan Jaya', 'Operator Produksi', '#LKR-8781', 76.00,
            'Lowongan operator produksi, proses cepat. Pelamar diminta mengirim fotokopi KTP melalui WhatsApp.',
            [
                [StatusKasus::PerluTinjauan, 'Tiket diterima sistem'],
                [StatusKasus::SedangDitinjau, 'Analis mulai meninjau kasus.'],
                [StatusKasus::MenungguKeputusan, 'Tinjauan Analis selesai: Terkonfirmasi Mencurigakan. Diteruskan ke Kepala Bidang.'],
                [StatusKasus::TidakDitindaklanjuti, 'Kasus ditandai tidak dilanjuti dan diarsipkan'],
            ]);
        $this->buatTinjauan($diarsipkan, $analis, HasilTinjauan::TerkonfirmasiMencurigakan,
            'Permintaan data pribadi lewat WhatsApp, tetapi tidak ada permintaan biaya.');
        $this->buatTindakLanjut($diarsipkan, $kepalaBidang, 'Tidak Ditindaklanjuti');

        // Selesai: surat sudah terbit
        $selesai = $this->buatKasus($masyarakat, 'PT Nusantara Cipta Mandiri', 'Customer Service', '#LKR-8798', 95.00,
            'Customer service dengan gaji Rp10 juta tanpa pengalaman. Wajib membayar biaya seragam Rp300.000 dan interview di hotel.',
            [
                [StatusKasus::PerluTinjauan, 'Tiket diterima sistem'],
                [StatusKasus::SedangDitinjau, 'Analis mulai meninjau kasus.'],
                [StatusKasus::MenungguKeputusan, 'Tinjauan Analis selesai: Terkonfirmasi Mencurigakan. Diteruskan ke Kepala Bidang.'],
                [StatusKasus::TindakLanjutDisetujui, 'Keputusan Kepala Bidang: Tindaklanjuti. Kasus dilanjutkan ke tahap pembuatan surat.'],
                [StatusKasus::Selesai, 'Laporan Polisi berhasil diterbitkan. Kasus terdokumentasi otomatis.'],
            ]);
        $this->buatTinjauan($selesai, $analis, HasilTinjauan::TerkonfirmasiMencurigakan,
            'Permintaan biaya seragam dan lokasi interview di hotel. Perusahaan tidak terdaftar.');
        $tindakLanjutSelesai = $this->buatTindakLanjut($selesai, $kepalaBidang, 'Tindaklanjuti');
        SuratTeguranLaporanPolisi::create([
            'id_tindaklanjut'    => $tindakLanjutSelesai->id_tindaklanjut,
            'nomor_surat'        => SuratTeguranLaporanPolisi::generateNomorSurat($selesai->nomor_tiket),
            'jenis_dokumen'      => 'Laporan Polisi',
            'kronologi_kejadian' => 'Pelapor diminta membayar biaya seragam Rp300.000 dan diundang interview di hotel.',
            'tujuan_instansi'    => 'Subdit Siber Ditreskrimsus Polda Metro Jaya / Wilayah Terkait',
            'status_penerbitan'  => 'Terbit',
            'tanggal_terbit'     => now(),
        ]);
    }

    /**
     * @param  array<int, array{0: StatusKasus, 1: string}>  $alur  urutan status yang sudah dilalui
     */
    private function buatKasus(User $pelapor, string $namaPerusahaan, string $posisi, string $nomorTiket, float $skor, string $deskripsi, array $alur): Pengaduan
    {
        $lowongan = LowonganKerja::create([
            'id_perusahaan'      => Perusahaan::create(['nama_perusahaan' => $namaPerusahaan, 'status_terdaftar' => 'Belum Diverifikasi'])->id_perusahaan,
            'posisi_lowongan'    => $posisi,
            'deskripsi_lowongan' => $deskripsi,
        ]);

        $pengaduan = Pengaduan::create([
            'id_lowongan'       => $lowongan->id_lowongan,
            'id_pengguna'       => $pelapor->id,
            'nomor_tiket'       => $nomorTiket,
            'deskripsi_singkat' => $posisi . ' ' . $namaPerusahaan,
            'status_pengaduan'  => end($alur)[0],
        ]);

        HasilKlasifikasi::create([
            'id_pengaduan'     => $pengaduan->id_pengaduan,
            'confidence_score' => $skor,
            'kategori_hasil'   => $skor >= 75 ? 'Lowongan berpotensi penipuan' : ($skor >= 50 ? 'Lowongan perlu diperiksa lebih lanjut' : 'Lowongan tampak normal'),
            'status_prioritas' => StatusKasus::awalDariSkor($skor) === StatusKasus::PerluTinjauan,
            'metode_analisis'  => 'Mock ML v1.0',
        ]);

        foreach (array_values($alur) as $i => [$status, $keterangan]) {
            RiwayatKasus::create([
                'id_pengaduan'   => $pengaduan->id_pengaduan,
                'status_kasus'   => $status->value,
                'keterangan'     => $keterangan,
                'tanggal_update' => now()->subDays(count($alur) - $i),
            ]);
        }

        return $pengaduan;
    }

    private function buatTinjauan(Pengaduan $pengaduan, User $analis, HasilTinjauan $hasil, string $catatan): void
    {
        TinjauanAnalis::create([
            'id_klasifikasi'   => $pengaduan->hasilKlasifikasi->id_klasifikasi,
            'id_pengguna'      => $analis->id,
            'catatan_tinjauan' => $catatan,
            'hasil_tinjauan'   => $hasil->value,
            'tanggal_tinjauan' => now()->subDays(2),
        ]);
    }

    private function buatTindakLanjut(Pengaduan $pengaduan, User $kepalaBidang, string $keputusan): TindakLanjutKasus
    {
        return TindakLanjutKasus::create([
            'id_pengaduan'      => $pengaduan->id_pengaduan,
            'id_pengguna'       => $kepalaBidang->id,
            'keputusan'         => $keputusan,
            'catatan_keputusan' => 'Data contoh seeder.',
            'tanggal_keputusan' => now()->subDay(),
        ]);
    }
}
