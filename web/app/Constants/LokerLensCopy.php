<?php

namespace App\Constants;

/**
 * Teks resmi LokerLens — satu sumber kebenaran untuk semua teks UI.
 * Sesuai Pedoman §9 (Daftar Teks Resmi) & NFR-QUA-01 (teks seragam).
 *
 * PENTING: Jangan mengubah teks di bawah tanpa verifikasi ke dokumen proyek.
 * Teks ini digunakan sebagai selector pada pengujian BDD (Sprint 3).
 */
final class LokerLensCopy
{
    // ──────────────────────────────────────────────
    //  Pesan Sistem
    // ──────────────────────────────────────────────
    public const VALIDASI_BELUM_LENGKAP    = 'Data belum lengkap, mohon lengkapi terlebih dahulu';
    public const DATA_TIDAK_DITEMUKAN      = 'Data Lowongan Tidak Ditemukan';
    public const TINJAUAN_BERHASIL         = 'Hasil Tinjauan Berhasil Disimpan';
    public const KASUS_DIARSIPKAN          = 'Kasus ditandai tidak dilanjuti dan diarsipkan';
    public const DRAF_TERSIMPAN            = 'Draf berhasil disimpan';
    public const GAGAL_TERBITKAN           = 'Gagal menerbitkan dokumen, draf telah disimpan';
    public const LAPORAN_SUDAH_TERBIT      = 'Laporan sudah diterbitkan dan tidak dapat diubah lagi';
    public const GAGAL_LOGIN               = 'Gagal masuk. Email atau kata sandi yang Anda masukkan tidak sesuai. Silakan periksa kembali dan coba lagi.';
    public const BELUM_ADA_PENGADUAN       = 'Belum ada pengaduan yang diajukan';
    public const KASUS_TERDOKUMENTASI      = 'Kasus terdokumentasi otomatis';
    public const KASUS_SELESAI             = 'Kasus selesai';

    // Belum ada di daftar teks resmi SRS (keputusan tim: API ML mati/timeout,
    // pengaduan tetap disimpan dan diklasifikasi ulang nanti).
    public const MENUNGGU_KLASIFIKASI      = 'Menunggu klasifikasi';
    public const KLASIFIKASI_TERTUNDA      = 'Layanan pemeriksaan otomatis sedang tidak tersedia. Data lowongan Anda sudah tersimpan dan akan diperiksa setelah layanan kembali normal. Silakan buka halaman ini lagi nanti.';

    // ──────────────────────────────────────────────
    //  Judul / Label yang Diuji BDD
    // ──────────────────────────────────────────────
    public const JUDUL_HASIL_PEMERIKSAAN   = 'Hasil Pemeriksaan';
    public const JUDUL_DAFTAR_PRIORITAS    = 'Daftar Lowongan Prioritas Tinggi';
    public const JUDUL_SESUAI_FILTER       = 'Daftar Sesuai Kriteria Filter';
    public const JUDUL_HASIL_PENCARIAN     = 'Hasil Pencarian';
    public const JUDUL_DETAIL_TERPILIH     = 'Detail Lowongan Terpilih';
    public const JUDUL_CONFIDENCE_SCORE    = 'Confidence Score';
    public const JUDUL_INDIKATOR           = 'Indikator Mencurigakan';
    public const JUDUL_DETAIL_LAPORAN      = 'Detail Laporan Lowongan Mencurigakan';
    public const JUDUL_INFO_LOWONGAN       = 'Informasi Lowongan';
    public const JUDUL_RINGKASAN_LAPORAN   = 'Ringkasan Laporan';
    public const JUDUL_ANOMALI_TINGGI      = 'Terindikasi Anomali Tinggi';
    public const JUDUL_MENINDAKLANJUTI     = 'Menindaklanjuti Laporan Lowongan Kerja yang Mencurigakan';
    public const JUDUL_BUAT_LAPORAN        = 'Buat Laporan Polisi';

