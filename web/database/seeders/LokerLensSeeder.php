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
    public function run(): void
    {
        // ─── Pengguna ─────────────────────────────────────────────────────
        $masyarakat = User::create([
            'name'     => 'Adela Ulin Nadhroh',
            'email'    => 'adelulinn@gmail.com',
            'password' => Hash::make('password123'), // Jangan gunakan password demo di produksi
            'role'     => 'Masyarakat',
            'no_telepon' => '081234567890',
        ]);

        $analis = User::create([
            'name'     => 'Budi Santoso',
            'email'    => 'budi.analis@lokerlens.go.id',
            'password' => Hash::make('password123'),
            'role'     => 'Analis',
            'no_telepon' => '081234567891',
        ]);

        $kepalaBidang = User::create([
            'name'     => 'Rizky Pratama',
            'email'    => 'rizky.kb@lokerlens.go.id',
            'password' => Hash::make('password123'),
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
    }
}
