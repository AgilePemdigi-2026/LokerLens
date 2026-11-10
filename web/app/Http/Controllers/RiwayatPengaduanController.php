<?php

namespace App\Http\Controllers;

use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller Riwayat Pengaduan (SRS 3.1.3, Appendix C no. 7, revisi issue #25).
 * Khusus Masyarakat — menampilkan riwayat pengaduan milik sendiri (NFR-SEC-05).
 * Hanya pengaduan yang statusnya sudah akhir (StatusKasus::isAkhir). Pengaduan yang
 * masih berjalan, termasuk "Menunggu klasifikasi", dipantau di Status Pengaduan.
 */
class RiwayatPengaduanController extends Controller
{
    public function index(Request $request): View
    {
        $userId = Auth::id();

        // Pilihan filter status = status akhir saja (Selesai - Risiko Rendah, Tidak Terbukti, Tidak Ditindaklanjuti, Selesai)
        $pilihanStatus = array_values(array_filter(StatusKasus::cases(), fn (StatusKasus $status) => $status->isAkhir()));

        // Query dasar pengaduan selesai milik pengguna yang sedang login
        $query = Pengaduan::with(['lowonganKerja.perusahaan', 'hasilKlasifikasi'])
            ->where('id_pengguna', $userId)
            ->sudahSelesai();

        // Filter pencarian: nomor tiket, posisi lowongan, atau nama perusahaan
        if ($cari = $request->filled('cari') ? trim($request->query('cari')) : null) {
            $query->where(function ($q) use ($cari) {
                $q->where('nomor_tiket', 'like', "%{$cari}%")
                    ->orWhereHas('lowonganKerja', function ($qLow) use ($cari) {
                        $qLow->where('posisi_lowongan', 'like', "%{$cari}%")
                            ->orWhereHas('perusahaan', function ($qPer) use ($cari) {
                                $qPer->where('nama_perusahaan', 'like', "%{$cari}%");
                            });
                    });
            });
        }

        // Filter status: hanya status akhir yang diterima, nilai lain diabaikan
        $status = StatusKasus::tryFrom((string) $request->query('status'));
        if ($status && $status->isAkhir()) {
            $query->where('status_pengaduan', $status->value);
        }

        // Urutkan dari yang terbaru dan paginasi 10 per halaman
        $pengaduan = $query->orderBy('tanggal_pengaduan', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Statistik banner (dari semua pengaduan selesai milik pengguna, tanpa filter):
        //   Ditindaklanjuti = status Selesai (surat resmi terbit)
        //   Ditutup         = status akhir lainnya
        $totalSelesai = Pengaduan::where('id_pengguna', $userId)->sudahSelesai()->count();
        $ditindaklanjuti = Pengaduan::where('id_pengguna', $userId)
            ->where('status_pengaduan', StatusKasus::Selesai->value)
            ->count();
        $ditutup = $totalSelesai - $ditindaklanjuti;

        return view('riwayat-pengaduan.index', compact('pengaduan', 'pilihanStatus', 'totalSelesai', 'ditindaklanjuti', 'ditutup'));
    }
}
