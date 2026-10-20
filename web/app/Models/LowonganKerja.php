<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LowonganKerja extends Model
{
    protected $table = 'lowongan_kerja';
    protected $primaryKey = 'id_lowongan';

    protected $fillable = [
        'id_perusahaan',
        'posisi_lowongan',
        'link_lowongan',
        'deskripsi_lowongan',
        'informasi_tambahan',
        'sumber_lowongan',
        'tanggal_input',
    ];

    protected $casts = [
        'tanggal_input' => 'datetime',
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function pengaduan()
    {
        return $this->hasMany(Pengaduan::class, 'id_lowongan', 'id_lowongan');
    }
}
