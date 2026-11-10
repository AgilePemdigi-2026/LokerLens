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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder data mock LokerLens sesuai Bagian 11 panduan.
 * Semua data FIKTIF untuk keperluan pengembangan dan demo.
 * Master Mock Data masih ? TBD (Appendix C no. 20).
 */
class LokerLensSeeder extends Seeder
{
    /** Email akun demo per peran (dipakai juga oleh halaman /dev). */
    public const AKUN_DEMO = [
        'Masyarakat'    => 'adelulinn@gmail.com',
        'Analis'        => 'budi.analis@lokerlens.go.id',
        'Kepala Bidang' => 'rizky.kb@lokerlens.go.id',
    ];

    public function run(): void
    {
        // ??? Pengguna ?????????????????????????????????????????????????????
        $masyarakat = User::create([
            'name'     => 'Adela Ulin Nadhroh',
            'email'    => self::AKUN_DEMO['Masyarakat'],
            'password' => Hash::make('password123'), // Jangan gunakan password demo di produksi
            'role'     => 'Masyarakat',
            'no_telepon' => '081234567890',
        ]);

        $analis = User::create([
            'name'     => 'Budi Santoso',
            'email'    => self::AKUN_DEMO['Analis'],
            'password' => Hash::make('password123'),
            'role'     => 'Analis',
            'no_telepon' => '081234567891',
        ]);

        $kepalaBidang = User::create([
            'name'     => 'Rizky Pratama',
            'email'    => self::AKUN_DEMO['Kepala Bidang'],
            'password' => Hash::make('password123'),
            'role'     => 'Kepala Bidang',
            'no_telepon' => '081234567892',
        ]);

        // ??? Perusahaan (fiktif) ?????????????????????????????????????????
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

        // ??? Lowongan Kerja (fiktif) ?????????????????????????????????????
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
            'deskripsi_lowongan' => 'Dibutuhkan admin typing untuk input data, bisa dikerjakan dari rumah. Kirim CV ke cv.intinusantara@yahoo.com. Wawancara dilakukan di kost area Sudirman, bukan di kantor. Gaji dibayar mingguan.',
        ]);

        $lowongan4 = LowonganKerja::create([
            'id_perusahaan'   => $perusahaan4->id_perusahaan,
            'posisi_lowongan' => 'Driver & Kurir Ekspres',
            'deskripsi_lowongan' => 'Butuh driver dan kurir ekspres segera. Gaji Rp10 juta per bulan, tidak perlu pengalaman. Pelamar wajib DP biaya BPJS Rp200.000 sebelum mulai bekerja.',
        ]);

        // Lowongan kasus Kepala Bidang
        $lowongan5 = LowonganKerja::create([
            'id_perusahaan'   => $perusahaan5->id_perusahaan,
            'posisi_lowongan' => 'Staff Administrasi Operasional',
            'deskripsi_lowongan' => 'Dibutuhkan staff administrasi operasional. Gaji besar tanpa pengalaman, proses cepat 1 hari. Pelamar wajib transfer biaya akomodasi sebelum wawancara.',
        ]);

        // Urutan tanggal tiap kasus: tanggal pengaduan = saat klasifikasi, lalu riwayat status sesudahnya.

        // ??? Pengaduan & Klasifikasi (kasus utama #LKR-8821) ????????????
        $diajukan1 = $this->waktu(5, 9, 12);
        $pengaduan1 = Pengaduan::create([
            'id_lowongan'       => $lowongan1->id_lowongan,
            'id_pengguna'       => $masyarakat->id,
            'nomor_tiket'       => '#LKR-8821',
            'deskripsi_singkat' => 'Lowongan Staff Administrasi PT Mandiri Sukses Global terindikasi penipuan',
            'status_pengaduan'  => StatusKasus::SedangDitinjau,
            'tanggal_pengaduan' => $diajukan1,
        ]);

        $klasifikasi1 = HasilKlasifikasi::create([
            'id_pengaduan'        => $pengaduan1->id_pengaduan,
            'confidence_score'    => 92.00,
            'kategori_hasil'      => 'Lowongan berpotensi penipuan',
            'status_prioritas'    => true,
            'metode_analisis'     => 'Mock ML v1.0 (TF-IDF + Rule-based)',
            'tanggal_klasifikasi' => $diajukan1,
        ]);

        IndikatorMencurigakan::insert([
            ['id_klasifikasi' => $klasifikasi1->id_klasifikasi, 'nama_indikator' => 'biaya administrasi Rp150.000 sebelum wawancara', 'kategori_indikator' => 'Biaya Fiktif', 'bobot_indikator' => 25, 'created_at' => now(), 'updated_at' => now()],
            ['id_klasifikasi' => $klasifikasi1->id_klasifikasi, 'nama_indikator' => 'Gaji Rp8.000.000?Rp12.000.000 tanpa pengalaman', 'kategori_indikator' => 'Gaji Janggal', 'bobot_indikator' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['id_klasifikasi' => $klasifikasi1->id_klasifikasi, 'nama_indikator' => 'mandirisuksesglobal@gmail.com (email gratisan)', 'kategori_indikator' => 'Email Tidak Resmi', 'bobot_indikator' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['id_klasifikasi' => $klasifikasi1->id_klasifikasi, 'nama_indikator' => 'Interview di Hotel, bukan kantor resmi', 'kategori_indikator' => 'Lokasi Mencurigakan', 'bobot_indikator' => 15, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->buatRiwayat($pengaduan1, [
            [StatusKasus::PerluTinjauan, 'Tiket diterima sistem', $diajukan1],
            [StatusKasus::SedangDitinjau, 'Laporan masuk ke Analis', $this->waktu(3, 10, 30)],
        ]);

        // ??? #LKR-8819 ??????????????????????????????????????????????????
        $diajukan2 = $this->waktu(4, 13, 40);
        $pengaduan2 = Pengaduan::create([
            'id_lowongan' => $lowongan2->id_lowongan, 'id_pengguna' => $masyarakat->id,
            'nomor_tiket' => '#LKR-8819', 'status_pengaduan' => StatusKasus::PerluTinjauan,
            'deskripsi_singkat' => 'Periksa lowongan: Tour Guide Internasional di PT Sejahtera Abadi Tours',
            'tanggal_pengaduan' => $diajukan2,
        ]);
        $klasifikasi2 = HasilKlasifikasi::create(['id_pengaduan' => $pengaduan2->id_pengaduan, 'confidence_score' => 88.00, 'kategori_hasil' => 'Lowongan berpotensi penipuan', 'status_prioritas' => true, 'metode_analisis' => 'Mock ML v1.0', 'tanggal_klasifikasi' => $diajukan2]);
        IndikatorMencurigakan::create(['id_klasifikasi' => $klasifikasi2->id_klasifikasi, 'nama_indikator' => 'Transfer biaya seragam sebelum kerja', 'kategori_indikator' => 'Biaya Fiktif', 'bobot_indikator' => 25]);
        $this->buatKataPemicu($klasifikasi2, ['proses cepat 1 hari' => 14]);
        $this->buatRiwayat($pengaduan2, [[StatusKasus::PerluTinjauan, 'Pengaduan diterima dan klasifikasi dilakukan. Masuk antrean tinjauan Analis.', $diajukan2]]);

        // ??? #LKR-8790 ??????????????????????????????????????????????????
        $diajukan3 = $this->waktu(3, 8, 5);
        $pengaduan3 = Pengaduan::create([
            'id_lowongan' => $lowongan3->id_lowongan, 'id_pengguna' => $masyarakat->id,
            'nomor_tiket' => '#LKR-8790', 'status_pengaduan' => StatusKasus::PerluTinjauan,
            'deskripsi_singkat' => 'Periksa lowongan: Admin Typing Staff di CV Inti Nusantara Karya',
            'tanggal_pengaduan' => $diajukan3,
        ]);
        $klasifikasi3 = HasilKlasifikasi::create(['id_pengaduan' => $pengaduan3->id_pengaduan, 'confidence_score' => 85.00, 'kategori_hasil' => 'Lowongan berpotensi penipuan', 'status_prioritas' => true, 'metode_analisis' => 'Mock ML v1.0', 'tanggal_klasifikasi' => $diajukan3]);
        $this->buatKataPemicu($klasifikasi3, ['di kost' => 24, '@yahoo.com' => 18, 'gaji dibayar mingguan' => 12]);
        $this->buatRiwayat($pengaduan3, [[StatusKasus::PerluTinjauan, 'Pengaduan diterima dan klasifikasi dilakukan. Masuk antrean tinjauan Analis.', $diajukan3]]);

        // ??? #LKR-8765 ??????????????????????????????????????????????????
        $diajukan4 = $this->waktu(2, 19, 22);
        $pengaduan4 = Pengaduan::create([
            'id_lowongan' => $lowongan4->id_lowongan, 'id_pengguna' => $masyarakat->id,
            'nomor_tiket' => '#LKR-8765', 'status_pengaduan' => StatusKasus::PerluTinjauan,
            'deskripsi_singkat' => 'Periksa lowongan: Driver & Kurir Ekspres di PT Mega Buana Logistik Perkasa',
            'tanggal_pengaduan' => $diajukan4,
        ]);
        $klasifikasi4 = HasilKlasifikasi::create(['id_pengaduan' => $pengaduan4->id_pengaduan, 'confidence_score' => 79.00, 'kategori_hasil' => 'Lowongan berpotensi penipuan', 'status_prioritas' => true, 'metode_analisis' => 'Mock ML v1.0', 'tanggal_klasifikasi' => $diajukan4]);
        $this->buatKataPemicu($klasifikasi4, ['biaya bpjs' => 26, 'wajib dp' => 20, 'tidak perlu pengalaman' => 11]);
        $this->buatRiwayat($pengaduan4, [[StatusKasus::PerluTinjauan, 'Pengaduan diterima dan klasifikasi dilakukan. Masuk antrean tinjauan Analis.', $diajukan4]]);

        // ??? Kasus Kepala Bidang (LKR-2025-0842, sudah ditinjau Analis) ?
        $diajukan5 = $this->waktu(8, 10, 0);
        $ditinjau5 = $this->waktu(4, 15, 10);
        $pengaduan5 = Pengaduan::create([
            'id_lowongan'       => $lowongan5->id_lowongan,
            'id_pengguna'       => $masyarakat->id,
            'nomor_tiket'       => '#LKR-2025-0842',
            'deskripsi_singkat' => 'Staff Administrasi Operasional PT Maju Sejahtera Energi Nusantara',
            'status_pengaduan'  => StatusKasus::MenungguKeputusan,
            'tanggal_pengaduan' => $diajukan5,
        ]);

        $klasifikasi5 = HasilKlasifikasi::create([
            'id_pengaduan'        => $pengaduan5->id_pengaduan,
            'confidence_score'    => 84.00,
            'kategori_hasil'      => 'Lowongan berpotensi penipuan',
            'status_prioritas'    => true,
            'metode_analisis'     => 'Mock ML v1.0',
            'tanggal_klasifikasi' => $diajukan5,
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
            'tanggal_tinjauan' => $ditinjau5,
        ]);

        $this->buatRiwayat($pengaduan5, [
            [StatusKasus::PerluTinjauan, 'Tiket diterima', $diajukan5],
            [StatusKasus::SedangDitinjau, 'Laporan masuk ke Analis', $this->waktu(6, 9, 30)],
            [StatusKasus::MenungguKeputusan, 'Tinjauan Analis selesai: Terkonfirmasi Mencurigakan. Diteruskan ke Kepala Bidang.', $ditinjau5],
        ]);

        $this->kasusUntukStatusLain($masyarakat, $analis, $kepalaBidang);
        $this->dataStatusPengaduan($masyarakat);
    }

    /**
     * Data tambahan untuk halaman Status Pengaduan (#11).
     */
    private function dataStatusPengaduan(User $masyarakat): void
    {
        // Menunggu klasifikasi: API ML mati saat pemeriksaan, belum ada Hasil_Klasifikasi.
        // Status sementara "Perlu Tinjauan" sama seperti PeriksaLowonganController@store.
        $diajukan = now()->subHours(2);
        $lowongan = LowonganKerja::create([
            'id_perusahaan'      => Perusahaan::create(['nama_perusahaan' => 'PT Arunika Daya Mandiri', 'status_terdaftar' => 'Belum Diverifikasi'])->id_perusahaan,
            'posisi_lowongan'    => 'Staf Pemasaran Digital',
            'deskripsi_lowongan' => 'Dicari staf pemasaran digital, bisa kerja dari rumah. Pelamar diminta mengirim CV dan nomor rekening melalui WhatsApp.',
        ]);
        $menunggu = Pengaduan::create([
            'id_lowongan'       => $lowongan->id_lowongan,
            'id_pengguna'       => $masyarakat->id,
            'nomor_tiket'       => '#LKR-8805',
            'deskripsi_singkat' => 'Periksa lowongan: Staf Pemasaran Digital di PT Arunika Daya Mandiri',
            'status_pengaduan'  => StatusKasus::PerluTinjauan,
            'tanggal_pengaduan' => $diajukan,
        ]);
        $this->buatRiwayat($menunggu, [[StatusKasus::PerluTinjauan, 'Pengaduan diterima. Menunggu klasifikasi karena layanan ML tidak tersedia.', $diajukan]]);

        // Akun Masyarakat kedua tanpa pengaduan: untuk melihat tampilan kosong
        // dan memastikan pengaduan akun lain tidak bisa dibuka (404).
        User::create([
            'name'       => 'Sari Wulandari',
            'email'      => 'masyarakat.baru@lokerlens.test',
            'password'   => Hash::make('password123'),
            'role'       => 'Masyarakat',
            'no_telepon' => '081234567893',
        ]);
    }

    /**
     * Kasus tambahan supaya setiap status kasus punya minimal satu contoh
     * (semua halaman bisa dibuka, termasuk Buat Surat dan hasil terbit).
     */
    private function kasusUntukStatusLain(User $masyarakat, User $analis, User $kepalaBidang): void
    {
        // Selesai - Risiko Rendah: skor di bawah ambang, tidak masuk antrean Analis
        $this->buatKasus($masyarakat, 'PT Cahaya Prima Logistik', 'Staff Gudang', '#LKR-8740', 32.00,
            'Dibutuhkan staff gudang untuk kantor cabang Surabaya. Kontrak kerja resmi, gaji sesuai UMR, seleksi di kantor perusahaan. Penempatan segera setelah lolos tes.',
            ['penempatan segera' => 6],
            $this->waktu(10, 11, 0),
            [StatusKasus::SelesaiRisikoRendah->value => 'Pengaduan diterima dan klasifikasi dilakukan. Skor di bawah ambang prioritas, kasus selesai.']);

        // Tidak Terbukti: ditutup Analis
        $tidakTerbukti = $this->buatKasus($masyarakat, 'CV Berkah Mitra Niaga', 'Sales Lapangan', '#LKR-8752', 73.00,
            'Lowongan sales lapangan dengan komisi besar tanpa target. Pelamar diminta datang ke kantor untuk wawancara, tanpa biaya. Bonus mingguan untuk penjualan terbaik.',
            ['komisi besar' => 19, 'tanpa target' => 15, 'bonus mingguan' => 9],
            $this->waktu(12, 14, 25),
            [
                StatusKasus::PerluTinjauan->value  => 'Tiket diterima sistem',
                StatusKasus::SedangDitinjau->value => 'Analis mulai meninjau kasus.',
                StatusKasus::TidakTerbukti->value  => 'Tinjauan Analis selesai: Tidak Terbukti. Kasus ditutup oleh Analis.',
            ]);
        $this->buatTinjauan($tidakTerbukti, $analis, HasilTinjauan::TidakTerbukti,
            'Perusahaan terdaftar dan alamat kantor dapat diverifikasi. Tidak ditemukan permintaan biaya.');

        // Tindak Lanjut Disetujui: siap dibuatkan surat (ada draf)
        $disetujui = $this->buatKasus($masyarakat, 'PT Gemilang Karya Persada', 'Admin Data Entry', '#LKR-8770', 90.00,
            'Kerja dari rumah sebagai admin data entry, gaji Rp9 juta tanpa pengalaman. Wajib transfer biaya pelatihan Rp250.000.',
            ['transfer biaya pelatihan' => 28, 'tanpa pengalaman' => 16, 'kerja dari rumah' => 10],
            $this->waktu(9, 8, 45),
            [
                StatusKasus::PerluTinjauan->value         => 'Tiket diterima sistem',
                StatusKasus::SedangDitinjau->value        => 'Analis mulai meninjau kasus.',
                StatusKasus::MenungguKeputusan->value     => 'Tinjauan Analis selesai: Terkonfirmasi Mencurigakan. Diteruskan ke Kepala Bidang.',
                StatusKasus::TindakLanjutDisetujui->value => 'Keputusan Kepala Bidang: Tindaklanjuti. Kasus dilanjutkan ke tahap pembuatan surat.',
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
            'Lowongan operator produksi, proses cepat. Pelamar diminta mengirim fotokopi KTP melalui WhatsApp. Penempatan langsung tanpa tes.',
            ['ktp melalui whatsapp' => 22, 'proses cepat' => 14, 'tanpa tes' => 10],
            $this->waktu(14, 16, 5),
            [
                StatusKasus::PerluTinjauan->value        => 'Tiket diterima sistem',
                StatusKasus::SedangDitinjau->value       => 'Analis mulai meninjau kasus.',
                StatusKasus::MenungguKeputusan->value    => 'Tinjauan Analis selesai: Terkonfirmasi Mencurigakan. Diteruskan ke Kepala Bidang.',
                StatusKasus::TidakDitindaklanjuti->value => 'Kasus ditandai tidak dilanjuti dan diarsipkan',
            ]);
        $this->buatTinjauan($diarsipkan, $analis, HasilTinjauan::TerkonfirmasiMencurigakan,
            'Permintaan data pribadi lewat WhatsApp, tetapi tidak ada permintaan biaya.');
        $this->buatTindakLanjut($diarsipkan, $kepalaBidang, 'Tidak Ditindaklanjuti');

        // Selesai: surat sudah terbit
        $selesai = $this->buatKasus($masyarakat, 'PT Nusantara Cipta Mandiri', 'Customer Service', '#LKR-8798', 95.00,
            'Customer service dengan gaji Rp10 juta tanpa pengalaman. Wajib membayar biaya seragam Rp300.000 dan interview di hotel.',
            ['biaya seragam' => 30, 'interview di hotel' => 21, 'tanpa pengalaman' => 13],
            $this->waktu(20, 10, 15),
            [
                StatusKasus::PerluTinjauan->value         => 'Tiket diterima sistem',
                StatusKasus::SedangDitinjau->value        => 'Analis mulai meninjau kasus.',
                StatusKasus::MenungguKeputusan->value     => 'Tinjauan Analis selesai: Terkonfirmasi Mencurigakan. Diteruskan ke Kepala Bidang.',
                StatusKasus::TindakLanjutDisetujui->value => 'Keputusan Kepala Bidang: Tindaklanjuti. Kasus dilanjutkan ke tahap pembuatan surat.',
                StatusKasus::Selesai->value               => 'Laporan Polisi berhasil diterbitkan. Kasus terdokumentasi otomatis.',
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
            'tanggal_terbit'     => $this->tanggalStatus($selesai, StatusKasus::Selesai),
        ]);
    }

    /**
     * Buat satu kasus lengkap. Status pertama dicatat saat pengaduan diajukan ($diajukan),
     * status berikutnya masing-masing 1 hari 2 jam sesudahnya.
     *
     * @param  array<string, float>  $kataPemicu  kata pemicu => bobot (0?100)
     * @param  array<string, string>  $alur  nilai StatusKasus => keterangan, sesuai urutan yang sudah dilalui
     */
    private function buatKasus(User $pelapor, string $namaPerusahaan, string $posisi, string $nomorTiket, float $skor, string $deskripsi, array $kataPemicu, Carbon $diajukan, array $alur): Pengaduan
    {
        $lowongan = LowonganKerja::create([
            'id_perusahaan'      => Perusahaan::create(['nama_perusahaan' => $namaPerusahaan, 'status_terdaftar' => 'Belum Diverifikasi'])->id_perusahaan,
            'posisi_lowongan'    => $posisi,
            'deskripsi_lowongan' => $deskripsi,
            'tanggal_input'      => $diajukan,
        ]);

        $pengaduan = Pengaduan::create([
            'id_lowongan'       => $lowongan->id_lowongan,
            'id_pengguna'       => $pelapor->id,
            'nomor_tiket'       => $nomorTiket,
            // Format sama dengan PeriksaLowonganController@store
            'deskripsi_singkat' => 'Periksa lowongan: ' . $posisi . ' di ' . $namaPerusahaan,
            'status_pengaduan'  => StatusKasus::from(array_key_last($alur)),
            'tanggal_pengaduan' => $diajukan,
        ]);

        $klasifikasi = HasilKlasifikasi::create([
            'id_pengaduan'        => $pengaduan->id_pengaduan,
            'confidence_score'    => $skor,
            'kategori_hasil'      => $skor >= 75 ? 'Lowongan berpotensi penipuan' : ($skor >= 50 ? 'Lowongan perlu diperiksa lebih lanjut' : 'Lowongan tampak normal'),
            'status_prioritas'    => StatusKasus::awalDariSkor($skor) === StatusKasus::PerluTinjauan,
            'metode_analisis'     => 'Mock ML v1.0',
            'tanggal_klasifikasi' => $diajukan,
        ]);
        $this->buatKataPemicu($klasifikasi, $kataPemicu);

        $riwayat = [];
        foreach (array_keys($alur) as $i => $status) {
            $riwayat[] = [StatusKasus::from($status), $alur[$status], $diajukan->copy()->addDays($i)->addHours($i * 2)];
        }
        $this->buatRiwayat($pengaduan, $riwayat);

        return $pengaduan;
    }

    /**
     * Kata pemicu seperti hasil model ML (kategori "Kata Pemicu Model").
     *
     * @param  array<string, float>  $kataPemicu  kata pemicu => bobot (0?100)
     */
    private function buatKataPemicu(HasilKlasifikasi $klasifikasi, array $kataPemicu): void
    {
        foreach ($kataPemicu as $kata => $bobot) {
            IndikatorMencurigakan::create([
                'id_klasifikasi'     => $klasifikasi->id_klasifikasi,
                'nama_indikator'     => $kata,
                'kategori_indikator' => IndikatorMencurigakan::KATEGORI_KATA_PEMICU,
                'bobot_indikator'    => $bobot,
            ]);
        }
    }

    /**
     * @param  array<int, array{0: StatusKasus, 1: string, 2: Carbon}>  $riwayat  status, keterangan, waktu
     */
    private function buatRiwayat(Pengaduan $pengaduan, array $riwayat): void
    {
        foreach ($riwayat as [$status, $keterangan, $waktu]) {
            RiwayatKasus::create([
                'id_pengaduan'   => $pengaduan->id_pengaduan,
                'status_kasus'   => $status->value,
                'keterangan'     => $keterangan,
                'tanggal_update' => $waktu,
            ]);
        }
    }

    /** Waktu catatan Riwayat Kasus untuk status tertentu (dipakai agar tanggal tinjauan/keputusan/surat sama). */
    private function tanggalStatus(Pengaduan $pengaduan, StatusKasus $status): Carbon
    {
        return $pengaduan->riwayatKasus()->where('status_kasus', $status->value)->firstOrFail()->tanggal_update;
    }

    /** Mis. waktu(5, 9, 12) = 5 hari lalu pukul 09:12. */
    private function waktu(int $hariLalu, int $jam, int $menit): Carbon
    {
        return now()->subDays($hariLalu)->setTime($jam, $menit);
    }

    /** Tanggal tinjauan = saat status berubah dari "Sedang Ditinjau" ke hasil tinjauan. */
    private function buatTinjauan(Pengaduan $pengaduan, User $analis, HasilTinjauan $hasil, string $catatan): void
    {
        TinjauanAnalis::create([
            'id_klasifikasi'   => $pengaduan->hasilKlasifikasi->id_klasifikasi,
            'id_pengguna'      => $analis->id,
            'catatan_tinjauan' => $catatan,
            'hasil_tinjauan'   => $hasil->value,
            'tanggal_tinjauan' => $this->tanggalStatus($pengaduan, $hasil->statusKasus()),
        ]);
    }

    /** Tanggal keputusan = saat status berubah karena keputusan Kepala Bidang. */
    private function buatTindakLanjut(Pengaduan $pengaduan, User $kepalaBidang, string $keputusan): TindakLanjutKasus
    {
        $statusKeputusan = $keputusan === 'Tindaklanjuti' ? StatusKasus::TindakLanjutDisetujui : StatusKasus::TidakDitindaklanjuti;

        return TindakLanjutKasus::create([
            'id_pengaduan'      => $pengaduan->id_pengaduan,
            'id_pengguna'       => $kepalaBidang->id,
            'keputusan'         => $keputusan,
            'catatan_keputusan' => 'Data contoh seeder.',
            'tanggal_keputusan' => $this->tanggalStatus($pengaduan, $statusKeputusan),
        ]);
    }
}
