<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Memanggil API klasifikasi LokerLens (ml/api/app.py, FastAPI).
 * Format request/response: docs/architecture.md §5.
 *
 * Alamat dan timeout diatur di config/services.php (ML_API_URL, ML_API_TIMEOUT).
 */
class KlasifikasiService
{
    /**
     * Kirim deskripsi lowongan ke API, kembalikan hasilnya.
     *
     * Hasil (jika berhasil):
     *   [
     *     'probabilitas_penipuan' => 0.92,              // 0–1
     *     'label'                 => 'palsu',           // 'asli' / 'palsu'
     *     'kata_pemicu'           => [['kata' => 'biaya administrasi', 'bobot' => 0.31], ...],
     *     'versi_model'           => 'Logistic Regression TF-IDF (2026-10-26)',
     *   ]
     *
     * Mengembalikan null jika API mati, timeout, atau jawabannya tidak sesuai format.
     */
    public function klasifikasi(string $deskripsi): ?array
    {
        try {
            $response = Http::timeout(config('services.ml.timeout'))
                ->acceptJson()
                ->post(config('services.ml.url') . '/predict', [
                    'deskripsi' => $deskripsi,
                ]);
        } catch (ConnectionException $e) {
            // API tidak menyala atau melewati batas waktu
            Log::warning('API klasifikasi tidak bisa dihubungi: ' . $e->getMessage());
            return null;
        }

        if ($response->failed()) {
            Log::warning('API klasifikasi menjawab error ' . $response->status() . ': ' . $response->body());
            return null;
        }

        $hasil = $response->json();

        // Pastikan semua data yang dibutuhkan ada sebelum disimpan
        if (! is_numeric($hasil['probabilitas_penipuan'] ?? null)
            || ! in_array($hasil['label'] ?? null, ['asli', 'palsu'], true)
            || ! is_array($hasil['kata_pemicu'] ?? null)) {
            Log::warning('Format jawaban API klasifikasi tidak sesuai: ' . $response->body());
            return null;
        }

        return $hasil;
    }
}
