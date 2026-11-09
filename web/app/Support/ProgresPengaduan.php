<?php

namespace App\Support;

use App\Constants\LokerLensCopy;
use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Progres penanganan pengaduan untuk Masyarakat (Status Pengaduan #11, dipakai juga Riwayat #12).
 * Satu-satunya tempat pemetaan status kasus (App\Enums\StatusKasus) ke 5 tahap tampilan:
 *
 *   1. Pengaduan Diajukan & Dianalisis Sistem  (belum ada hasil klasifikasi → "Menunggu klasifikasi")
 *   2. Ditinjau Analis                         (Perlu Tinjauan, Sedang Ditinjau)
 *   3. Menunggu Keputusan                      (Menunggu Keputusan)
 *   4. Tindak Lanjut                           (Tindak Lanjut Disetujui, surat diproses)
 *   5. Selesai                                 (Selesai, surat terbit)
 *
 * Berhenti di tengah (tahap sesudahnya tidak ditampilkan):
 *   Selesai - Risiko Rendah → tahap 1, Tidak Terbukti → tahap 2, Tidak Ditindaklanjuti → tahap 3.
 *
 * Kelas ini juga berisi pengelompokan filter daftar (Semua / Aktif / Menunggu) dan format tanggal.
 *
 * Membutuhkan relasi hasilKlasifikasi dan riwayatKasus (sebaiknya sudah di-eager load).
 */
class ProgresPengaduan
{
    // Keadaan tiap tahap (kunci LokerLensCopy::KEADAAN_TAHAP)
    public const SELESAI     = 'selesai';
    public const BERLANGSUNG = 'berlangsung';
    public const BELUM       = 'belum';
    public const DIHENTIKAN  = 'dihentikan';

    // Pilihan filter daftar pengaduan (kunci LokerLensCopy::FILTER_PENGADUAN)
    public const FILTER_SEMUA    = 'semua';
    public const FILTER_AKTIF    = 'aktif';
    public const FILTER_MENUNGGU = 'menunggu';

    // Kelompok untuk pengaduan yang sudah selesai (tidak masuk filter)
    public const KELOMPOK_SELESAI    = 'selesai';
    public const KELOMPOK_DIHENTIKAN = 'dihentikan';

    /** Warna badge kelompok: Menunggu kuning, Aktif teal, Selesai abu-abu, Dihentikan merah. */
    private const WARNA_KELOMPOK = [
        self::FILTER_MENUNGGU     => 'bg-amber-100 text-amber-800 border border-amber-200',
        self::FILTER_AKTIF        => 'bg-teal-600 text-white border border-teal-600',
        self::KELOMPOK_SELESAI    => 'bg-gray-100 text-gray-600 border border-gray-200',
        self::KELOMPOK_DIHENTIKAN => 'bg-red-100 text-risk-high border border-red-200',
    ];

    /** Status di Riwayat Kasus yang menjadi tanggal tiap tahap (tahap 1 memakai tanggal pengaduan). */
    private const STATUS_PER_TAHAP = [
        1 => [StatusKasus::SelesaiRisikoRendah],
        2 => [StatusKasus::PerluTinjauan, StatusKasus::SedangDitinjau, StatusKasus::TidakTerbukti],
        3 => [StatusKasus::MenungguKeputusan, StatusKasus::TidakDitindaklanjuti],
        4 => [StatusKasus::TindakLanjutDisetujui],
        5 => [StatusKasus::Selesai],
    ];

    /**
     * @param  array<int, array{nomor: int, judul: string, keterangan: string, keadaan: string, label: string, tanggal: ?Carbon}>  $tahap
     */
    public function __construct(
        public readonly array $tahap,            // tahap yang ditampilkan di timeline
        public readonly int $tahapAktif,         // nomor tahap saat ini (1–5)
        public readonly string $labelStatus,     // nama status lengkap, mis. "Perlu Tinjauan"
        public readonly string $kelompok,        // menunggu | aktif | selesai | dihentikan (kunci LokerLensCopy::KELOMPOK_PENGADUAN)
        public readonly string $labelKelompok,   // teks badge, mis. "Menunggu"
        public readonly string $warnaKelompok,   // kelas Tailwind badge kelompok
        public readonly string $penjelasan,      // penjelasan status dalam bahasa sederhana
        public readonly ?string $pesanBerhenti,  // diisi jika pengaduan berhenti di tengah
        public readonly bool $ditutup,           // true jika status sudah akhir
        public readonly Carbon $pembaruanTerakhir,
    ) {}

    public static function dari(Pengaduan $pengaduan): self
    {
        $status              = $pengaduan->status_pengaduan;
        $menungguKlasifikasi = $pengaduan->hasilKlasifikasi === null;

        // Tentukan tahap aktif, keadaannya, dan apakah berhenti di tahap itu
        if ($menungguKlasifikasi) {
            [$tahapAktif, $keadaanAktif, $pesanBerhenti] = [1, self::BERLANGSUNG, null];
        } else {
            [$tahapAktif, $keadaanAktif, $pesanBerhenti] = match ($status) {
                StatusKasus::SelesaiRisikoRendah   => [1, self::SELESAI, LokerLensCopy::PESAN_BERHENTI_RISIKO_RENDAH],
                StatusKasus::PerluTinjauan,
                StatusKasus::SedangDitinjau        => [2, self::BERLANGSUNG, null],
                StatusKasus::TidakTerbukti         => [2, self::DIHENTIKAN, LokerLensCopy::PESAN_BERHENTI_TIDAK_TERBUKTI],
                StatusKasus::MenungguKeputusan     => [3, self::BERLANGSUNG, null],
                StatusKasus::TidakDitindaklanjuti  => [3, self::DIHENTIKAN, LokerLensCopy::PESAN_BERHENTI_TIDAK_DITINDAKLANJUTI],
                StatusKasus::TindakLanjutDisetujui => [4, self::BERLANGSUNG, null],
                StatusKasus::Selesai               => [5, self::SELESAI, null],
            };
        }

        // Susun tahap 1–5. Jika berhenti, tahap sesudah tahap aktif tidak ditampilkan.
        $tahap = [];
        foreach (LokerLensCopy::TAHAP_PENGADUAN as $nomor => $teks) {
            if ($nomor > $tahapAktif && $pesanBerhenti !== null) {
                break;
            }

            $keadaan = match (true) {
                $nomor < $tahapAktif   => self::SELESAI,
                $nomor === $tahapAktif => $keadaanAktif,
                default                => self::BELUM,
            };

            $tahap[] = [
                'nomor'      => $nomor,
                'judul'      => $teks['judul'],
                'keterangan' => $teks['keterangan'],
                'keadaan'    => $keadaan,
                'label'      => $nomor === 1 && $menungguKlasifikasi
                    ? LokerLensCopy::MENUNGGU_KLASIFIKASI
                    : LokerLensCopy::KEADAAN_TAHAP[$keadaan],
                'tanggal'    => $keadaan === self::BELUM ? null : self::tanggalTahap($pengaduan, $nomor),
            ];
        }

        $labelStatus = $menungguKlasifikasi ? LokerLensCopy::MENUNGGU_KLASIFIKASI : $status->value;

        // Badge mengikuti kelompok filter (Menunggu / Aktif). Pengaduan yang sudah
        // selesai: "Dihentikan" jika berhenti di tengah oleh petugas, selain itu "Selesai".
        $kelompok = self::kelompokFilter($pengaduan)
            ?? ($keadaanAktif === self::DIHENTIKAN ? self::KELOMPOK_DIHENTIKAN : self::KELOMPOK_SELESAI);

        return new self(
            tahap: $tahap,
            tahapAktif: $tahapAktif,
            labelStatus: $labelStatus,
            kelompok: $kelompok,
            labelKelompok: LokerLensCopy::KELOMPOK_PENGADUAN[$kelompok],
            warnaKelompok: self::WARNA_KELOMPOK[$kelompok],
            penjelasan: LokerLensCopy::PENJELASAN_STATUS[$labelStatus],
            pesanBerhenti: $pesanBerhenti,
            ditutup: $status->isAkhir(),
            pembaruanTerakhir: $pengaduan->riwayatKasus->sortByDesc('tanggal_update')->first()?->tanggal_update
                ?? $pengaduan->tanggal_pengaduan,
        );
    }

    // ─── Filter daftar pengaduan yang belum selesai ──────────────────────

    /**
     * Kelompok filter (kunci LokerLensCopy::FILTER_PENGADUAN):
     *   menunggu = belum diproses petugas: Menunggu klasifikasi, Perlu Tinjauan
     *   aktif    = sudah mulai diproses petugas: Sedang Ditinjau, Menunggu Keputusan, Tindak Lanjut Disetujui
     * Setelah Analis mulai meninjau, pengaduan tidak pernah kembali ke "menunggu".
     * Pengaduan yang sudah selesai (status akhir) tidak masuk kelompok mana pun (null).
     */
    public static function kelompokFilter(Pengaduan $pengaduan): ?string
    {
        if ($pengaduan->status_pengaduan->isAkhir()) {
            return null;
        }

        // Belum ada hasil klasifikasi (API ML mati) = belum diproses petugas
        if ($pengaduan->hasilKlasifikasi === null) {
            return self::FILTER_MENUNGGU;
        }

        return match ($pengaduan->status_pengaduan) {
            StatusKasus::PerluTinjauan         => self::FILTER_MENUNGGU,
            StatusKasus::SedangDitinjau,
            StatusKasus::MenungguKeputusan,
            StatusKasus::TindakLanjutDisetujui => self::FILTER_AKTIF,
            default                            => null,
        };
    }

    /** Nilai ?filter= dari URL; selain "aktif"/"menunggu" dianggap "semua". */
    public static function filterValid(?string $filter): string
    {
        return in_array($filter, [self::FILTER_AKTIF, self::FILTER_MENUNGGU], true) ? $filter : self::FILTER_SEMUA;
    }

    /** Saring daftar pengaduan yang belum selesai sesuai filter. */
    public static function saring(Collection $daftar, string $filter): Collection
    {
        if ($filter === self::FILTER_SEMUA) {
            return $daftar;
        }

        return $daftar->filter(fn (Pengaduan $pengaduan) => self::kelompokFilter($pengaduan) === $filter)->values();
    }

    /**
     * Jumlah pengaduan di tiap pilihan filter, mis. ['semua' => 7, 'aktif' => 3, 'menunggu' => 4].
     *
     * @return array<string, int>
     */
    public static function jumlahPerFilter(Collection $daftar): array
    {
        return [
            self::FILTER_SEMUA    => $daftar->count(),
            self::FILTER_AKTIF    => self::saring($daftar, self::FILTER_AKTIF)->count(),
            self::FILTER_MENUNGGU => self::saring($daftar, self::FILTER_MENUNGGU)->count(),
        ];
    }

    /**
     * Format tanggal berbahasa Indonesia, mis. "27 Okt 2026, 21:18 WIB".
     * Locale dipasang di sini karena APP_LOCALE aplikasi masih "en".
     */
    public static function formatTanggal(?CarbonInterface $tanggal): string
    {
        return $tanggal ? $tanggal->copy()->locale('id')->translatedFormat('d M Y, H:i') . ' WIB' : '—';
    }

    /**
     * Tanggal tahap = catatan Riwayat Kasus TERAKHIR yang statusnya termasuk tahap itu.
     * Tahap 1 memakai tanggal pengaduan jika tidak ada catatan. Label status lama di riwayat diabaikan.
     */
    private static function tanggalTahap(Pengaduan $pengaduan, int $nomor): ?Carbon
    {
        $statusTahap = array_map(fn (StatusKasus $s) => $s->value, self::STATUS_PER_TAHAP[$nomor]);

        $tanggal = $pengaduan->riwayatKasus
            ->filter(fn ($riwayat) => in_array($riwayat->status_kasus, $statusTahap, true))
            ->sortByDesc('tanggal_update')
            ->first()?->tanggal_update;

        return $tanggal ?? ($nomor === 1 ? $pengaduan->tanggal_pengaduan : null);
    }
}
