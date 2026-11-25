<?php

namespace App\Support;

use App\Constants\LokerLensCopy;
use App\Enums\HasilTinjauan;
use App\Enums\StatusKasus;

/**
 * SATU-SATUNYA aturan warna badge untuk Masyarakat, Analis, dan Kepala Bidang.
 * View tidak menulis warna status sendiri, tetapi memakai <x-badge-status>, <x-badge>, atau kelas ini.
 *
 * "Nada" = nama warna (mis. "kuning"). Untuk mengganti warna sebuah status,
 * cukup ubah nadanya di status(), atau kelas Tailwind-nya di GAYA.
 *
 * Warna dari Data Mockup:
 *   Perlu Tinjauan     → kuning     (Gambar 2.8, "PERLU TINJAUAN SEGERA")
 *   Sedang Ditinjau    → biru       (Gambar 2.8, "INVESTIGASI AWAL")
 *   Menunggu Keputusan → biru baja  (Gambar 2.10, 2.11, 2.14)
 *   Prioritas Tinggi   → merah, Prioritas Sedang → kuning (Gambar 2.10)
 * Status lain tidak punya warna di mockup, jadi memakai warna usulan tim.
 */
class WarnaBadge
{
    public const ABU       = 'abu';
    public const ABU_GELAP = 'abu-gelap';
    public const BIRU      = 'biru';
    public const BIRU_BAJA = 'biru-baja';
    public const KUNING    = 'kuning';
    public const HIJAU     = 'hijau';
    public const MERAH     = 'merah';

    /** Kelas Tailwind tiap nada: badge = isi badge, titik = titik timeline dan garis kartu, teks = tulisan di latar putih. */
    private const GAYA = [
        self::ABU       => ['badge' => 'bg-slate-100 text-slate-700 border-slate-300',    'titik' => 'bg-slate-300',   'teks' => 'text-slate-600'],
        self::ABU_GELAP => ['badge' => 'bg-slate-600 text-white border-slate-600',        'titik' => 'bg-slate-600',   'teks' => 'text-slate-600'],
        self::BIRU      => ['badge' => 'bg-blue-100 text-blue-800 border-blue-300',       'titik' => 'bg-blue-600',    'teks' => 'text-blue-800'],
        self::BIRU_BAJA => ['badge' => 'bg-steel-100 text-steel-600 border-steel-200',    'titik' => 'bg-steel-600',   'teks' => 'text-steel-600'],
        self::KUNING    => ['badge' => 'bg-amber-100 text-amber-900 border-amber-300',    'titik' => 'bg-risk-mid',    'teks' => 'text-risk-mid'],
        self::HIJAU     => ['badge' => 'bg-emerald-100 text-emerald-800 border-emerald-300', 'titik' => 'bg-risk-low', 'teks' => 'text-risk-low'],
        self::MERAH     => ['badge' => 'bg-red-100 text-risk-high border-red-300',        'titik' => 'bg-risk-high',   'teks' => 'text-risk-high'],
    ];

    /** Nada untuk status kasus. Teks "Menunggu klasifikasi" dan label lama di Riwayat Kasus → abu-abu. */
    public static function status(StatusKasus|string|null $status): string
    {
        if (is_string($status)) {
            $status = StatusKasus::tryFrom($status);
        }

        return match ($status) {
            StatusKasus::PerluTinjauan         => self::KUNING,
            StatusKasus::SedangDitinjau        => self::BIRU,
            StatusKasus::MenungguKeputusan     => self::BIRU_BAJA,
            StatusKasus::TindakLanjutDisetujui,
            StatusKasus::Selesai               => self::HIJAU,
            StatusKasus::SelesaiRisikoRendah,
            StatusKasus::TidakTerbukti,
            StatusKasus::TidakDitindaklanjuti  => self::ABU_GELAP,
            null                               => self::ABU,
        };
    }

    /** Nada untuk hasil tinjauan Analis. */
    public static function hasilTinjauan(HasilTinjauan|string|null $hasil): string
    {
        $nilai = $hasil instanceof HasilTinjauan ? $hasil->value : $hasil;

        return match ($nilai) {
            HasilTinjauan::TerkonfirmasiMencurigakan->value => self::MERAH,
            HasilTinjauan::TidakTerbukti->value             => self::ABU_GELAP,
            default                                         => self::ABU,
        };
    }

    /** Nada untuk tingkat risiko / prioritas: tinggi, sedang, rendah (HasilKlasifikasi::tingkat_risiko). */
    public static function risiko(string $tingkat): string
    {
        return match ($tingkat) {
            'tinggi' => self::MERAH,
            'sedang' => self::KUNING,
            default  => self::HIJAU,
        };
    }

    /** Nada untuk status penerbitan surat. */
    public static function surat(bool $terbit): string
    {
        return $terbit ? self::HIJAU : self::KUNING;
    }

    /** Kelas isi badge (latar, tulisan, garis tepi). */
    public static function badge(string $nada): string
    {
        return 'border ' . self::gaya($nada)['badge'];
    }

    /** Kelas latar untuk titik timeline dan garis atas kartu angka. */
    public static function titik(string $nada): string
    {
        return self::gaya($nada)['titik'];
    }

    /** Kelas warna tulisan di latar putih. */
    public static function teks(string $nada): string
    {
        return self::gaya($nada)['teks'];
    }

    private static function gaya(string $nada): array
    {
        return self::GAYA[$nada] ?? self::GAYA[self::ABU];
    }
}