    // ──────────────────────────────────────────────
    //  Tombol (teks persis)
    // ──────────────────────────────────────────────
    public const BTN_PERIKSA_SEKARANG      = 'Periksa Sekarang';
    public const BTN_RESET_FORM            = 'Reset Form';
    public const BTN_CARI                  = 'Cari';
    public const BTN_LIHAT_DETAIL          = 'Lihat Detail';
    public const BTN_LANJUTKAN             = 'Lanjutkan';
    public const BTN_TINDAKLANJUTI         = 'Tindaklanjuti';
    public const BTN_TIDAK_DITINDAKLANJUTI = 'Tidak Ditindaklanjuti';
    public const BTN_KONFIRMASI_KEPUTUSAN  = 'Konfirmasi Keputusan';
    public const BTN_BATAL                 = 'Batal';
    public const BTN_YA_KONFIRMASI         = 'Ya, Konfirmasi';
    public const BTN_KEMBALI_DAFTAR        = 'Kembali ke Daftar';
    public const BTN_KEMBALI_DAFTAR_LAP    = 'Kembali ke Daftar Laporan';
    public const BTN_LANJUT_SURAT          = 'Lanjut ke Pembuatan Surat';
    public const BTN_SIMPAN_DRAFT          = 'Simpan Draft';
    public const BTN_TERBITKAN_LAPORAN     = 'Terbitkan Laporan';
    public const BTN_BUAT_AKUN             = 'Buat Akun';
    public const BTN_MASUK                 = 'Masuk';
    public const BTN_DAFTAR                = 'Daftar';
    public const BTN_AJUKAN_PENGADUAN      = 'Ajukan Pengaduan';
    public const BTN_CEK_STATUS            = 'Cek Status Pengaduan';
    public const BTN_LIHAT_BUKTI           = 'Lihat Bukti';
    public const BTN_KEMBALI_ANTREAN       = 'Kembali ke Antrean';
    public const BTN_LIHAT_ARSIP           = 'Lihat Arsip Kasus';
    public const BTN_KEMBALI_LAPORAN       = 'Kembali ke Laporan';

    // ──────────────────────────────────────────────
    //  Status Kasus → lihat App\Enums\StatusKasus (satu-satunya daftar status)
    // ──────────────────────────────────────────────

    // ──────────────────────────────────────────────
    //  Label Tambahan Mockup Analis
    // ──────────────────────────────────────────────
    public const LABEL_MENUNGGU_VERIFIKASI = 'MENUNGGU VERIFIKASI';
    public const LABEL_INVESTIGASI_AWAL    = 'INVESTIGASI AWAL';
    public const LABEL_AKTIF               = 'AKTIF';

        public const JUDUL_DASHBOARD_KEPALA_BIDANG = 'Dashboard Kepala Bidang';
    public const DESKRIPSI_DASHBOARD_KEPALA_BIDANG = 'Ringkasan jumlah laporan berdasarkan status penanganan.';
    public const JUDUL_DASHBOARD_ANALIS            = 'Dashboard Analis';
    public const DESKRIPSI_DASHBOARD_ANALIS        = 'Pantau beban kerja dan prioritaskan lowongan berisiko tinggi.';
    public const ANTREAN_ANALIS_KOSONG_JUDUL       = 'Belum ada antrean pada periode ini';
    public const ANTREAN_ANALIS_KOSONG_KETERANGAN  = 'Ubah filter periode untuk melihat pengaduan yang perlu ditinjau.';
    public const BTN_TAMPILKAN_7_HARI              = 'Tampilkan 7 hari terakhir';

    // ──────────────────────────────────────────────
    //  Catatan hasil analisis (NFR-SAF-01, BR-04)
    //  Dipakai di halaman Hasil Pemeriksaan dan Status Pengaduan.
    // ──────────────────────────────────────────────
    public const CATATAN_PENDUKUNG_KEPUTUSAN = 'Hasil analisis sistem LokerLens merupakan rekomendasi pendukung keputusan berbasis kecerdasan buatan, bukan penetapan penipuan secara hukum.';

