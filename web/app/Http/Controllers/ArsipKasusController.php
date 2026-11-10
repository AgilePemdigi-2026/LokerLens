<?php

namespace App\Http\Controllers;

use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use App\Models\SuratTeguranLaporanPolisi;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller untuk Fitur Arsip Kasus (Kepala Bidang) — Sprint 2.
 * Menampilkan dan mengelola dokumen rekam kasus pengawasan berstatus akhir:
 * - Kasus Selesai (Surat Polisi Terbit)
 * - Kasus Tidak Ditindaklanjuti (Diarsipkan)
 * - Kasus Tidak Terbukti (Ditutup Analis)
 */
class ArsipKasusController extends Controller
{
    /**
     * Tampilkan daftar arsip kasus berstatus akhir.
     */
    public function index(Request $request): View
    {
        $statusAkhirCases = StatusKasus::statusAkhir();
        $statusAkhirValues = array_map(fn($s) => $s->value, $statusAkhirCases);

        // Query dasar: Hanya pengaduan berstatus akhir
        $query = Pengaduan::with([
            'lowonganKerja.perusahaan',
            'hasilKlasifikasi.tinjauanAnalis',
            'tindakLanjutKasus.suratTeguranLaporanPolisi',
            'tindakLanjutKasus.kepalaBidang',
        ])->whereIn('status_pengaduan', $statusAkhirValues);

        // Filter status (validasi hanya nilai status akhir yang diizinkan)
        if ($status = $request->query('status')) {
            if (in_array($status, $statusAkhirValues, true)) {
                $query->where('status_pengaduan', $status);
            }
        }

        // Pencarian: nomor tiket, posisi lowongan, nama perusahaan, atau nomor surat
        if ($cari = trim((string) $request->query('cari', ''))) {
            $query->where(function ($q) use ($cari) {
                $q->where('nomor_tiket', 'like', "%{$cari}%")
                    ->orWhereHas('lowonganKerja', function ($qLow) use ($cari) {
                        $qLow->where('posisi_lowongan', 'like', "%{$cari}%")
                            ->orWhereHas('perusahaan', function ($qPer) use ($cari) {
                                $qPer->where('nama_perusahaan', 'like', "%{$cari}%");
                            });
                    })
                    ->orWhereHas('tindakLanjutKasus.suratTeguranLaporanPolisi', function ($qSurat) use ($cari) {
                        $qSurat->where('nomor_surat', 'like', "%{$cari}%");
                    });
            });
        }

        // Paginasi 10 kasus per halaman dengan retensi query string.
        // Urut dari kasus yang paling baru diselesaikan (tanggal dari Riwayat Kasus, bukan updated_at).
        $arsip = $query->denganTanggalSelesai()
            ->orderByDesc('tanggal_selesai')
            ->orderByDesc('id_pengaduan')
            ->paginate(10)
            ->withQueryString();

        // Hitung total ringkasan untuk filter chips dan banner
        $totalArsip = Pengaduan::whereIn('status_pengaduan', $statusAkhirValues)->count();
        $jumlahSelesai = Pengaduan::where('status_pengaduan', StatusKasus::Selesai)->count();
        $jumlahTidakDitindaklanjuti = Pengaduan::where('status_pengaduan', StatusKasus::TidakDitindaklanjuti)->count();
        $jumlahTidakTerbukti = Pengaduan::where('status_pengaduan', StatusKasus::TidakTerbukti)->count();
        $jumlahSuratTerbit = SuratTeguranLaporanPolisi::where('status_penerbitan', 'Terbit')->count();

        return view('arsip-kasus.index', compact(
            'arsip',
            'totalArsip',
            'jumlahSelesai',
            'jumlahTidakDitindaklanjuti',
            'jumlahTidakTerbukti',
            'jumlahSuratTerbit',
            'statusAkhirCases'
        ));
    }

    /**
     * Tampilkan detail rekaman arsip kasus dan kronologi riwayat kasus.
     * Mengembalikan HTTP 404 jika kasus belum berstatus akhir.
     */
    public function show(Pengaduan $pengaduan): View
    {
        $statusAkhirValues = array_map(fn($s) => $s->value, StatusKasus::statusAkhir());
        $statusCurrent = $pengaduan->status_pengaduan instanceof StatusKasus
            ? $pengaduan->status_pengaduan->value
            : (string) $pengaduan->status_pengaduan;

        // Pastikan kasus berstatus akhir; jika bukan, tolak 404
        if (!in_array($statusCurrent, $statusAkhirValues, true)) {
            abort(404, 'Kasus belum berstatus akhir dan belum masuk dalam arsip pengawasan.');
        }

        // Muat relasi lengkap untuk detail arsip (bebas N+1 query)
        $pengaduan->load([
            'lowonganKerja.perusahaan',
            'hasilKlasifikasi.tinjauanAnalis.analis',
            'hasilKlasifikasi.indikatorMencurigakan',
            'tindakLanjutKasus.suratTeguranLaporanPolisi',
            'tindakLanjutKasus.kepalaBidang',
            'riwayatKasus' => fn($q) => $q->orderBy('tanggal_update', 'asc'),
            'buktiPendukung',
        ]);

        $surat = $pengaduan->tindakLanjutKasus?->suratTeguranLaporanPolisi;

        return view('arsip-kasus.show', compact('pengaduan', 'surat'));
    }
}

