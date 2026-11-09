<?php

namespace App\Models;

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
     * Generate nomor surat resmi, mis: #LP-2026/POLRI/8821
     */
    public static function generateNomorSurat(string $nomorTiket): string
    {
        $number = str_replace('#LKR-', '', $nomorTiket);
        return '#LP-' . date('Y') . '/POLRI/' . $number;
    }
}
