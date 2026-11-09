<?php

namespace App\Http\Controllers;

use App\Enums\StatusKasus;
use App\Models\Pengaduan;
use App\Models\SuratTeguranLaporanPolisi;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller untuk Dashboard Kepala Bidang (Sprint 2 - Issue #14).
 * Menyajikan ringkasan eksekutif pengawasan kasus penipuan lowongan kerja:
 * - Metrik kasus per tahap Kepala Bidang (Menunggu Keputusan, Disetujui, Tidak Ditindaklanjuti, Selesai)
 * - Antrean kasus yang memerlukan penetapan keputusan
 * - Dokumen surat / laporan polisi resmi yang telah diterbitkan
 */
class DashboardKepalaBidangController extends Controller
{
    public function index(Request $request): View
    {
        // 1. Metrik Ringkasan Kasus Tahap Kepala Bidang
        $jumlahMenunggu = Pengaduan::where('status_pengaduan', StatusKasus::MenungguKeputusan)->count();
        $jumlahDisetujui = Pengaduan::where('status_pengaduan', StatusKasus::TindakLanjutDisetujui)->count();
        $jumlahDitolak = Pengaduan::where('status_pengaduan', StatusKasus::TidakDitindaklanjuti)->count();
        $jumlahSelesai = Pengaduan::where('status_pengaduan', StatusKasus::Selesai)->count();
        $jumlahSuratTerbit = SuratTeguranLaporanPolisi::where('status_penerbitan', 'Terbit')->count();

        // Total keputusan yang sudah diproses (Disetujui + Ditolak + Selesai)
        $totalKeputusan = $jumlahDisetujui + $jumlahDitolak + $jumlahSelesai;

        // 2. Antrean Kasus Menunggu Keputusan (5 kasus terbaru yang perlu tindakan)
        $antreanKeputusan = Pengaduan::with([
                'lowonganKerja.perusahaan',
                'hasilKlasifikasi.tinjauanAnalis',
                'hasilKlasifikasi.indikatorMencurigakan',
            ])
            ->where('status_pengaduan', StatusKasus::MenungguKeputusan)
            ->orderBy('tanggal_pengaduan', 'desc')
            ->limit(5)
            ->get();

        // 3. Kasus yang Baru Saja Diputuskan / Ditindaklanjuti (5 kasus terbaru)
        $riwayatKeputusan = Pengaduan::with([
                'lowonganKerja.perusahaan',
                'tindakLanjutKasus.suratTeguranLaporanPolisi',
            ])
            ->whereIn('status_pengaduan', [
                StatusKasus::TindakLanjutDisetujui,
                StatusKasus::TidakDitindaklanjuti,
                StatusKasus::Selesai,
            ])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        // 4. Daftar Dokumen Surat / Laporan Polisi Resmi yang Diterbitkan
        $suratTerbaru = SuratTeguranLaporanPolisi::with([
                'tindakLanjutKasus.pengaduan.lowonganKerja.perusahaan',
            ])
            ->where('status_penerbitan', 'Terbit')
            ->orderBy('tanggal_terbit', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard-kepala-bidang.index', compact(
            'jumlahMenunggu',
            'jumlahDisetujui',
            'jumlahDitolak',
            'jumlahSelesai',
            'jumlahSuratTerbit',
            'totalKeputusan',
            'antreanKeputusan',
            'riwayatKeputusan',
            'suratTerbaru'
        ));
    }
}

