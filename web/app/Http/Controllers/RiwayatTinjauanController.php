<?php

namespace App\Http\Controllers;

use App\Constants\LokerLensCopy;
use App\Enums\HasilTinjauan;
use App\Enums\StatusKasus;
use App\Models\HasilKlasifikasi;
use App\Models\TinjauanAnalis;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Riwayat Tinjauan Analis (issue #25).
 * Rute: /riwayat-tinjauan (daftar) dan /riwayat-tinjauan/{id} (detail hanya-baca, id = id_klasifikasi)
 * Peran: Analis
 *
 * Hanya kasus yang SUDAH punya hasil tinjauan (tabel tinjauan_analis). Kasus yang masih
 * Perlu Tinjauan / Sedang Ditinjau tetap di Pemantauan (TinjauLowonganController).
 * Halaman ini tidak mengubah status apa pun.
 */
class RiwayatTinjauanController extends Controller
{
    /**
     * "Hasil akhir" = pengelompokan status kasus yang sudah ada (bukan status baru).
     * Tiga yang pertama juga menjadi pilihan filter "Hasil Akhir".
     */
    public const HASIL_AKHIR = [
        LokerLensCopy::HASIL_AKHIR_MENUNGGU              => [StatusKasus::MenungguKeputusan],
        LokerLensCopy::HASIL_AKHIR_DITINDAKLANJUTI       => [StatusKasus::TindakLanjutDisetujui, StatusKasus::Selesai],
        LokerLensCopy::HASIL_AKHIR_TIDAK_DITINDAKLANJUTI => [StatusKasus::TidakDitindaklanjuti],
        LokerLensCopy::HASIL_AKHIR_TIDAK_TERBUKTI        => [StatusKasus::TidakTerbukti],
    ];

    /** Pilihan filter "Hasil Akhir" (Tidak Terbukti cukup lewat filter hasil tinjauan). */
    public const PILIHAN_HASIL_AKHIR = [
        LokerLensCopy::HASIL_AKHIR_MENUNGGU,
        LokerLensCopy::HASIL_AKHIR_DITINDAKLANJUTI,
        LokerLensCopy::HASIL_AKHIR_TIDAK_DITINDAKLANJUTI,
    ];

    public const PILIHAN_URUTAN = [
        LokerLensCopy::URUTAN_TERBARU,
        LokerLensCopy::URUTAN_SKOR_TERTINGGI,
        LokerLensCopy::URUTAN_SKOR_TERENDAH,
    ];

    /** Label hasil akhir untuk satu status kasus, mis. Selesai → "Ditindaklanjuti". */
    public static function hasilAkhir(StatusKasus $status): string
    {
        foreach (self::HASIL_AKHIR as $label => $daftarStatus) {
            if (in_array($status, $daftarStatus, true)) {
                return $label;
            }
        }

        return $status->value;
    }

    /**
     * Daftar kasus yang sudah ditinjau Analis, dengan pencarian, filter, dan urutan
     * (pola sama dengan halaman Pemantauan).
     */
    public function index(Request $request): View
    {
        // Statistik dari semua kasus yang sudah ditinjau (tanpa filter)
        $jumlahPerHasil = TinjauanAnalis::whereHas('hasilKlasifikasi.pengaduan', fn ($q) => $q->whereNotIn('status_pengaduan', StatusKasus::tahapAnalis()))
            ->selectRaw('hasil_tinjauan, count(*) as jumlah')
            ->groupBy('hasil_tinjauan')
            ->pluck('jumlah', 'hasil_tinjauan');

        $statistik = [
            'total'         => (int) $jumlahPerHasil->sum(),
            'terkonfirmasi' => (int) ($jumlahPerHasil[HasilTinjauan::TerkonfirmasiMencurigakan->value] ?? 0),
            'tidakTerbukti' => (int) ($jumlahPerHasil[HasilTinjauan::TidakTerbukti->value] ?? 0),
        ];

        $query = $this->querySudahDitinjau()->with([
            'pengaduan.lowonganKerja.perusahaan',
            'tinjauanAnalis.analis',
        ]);

        // Pencarian: nomor tiket, nama perusahaan, atau posisi
        if ($request->filled('cari')) {
            $cari = trim($request->cari);
            $query->whereHas('pengaduan', function ($q) use ($cari) {
                $q->where('nomor_tiket', 'like', "%{$cari}%")
                  ->orWhereHas('lowonganKerja', function ($q2) use ($cari) {
                      $q2->where('posisi_lowongan', 'like', "%{$cari}%")
                         ->orWhereHas('perusahaan', fn ($q3) => $q3->where('nama_perusahaan', 'like', "%{$cari}%"));
                  });
            });
        }

        // Filter hasil tinjauan (nilai dari enum HasilTinjauan; nilai lain diabaikan)
        if ($hasil = HasilTinjauan::tryFrom((string) $request->filter_hasil)) {
            $query->whereHas('tinjauanAnalis', fn ($q) => $q->where('hasil_tinjauan', $hasil->value));
        }

        // Filter hasil akhir (hanya pilihan yang tersedia di dropdown)
        if (in_array($request->filter_hasil_akhir, self::PILIHAN_HASIL_AKHIR, true)) {
            $status = array_map(fn (StatusKasus $s) => $s->value, self::HASIL_AKHIR[$request->filter_hasil_akhir]);
            $query->whereHas('pengaduan', fn ($q) => $q->whereIn('status_pengaduan', $status));
        }

        // Urutan; tanpa pilihan = terbaru ditinjau
        match ($request->urutan) {
            LokerLensCopy::URUTAN_SKOR_TERTINGGI => $query->orderBy('confidence_score', 'desc'),
            LokerLensCopy::URUTAN_SKOR_TERENDAH  => $query->orderBy('confidence_score', 'asc'),
            default => $query->orderByDesc(
                TinjauanAnalis::select('tanggal_tinjauan')->whereColumn('tinjauan_analis.id_klasifikasi', 'hasil_klasifikasi.id_klasifikasi')
            ),
        };

        $daftar = $query->orderBy('hasil_klasifikasi.id_klasifikasi', 'desc')->paginate(10)->withQueryString();
        $adaFilter = $request->filled('cari') || $request->filled('filter_hasil') || $request->filled('filter_hasil_akhir');

        return view('riwayat-tinjauan.index', compact('daftar', 'statistik', 'adaFilter'));
    }

    /**
     * Detail hanya-baca: memakai view detail Analis (tinjau-lowongan.detail) dengan $modeBaca = true,
     * sehingga form tinjauan tidak tampil. Status kasus tidak diubah.
     */
    public function detail(int $id): View
    {
        $klasifikasi = $this->querySudahDitinjau()->with([
            'pengaduan.lowonganKerja.perusahaan',
            'pengaduan.tindakLanjutKasus.kepalaBidang',
            'pengaduan.tindakLanjutKasus.suratTeguranLaporanPolisi',
            'indikatorMencurigakan',
            'tinjauanAnalis.analis',
        ])->where('hasil_klasifikasi.id_klasifikasi', $id)->firstOrFail();

        return view('tinjau-lowongan.detail', [
            'klasifikasi' => $klasifikasi,
            'modeBaca'    => true,
        ]);
    }

    /**
     * Kasus yang sudah punya hasil tinjauan dan statusnya sudah lewat tahap Analis.
     */
    private function querySudahDitinjau()
    {
        return HasilKlasifikasi::query()
            ->select('hasil_klasifikasi.*')
            ->whereHas('tinjauanAnalis')
            ->whereHas('pengaduan', fn ($q) => $q->whereNotIn('status_pengaduan', StatusKasus::tahapAnalis()));
    }
}
