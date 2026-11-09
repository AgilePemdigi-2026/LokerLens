<?php

namespace App\Http\Controllers;

use App\Constants\LokerLensCopy;
use App\Enums\HasilTinjauan;
use App\Enums\StatusKasus;
use App\Models\HasilKlasifikasi;
use App\Models\TinjauanAnalis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Fitur Meninjau Lowongan Kerja dengan Prioritas Tinggi (F-02 / UC-02 / US-02).
 * Rute: /tinjau-lowongan/ dan /tinjau-lowongan/detail/
 * Peran: Analis
 *
 * BR-05: Hanya lowongan prioritas tinggi (status_prioritas = true).
 * BR-06/BR-07 (revisi tim): hasil tinjauan "Terkonfirmasi Mencurigakan" diteruskan
 * ke Kepala Bidang, "Tidak Terbukti" ditutup oleh Analis.
 */
class TinjauLowonganController extends Controller
{
    /**
     * Daftar lowongan prioritas tinggi yang masih dalam antrean Analis.
     * NFR-PERF-01: daftar muncul ≤ 3 detik untuk 100 data.
     */
    public function index(Request $request): View
    {
        $query = HasilKlasifikasi::with([
            'pengaduan.lowonganKerja.perusahaan',
            'pengaduan.pengguna',
            'tinjauanAnalis',
        ])->where('status_prioritas', true)
          ->whereHas('pengaduan', fn ($q) => $q->whereIn('status_pengaduan', StatusKasus::tahapAnalis()))
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

        // Filter skor. Batas "Skor Tinggi" = ambang prioritas (config/lokerlens.php, default 70),
        // sama dengan tingkat risiko di halaman Hasil (HasilKlasifikasi::tingkat_risiko).
        if ($request->filled('filter_skor')) {
            $ambang = config('lokerlens.ambang_prioritas');
            $query->when($request->filter_skor === 'Skor Tinggi', function ($q) use ($ambang) {
                $q->where('confidence_score', '>=', $ambang);
            })->when($request->filter_skor === 'Skor Sedang', function ($q) use ($ambang) {
                $q->where('confidence_score', '>=', 50)->where('confidence_score', '<', $ambang);
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
     * Saat pertama kali dibuka Analis, status maju dari "Perlu Tinjauan" ke "Sedang Ditinjau".
     */
    public function detail(int $id): View
    {
        $klasifikasi = HasilKlasifikasi::with([
            'pengaduan.lowonganKerja.perusahaan',
            'pengaduan.buktiPendukung',
            'indikatorMencurigakan',
            'tinjauanAnalis.analis',
        ])->where('id_klasifikasi', $id)
          ->where('status_prioritas', true)
          ->firstOrFail();

        $pengaduan = $klasifikasi->pengaduan;
        if ($pengaduan->status_pengaduan === StatusKasus::PerluTinjauan) {
            $pengaduan->ubahStatus(StatusKasus::SedangDitinjau, 'Analis (' . Auth::user()->name . ') mulai meninjau kasus.');
        }
        $pengaduan->load('riwayatKasus');

        return view('tinjau-lowongan.detail', compact('klasifikasi'));
    }

    /**
     * Simpan hasil tinjauan Analis.
     * FR-ML-08: simpan catatan + hasil + tanggal → "Hasil Tinjauan Berhasil Disimpan".
     * BR-07 (revisi tim): "Terkonfirmasi Mencurigakan" → Menunggu Keputusan; "Tidak Terbukti" → kasus ditutup.
     */
    public function simpanTinjauan(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'catatan_tinjauan' => ['required', 'string'],
            'hasil_tinjauan'   => ['required', Rule::enum(HasilTinjauan::class)],
        ], [
            'catatan_tinjauan.required' => 'Catatan hasil audit investigasi wajib diisi.',
            'hasil_tinjauan.required'   => 'Hasil tinjauan wajib dipilih.',
        ]);

        $klasifikasi = HasilKlasifikasi::where('id_klasifikasi', $id)
            ->where('status_prioritas', true)
            ->firstOrFail();

        $hasil = HasilTinjauan::from($request->hasil_tinjauan);
        $pengaduan = $klasifikasi->pengaduan;

        DB::transaction(function () use ($request, $klasifikasi, $hasil, $pengaduan) {
            // Kasus yang belum dibuka lewat halaman detail tetap harus melewati "Sedang Ditinjau"
            if ($pengaduan->status_pengaduan === StatusKasus::PerluTinjauan) {
                $pengaduan->ubahStatus(StatusKasus::SedangDitinjau, 'Analis (' . Auth::user()->name . ') mulai meninjau kasus.');
            }

            // Ditolak jika kasus sudah tidak dalam tahap tinjauan (mis. sudah diputuskan)
            $keterangan = $hasil === HasilTinjauan::TerkonfirmasiMencurigakan
                ? 'Tinjauan Analis (' . Auth::user()->name . ') selesai: ' . $hasil->value . '. Diteruskan ke Kepala Bidang.'
                : 'Tinjauan Analis (' . Auth::user()->name . ') selesai: ' . $hasil->value . '. Kasus ditutup oleh Analis.';
            $pengaduan->ubahStatus($hasil->statusKasus(), $keterangan);

            TinjauanAnalis::updateOrCreate(
                ['id_klasifikasi' => $klasifikasi->id_klasifikasi],
                [
                    'id_pengguna'      => Auth::id(),
                    'catatan_tinjauan' => $request->catatan_tinjauan,
                    'hasil_tinjauan'   => $hasil->value,
                    'tanggal_tinjauan' => now(),
                ]
            );
        });

        return redirect()->route('tinjau-lowongan.index')
            ->with('success', LokerLensCopy::TINJAUAN_BERHASIL);
    }
}
