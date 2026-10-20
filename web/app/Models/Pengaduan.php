<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaduan extends Model
{
    protected $table = 'pengaduan';
    protected $primaryKey = 'id_pengaduan';

    protected $fillable = [
        'id_lowongan',
        'id_pengguna',
        'nomor_tiket',
        'deskripsi_singkat',
        'status_pengaduan',
        'tanggal_pengaduan',
    ];

    protected $casts = [
        'tanggal_pengaduan' => 'datetime',
    ];

    public function lowonganKerja()
    {
        return $this->belongsTo(LowonganKerja::class, 'id_lowongan', 'id_lowongan');
    }

    public function pengguna()
    {
        return $this->belongsTo(User::class, 'id_pengguna', 'id');
    }

    public function buktiPendukung()
    {
        return $this->hasMany(BuktiPendukung::class, 'id_pengaduan', 'id_pengaduan');
    }

    public function hasilKlasifikasi()
    {
        return $this->hasOne(HasilKlasifikasi::class, 'id_pengaduan', 'id_pengaduan');
    }

    public function tindakLanjutKasus()
    {
        return $this->hasOne(TindakLanjutKasus::class, 'id_pengaduan', 'id_pengaduan');
    }

    public function riwayatKasus()
    {
        return $this->hasMany(RiwayatKasus::class, 'id_pengaduan', 'id_pengaduan')->orderBy('tanggal_update', 'desc');
    }

    /**
     * Generate nomor tiket unik, mis: #LKR-8821
     */
    public static function generateNomorTiket(): string
    {
        $last = self::latest('id_pengaduan')->first();
        $next = $last ? ($last->id_pengaduan + 8820) : 8821;
        return '#LKR-' . $next;
    }
}
