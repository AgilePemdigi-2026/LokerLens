<?php

namespace App\Http\Controllers;

use App\Constants\LokerLensCopy;
use App\Enums\StatusKasus;
use App\Models\LowonganKerja;
use App\Models\Pengaduan;
use App\Models\Perusahaan;
use App\Services\KlasifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Fitur Periksa Lowongan Kerja (F-01 / UC-01 / US-01).
 * Rute: /periksa-lowongan/
 * Peran: Masyarakat
 *
 * Alur: idle → validasi → klasifikasi via API ML (KlasifikasiService) → hasil
 * NFR-SAF-01: Hasil adalah prediksi, bukan penetapan penipuan.
 */
class PeriksaLowonganController extends Controller
{
    public function index(): View
    {
        return view('periksa-lowongan.index');
    }

    public function store(Request $request, KlasifikasiService $klasifikasiService): RedirectResponse
    {
        // FR-PL-02, FR-PL-10, NFR-SAF-05: Validasi wajib
        $request->validate([
            'nama_perusahaan'   => ['required', 'string', 'max:255'],
            'posisi_lowongan'   => ['required', 'string', 'max:255'],
            'deskripsi_lowongan' => ['required', 'string', 'min:50'],
            'link_lowongan'     => ['nullable', 'url', 'max:500'],
        ], [
            'nama_perusahaan.required'     => LokerLensCopy::VALIDASI_BELUM_LENGKAP,
            'posisi_lowongan.required'     => LokerLensCopy::VALIDASI_BELUM_LENGKAP,
            'deskripsi_lowongan.required'  => LokerLensCopy::VALIDASI_BELUM_LENGKAP,
            'deskripsi_lowongan.min'       => LokerLensCopy::VALIDASI_BELUM_LENGKAP,
            'link_lowongan.url'            => 'Format URL tidak valid.',
        ]);

        // Klasifikasi oleh model ML. Hanya deskripsi yang dikirim (docs/architecture.md §5.2).
        // Dipanggil SEBELUM transaksi supaya database tidak terkunci selama menunggu API.
        // Hasilnya null jika API mati atau timeout.
        $hasilMl = $klasifikasiService->klasifikasi($request->deskripsi_lowongan);

        // NFR-SAF-04: semua data tersimpan utuh atau tidak sama sekali
        $pengaduan = DB::transaction(function () use ($request, $hasilMl) {
            // Simpan atau cari perusahaan
            $perusahaan = Perusahaan::firstOrCreate(
                ['nama_perusahaan' => $request->nama_perusahaan],
                [
                    'status_terdaftar' => 'Belum Diverifikasi',
                    'domain_email'     => $this->extractDomain($request->informasi_tambahan ?? ''),
                ]
            );

            // Simpan lowongan
            $lowongan = LowonganKerja::create([
                'id_perusahaan'       => $perusahaan->id_perusahaan,
                'posisi_lowongan'     => $request->posisi_lowongan,
                'link_lowongan'       => $request->link_lowongan,
                'deskripsi_lowongan'  => $request->deskripsi_lowongan,
                'informasi_tambahan'  => $request->informasi_tambahan,
            ]);

            // Buat pengaduan. Status sementara "Perlu Tinjauan"; jika klasifikasi
            // berhasil, statusnya langsung disesuaikan dengan skor (BR-05).
            $pengaduan = Pengaduan::create([
                'id_lowongan'     => $lowongan->id_lowongan,
                'id_pengguna'     => Auth::id(),
                'nomor_tiket'     => Pengaduan::generateNomorTiket(),
                'deskripsi_singkat' => 'Periksa lowongan: ' . $request->posisi_lowongan . ' di ' . $request->nama_perusahaan,
                'status_pengaduan' => StatusKasus::PerluTinjauan,
            ]);

            // Riwayat kasus dicatat di kedua cabang (NFR-SAF-06, BR-11)
            if ($hasilMl !== null) {
                $pengaduan->simpanHasilKlasifikasi($hasilMl);
            } else {
                // API gagal: pengaduan tersimpan tanpa Hasil_Klasifikasi ("Menunggu
                // klasifikasi"), lalu diulang dengan php artisan lokerlens:klasifikasi-ulang
                $pengaduan->catatRiwayat('Pengaduan diterima. Menunggu klasifikasi karena layanan ML tidak tersedia.');
            }

            return $pengaduan;
        });

        return redirect()->route('periksa-lowongan.hasil', $pengaduan->id_pengaduan);
    }

    public function hasil(int $id): View
    {
        $pengaduan = Pengaduan::with([
            'lowonganKerja.perusahaan',
            'hasilKlasifikasi.indikatorMencurigakan',
        ])->where('id_pengaduan', $id)
          ->where('id_pengguna', Auth::id())
          ->firstOrFail();

        return view('periksa-lowongan.hasil', compact('pengaduan'));
    }

    private function extractDomain(string $text): ?string
    {
        if (preg_match('/[\w.]+@([\w.]+)/', $text, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
