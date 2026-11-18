<?php

namespace App\Models;

use App\Constants\LokerLensCopy;
use Illuminate\Database\Eloquent\Model;

class SuratTeguranLaporanPolisi extends Model
{
    /**
     * Pasal hukum yang bisa dipilih di panel "Detail Pelanggaran" (Buat Laporan Polisi).
     * Kunci = kode yang disimpan di kolom pasal_dilanggar.
     */
    public const DAFTAR_PASAL = [
        '378_kuhp' => [
            'judul'      => 'Dugaan Penipuan Rekrutmen Kerja (Pasal 378 KUHP)',
            'keterangan' => 'Tindakan menguntungkan diri sendiri secara melawan hukum dengan memakai nama palsu atau martabat palsu.',
        ],
        '28_ite' => [
            'judul'      => 'Penyebaran Informasi Bohong/Hoaks melalui Media Digital (Pasal 28 ayat 1 UU ITE)',
            'keterangan' => 'Menyebarkan informasi bohong yang mengakibatkan kerugian materiil konsumen/pelamar kerja dalam transaksi elektronik.',
        ],
        '13_2003' => [
            'judul'      => 'Pungutan Biaya Penempatan Ilegal bagi Calon Tenaga Kerja (UU No. 13/2003)',
            'keterangan' => 'Larangan pemungutan biaya dalam proses penempatan tenaga kerja di luar ketentuan perundang-undangan.',
        ],
    ];

    /** Jenis dokumen yang bisa dipilih (sama dengan enum kolom jenis_dokumen). Satu kasus hanya punya satu surat. */
    public const DAFTAR_JENIS = [
        LokerLensCopy::JENIS_LAPORAN_POLISI,
        LokerLensCopy::JENIS_SURAT_TEGURAN,
    ];

    protected $table = 'surat_teguran_laporan_polisi';
    protected $primaryKey = 'id_surat';

    protected $fillable = [
        'id_tindaklanjut',
        'nomor_surat',
        'jenis_dokumen',
        'kronologi_kejadian',
        'tujuan_instansi',
        'pasal_dilanggar',
        'status_penerbitan',
        'tanggal_terbit',
    ];

    protected $casts = [
        'tanggal_terbit'  => 'datetime',
        'pasal_dilanggar' => 'array',
    ];

    public function tindakLanjutKasus()
    {
        return $this->belongsTo(TindakLanjutKasus::class, 'id_tindaklanjut', 'id_tindaklanjut');
    }

    /**
     * Nomor surat resmi dengan tahun berjalan, mis.:
     *   Laporan Polisi: #LP-2026/POLRI/8821
     *   Surat Teguran : #ST-2026/DISNAKER/8821
     */
    public static function generateNomorSurat(string $nomorTiket, string $jenis = LokerLensCopy::JENIS_LAPORAN_POLISI): string
    {
        $number = str_replace('#LKR-', '', $nomorTiket);
        $kode = $jenis === LokerLensCopy::JENIS_SURAT_TEGURAN ? 'ST' : 'LP';
        $instansi = $jenis === LokerLensCopy::JENIS_SURAT_TEGURAN ? 'DISNAKER' : 'POLRI';

        return '#' . $kode . '-' . now()->year . '/' . $instansi . '/' . $number;
    }
}
