<?php

namespace App\Http\Controllers;

use App\Constants\LokerLensCopy;
use App\Models\HasilKlasifikasi;
use App\Models\Pengaduan;
use App\Models\RiwayatKasus;
use App\Models\TinjauanAnalis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Fitur Meninjau Lowongan Kerja dengan Prioritas Tinggi (F-02 / UC-02 / US-02).
 * Rute: /tinjau-lowongan/ dan /tinjau-lowongan/detail/
 * Peran: Analis
 *
 * BR-05: Hanya lowongan prioritas tinggi (status_prioritas = true).
 * BR-06: Analis hanya beri catatan & hasil tinjauan, bukan keputusan akhir.
 */
class TinjauLowonganController extends Controller
{
    /**
     * Daftar lowongan prioritas tinggi dengan pencarian dan filter.
     * NFR-PERF-01: daftar muncul ≤ 3 detik untuk 100 data.
     */
    public function index(Request $request): View
    {
        $query = HasilKlasifikasi::with([
            'pengaduan.lowonganKerja.perusahaan',
            'pengaduan.pengguna',
            'tinjauanAnalis',
        ])->where('status_prioritas', true)
          ->orderBy('confidence_score', 'desc');

        // Pencarian berdasarkan nomor_tiket / perusahaan
        if ($request->filled('nomor_tiket')) {
            $cari = $request->nomor_tiket;
            $query->whereHas('pengaduan', function ($q) use ($cari) {
                $q->where('nomor_tiket', 'like', "%{$cari}%")
                  ->orWhereHas('lowonganKerja.perusahaan', function ($q2) use ($cari) {
                      $q2->where('nama_perusahaan', 'like', "%{$cari}%");
                  });
            });
        }

        // Filter skor
        if ($request->filled('filter_skor')) {
            $query->when($request->filter_skor === 'Skor Tinggi', function ($q) {
                $q->where('confidence_score', '>=', 75);
            })->when($request->filter_skor === 'Skor Sedang', function ($q) {
                $q->whereBetween('confidence_score', [50, 74.99]);
            });
        }

        // Urutan
        if ($request->filled('urutan')) {
            $query->when($request->urutan === 'Skor Tertinggi', function ($q) {
                $q->orderBy('confidence_score', 'desc');
            })->when($request->urutan === 'Skor Terendah', function ($q) {
                $q->orderBy('confidence_score', 'asc');
            })->when($request->urutan === 'Terbaru', function ($q) {
                $q->orderBy('tanggal_klasifikasi', 'desc');
            });
        }

        $hasilKlasifikasi = $query->paginate(10)->withQueryString();
        $adaFilter = $request->filled('nomor_tiket') || $request->filled('filter_skor') || $request->filled('urutan');

        return view('tinjau-lowongan.index', compact('hasilKlasifikasi', 'adaFilter'));
    }

    /**
     * Detail satu lowongan prioritas tinggi.
     * FR-ML-08/09: tampil teks "Detail Lowongan Terpilih", "Confidence Score", "Indikator Mencurigakan".
     */
    public function detail(int $id): View
    {
        $klasifikasi = HasilKlasifikasi::with([
            'pengaduan.lowonganKerja.perusahaan',
            'pengaduan.buktiPendukung',
            'pengaduan.riwayatKasus',
            'indikatorMencurigakan',
            'tinjauanAnalis.analis',
        ])->where('id_klasifikasi', $id)
          ->where('status_prioritas', true)
          ->firstOrFail();

        return view('tinjau-lowongan.detail', compact('klasifikasi'));
    }

    /**
     * Simpan hasil tinjauan Analis.
     * FR-ML-08/09: simpan catatan + hasil + tanggal → "Hasil Tinjauan Berhasil Disimpan".
     * BR-07: setelah selesai → diteruskan ke Kepala Bidang (status = Menunggu Keputusan).
     */
    public function simpanTinjauan(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'catatan_tinjauan' => ['required', 'string'],
            'hasil_tinjauan'   => ['required', 'string'],
        ], [
            'catatan_tinjauan.required' => 'Catatan hasil audit investigasi wajib diisi.',
            'hasil_tinjauan.required'   => 'Hasil tinjauan wajib dipilih.',
        ]);

        $klasifikasi = HasilKlasifikasi::where('id_klasifikasi', $id)
            ->where('status_prioritas', true)
            ->firstOrFail();

        // Buat atau update tinjauan analis
        TinjauanAnalis::updateOrCreate(
            ['id_klasifikasi' => $klasifikasi->id_klasifikasi],
            [
                'id_pengguna'      => Auth::id(),
                'catatan_tinjauan' => $request->catatan_tinjauan,
                'hasil_tinjauan'   => $request->hasil_tinjauan,
                'tanggal_tinjauan' => now(),
            ]
        );

        // Update status pengaduan → Menunggu Keputusan (BR-07)
        $pengaduan = $klasifikasi->pengaduan;
        $pengaduan->update(['status_pengaduan' => LokerLensCopy::STATUS_MENUNGGU_KEPUTUSAN]);

        // Catat riwayat kasus (BR-11)
        RiwayatKasus::create([
            'id_pengaduan' => $pengaduan->id_pengaduan,
            'status_kasus' => LokerLensCopy::STATUS_MENUNGGU_KEPUTUSAN,
            'keterangan'   => 'Tinjauan Analis (' . Auth::user()->name . ') selesai: ' . $request->hasil_tinjauan . '. Diteruskan ke Kepala Bidang.',
        ]);

        return redirect()->route('tinjau-lowongan.index')
            ->with('success', LokerLensCopy::TINJAUAN_BERHASIL);
    }
}
