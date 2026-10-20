<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Beranda untuk Masyarakat — halaman pertama setelah login (Bagian 7.1).
 * Sprint 1 & 2 target integrasi.
 */
class BerandaController extends Controller
{
    public function index(): View
    {
        $pengaduanTerbaru = Pengaduan::with(['lowonganKerja.perusahaan', 'hasilKlasifikasi'])
            ->where('id_pengguna', Auth::id())
            ->orderBy('tanggal_pengaduan', 'desc')
            ->take(5)
            ->get();

        return view('beranda.index', compact('pengaduanTerbaru'));
    }
}
