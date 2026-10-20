<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perusahaan extends Model
{
    protected $table = 'perusahaan';
    protected $primaryKey = 'id_perusahaan';

    protected $fillable = [
        'nama_perusahaan',
        'status_terdaftar',
        'alamat',
        'domain_email',
    ];

    public function lowonganKerja()
    {
        return $this->hasMany(LowonganKerja::class, 'id_perusahaan', 'id_perusahaan');
    }
}
