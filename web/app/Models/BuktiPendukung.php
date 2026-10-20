<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuktiPendukung extends Model
{
    protected $table = 'bukti_pendukung';
    protected $primaryKey = 'id_bukti';

    protected $fillable = [
        'id_pengaduan',
        'nama_file',
        'tipe_file',
        'ukuran_file',
        'path_file',
        'tanggal_upload',
    ];

    protected $casts = [
        'tanggal_upload' => 'datetime',
    ];

    public function pengaduan()
    {
        return $this->belongsTo(Pengaduan::class, 'id_pengaduan', 'id_pengaduan');
    }
}
