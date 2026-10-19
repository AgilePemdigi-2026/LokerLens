<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TinjauanAnalis extends Model
{
    protected $table = 'tinjauan_analis';
    protected $primaryKey = 'id_tinjauan';

    protected $fillable = [
        'id_klasifikasi',
        'id_pengguna',
        'catatan_tinjauan',
        'hasil_tinjauan',
        'tanggal_tinjauan',
    ];

    protected $casts = [
        'tanggal_tinjauan' => 'datetime',
    ];

    public function hasilKlasifikasi()
    {
        return $this->belongsTo(HasilKlasifikasi::class, 'id_klasifikasi', 'id_klasifikasi');
    }

    public function analis()
    {
        return $this->belongsTo(User::class, 'id_pengguna', 'id');
    }
}
