<?php

namespace App\Http\Controllers;

use App\Constants\LokerLensCopy;
use App\Models\BuktiPendukung;
use App\Models\Pengaduan;
use App\Models\RiwayatKasus;
use App\Models\SuratTeguranLaporanPolisi;
use App\Models\TindakLanjutKasus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Fitur Buat Surat Teguran/Laporan Polisi (F-04 / UC-04 / US-04).
 * Rute: /buat-surat/
 * Peran: Kepala Bidang
 *
 * FR-ST-02: Hanya bisa dibuka setelah keputusan "Tindaklanjuti".
 * BR-08: Surat hanya dibuat/diterbitkan Kepala Bidang.
 * BR-09: Draf belum terbit; resmi setelah diterbitkan.
 * NFR-SAF-03: Halaman tidak bisa dibuka tanpa keputusan.
 */
class BuatSuratController extends Controller
{
    /**
     * Form buat laporan polisi.
     * Otomatis terisi data kasus (FR-ST-03).
     */
    public function index(int $pengaduanId): View|RedirectResponse
    {
        $pengaduan = Pengaduan::with([
            'lowonganKerja.perusahaan',
            'hasilKlasifikasi.indikatorMencurigakan',
            'hasilKlasifikasi.tinjauanAnalis',
            'buktiPendukung',
            'tindakLanjutKasus',
        ])->findOrFail($pengaduanId);

        // FR-ST-02, NFR-SAF-03: Pastikan keputusan sudah "Tindaklanjuti"
        $tindakLanjut = $pengaduan->tindakLanjutKasus;
        if (!$tindakLanjut || $tindakLanjut->keputusan !== 'Tindaklanjuti') {
            abort(403, 'Halaman ini hanya bisa diakses setelah keputusan "Tindaklanjuti" ditetapkan.');
        }

        // Cek apakah sudah ada surat sebelumnya (draf)
        $surat = $tindakLanjut->suratTeguranLaporanPolisi;

        // Kronologi otomatis dari data kasus (dapat diedit)
        $kronologiDefault = $this->buatKronologiDefault($pengaduan);

        return view('buat-surat.index', compact('pengaduan', 'surat', 'kronologiDefault'));
    }

    /**
     * Simpan sebagai draf.
     * FR-ST-05, BR-09: Draf belum terbit.
     * NFR-SAF-04: Jika gagal, data isian tetap ada.
     */
    public function simpanDraft(Request $request, int $pengaduanId): RedirectResponse
    {
        $pengaduan    = $this->getPengaduanTindaklanjuti($pengaduanId);
        $tindakLanjut = $pengaduan->tindakLanjutKasus;

        $request->validate([
            'kronologi'         => ['required', 'string'],
            'tujuan_kepolisian' => ['required', 'string', 'max:255'],
        ], [
            'kronologi.required'         => 'Kronologi kejadian wajib diisi.',
            'tujuan_kepolisian.required' => 'Tujuan kepolisian wajib dipilih.',
        ]);

        // Tangani upload bukti tambahan (NFR-SEC-07: hanya pdf/jpg/png)
        if ($request->hasFile('bukti_tambahan')) {
            $request->validate([
                'bukti_tambahan.*' => ['mimes:pdf,jpg,jpeg,png', 'max:10240'],
            ]);

            foreach ($request->file('bukti_tambahan') as $file) {
                // Enum tipe_file hanya pdf/jpg/png, jadi .jpeg disamakan menjadi jpg
                $tipeFile = strtolower($file->getClientOriginalExtension());
                if ($tipeFile === 'jpeg') {
                    $tipeFile = 'jpg';
                }

                $path = $file->store('bukti', 'public');
                BuktiPendukung::create([
                    'id_pengaduan' => $pengaduan->id_pengaduan,
                    'nama_file'    => $file->getClientOriginalName(),
                    'tipe_file'    => $tipeFile,
                    'ukuran_file'  => $file->getSize(),
                    'path_file'    => $path,
                ]);
            }
        }

        SuratTeguranLaporanPolisi::updateOrCreate(
            ['id_tindaklanjut' => $tindakLanjut->id_tindaklanjut],
            [
                'jenis_dokumen'       => 'Laporan Polisi',
                'kronologi_kejadian'  => $request->kronologi,
                'tujuan_instansi'     => $request->tujuan_kepolisian,
                'status_penerbitan'   => 'Draft',
            ]
        );

        return redirect()->route('buat-surat.index', $pengaduanId)
            ->with('success', LokerLensCopy::DRAF_TERSIMPAN);
    }

