<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilKlasifikasi extends Model
{
    protected $table = 'hasil_klasifikasi';
    protected $primaryKey = 'id_klasifikasi';

    protected $fillable = [
        'id_pengaduan',
        'confidence_score',
        'kategori_hasil',
        'status_prioritas',
        'metode_analisis',
        'tanggal_klasifikasi',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'status_prioritas' => 'boolean',
        'tanggal_klasifikasi' => 'datetime',
    ];

    public function pengaduan()
    {
        return $this->belongsTo(Pengaduan::class, 'id_pengaduan', 'id_pengaduan');
    }

    public function indikatorMencurigakan()
    {
        return $this->hasMany(IndikatorMencurigakan::class, 'id_klasifikasi', 'id_klasifikasi');
    }

    public function tinjauanAnalis()
    {
        return $this->hasOne(TinjauanAnalis::class, 'id_klasifikasi', 'id_klasifikasi');
    }

    /**
     * Label risiko berdasarkan confidence_score.
     * 92% = Risiko Tinggi (makin tinggi = makin berbahaya)
     */
    public function getLabelRisikoAttribute(): string
    {
        return match (true) {
            $this->confidence_score >= 75 => 'Risiko Tinggi',
            $this->confidence_score >= 50 => 'Risiko Sedang',
            default => 'Risiko Rendah',
        };
    }

    public function getWarnaPillAttribute(): string
    {
        return match (true) {
            $this->confidence_score >= 75 => 'risk-high',
            $this->confidence_score >= 50 => 'risk-mid',
            default => 'ok',
        };
    }
}
