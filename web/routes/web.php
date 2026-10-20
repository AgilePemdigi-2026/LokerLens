<?php

use App\Http\Controllers\BerandaController;
use App\Http\Controllers\BuatSuratController;
use App\Http\Controllers\PeriksaLowonganController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TindakLanjutController;
use App\Http\Controllers\TinjauLowonganController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — LokerLens
|--------------------------------------------------------------------------
|
| Rute resmi dari skenario BDD (Bagian 5.1 panduan) — jangan diubah.
| Route guard: CheckRole middleware memastikan akses per peran (NFR-SEC-02).
|
*/

// Halaman awal → redirect ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// ─── Autentikasi (Laravel Breeze) ────────────────────────────────────────────
require __DIR__ . '/auth.php';

// ─── Mode Preview Pengembangan (/dev) ────────────────────────────────────────
// Hanya terdaftar jika APP_ENV=local DAN DEV_PREVIEW=true.
if (\App\Support\DevPreview::aktif()) {
    require __DIR__ . '/dev.php';
}

// ─── Rute Terproteksi (wajib login) ──────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // ── Masyarakat ─────────────────────────────────────────────────────────
    Route::middleware('role:Masyarakat')->group(function () {

        // Beranda (Sprint 2 — route sudah ada sebagai redirect target)
        Route::get('/beranda', [BerandaController::class, 'index'])
            ->name('beranda');

        // Periksa Lowongan Kerja — /periksa-lowongan/ (Sprint 1)
        Route::get('/periksa-lowongan', [PeriksaLowonganController::class, 'index'])
            ->name('periksa-lowongan.index');
        Route::post('/periksa-lowongan', [PeriksaLowonganController::class, 'store'])
            ->name('periksa-lowongan.store');
        Route::get('/periksa-lowongan/hasil/{id}', [PeriksaLowonganController::class, 'hasil'])
            ->name('periksa-lowongan.hasil');
    });

    // ── Analis ──────────────────────────────────────────────────────────────
    Route::middleware('role:Analis')->group(function () {

        // Tinjau Lowongan — /tinjau-lowongan/ (Sprint 1)
        Route::get('/tinjau-lowongan', [TinjauLowonganController::class, 'index'])
            ->name('tinjau-lowongan.index');
        Route::get('/tinjau-lowongan/detail/{id}', [TinjauLowonganController::class, 'detail'])
            ->name('tinjau-lowongan.detail');
        Route::post('/tinjau-lowongan/detail/{id}/simpan', [TinjauLowonganController::class, 'simpanTinjauan'])
            ->name('tinjau-lowongan.simpan');
    });

    // ── Kepala Bidang ────────────────────────────────────────────────────────
    Route::middleware('role:Kepala Bidang')->group(function () {

        // Tindak Lanjut — /tindak-lanjut/ (Sprint 1)
        Route::get('/tindak-lanjut', [TindakLanjutController::class, 'index'])
            ->name('tindak-lanjut.index');
        Route::get('/tindak-lanjut/{id}', [TindakLanjutController::class, 'detail'])
            ->name('tindak-lanjut.detail');
        Route::post('/tindak-lanjut/{id}/keputusan', [TindakLanjutController::class, 'simpanKeputusan'])
            ->name('tindak-lanjut.keputusan');
        Route::get('/tindak-lanjut/{id}/hasil', [TindakLanjutController::class, 'hasil'])
            ->name('tindak-lanjut.hasil');

        // Buat Surat — /buat-surat/ (Sprint 1) — FR-ST-02: hanya setelah Tindaklanjuti
        Route::get('/buat-surat/{pengaduanId}', [BuatSuratController::class, 'index'])
            ->name('buat-surat.index');
        Route::post('/buat-surat/{pengaduanId}/draft', [BuatSuratController::class, 'simpanDraft'])
            ->name('buat-surat.draft');
        Route::post('/buat-surat/{pengaduanId}/terbitkan', [BuatSuratController::class, 'terbitkan'])
            ->name('buat-surat.terbitkan');
        Route::get('/buat-surat/{pengaduanId}/terbit/{suratId}', [BuatSuratController::class, 'terbit'])
            ->name('buat-surat.terbit');
    });

    // ── Profil (semua peran) ─────────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
