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
}
