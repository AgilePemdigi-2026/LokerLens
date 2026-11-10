<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Support\ProgresPengaduan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Fitur Status Pengaduan (issue #11).
 * Rute: /status-pengaduan (daftar, filter ?filter=aktif / ?filter=menunggu)
 *       /status-pengaduan/{id} (detail satu pengaduan)
 * Peran: Masyarakat — hanya boleh melihat pengaduan miliknya sendiri.
 *
 * Pemetaan status ke 5 tahap, kelompok badge, dan filter ada di App\Support\ProgresPengaduan.
 */
class StatusPengaduanController extends Controller
{
    /** Relasi yang dibutuhkan halaman (eager loading supaya tidak banyak query). */
    private const RELASI = [
        'lowonganKerja.perusahaan',
        'hasilKlasifikasi.indikatorMencurigakan',
        'riwayatKasus',
    ];

    /**
     * Daftar pengaduan yang belum selesai, terbaru di atas.
     */
    public function index(Request $request): View
    {
        $semua = Pengaduan::with(self::RELASI)
            ->where('id_pengguna', Auth::id())
            ->aktif()
            ->orderBy('tanggal_pengaduan', 'desc')
            ->orderBy('id_pengaduan', 'desc')
            ->get();

        $filter = ProgresPengaduan::filterValid($request->query('filter'));

        return view('status-pengaduan.index', [
            'filter'          => $filter,
            'jumlahPerFilter' => ProgresPengaduan::jumlahPerFilter($semua),
            'adaPengaduan'    => $semua->isNotEmpty(),
            'daftar'          => ProgresPengaduan::saring($semua, $filter),
        ]);
    }

    /**
     * Detail satu pengaduan (belum selesai maupun sudah selesai, mis. dibuka dari Beranda/Riwayat).
     * Pengaduan milik orang lain → 404. Filter dibawa supaya tombol kembali ke daftar yang sama.
     */
    public function detail(Request $request, int $id): View
    {
        $pengaduan = Pengaduan::with(self::RELASI)
            ->where('id_pengaduan', $id)
            ->where('id_pengguna', Auth::id())
            ->firstOrFail();

        return view('status-pengaduan.detail', [
            'pengaduan' => $pengaduan,
            'progres'   => $pengaduan->progres(),
            'filter'    => ProgresPengaduan::filterValid($request->query('filter')),
        ]);
    }
}
