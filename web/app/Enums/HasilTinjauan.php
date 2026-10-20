<?php

namespace App\Enums;

/**
 * Hasil tinjauan Analis (keputusan tim, berbeda dengan SRS BR-06/BR-07).
 * Hanya "Terkonfirmasi Mencurigakan" yang diteruskan ke Kepala Bidang.
 */
enum HasilTinjauan: string
{
    case TerkonfirmasiMencurigakan = 'Terkonfirmasi Mencurigakan';
    case TidakTerbukti             = 'Tidak Terbukti';

    /**
     * Status kasus setelah tinjauan disimpan.
     */
    public function statusKasus(): StatusKasus
    {
        return match ($this) {
            self::TerkonfirmasiMencurigakan => StatusKasus::MenungguKeputusan,
            self::TidakTerbukti             => StatusKasus::TidakTerbukti,
        };
    }
}