    // ──────────────────────────────────────────────
    //  Status Pengaduan (issue #11)
    //  Belum ada di SRS (Appendix C no. 7); teks dari rancangan tim.
    // ──────────────────────────────────────────────
    public const STATUS_HERO_LABEL         = 'PEMANTAUAN PENGADUAN';
    public const STATUS_HERO_JUDUL         = 'Pantau perkembangan';
    public const STATUS_HERO_JUDUL_MIRING  = 'pengaduan Anda.';
    public const STATUS_HERO_DESKRIPSI     = 'Ketahui setiap tahapan pemeriksaan dan tindak lanjut pengaduan secara transparan melalui satu halaman.';

    public const STATUS_LABEL_DAFTAR       = 'Pengaduan Saat Ini';
    public const STATUS_JUDUL_DAFTAR       = 'Pengaduan Berjalan';

    public const BELUM_ADA_PENGADUAN_BERJALAN = 'Belum ada pengaduan berjalan';
    public const BELUM_ADA_PENGADUAN_BERJALAN_KETERANGAN = 'Semua pengaduan Anda sudah selesai ditangani, atau Anda belum pernah mengajukan pengaduan.';
    public const TIDAK_ADA_PENGADUAN_FILTER = 'Tidak ada pengaduan pada kategori ini';

    /** Pilihan filter daftar pengaduan (kunci = nilai ?filter=, pengelompokan di App\Support\ProgresPengaduan). */
    public const FILTER_PENGADUAN = [
        'semua'    => 'Semua',
        'aktif'    => 'Aktif',
        'menunggu' => 'Menunggu',
    ];

    /** Teks badge kelompok pengaduan (kunci dari ProgresPengaduan::kelompokFilter, plus status akhir). */
    public const KELOMPOK_PENGADUAN = [
        'menunggu'   => 'Menunggu',
        'aktif'      => 'Aktif',
        'selesai'    => 'Selesai',
        'dihentikan' => 'Dihentikan',
    ];

    // Label pada kartu dan halaman detail
    public const SKOR_BELUM_TERSEDIA       = 'Belum tersedia';
    public const BTN_KEMBALI_STATUS        = 'Kembali ke Status Pengaduan';
    public const LABEL_SKOR_RISIKO         = 'Skor Risiko';
    public const LABEL_PEMBARUAN_TERAKHIR  = 'Pembaruan Terakhir';
    public const LABEL_SKOR_DAN_KATEGORI   = 'Skor Risiko & Kategori Hasil';
    public const LABEL_KATA_PEMICU         = 'Kata Pemicu';
    public const LABEL_PROGRES_PENANGANAN  = 'Progres Penanganan';
    public const TIDAK_ADA_KATA_PEMICU     = 'Tidak ada kata pemicu.';

    public const BTN_PERIKSA_LOWONGAN      = 'Periksa Lowongan Kerja';
    public const BTN_RIWAYAT_PENGADUAN     = 'Riwayat Pengaduan';
    public const BTN_LIHAT_HASIL_PERIKSA   = 'Lihat Hasil Pemeriksaan';
    public const BTN_PANTAU_STATUS         = 'Pantau Status Pengaduan';

    public const CATATAN_TANPA_TINDAKAN    = 'Anda tidak perlu melakukan tindakan tambahan saat ini. Perkembangan terbaru akan ditampilkan pada halaman ini.';
    public const CATATAN_PENGADUAN_DITUTUP = 'Penanganan pengaduan ini sudah berakhir. Anda tidak perlu melakukan tindakan tambahan.';

    /** Lima tahap penanganan pengaduan (nomor => judul & keterangan). Pemetaan ke status ada di App\Support\ProgresPengaduan. */
    public const TAHAP_PENGADUAN = [
        1 => ['judul' => 'Pengaduan Diajukan & Dianalisis Sistem', 'keterangan' => 'Pengaduan diterima dan lowongan diperiksa otomatis oleh sistem.'],
        2 => ['judul' => 'Ditinjau Analis',                        'keterangan' => 'Analis memeriksa ulang lowongan yang berisiko tinggi.'],
        3 => ['judul' => 'Menunggu Keputusan',                     'keterangan' => 'Kepala Bidang menilai hasil tinjauan untuk menentukan tindak lanjut.'],
        4 => ['judul' => 'Tindak Lanjut',                          'keterangan' => 'Laporan disetujui untuk ditindaklanjuti dan surat resmi sedang diproses.'],
        5 => ['judul' => 'Selesai',                                'keterangan' => 'Surat resmi telah terbit dan kasus selesai ditangani.'],
    ];

