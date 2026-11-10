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

        $menungguKeputusan = Pengaduan::with('lowonganKerja.perusahaan')
            ->where('status_pengaduan', 'Menunggu Keputusan')
            ->orderByDesc('tanggal_pengaduan')
            ->limit(5)
            ->get();

        return view('dashboard-kepala-bidang.index', compact('ringkasan', 'menungguKeputusan'));
    }
}