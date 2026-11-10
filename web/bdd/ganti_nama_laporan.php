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
 *
 * Supaya laporan tidak menumpuk, setelah ganti nama:
 *   - sisa Twig_*.html lama dihapus;
 *   - hanya 5 laporan hasil-test-bdd_*.html terbaru yang disimpan. Laporan final yang
 *     di-track Git (bukti sprint) tidak pernah dihapus.
 */

$folderLaporan = __DIR__ . '/laporan';
$jumlahLaporanDisimpan = 5;
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

bersihkanLaporanLama($folderLaporan, $jumlahLaporanDisimpan);

// Kode keluar mengikuti hasil Behat (bukan 0) supaya kegagalan test tetap terlihat.
exit($kodeKeluar);

/**
 * Hapus sisa Twig_*.html, lalu simpan hanya $jumlah laporan hasil-test-bdd_*.html terbaru
 * yang tidak di-track Git. Laporan yang di-track Git selalu dibiarkan.
 */
function bersihkanLaporanLama(string $folder, int $jumlah): void
{
    // Sisa laporan Twig yang belum diganti namanya (misalnya dari menjalankan behat langsung)
    foreach (glob($folder . '/Twig_*.html') ?: [] as $file) {
        unlink($file);
    }

    // Daftar laporan yang di-track Git. Kalau Git tidak bisa dipanggil, jangan hapus apa pun.
    $keluaran = [];
    $kode = 1;
    exec('git -C ' . escapeshellarg($folder) . ' ls-files -- ' . escapeshellarg('hasil-test-bdd_*.html') . ' 2>&1', $keluaran, $kode);
    if ($kode !== 0) {
        echo 'Git tidak tersedia, laporan lama tidak dibersihkan.' . PHP_EOL;
        return;
    }
    $dilacakGit = array_map('basename', $keluaran);

    // Laporan yang tidak di-track Git, terbaru di depan (nama berisi tanggal-jam)
    $laporan = array_filter(
        glob($folder . '/hasil-test-bdd_*.html') ?: [],
        fn (string $file) => ! in_array(basename($file), $dilacakGit, true)
    );
    rsort($laporan);

    $dihapus = array_slice($laporan, $jumlah);
    foreach ($dihapus as $file) {
        unlink($file);
    }

    if ($dihapus !== []) {
        echo count($dihapus) . " laporan lama dihapus (disimpan {$jumlah} laporan terbaru)." . PHP_EOL;
    }
}
