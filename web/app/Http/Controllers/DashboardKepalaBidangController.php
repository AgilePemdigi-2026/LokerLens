<?php

namespace App\Http\Controllers;

use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardKepalaBidangController extends Controller
{
    public function index(): View
    {
        $tahap = StatusKasus::tahapKepalaBidang();

        $jumlah = DB::table('pengaduan')
            ->whereIn('status_pengaduan', array_map(fn (StatusKasus $s) => $s->value, $tahap))
            ->select('status_pengaduan', DB::raw('COUNT(*) as total'))
            ->groupBy('status_pengaduan')
            ->pluck('total', 'status_pengaduan');

        $ringkasan = collect($tahap)->map(fn (StatusKasus $status) => [
            'status' => $status,
            'jumlah' => (int) ($jumlah[$status->value] ?? 0),
        ]);

        // Skor risiko dimuat bersama (hasilKlasifikasi) supaya bisa ditampilkan per kasus.
        // Diurutkan dari yang paling lama menunggu agar kasus mendesak ada di atas.
        $menungguKeputusan = Pengaduan::with(['lowonganKerja.perusahaan', 'hasilKlasifikasi'])
            ->where('status_pengaduan', StatusKasus::MenungguKeputusan->value)
            ->orderBy('tanggal_pengaduan')
            ->limit(5)
            ->get();

        // Kasus yang sudah diputuskan Kepala Bidang (status selain "Menunggu Keputusan").
        $statusDiputuskan = collect($tahap)
            ->reject(fn (StatusKasus $s) => $s === StatusKasus::MenungguKeputusan)
            ->map(fn (StatusKasus $s) => $s->value)
            ->values()
            ->all();

        $keputusanTerbaru = Pengaduan::with('lowonganKerja.perusahaan')
            ->whereIn('status_pengaduan', $statusDiputuskan)
            ->orderByDesc('tanggal_pengaduan')
            ->limit(5)
            ->get();

        return view('dashboard-kepala-bidang.index', compact('ringkasan', 'menungguKeputusan', 'keputusanTerbaru'));
    }
}