    /** Label keadaan tiap tahap di timeline. */
    public const KEADAAN_TAHAP = [
        'selesai'     => 'Selesai',
        'berlangsung' => 'Sedang berlangsung',
        'belum'       => 'Belum dimulai',
        'dihentikan'  => 'Dihentikan',
    ];

    /** Penjelasan sederhana per status (kunci = nilai StatusKasus, atau MENUNGGU_KLASIFIKASI). */
    public const PENJELASAN_STATUS = [
        'Menunggu klasifikasi'    => 'Pengaduan Anda sudah tersimpan. Pemeriksaan otomatis akan dilakukan setelah layanan kembali normal.',
        'Selesai - Risiko Rendah' => 'Sistem tidak menemukan risiko tinggi pada lowongan ini, sehingga pengaduan tidak perlu ditinjau Analis.',
        'Perlu Tinjauan'          => 'Lowongan yang Anda laporkan berisiko tinggi dan sedang menunggu giliran ditinjau Analis.',
        'Sedang Ditinjau'         => 'Analis sedang memeriksa lowongan yang Anda laporkan.',
        'Tidak Terbukti'          => 'Setelah ditinjau Analis, lowongan ini tidak terbukti mencurigakan. Pengaduan ditutup.',
        'Menunggu Keputusan'      => 'Analis mengonfirmasi lowongan ini mencurigakan. Kepala Bidang sedang menentukan tindak lanjutnya.',
        'Tindak Lanjut Disetujui' => 'Kepala Bidang memutuskan laporan Anda ditindaklanjuti. Surat resmi sedang diproses.',
        'Tidak Ditindaklanjuti'   => 'Kepala Bidang memutuskan laporan ini tidak ditindaklanjuti dan diarsipkan.',
        'Selesai'                 => 'Surat resmi sudah terbit. Penanganan pengaduan Anda selesai.',
    ];

    /** Pesan penutup timeline saat pengaduan berhenti di tengah. */
    public const PESAN_BERHENTI_RISIKO_RENDAH     = 'Lowongan ini tidak menunjukkan risiko tinggi, sehingga tidak perlu ditinjau lebih lanjut.';
    public const PESAN_BERHENTI_TIDAK_TERBUKTI    = 'Hasil tinjauan Analis: lowongan tidak terbukti mencurigakan. Pengaduan ditutup pada tahap ini.';
    public const PESAN_BERHENTI_TIDAK_DITINDAKLANJUTI = 'Kepala Bidang memutuskan laporan tidak ditindaklanjuti. Pengaduan diarsipkan pada tahap ini.';

    // ──────────────────────────────────────────────
    //  Lupa Password (issue #9)
    //  Belum ada di SRS; teks dari rancangan tim.
    // ──────────────────────────────────────────────
    public const LUPA_EMAIL_WAJIB          = 'Email wajib diisi.';
    public const LUPA_EMAIL_TIDAK_VALID    = 'Format email tidak valid. Silakan periksa kembali alamat email Anda.';
    public const LUPA_TERLALU_SERING       = 'Terlalu banyak permintaan. Silakan coba lagi beberapa saat lagi.';

    // Layar konfirmasi: netral, tidak menyebut apakah email terdaftar.
    public const LUPA_TERKIRIM_JUDUL       = 'Periksa email Anda';
    public const LUPA_TERKIRIM_PESAN       = 'Jika alamat email tersebut terdaftar di LokerLens, tautan untuk mengatur ulang kata sandi telah dikirim. Tautan berlaku selama 60 menit dan hanya dapat digunakan satu kali.';

