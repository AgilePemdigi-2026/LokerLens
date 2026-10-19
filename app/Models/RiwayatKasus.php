<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatKasus extends Model
{
    protected $table = 'riwayat_kasus';
    protected $primaryKey = 'id_riwayat';

    protected $fillable = [
        'id_pengaduan',
        'status_kasus',
        'keterangan',
        'tanggal_update',
    ];

    protected $casts = [
        'tanggal_update' => 'datetime',
    ];

    public function pengaduan()
    {
        return $this->belongsTo(Pengaduan::class, 'id_pengaduan', 'id_pengaduan');
    }
}
