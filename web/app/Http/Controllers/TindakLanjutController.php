<?php

namespace App\Http\Controllers;

use App\Constants\LokerLensCopy;
use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use App\Models\TindakLanjutKasus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Fitur Menindaklanjuti Laporan Lowongan Kerja yang Mencurigakan (F-03 / UC-03 / US-03).
 * Rute: /tindak-lanjut/
 * Peran: Kepala Bidang
 *
 * BR-01: Hanya Kepala Bidang yang memutuskan tindak lanjut.
 * BR-02: Keputusan hanya dua: Tindaklanjuti / Tidak Ditindaklanjuti.
 */
class TindakLanjutController extends Controller
{
    /**
     * Daftar laporan yang menunggu keputusan.
     */
    public function index(Request $request): View
    {
        $query = Pengaduan::with([
            'lowonganKerja.perusahaan',
            'pengguna',
            'hasilKlasifikasi.tinjauanAnalis',
            'tindakLanjutKasus',
        ]);

        // Filter berdasarkan status (§6.5.1) — hanya status tahap Kepala Bidang
        $status = StatusKasus::tryFrom((string) $request->status);
        if ($status && in_array($status, StatusKasus::tahapKepalaBidang(), true)) {
            $query->where('status_pengaduan', $status);
        } else {
            $query->whereIn('status_pengaduan', [StatusKasus::MenungguKeputusan, StatusKasus::TindakLanjutDisetujui]);
        }

        // Filter berdasarkan prioritas (§6.5.1: via confidence_score).
        // Batas tinggi = ambang prioritas (config/lokerlens.php, default 70), sama dengan
        // tingkat risiko di halaman Hasil (HasilKlasifikasi::tingkat_risiko).
        $ambang = config('lokerlens.ambang_prioritas');
        if ($request->filled('prioritas')) {
            $query->whereHas('hasilKlasifikasi', function ($q) use ($request, $ambang) {
                if ($request->prioritas === 'tinggi') {
                    $q->where('confidence_score', '>=', $ambang);
                } elseif ($request->prioritas === 'sedang') {
                    $q->where('confidence_score', '>=', 50)
                      ->where('confidence_score', '<', $ambang);
                }
            });
        }

        // Pencarian (§6.5.1)
        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('nomor_tiket', 'like', "%{$cari}%")
                  ->orWhereHas('lowonganKerja', function ($q2) use ($cari) {
                      $q2->where('posisi_lowongan', 'like', "%{$cari}%");
                  })
                  ->orWhereHas('lowonganKerja.perusahaan', function ($q3) use ($cari) {
                      $q3->where('nama_perusahaan', 'like', "%{$cari}%");
                  });
            });
        }

        $query->orderBy('tanggal_pengaduan', 'desc');

        $laporan = $query->paginate(10)->withQueryString();

        $jumlahMenunggu = Pengaduan::where('status_pengaduan', StatusKasus::MenungguKeputusan)->count();

        // Rincian prioritas (§6.5.1: "2 Prioritas tinggi · 1 Prioritas sedang")
        $jumlahPrioritasTinggi = Pengaduan::where('status_pengaduan', StatusKasus::MenungguKeputusan)
            ->whereHas('hasilKlasifikasi', fn ($q) => $q->where('confidence_score', '>=', $ambang))
            ->count();
        $jumlahPrioritasSedang = $jumlahMenunggu - $jumlahPrioritasTinggi;

        return view('tindak-lanjut.index', compact(
            'laporan', 'jumlahMenunggu', 'jumlahPrioritasTinggi', 'jumlahPrioritasSedang'
        ));
    }

    /**
     * Detail laporan — untuk Kepala Bidang ambil keputusan.
     * Wajib memuat: "Detail Laporan Lowongan Mencurigakan", "Informasi Lowongan",
     * "Ringkasan Laporan", "Terindikasi Anomali Tinggi" (BDD).
     */
    public function detail(int $id): View
    {
        $pengaduan = Pengaduan::with([
            'lowonganKerja.perusahaan',
            'pengguna',
            'hasilKlasifikasi.indikatorMencurigakan',
            'hasilKlasifikasi.tinjauanAnalis.analis',
            'buktiPendukung',
            'riwayatKasus',
            'tindakLanjutKasus',
        ])->whereIn('status_pengaduan', StatusKasus::tahapKepalaBidang())
          ->findOrFail($id);

        return view('tindak-lanjut.detail', compact('pengaduan'));
    }

    /**
     * Simpan keputusan akhir Kepala Bidang.
     * BR-03: "Tidak Ditindaklanjuti" → ditandai & diarsipkan.
     * FR-ST-02: "Tindaklanjuti" → buka akses /buat-surat/.
     * Hanya kasus "Menunggu Keputusan" yang bisa diputuskan, dan hanya sekali.
     */
    public function simpanKeputusan(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'keputusan'         => ['required', 'in:Tindaklanjuti,Tidak Ditindaklanjuti'],
            'catatan_keputusan' => ['nullable', 'string'],
        ]);

        $pengaduan = Pengaduan::findOrFail($id);

        DB::transaction(function () use ($request, $pengaduan) {
            if ($request->keputusan === 'Tindaklanjuti') {
                $pengaduan->ubahStatus(StatusKasus::TindakLanjutDisetujui, 'Keputusan Kepala Bidang: Tindaklanjuti. Kasus dilanjutkan ke tahap pembuatan surat.');
            } else {
                // BR-03: ditandai tidak dilanjuti dan diarsipkan
                $pengaduan->ubahStatus(StatusKasus::TidakDitindaklanjuti, LokerLensCopy::KASUS_DIARSIPKAN);
            }

            TindakLanjutKasus::create([
                'id_pengaduan'     => $pengaduan->id_pengaduan,
                'id_pengguna'      => Auth::id(),
                'keputusan'        => $request->keputusan,
                'catatan_keputusan' => $request->catatan_keputusan,
                'tanggal_keputusan' => now(),
            ]);
        });

        return redirect()->route('tindak-lanjut.hasil', [
            'id'        => $id,
            'keputusan' => $request->keputusan,
        ]);
    }

    /**
     * Halaman hasil setelah keputusan disimpan.
     */
    public function hasil(int $id): View
    {
        $pengaduan = Pengaduan::with([
            'lowonganKerja.perusahaan',
            'tindakLanjutKasus',
            'riwayatKasus',
        ])->whereHas('tindakLanjutKasus')
          ->findOrFail($id);

        return view('tindak-lanjut.hasil', compact('pengaduan'));
    }
}
