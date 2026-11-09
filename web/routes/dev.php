<?php

use App\Http\Controllers\DevPreviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route Mode Preview Pengembangan
|--------------------------------------------------------------------------
|
| Hanya dimuat jika APP_ENV=local DAN DEV_PREVIEW=true (lihat routes/web.php).
| Tidak memakai middleware auth/role: halaman tujuan tetap dilindungi
| aturan login dan hak akses yang sudah ada.
|
*/

Route::get('/dev', [DevPreviewController::class, 'index'])->name('dev.index');
Route::post('/dev/masuk/{peran}', [DevPreviewController::class, 'masuk'])->name('dev.masuk');
