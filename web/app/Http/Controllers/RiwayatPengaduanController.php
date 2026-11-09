<?php

namespace App\Http\Controllers;

use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller Riwayat Pengaduan (SRS 3.1.3, Appendix C no. 7).
 * Khusus Masyarakat — menampilkan riwayat pengaduan milik sendiri (NFR-SEC-05).
 */
class RiwayatPengaduanController extends Controller
{
    public function index(Request $request): View
    {
        $userId = Auth::id();

        // Query dasar pengaduan milik pengguna yang sedang login
        $query = Pengaduan::with(['lowonganKerja.perusahaan', 'hasilKlasifikasi'])
            ->where('id_pengguna', $userId);

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

        // Filter status pengaduan
        if ($status = $request->filled('status') ? $request->query('status') : null) {
            $query->where('status_pengaduan', $status);
        }

        // Urutkan dari yang terbaru dan paginasi 10 per halaman
        $pengaduan = $query->orderBy('tanggal_pengaduan', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Hitung statistik untuk banner ringkasan (dari semua pengaduan milik pengguna)
        $statusSelesai = [
            StatusKasus::Selesai->value,
            StatusKasus::SelesaiRisikoRendah->value,
            StatusKasus::TidakTerbukti->value,
            StatusKasus::TidakDitindaklanjuti->value,
        ];

        $statusSedangBerjalan = [
            StatusKasus::PerluTinjauan->value,
            StatusKasus::SedangDitinjau->value,
            StatusKasus::MenungguKeputusan->value,
            StatusKasus::TindakLanjutDisetujui->value,
        ];

        $sedangBerjalan = Pengaduan::where('id_pengguna', $userId)
            ->whereIn('status_pengaduan', $statusSedangBerjalan)
            ->count();

        $selesai = Pengaduan::where('id_pengguna', $userId)
            ->whereIn('status_pengaduan', $statusSelesai)
            ->count();

        return view('riwayat-pengaduan.index', compact('pengaduan', 'sedangBerjalan', 'selesai'));
    }
}

