<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndikatorMencurigakan extends Model
{
    /** Kategori untuk kata pemicu dari model ML (docs/architecture.md §5.3). */
    public const KATEGORI_KATA_PEMICU = 'Kata Pemicu Model';

    protected $table = 'indikator_mencurigakan';
    protected $primaryKey = 'id_indikator';

    protected $fillable = [
        'id_klasifikasi',
        'nama_indikator',
        'kategori_indikator',
        'bobot_indikator',
    ];

    protected $casts = [
        'bobot_indikator' => 'decimal:2',
    ];

    public function hasilKlasifikasi()
    {
        return $this->belongsTo(HasilKlasifikasi::class, 'id_klasifikasi', 'id_klasifikasi');
    }
}
