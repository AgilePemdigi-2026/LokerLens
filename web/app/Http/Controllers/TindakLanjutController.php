<?php

namespace App\Http\Controllers;

use App\Constants\LokerLensCopy;
use App\Models\Pengaduan;
use App\Models\RiwayatKasus;
use App\Models\TindakLanjutKasus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Filter berdasarkan status (§6.5.1)
        if ($request->filled('status')) {
            $query->where('status_pengaduan', $request->status);
        } else {
            $query->where(function ($q) {
                $q->where('status_pengaduan', 'Menunggu Keputusan')
                  ->orWhere('status_pengaduan', 'Tindak Lanjut Disetujui');
            });
        }

        // Filter berdasarkan prioritas (§6.5.1: via confidence_score)
        if ($request->filled('prioritas')) {
            $query->whereHas('hasilKlasifikasi', function ($q) use ($request) {
                if ($request->prioritas === 'tinggi') {
                    $q->where('confidence_score', '>=', 75);
                } elseif ($request->prioritas === 'sedang') {
                    $q->where('confidence_score', '>=', 50)
                      ->where('confidence_score', '<', 75);
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

        $jumlahMenunggu = Pengaduan::where('status_pengaduan', 'Menunggu Keputusan')->count();

        // Rincian prioritas (§6.5.1: "2 Prioritas tinggi · 1 Prioritas sedang")
        $jumlahPrioritasTinggi = Pengaduan::where('status_pengaduan', 'Menunggu Keputusan')
            ->whereHas('hasilKlasifikasi', fn ($q) => $q->where('confidence_score', '>=', 75))
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
        ])->findOrFail($id);

        return view('tindak-lanjut.detail', compact('pengaduan'));
    }

    /**
     * Tampilkan pop-up konfirmasi sebelum keputusan disimpan.
     */
    public function konfirmasi(Request $request, int $id): View
    {
        $request->validate([
            'keputusan'         => ['required', 'in:Tindaklanjuti,Tidak Ditindaklanjuti'],
            'catatan_keputusan' => ['nullable', 'string'],
        ]);

        $pengaduan = Pengaduan::with(['lowonganKerja.perusahaan', 'hasilKlasifikasi'])
            ->findOrFail($id);

        return view('tindak-lanjut.konfirmasi', [
            'pengaduan'         => $pengaduan,
            'keputusan'         => $request->keputusan,
            'catatan_keputusan' => $request->catatan_keputusan,
        ]);
    }

    /**
     * Simpan keputusan akhir Kepala Bidang.
     * BR-03: "Tidak Ditindaklanjuti" → ditandai & diarsipkan.
     * FR-ST-02: "Tindaklanjuti" → buka akses /buat-surat/.
     */
    public function simpanKeputusan(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'keputusan'         => ['required', 'in:Tindaklanjuti,Tidak Ditindaklanjuti'],
            'catatan_keputusan' => ['nullable', 'string'],
        ]);

        $pengaduan = Pengaduan::findOrFail($id);

        // Cegah keputusan ganda
        if ($pengaduan->tindakLanjutKasus()->exists()) {
            return redirect()->route('tindak-lanjut.detail', $id)
                ->with('error', 'Keputusan sudah pernah dibuat untuk laporan ini.');
        }

        $tindakLanjut = TindakLanjutKasus::create([
            'id_pengaduan'     => $pengaduan->id_pengaduan,
            'id_pengguna'      => Auth::id(),
            'keputusan'        => $request->keputusan,
            'catatan_keputusan' => $request->catatan_keputusan,
            'tanggal_keputusan' => now(),
        ]);

        // Update status pengaduan
        if ($request->keputusan === 'Tindaklanjuti') {
            $pengaduan->update(['status_pengaduan' => LokerLensCopy::STATUS_TINDAK_LANJUT]);
            $keterangan = 'Keputusan Kepala Bidang: Tindaklanjuti. Kasus dilanjutkan ke tahap pembuatan surat.';
        } else {
            // BR-03: ditandai tidak dilanjuti dan diarsipkan
            $pengaduan->update(['status_pengaduan' => LokerLensCopy::STATUS_TIDAK_DILANJUTI]);
            $keterangan = LokerLensCopy::KASUS_DIARSIPKAN;
        }

        // Catat riwayat kasus (BR-11, NFR-SAF-06)
        RiwayatKasus::create([
            'id_pengaduan' => $pengaduan->id_pengaduan,
            'status_kasus' => $pengaduan->status_pengaduan,
            'keterangan'   => $keterangan,
        ]);

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
        ])->findOrFail($id);

        return view('tindak-lanjut.hasil', compact('pengaduan'));
    }
}