    // Form kata sandi baru
    public const SANDI_BARU_WAJIB          = 'Kata Sandi Baru wajib diisi.';
    public const SANDI_BARU_MINIMAL        = 'Kata Sandi minimal 8 karakter.';
    public const SANDI_BARU_TIDAK_COCOK    = 'Konfirmasi Kata Sandi tidak cocok.';
    public const TAUTAN_RESET_TIDAK_VALID  = 'Tautan atur ulang kata sandi tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.';

    // Layar berhasil
    public const SANDI_BERHASIL_JUDUL      = 'Kata sandi berhasil diubah';
    public const SANDI_BERHASIL_PESAN      = 'Kata sandi Anda telah diperbarui. Silakan masuk menggunakan kata sandi baru.';

    public const BTN_KIRIM_TAUTAN_RESET    = 'Kirim Tautan Reset Kata Sandi';
    public const BTN_SIMPAN_SANDI_BARU     = 'Simpan Kata Sandi Baru';
    public const BTN_KEMBALI_LOGIN         = 'Kembali ke Login';
    public const BTN_MINTA_TAUTAN_BARU     = 'Minta Tautan Baru';

    // ──────────────────────────────────────────────
    //  Beranda & Riwayat Pengaduan (issue #25)
    //  Belum ada di SRS; teks dari rancangan tim.
    //  Riwayat hanya berisi pengaduan dengan status akhir (StatusKasus::isAkhir).
    // ──────────────────────────────────────────────
    public const BERANDA_KARTU_RIWAYAT     = 'Melihat kembali pengaduan yang sudah selesai beserta hasil pemeriksaan dan keputusan akhirnya.';

    public const RIWAYAT_HERO_DESKRIPSI    = 'Daftar pengaduan lowongan kerja mencurigakan yang sudah selesai ditangani, beserta hasil pemeriksaan dan keputusan akhirnya.';

    // Kotak angka di banner
    public const RIWAYAT_STAT_TOTAL        = 'Total Selesai';
    public const RIWAYAT_STAT_DITINDAKLANJUTI = 'Ditindaklanjuti';
    public const RIWAYAT_STAT_DITUTUP      = 'Ditutup';

    public const RIWAYAT_SEMUA_STATUS      = 'Semua Status';
    public const RIWAYAT_MENAMPILKAN_SEMUA = 'Menampilkan semua pengaduan selesai';
    public const RIWAYAT_SATUAN_PENGADUAN  = 'pengaduan';

    // Tampilan kosong
    public const BELUM_ADA_PENGADUAN_SELESAI = 'Belum ada pengaduan yang selesai';
    public const BELUM_ADA_PENGADUAN_SELESAI_KETERANGAN = 'Pengaduan yang sudah selesai ditangani akan tampil di sini. Pengaduan yang masih berjalan dapat dipantau di halaman Status Pengaduan.';

    // ──────────────────────────────────────────────
    //  Riwayat Tinjauan Analis (issue #25)
    //  Belum ada di SRS; teks dari rancangan tim.
    //  Berisi kasus yang sudah punya hasil tinjauan Analis (tabel tinjauan_analis).
    // ──────────────────────────────────────────────
    public const NAV_RIWAYAT               = 'Riwayat';

    public const RIWAYAT_TINJAUAN_JUDUL    = 'Riwayat Tinjauan Lowongan';
    public const RIWAYAT_TINJAUAN_DESKRIPSI = 'Daftar lowongan yang sudah selesai ditinjau Analis beserta hasil tinjauan dan hasil akhir kasusnya.';
    public const RIWAYAT_TINJAUAN_JUDUL_DAFTAR = 'Daftar Lowongan yang Sudah Ditinjau';
    public const RIWAYAT_TINJAUAN_KETERANGAN_DAFTAR = 'Hanya berisi kasus dengan hasil tinjauan. Kasus yang belum ditinjau ada di menu Pemantauan.';

    // Kotak angka
    public const RIWAYAT_TINJAUAN_STAT_TOTAL = 'Total Ditinjau';

