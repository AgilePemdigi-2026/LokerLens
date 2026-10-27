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
     * Tingkat risiko berdasarkan confidence_score (0–100, makin tinggi makin berbahaya):
     *   rendah : di bawah 50 (label model "asli")
     *   sedang : 50 sampai di bawah ambang prioritas (label "palsu", belum prioritas)
     *   tinggi : >= ambang prioritas (config/lokerlens.php, default 70)
     */
    public function getTingkatRisikoAttribute(): string
    {
        return match (true) {
            $this->confidence_score >= config('lokerlens.ambang_prioritas') => 'tinggi',
            $this->confidence_score >= 50 => 'sedang',
            default => 'rendah',
        };
    }

    /** Mis. "Risiko tinggi". */
    public function getLabelRisikoAttribute(): string
    {
        return 'Risiko ' . $this->tingkat_risiko;
    }

    /** Palsu jika skor >= 50, sama dengan label dari model (ml/api/app.py). */
    public function getIsPalsuAttribute(): bool
    {
        return $this->confidence_score >= 50;
    }

    /**
     * Kelas warna Tailwind sesuai tingkat risiko (warna dari tailwind.config.js:
     * risk-high merah, risk-mid oranye, risk-low hijau).
     *
     * @return array{teks: string, bar: string, badge: string, kotak: string}
     */
    public function getWarnaRisikoAttribute(): array
    {
        return match ($this->tingkat_risiko) {
            'tinggi' => ['teks' => 'text-risk-high', 'bar' => 'bg-risk-high', 'badge' => 'bg-red-100 text-risk-high border border-red-200', 'kotak' => 'bg-red-50 border-red-200'],
            'sedang' => ['teks' => 'text-risk-mid', 'bar' => 'bg-risk-mid', 'badge' => 'bg-amber-100 text-risk-mid border border-amber-200', 'kotak' => 'bg-amber-50 border-amber-200'],
            default  => ['teks' => 'text-risk-low', 'bar' => 'bg-risk-low', 'badge' => 'bg-emerald-100 text-risk-low border border-emerald-200', 'kotak' => 'bg-emerald-50 border-emerald-200'],
        };
    }
}
