<?php

namespace App\Models;

use App\Enums\StatusKasus;
use App\Exceptions\PerubahanStatusTidakValid;
use Illuminate\Database\Eloquent\Model;
   use Illuminate\Database\Eloquent\Casts\Attribute;

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
       protected function warnaBadge(): Attribute
   {
       return Attribute::get(fn () => $this->status_pengaduan->warnaBadge());
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
     * Pengaduan yang belum punya hasil klasifikasi, karena API ML mati atau
     * timeout saat pemeriksaan. Ditampilkan sebagai "Menunggu klasifikasi".
     */
    public function scopeMenungguKlasifikasi($query)
    {
        return $query->doesntHave('hasilKlasifikasi');
    }

    /**
     * Simpan hasil dari API klasifikasi (KlasifikasiService) untuk pengaduan ini:
     * Hasil_Klasifikasi, satu Indikator_Mencurigakan per kata pemicu, lalu status
     * awal kasus sesuai ambang prioritas (BR-05) dan catatan Riwayat Kasus.
     * Dipanggil di dalam DB::transaction oleh pemanggilnya.
     */
    public function simpanHasilKlasifikasi(array $hasil): HasilKlasifikasi
    {
        // API memberi 0–1, database menyimpan 0–100 (docs/architecture.md §5.3)
        $skor   = round($hasil['probabilitas_penipuan'] * 100, 2);
        $status = StatusKasus::awalDariSkor($skor);

        $klasifikasi = HasilKlasifikasi::create([
            'id_pengaduan'     => $this->id_pengaduan,
            'confidence_score' => $skor,
            'kategori_hasil'   => $hasil['label'] === 'palsu'
                ? 'Lowongan berpotensi penipuan'
                : 'Lowongan kemungkinan asli',
            'status_prioritas' => $status === StatusKasus::PerluTinjauan,
            'metode_analisis'  => $hasil['versi_model'] ?? null,
        ]);

        foreach ($hasil['kata_pemicu'] as $pemicu) {
            IndikatorMencurigakan::create([
                'id_klasifikasi'     => $klasifikasi->id_klasifikasi,
                'nama_indikator'     => $pemicu['kata'],
                'kategori_indikator' => IndikatorMencurigakan::KATEGORI_KATA_PEMICU,
                'bobot_indikator'    => round($pemicu['bobot'] * 100, 2),
            ]);
        }

        // Status diisi langsung (bukan ubahStatus) karena ini status AWAL kasus
        $this->update(['status_pengaduan' => $status]);
        $this->catatRiwayat($status === StatusKasus::PerluTinjauan
            ? 'Pengaduan diterima dan klasifikasi dilakukan. Masuk antrean tinjauan Analis.'
            : 'Pengaduan diterima dan klasifikasi dilakukan. Skor di bawah ambang prioritas, kasus selesai.');

        return $klasifikasi;
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
