<?php

namespace App\Http\Controllers;

use App\Enums\HasilTinjauan;
use App\Enums\StatusKasus;
use App\Models\HasilKlasifikasi;
use App\Models\Pengaduan;
use App\Models\TinjauanAnalis;
use Illuminate\View\View;

/**
 * Fitur Dashboard Analis (Sprint 2).
 * Halaman ringkasan monitoring untuk peran Analis Pengawasan Ketenagakerjaan.
 * NFR-SEC-02: Hanya dapat diakses oleh Analis.
 */
class DashboardAnalisController extends Controller
{
    /**
     * Tampilkan halaman utama ringkasan monitoring Analis.
     */
    public function index(): View
    {
        // 1. Ringkasan jumlah kasus per status tahap Analis
        $jumlahPerluTinjauan = Pengaduan::where('status_pengaduan', StatusKasus::PerluTinjauan)->count();
        $jumlahSedangDitinjau = Pengaduan::where('status_pengaduan', StatusKasus::SedangDitinjau)->count();

        // 2. Ringkasan hasil tinjauan Analis
        $jumlahTerkonfirmasi = TinjauanAnalis::where('hasil_tinjauan', 'like', HasilTinjauan::TerkonfirmasiMencurigakan->value . '%')->count();
        $jumlahTidakTerbukti = TinjauanAnalis::where('hasil_tinjauan', 'like', HasilTinjauan::TidakTerbukti->value . '%')->count();

        // 3. Total kasus antrean & total selesai ditinjau
        $totalAntrean = $jumlahPerluTinjauan + $jumlahSedangDitinjau;
        $totalSelesaiDitinjau = $jumlahTerkonfirmasi + $jumlahTidakTerbukti;

        // 4. Daftar kasus prioritas tinggi mendesak yang masih dalam antrean (top 5)
        $antreanPrioritas = HasilKlasifikasi::with(['pengaduan.lowonganKerja.perusahaan'])
            ->where('status_prioritas', true)
            ->whereHas('pengaduan', fn ($q) => $q->whereIn('status_pengaduan', StatusKasus::tahapAnalis()))
            ->orderBy('confidence_score', 'desc')
            ->limit(5)
            ->get();

        // 5. Riwayat tinjauan terakhir yang telah dievaluasi Analis (top 5)
        $tinjauanTerakhir = TinjauanAnalis::with([
            'hasilKlasifikasi.pengaduan.lowonganKerja.perusahaan',
            'analis',
        ])->orderBy('tanggal_tinjauan', 'desc')
          ->limit(5)
          ->get();

        return view('dashboard-analis.index', compact(
            'jumlahPerluTinjauan',
            'jumlahSedangDitinjau',
            'jumlahTerkonfirmasi',
            'jumlahTidakTerbukti',
            'totalAntrean',
            'totalSelesaiDitinjau',
            'antreanPrioritas',
            'tinjauanTerakhir'
        ));
    }
}

