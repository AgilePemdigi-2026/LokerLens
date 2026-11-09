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

    /**
     * Rapikan frasa kata pemicu dari model: kata yang berulang cukup ditulis sekali.
     * Mis. "nominal Rp nominal Rp" → "nominal Rp" (bigram token "nominalrp nominalrp" dari API ML).
     * Hanya mengubah tampilan; data di database tetap apa adanya.
     */
    public static function rapikanFrasa(string $frasa): string
    {
        $hasil = [];
        foreach (preg_split('/\s+/', trim($frasa)) as $kata) {
            // Kunci huruf kecil supaya "Rp" dan "rp" dianggap sama
            $hasil[mb_strtolower($kata)] ??= $kata;
        }

        return implode(' ', $hasil);
    }

    /**
     * Nama indikator untuk ditampilkan. Kata pemicu model dirapikan; indikator lain apa adanya.
     */
    public function getNamaTampilAttribute(): string
    {
        return $this->kategori_indikator === self::KATEGORI_KATA_PEMICU
            ? self::rapikanFrasa($this->nama_indikator)
            : $this->nama_indikator;
    }

    /**
     * Kata pemicu yang ditampilkan ke pengguna. Dari model ML = nama indikator yang sudah dirapikan;
     * data lama/seeder memakai kata pertama nama indikator.
     */
    public function getKataPemicuAttribute(): string
    {
        return $this->kategori_indikator === self::KATEGORI_KATA_PEMICU
            ? $this->nama_tampil
            : strtolower(explode(' ', $this->nama_indikator)[0]);
    }
}
