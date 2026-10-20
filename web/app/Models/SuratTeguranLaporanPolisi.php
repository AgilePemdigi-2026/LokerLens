<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratTeguranLaporanPolisi extends Model
{
    protected $table = 'surat_teguran_laporan_polisi';
    protected $primaryKey = 'id_surat';

    protected $fillable = [
        'id_tindaklanjut',
        'nomor_surat',
        'jenis_dokumen',
        'kronologi_kejadian',
        'tujuan_instansi',
        'status_penerbitan',
        'tanggal_terbit',
    ];

    protected $casts = [
        'tanggal_terbit' => 'datetime',
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
