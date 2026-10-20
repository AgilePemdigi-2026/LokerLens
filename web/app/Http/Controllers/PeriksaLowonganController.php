<?php

namespace App\Http\Controllers;

use App\Constants\LokerLensCopy;
use App\Enums\StatusKasus;
use App\Models\HasilKlasifikasi;
use App\Models\IndikatorMencurigakan;
use App\Models\LowonganKerja;
use App\Models\Pengaduan;
use App\Models\Perusahaan;
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
 * Alur: idle → validasi → loading (simulasi ML) → hasil
 * NFR-SAF-01: Hasil adalah prediksi, bukan penetapan penipuan.
 */
class PeriksaLowonganController extends Controller
{
    public function index(): View
    {
        return view('periksa-lowongan.index');
    }

    public function store(Request $request): RedirectResponse|View
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

        // Simulasi klasifikasi ML (mock — integrasi backend/ML Sprint berikutnya)
        $hasilMock = $this->simulasiKlasifikasi($request);

        // Status awal: di bawah ambang prioritas langsung "Selesai - Risiko Rendah" (BR-05)
        $statusAwal = StatusKasus::awalDariSkor($hasilMock['score']);

        // NFR-SAF-04: semua data tersimpan utuh atau tidak sama sekali
        $pengaduan = DB::transaction(function () use ($request, $hasilMock, $statusAwal) {
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

            // Buat pengaduan
            $pengaduan = Pengaduan::create([
                'id_lowongan'     => $lowongan->id_lowongan,
                'id_pengguna'     => Auth::id(),
                'nomor_tiket'     => Pengaduan::generateNomorTiket(),
                'deskripsi_singkat' => 'Periksa lowongan: ' . $request->posisi_lowongan . ' di ' . $request->nama_perusahaan,
                'status_pengaduan' => $statusAwal,
            ]);

            $klasifikasi = HasilKlasifikasi::create([
                'id_pengaduan'      => $pengaduan->id_pengaduan,
                'confidence_score'  => $hasilMock['score'],
                'kategori_hasil'    => $hasilMock['kategori'],
                'status_prioritas'  => $statusAwal === StatusKasus::PerluTinjauan,
                'metode_analisis'   => 'Mock ML v1.0 (TF-IDF + Rule-based) — TODO: integrasi model real',
            ]);

            // Simpan indikator/kata pemicu dari checklist & analisis mock
            foreach ($hasilMock['indikator'] as $ind) {
                IndikatorMencurigakan::create([
                    'id_klasifikasi'    => $klasifikasi->id_klasifikasi,
                    'nama_indikator'    => $ind['nama'],
                    'kategori_indikator' => $ind['kategori'],
                    'bobot_indikator'   => $ind['bobot'],
                ]);
            }

            // Catat riwayat kasus (NFR-SAF-06, BR-11)
            $pengaduan->catatRiwayat($statusAwal === StatusKasus::PerluTinjauan
                ? 'Pengaduan diterima dan klasifikasi dilakukan. Masuk antrean tinjauan Analis.'
                : 'Pengaduan diterima dan klasifikasi dilakukan. Skor di bawah ambang prioritas, kasus selesai.');

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

    /**
     * Simulasi klasifikasi ML dengan aturan sederhana berbasis kata kunci.
     * TODO: Ganti dengan integrasi API ML nyata saat backend/ML siap.
     * NFR-SAF-01: Hasil adalah prediksi, bukan penetapan penipuan.
     */
    private function simulasiKlasifikasi(Request $request): array
    {
        $deskripsi = strtolower($request->deskripsi_lowongan . ' ' . ($request->informasi_tambahan ?? ''));
        $score     = 20.0;
        $indikator = [];

        $checklist = $request->input('checklist', []);

        // Aturan berbasis kata kunci dan checklist
        $rules = [
            ['kata' => ['biaya', 'bayar', 'transfer', 'dp', 'administrasi', 'seragam'],
             'nama' => 'Permintaan biaya/pembayaran sebelum kerja',
             'kategori' => 'Biaya Fiktif', 'bobot' => 25],
            ['kata' => ['gmail', 'yahoo', 'hotmail', 'ymail'],
             'nama' => 'Penggunaan email gratisan',
             'kategori' => 'Email Tidak Resmi', 'bobot' => 20],
            ['kata' => ['gaji', 'penghasilan', 'income'],
             'nama' => 'Penawaran gaji tidak masuk akal tanpa kualifikasi',
             'kategori' => 'Gaji Janggal', 'bobot' => 15],
            ['kata' => ['hotel', 'penginapan', 'apartemen', 'kost'],
             'nama' => 'Lokasi interview mencurigakan',
             'kategori' => 'Lokasi Mencurigakan', 'bobot' => 15],
        ];

        foreach ($rules as $rule) {
            foreach ($rule['kata'] as $kata) {
                if (str_contains($deskripsi, $kata)) {
                    $score += $rule['bobot'];
                    $indikator[] = [
                        'nama'     => $rule['nama'],
                        'kategori' => $rule['kategori'],
                        'bobot'    => $rule['bobot'],
                    ];
                    break;
                }
            }
        }

        // Tambah skor dari checklist (NFR-SAF-01: tetap prediksi)
        if (in_array('biaya', $checklist)) {
            $score += 15;
            $indikator[] = ['nama' => 'Ada permintaan biaya pendaftaran/tes/seragam (checklist)', 'kategori' => 'Biaya Fiktif', 'bobot' => 15];
        }
        if (in_array('email_gratis', $checklist)) {
            $score += 10;
            $indikator[] = ['nama' => 'Menggunakan alamat email gratisan (checklist)', 'kategori' => 'Email Tidak Resmi', 'bobot' => 10];
        }
        if (in_array('lokasi', $checklist)) {
            $score += 10;
            $indikator[] = ['nama' => 'Lokasi interview mencurigakan (checklist)', 'kategori' => 'Lokasi Mencurigakan', 'bobot' => 10];
        }
        if (in_array('gaji', $checklist)) {
            $score += 10;
            $indikator[] = ['nama' => 'Gaji tidak masuk akal tanpa kualifikasi (checklist)', 'kategori' => 'Gaji Janggal', 'bobot' => 10];
        }

        $score = min(99, $score);

        $kategori = match (true) {
            $score >= 75 => 'Lowongan berpotensi penipuan',
            $score >= 50 => 'Lowongan perlu diperiksa lebih lanjut',
            default      => 'Lowongan tampak normal',
        };

        return compact('score', 'kategori', 'indikator');
    }

    private function extractDomain(string $text): ?string
    {
        if (preg_match('/[\w.]+@([\w.]+)/', $text, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