    // Filter, pencarian, dan urutan
    public const RIWAYAT_TINJAUAN_LABEL_CARI = 'Pencarian';
    public const RIWAYAT_TINJAUAN_PLACEHOLDER_CARI = 'Cari nomor tiket / perusahaan / posisi';
    public const RIWAYAT_TINJAUAN_LABEL_HASIL = 'Hasil Tinjauan';
    public const RIWAYAT_TINJAUAN_SEMUA_HASIL = 'Semua Hasil';
    public const RIWAYAT_TINJAUAN_LABEL_HASIL_AKHIR = 'Hasil Akhir';
    public const RIWAYAT_TINJAUAN_SEMUA_HASIL_AKHIR = 'Semua';
    public const RIWAYAT_TINJAUAN_LABEL_URUTAN = 'Urutkan';
    public const URUTAN_TERBARU            = 'Terbaru';
    public const URUTAN_SKOR_TERTINGGI     = 'Skor Tertinggi';
    public const URUTAN_SKOR_TERENDAH      = 'Skor Terendah';

    // Hasil akhir kasus (pengelompokan status yang sudah ada, bukan status baru)
    public const HASIL_AKHIR_MENUNGGU      = 'Menunggu Keputusan';
    public const HASIL_AKHIR_DITINDAKLANJUTI = 'Ditindaklanjuti';
    public const HASIL_AKHIR_TIDAK_DITINDAKLANJUTI = 'Tidak Ditindaklanjuti';
    public const HASIL_AKHIR_TIDAK_TERBUKTI = 'Tidak Terbukti';

    // Kolom tabel
    public const KOLOM_NOMOR_TIKET         = 'Nomor Tiket';
    public const KOLOM_PERUSAHAAN          = 'Perusahaan';
    public const KOLOM_POSISI              = 'Posisi';
    public const KOLOM_ANALIS              = 'Analis Peninjau';
    public const KOLOM_TANGGAL_DITINJAU    = 'Tanggal Ditinjau';
    public const KOLOM_AKSI                = 'Aksi';

    // Tampilan kosong
    public const BELUM_ADA_TINJAUAN        = 'Belum ada lowongan yang ditinjau';
    public const BELUM_ADA_TINJAUAN_KETERANGAN = 'Lowongan yang sudah Anda tinjau di menu Pemantauan akan tampil di sini.';
    public const TINJAUAN_TIDAK_DITEMUKAN_KETERANGAN = 'Tidak ada lowongan yang cocok dengan kata kunci atau filter yang Anda pilih.';
    public const BTN_RESET_FILTER          = 'Reset Filter Pencarian';

    // Detail hanya-baca
    public const BTN_KEMBALI_RIWAYAT_TINJAUAN = '← Kembali ke Riwayat';
    public const LABEL_MODE_HANYA_BACA     = 'Hanya baca';
    public const JUDUL_HASIL_TINJAUAN      = 'Hasil Tinjauan Analis';
    public const LABEL_CATATAN_TINJAUAN    = 'Catatan Tinjauan';
    public const LABEL_STATUS_KASUS_SEKARANG = 'Status Kasus Saat Ini';

    public const JUDUL_HASIL_AKHIR_KASUS   = 'Hasil Akhir Kasus';
    public const LABEL_KEPUTUSAN           = 'Keputusan';
    public const LABEL_KEPALA_BIDANG       = 'Kepala Bidang';
    public const LABEL_TANGGAL_KEPUTUSAN   = 'Tanggal Keputusan';
    public const LABEL_CATATAN_KEPUTUSAN   = 'Catatan Keputusan';
    public const LABEL_JENIS_DOKUMEN       = 'Jenis Dokumen';
    public const LABEL_NOMOR_SURAT         = 'Nomor Surat';
    public const LABEL_TANGGAL_TERBIT      = 'Tanggal Terbit';
    public const MENUNGGU_KEPUTUSAN_KABID  = 'Menunggu keputusan Kepala Bidang';
    public const KASUS_DITUTUP_TAHAP_ANALIS = 'Kasus ditutup pada tahap tinjauan Analis';
}