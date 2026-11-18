<?php

namespace App\Http\Controllers;

use App\Constants\LokerLensCopy;
use App\Models\SuratTeguranLaporanPolisi;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daftar Surat Teguran/Laporan Polisi (issue #8, bagian dari F-04 / US-04).
 * Rute: /surat
 * Peran: Kepala Bidang
 *
 * Berisi semua surat dari tabel surat_teguran_laporan_polisi, baik draf maupun yang sudah terbit.
 * Draf dilanjutkan di halaman Buat Surat, surat terbit dibuka di halaman surat terbit.
 * Halaman ini hanya menampilkan data (tidak ada tabel, kolom, atau status baru).
 */
class SuratController extends Controller
{
    /** Pilihan filter jenis dokumen (sama dengan enum kolom jenis_dokumen). */
    public const PILIHAN_JENIS = ['Surat Teguran', 'Laporan Polisi'];

    /** Pilihan filter status: nilai di database => label di layar. */
    public const PILIHAN_STATUS = [
        'Draft'  => LokerLensCopy::SURAT_STATUS_DRAF,
        'Terbit' => LokerLensCopy::SURAT_STATUS_TERBIT,
    ];

    public const PILIHAN_URUTAN = [
        LokerLensCopy::URUTAN_TERBARU,
        LokerLensCopy::URUTAN_TERLAMA,
    ];

    /**
     * Tanggal surat untuk ditampilkan dan diurutkan:
     * surat terbit memakai tanggal terbit, draf memakai waktu terakhir disimpan (updated_at).
     */
    private const KOLOM_TANGGAL = 'COALESCE(tanggal_terbit, updated_at)';

    public function index(Request $request): View
    {
        // Kotak angka dari semua surat (tanpa filter)
        $jumlahPerStatus = SuratTeguranLaporanPolisi::selectRaw('status_penerbitan, count(*) as jumlah')
            ->groupBy('status_penerbitan')
            ->pluck('jumlah', 'status_penerbitan');

        $statistik = [
            'total'  => (int) $jumlahPerStatus->sum(),
            'draf'   => (int) ($jumlahPerStatus['Draft'] ?? 0),
            'terbit' => (int) ($jumlahPerStatus['Terbit'] ?? 0),
        ];

        $query = SuratTeguranLaporanPolisi::with('tindakLanjutKasus.pengaduan.lowonganKerja.perusahaan');

        // Pencarian: nomor surat, nomor tiket, atau nama perusahaan
        if ($request->filled('cari')) {
            $cari = trim($request->cari);
            $query->where(function ($q) use ($cari) {
                $q->where('nomor_surat', 'like', "%{$cari}%")
                  ->orWhereHas('tindakLanjutKasus.pengaduan', function ($q2) use ($cari) {
                      $q2->where('nomor_tiket', 'like', "%{$cari}%")
                         ->orWhereHas('lowonganKerja.perusahaan', fn ($q3) => $q3->where('nama_perusahaan', 'like', "%{$cari}%"));
                  });
            });
        }

        // Filter jenis dokumen dan status (nilai lain diabaikan)
        if (in_array($request->jenis_dokumen, self::PILIHAN_JENIS, true)) {
            $query->where('jenis_dokumen', $request->jenis_dokumen);
        }
        if (array_key_exists((string) $request->status, self::PILIHAN_STATUS)) {
            $query->where('status_penerbitan', $request->status);
        }

        // Urutan; tanpa pilihan = terbaru
        $arah = $request->urutan === LokerLensCopy::URUTAN_TERLAMA ? 'asc' : 'desc';
        $surat = $query->orderByRaw(self::KOLOM_TANGGAL . ' ' . $arah)
            ->orderBy('id_surat', $arah)
            ->paginate(10)
            ->withQueryString();

        $adaFilter = $request->filled('cari') || $request->filled('jenis_dokumen') || $request->filled('status');

        return view('surat.index', compact('surat', 'statistik', 'adaFilter'));
    }
}
