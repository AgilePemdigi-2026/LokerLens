<?php

namespace App\Models;

use App\Enums\StatusKasus;
use App\Exceptions\PerubahanStatusTidakValid;
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
        'status_pengaduan'  => StatusKasus::class,
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
     * Ubah status kasus satu langkah maju dan catat ke Riwayat Kasus (BR-11, NFR-SAF-06).
     * Perubahan yang melompat, mundur, atau dari status akhir ditolak.
     *
     * @throws PerubahanStatusTidakValid
     */
    public function ubahStatus(StatusKasus $tujuan, string $keterangan): void
    {
        if (! $this->status_pengaduan->bolehMenjadi($tujuan)) {
            throw PerubahanStatusTidakValid::dari($this->status_pengaduan, $tujuan);
        }

        $this->update(['status_pengaduan' => $tujuan]);
        $this->catatRiwayat($keterangan);
    }

    /**
     * Catat status saat ini ke Riwayat Kasus.
     */
    public function catatRiwayat(string $keterangan): RiwayatKasus
    {
        return RiwayatKasus::create([
            'id_pengaduan' => $this->id_pengaduan,
            'status_kasus' => $this->status_pengaduan->value,
            'keterangan'   => $keterangan,
        ]);
    }

    /**
     * Generate nomor tiket unik, mis: #LKR-8821
     * Dimulai dari 8821 + id terakhir, lalu dinaikkan sampai belum dipakai
     * (nomor tiket pada data seeder bisa saja sudah menempati angka tersebut).
     */
    public static function generateNomorTiket(): string
    {
        $angka = 8821 + (int) self::max('id_pengaduan');

        while (self::where('nomor_tiket', '#LKR-' . $angka)->exists()) {
            $angka++;
        }

        return '#LKR-' . $angka;
    }
}
