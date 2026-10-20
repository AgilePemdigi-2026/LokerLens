<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TindakLanjutKasus extends Model
{
    protected $table = 'tindak_lanjut_kasus';
    protected $primaryKey = 'id_tindaklanjut';

    protected $fillable = [
        'id_pengaduan',
        'id_pengguna',
        'keputusan',
        'catatan_keputusan',
        'tanggal_keputusan',
    ];

    protected $casts = [
        'tanggal_keputusan' => 'datetime',
    ];

    public function pengaduan()
    {
        return $this->belongsTo(Pengaduan::class, 'id_pengaduan', 'id_pengaduan');
    }

    public function kepalaBidang()
    {
        return $this->belongsTo(User::class, 'id_pengguna', 'id');
    }

    public function suratTeguranLaporanPolisi()
    {
        return $this->hasOne(SuratTeguranLaporanPolisi::class, 'id_tindaklanjut', 'id_tindaklanjut');
    }
}
