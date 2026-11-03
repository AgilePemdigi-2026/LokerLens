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
}
