<?php

namespace App\Console\Commands;

use App\Models\Pengaduan;
use App\Services\KlasifikasiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mengulang klasifikasi pengaduan yang tersimpan saat API ML mati/timeout
 * ("Menunggu klasifikasi"). Jalankan setelah API ML menyala kembali:
 *
 *     php artisan lokerlens:klasifikasi-ulang
 */
class KlasifikasiUlang extends Command
{
    protected $signature = 'lokerlens:klasifikasi-ulang';

    protected $description = 'Klasifikasi ulang pengaduan yang belum punya hasil klasifikasi';

    public function handle(KlasifikasiService $klasifikasiService): int
    {
        $daftar = Pengaduan::menungguKlasifikasi()->with('lowonganKerja')->get();

        if ($daftar->isEmpty()) {
            $this->info('Tidak ada pengaduan yang menunggu klasifikasi.');
            return self::SUCCESS;
        }

        $berhasil = 0;
        foreach ($daftar as $pengaduan) {
            $hasil = $klasifikasiService->klasifikasi($pengaduan->lowonganKerja->deskripsi_lowongan);

            if ($hasil === null) {
                // API masih mati: berhenti, tidak perlu mencoba sisanya
                $this->error("Gagal mengklasifikasi {$pengaduan->nomor_tiket}. Pastikan API ML menyala (" . config('services.ml.url') . ').');
                break;
            }

            DB::transaction(fn () => $pengaduan->simpanHasilKlasifikasi($hasil));
            $berhasil++;

            $this->line("{$pengaduan->nomor_tiket}: skor " . round($hasil['probabilitas_penipuan'] * 100, 1)
                . ", status {$pengaduan->status_pengaduan->value}");
        }

        $this->info("Selesai: {$berhasil} dari {$daftar->count()} pengaduan diklasifikasi.");

        return $berhasil === $daftar->count() ? self::SUCCESS : self::FAILURE;
    }
}