    /**
     * Terbitkan laporan secara resmi.
     * FR-ST-08, BR-10: status → Terbit; kasus selesai & terdokumentasi.
     * FR-ST-09, NFR-SAF-04: Jika gagal → pesan error, data isian tidak hilang.
     */
    public function terbitkan(Request $request, int $pengaduanId): RedirectResponse
    {
        $pengaduan    = $this->getPengaduanTindaklanjuti($pengaduanId);
        $tindakLanjut = $pengaduan->tindakLanjutKasus;

        $request->validate([
            'kronologi'         => ['required', 'string'],
            'tujuan_kepolisian' => ['required', 'string', 'max:255'],
        ]);

        try {
            $nomorSurat = SuratTeguranLaporanPolisi::generateNomorSurat($pengaduan->nomor_tiket);

            $surat = SuratTeguranLaporanPolisi::updateOrCreate(
                ['id_tindaklanjut' => $tindakLanjut->id_tindaklanjut],
                [
                    'nomor_surat'        => $nomorSurat,
                    'jenis_dokumen'      => 'Laporan Polisi',
                    'kronologi_kejadian' => $request->kronologi,
                    'tujuan_instansi'    => $request->tujuan_kepolisian,
                    'status_penerbitan'  => 'Terbit',
                    'tanggal_terbit'     => now(),
                ]
            );

            // Update status pengaduan → Kasus selesai (BR-10)
            $pengaduan->update(['status_pengaduan' => LokerLensCopy::STATUS_SELESAI]);

            // Catat riwayat kasus (BR-11)
            RiwayatKasus::create([
                'id_pengaduan' => $pengaduan->id_pengaduan,
                'status_kasus' => LokerLensCopy::STATUS_SELESAI,
                'keterangan'   => 'Laporan Polisi ' . $nomorSurat . ' berhasil diterbitkan. ' . LokerLensCopy::KASUS_TERDOKUMENTASI . '.',
            ]);

            return redirect()->route('buat-surat.terbit', ['pengaduanId' => $pengaduanId, 'suratId' => $surat->id_surat]);
        } catch (\Exception $e) {
            // FR-ST-09, NFR-SAF-04: Gagal → data isian tidak hilang
            return redirect()->route('buat-surat.index', $pengaduanId)
                ->withInput()
                ->with('error', LokerLensCopy::GAGAL_TERBITKAN);
        }
    }

    /**
     * Halaman sukses setelah laporan diterbitkan.
     */
    public function terbit(int $pengaduanId, int $suratId): View
    {
        $pengaduan = Pengaduan::with([
            'lowonganKerja.perusahaan',
            'buktiPendukung',
            'tindakLanjutKasus.suratTeguranLaporanPolisi',
        ])->findOrFail($pengaduanId);

        $surat = SuratTeguranLaporanPolisi::findOrFail($suratId);

        return view('buat-surat.terbit', compact('pengaduan', 'surat'));
    }

    /**
     * Helper: ambil pengaduan yang sudah "Tindaklanjuti".
     */
    private function getPengaduanTindaklanjuti(int $id): Pengaduan
    {
        $pengaduan = Pengaduan::with('tindakLanjutKasus')->findOrFail($id);

        if (!$pengaduan->tindakLanjutKasus || $pengaduan->tindakLanjutKasus->keputusan !== 'Tindaklanjuti') {
            abort(403, 'Aksi ini hanya bisa dilakukan untuk kasus yang sudah ditindaklanjuti.');
        }

        return $pengaduan;
    }

    private function buatKronologiDefault(Pengaduan $pengaduan): string
    {
        $perusahaan = $pengaduan->lowonganKerja->perusahaan->nama_perusahaan ?? '-';
        $posisi     = $pengaduan->lowonganKerja->posisi_lowongan ?? '-';
        $skor       = $pengaduan->hasilKlasifikasi->confidence_score ?? '-';

        return "Telah ditemukan indikasi lowongan kerja mencurigakan yang mengatasnamakan {$perusahaan} untuk posisi {$posisi}. " .
               "Sistem LokerLens mendeteksi probabilitas penipuan sebesar {$skor}% berdasarkan analisis pola deskripsi dan indikator bahaya. " .
               "Berdasarkan tinjauan Analis dan keputusan Kepala Bidang, kasus ini perlu ditindaklanjuti oleh pihak berwajib.";
    }
}
