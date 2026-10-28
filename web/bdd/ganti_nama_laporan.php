<?php

/**
 * Ganti nama laporan HTML Behat terbaru supaya mudah dipahami.
 *
 *   laporan/Twig_20261028153831.html  ->  laporan/hasil-test-bdd_2026-10-28_15-38.html
 *
 * Pemakaian (dari dalam web/bdd/):
 *   composer test-bdd                       jalankan semua skenario, lalu ganti nama laporan
 *   composer test-bdd -- --tags=@ml         argumen setelah "--" diteruskan ke Behat
 *   php ganti_nama_laporan.php              hanya ganti nama laporan terbaru (tanpa menjalankan Behat)
 *
 * Laporan tetap berada di folder laporan/, jadi CSS di laporan/assets/ tetap terbaca.
 */

$folderLaporan = __DIR__ . '/laporan';
$argumen = array_slice($argv, 1);
$kodeKeluar = 0;

// Dengan --jalankan-behat, Behat dijalankan dulu di sini (bukan di composer.json),
// supaya ganti nama tetap dilakukan walaupun ada skenario yang gagal.
if (($argumen[0] ?? '') === '--jalankan-behat') {
    $perintah = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/vendor/bin/behat')
        . ' --config ' . escapeshellarg(__DIR__ . '/behat.yml');
    foreach (array_slice($argumen, 1) as $arg) {
        $perintah .= ' ' . escapeshellarg($arg);
    }
    passthru($perintah, $kodeKeluar);
    echo PHP_EOL;
}

// Cari laporan Twig_*.html terbaru. Nama file berisi tanggal-jam (YmdHis), jadi urutan nama = urutan waktu.
$daftar = glob($folderLaporan . '/Twig_*.html') ?: [];
if ($daftar === []) {
    echo "Tidak ada laporan Twig_*.html di {$folderLaporan}" . PHP_EOL;
    exit($kodeKeluar ?: 1);
}
sort($daftar);
$laporanLama = end($daftar);

// Ambil tanggal dan jam dari nama file, misalnya Twig_20261028153831.html
if (!preg_match('/Twig_(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})\.html$/', $laporanLama, $w)) {
    echo 'Format nama laporan tidak dikenali: ' . basename($laporanLama) . PHP_EOL;
    exit($kodeKeluar ?: 1);
}
$laporanBaru = "{$folderLaporan}/hasil-test-bdd_{$w[1]}-{$w[2]}-{$w[3]}_{$w[4]}-{$w[5]}.html";

// Kalau dalam menit yang sama sudah ada laporan, tambahkan detik supaya tidak tertimpa.
if (file_exists($laporanBaru)) {
    $laporanBaru = "{$folderLaporan}/hasil-test-bdd_{$w[1]}-{$w[2]}-{$w[3]}_{$w[4]}-{$w[5]}-{$w[6]}.html";
}

if (!rename($laporanLama, $laporanBaru)) {
    echo 'Gagal mengganti nama ' . basename($laporanLama) . PHP_EOL;
    exit($kodeKeluar ?: 1);
}

echo 'Laporan HTML: ' . realpath($laporanBaru) . PHP_EOL;

// Kode keluar mengikuti hasil Behat (bukan 0) supaya kegagalan test tetap terlihat.
exit($kodeKeluar);
