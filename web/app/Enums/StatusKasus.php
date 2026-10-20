<?php

namespace App\Enums;

/**
 * Status resmi kasus (pengaduan) LokerLens — satu-satunya sumber daftar status.
 * Alur sesuai keputusan tim (docs/requirements.md §5):
 *
 *   Selesai - Risiko Rendah                      (akhir, skor di bawah ambang)
 *   Perlu Tinjauan → Sedang Ditinjau → Tidak Terbukti            (akhir)
 *                                    → Menunggu Keputusan → Tindak Lanjut Disetujui → Selesai (akhir)
 *                                                         → Tidak Ditindaklanjuti            (akhir)
 *
 * Nilai enum sama dengan nilai kolom pengaduan.status_pengaduan.
 */
enum StatusKasus: string
{
    case SelesaiRisikoRendah   = 'Selesai - Risiko Rendah';
    case PerluTinjauan         = 'Perlu Tinjauan';
    case SedangDitinjau        = 'Sedang Ditinjau';
    case TidakTerbukti         = 'Tidak Terbukti';
    case MenungguKeputusan     = 'Menunggu Keputusan';
    case TindakLanjutDisetujui = 'Tindak Lanjut Disetujui';
    case TidakDitindaklanjuti  = 'Tidak Ditindaklanjuti';
    case Selesai               = 'Selesai';

    /**
     * Status yang boleh dituju dari status ini (hanya maju sesuai urutan).
     *
     * @return array<int, self>
     */
    public function statusBerikutnya(): array
    {
        return match ($this) {
            self::PerluTinjauan         => [self::SedangDitinjau],
            self::SedangDitinjau        => [self::TidakTerbukti, self::MenungguKeputusan],
            self::MenungguKeputusan     => [self::TindakLanjutDisetujui, self::TidakDitindaklanjuti],
            self::TindakLanjutDisetujui => [self::Selesai],
            self::SelesaiRisikoRendah,
            self::TidakTerbukti,
            self::TidakDitindaklanjuti,
            self::Selesai               => [],
        };
    }

    public function bolehMenjadi(self $tujuan): bool
    {
        return in_array($tujuan, $this->statusBerikutnya(), true);
    }

    /**
     * Status akhir tidak bisa diubah lagi.
     */
    public function isAkhir(): bool
    {
        return $this->statusBerikutnya() === [];
    }

    /**
     * Status awal kasus baru berdasarkan probabilitas penipuan (0–100).
     */
    public static function awalDariSkor(float $skor): self
    {
        return $skor >= config('lokerlens.ambang_prioritas')
            ? self::PerluTinjauan
            : self::SelesaiRisikoRendah;
    }

    /**
     * Status kasus yang masih dalam antrean Analis.
     *
     * @return array<int, self>
     */
    public static function tahapAnalis(): array
    {
        return [self::PerluTinjauan, self::SedangDitinjau];
    }

    /**
     * Status kasus yang sudah diteruskan ke Kepala Bidang.
     *
     * @return array<int, self>
     */
    public static function tahapKepalaBidang(): array
    {
        return [self::MenungguKeputusan, self::TindakLanjutDisetujui, self::TidakDitindaklanjuti, self::Selesai];
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
