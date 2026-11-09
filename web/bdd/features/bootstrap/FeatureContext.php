<?php

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Langkah tambahan untuk skenario BDD LokerLens (SRS Appendix B).
 *
 * Langkah bawaan Mink ("I fill in", "I press", "I should see", ...) diwarisi
 * dari MinkContext. Di sini hanya ada langkah yang tidak disediakan Mink,
 * ditambah pengaman dan reset database sebelum setiap skenario.
 */
class FeatureContext extends MinkContext
{
    /** Folder web/ (aplikasi Laravel). */
    private const FOLDER_WEB = __DIR__ . '/../../..';

    /** Akun demo dari database/seeders/LokerLensSeeder.php (AKUN_DEMO). */
    private const AKUN = [
        'Masyarakat'    => 'adelulinn@gmail.com',
        'Analis'        => 'budi.analis@lokerlens.go.id',
        'Kepala Bidang' => 'rizky.kb@lokerlens.go.id',
    ];
    private const PASSWORD = 'password123';

    // ─── Persiapan sebelum setiap skenario ────────────────────────────────

    /**
     * 1. Pastikan server test memakai .env.behat (bukan .env biasa).
     * 2. Kosongkan database test dan isi ulang dengan seeder.
     *
     * @BeforeScenario
     */
    public function siapkanSkenario(): void
    {
        $this->pastikanDatabaseTest();
        $this->pastikanServerBehat();

        // Jalankan artisan dari folder web/ dengan --env=behat (membaca .env.behat)
        $folderAwal = getcwd();
        chdir(realpath(self::FOLDER_WEB));
        exec(escapeshellarg(PHP_BINARY) . ' artisan migrate:fresh --seed --force --env=behat 2>&1', $keluaran, $kode);
        chdir($folderAwal);

        if ($kode !== 0) {
            throw new RuntimeException("Reset database test gagal:\n" . implode("\n", $keluaran));
        }
    }

    /**
     * Skenario @ml memakai API ML sungguhan, jadi API harus menyala.
     *
     * @BeforeScenario @ml
     */
    public function pastikanApiMlMenyala(BeforeScenarioScope $scope): void
    {
        $url = $this->env('ML_API_URL') . '/health';

        if (@file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 3]])) === false) {
            throw new RuntimeException("API ML tidak menjawab di {$url}.\n"
                . 'Nyalakan dulu dari folder ml/: uvicorn api.app:app --port 8001');
        }
    }

    // ─── Langkah tambahan ─────────────────────────────────────────────────

    /**
     * Login lewat halaman /login dengan akun demo seeder.
     *
     * @Given /^I am logged in as "(?P<peran>[^"]+)"$/
     */
    public function iAmLoggedInAs(string $peran): void
    {
        if (! isset(self::AKUN[$peran])) {
            throw new InvalidArgumentException("Peran tidak dikenal: {$peran}");
        }

        $this->visitPath('/login');
        $this->fillField('email', self::AKUN[$peran]);
        $this->fillField('password', self::PASSWORD);
        $this->pressButton('Masuk');

        // Masih di halaman login berarti login gagal
        if (str_contains($this->getSession()->getCurrentUrl(), '/login')) {
            throw new RuntimeException("Login sebagai {$peran} gagal.");
        }
    }

    /**
     * Gaya kalimat SRS: Given I am on the "/periksa-lowongan/" page
     *
     * @Given /^I am on the "(?P<path>[^"]+)" page$/
     */
    public function iAmOnThePage(string $path): void
    {
        $this->visitPath($path);
    }

    /**
     * Detail tinjauan Analis untuk satu nomor tiket (dicari lewat daftar).
     *
     * @Given /^I am on the review detail page for ticket "(?P<tiket>[^"]+)"$/
     */
    public function iAmOnTheReviewDetailPageForTicket(string $tiket): void
    {
        $this->visitPath('/tinjau-lowongan?nomor_tiket=' . urlencode($tiket));
        $this->klikTautanYangCocok('#/tinjau-lowongan/detail/\d+$#', $tiket);
    }

    /**
     * Detail laporan Kepala Bidang untuk satu nomor tiket (dicari lewat daftar).
     *
     * @Given /^I am on the report detail page for ticket "(?P<tiket>[^"]+)"$/
     */
    public function iAmOnTheReportDetailPageForTicket(string $tiket): void
    {
        $this->visitPath('/tindak-lanjut?cari=' . urlencode($tiket));
        $this->klikTautanYangCocok('#/tindak-lanjut/\d+$#', $tiket);
    }

    /**
     * Halaman Buat Laporan Polisi untuk satu nomor tiket (lewat detail laporan).
     *
     * @Given /^I am on the letter page for ticket "(?P<tiket>[^"]+)"$/
     */
    public function iAmOnTheLetterPageForTicket(string $tiket): void
    {
        $this->iAmOnTheReportDetailPageForTicket($tiket);
        $this->clickLink('Lanjut ke Pembuatan Surat');
    }

    /**
     * Tombol di dalam pop-up baru bisa ditekan setelah pop-up tampil.
     *
     * @When /^I press "(?P<tombol>[^"]+)" in the pop-up$/
     */
    public function iPressInThePopUp(string $tombol): void
    {
        $halaman = $this->getSession()->getPage();

        // Tunggu maksimal 5 detik sampai tombol terlihat
        for ($i = 0; $i < 50; $i++) {
            $button = $halaman->findButton($tombol);
            if ($button !== null && $button->isVisible()) {
                $button->press();
                return;
            }
            usleep(100_000);
        }

        throw new RuntimeException("Tombol \"{$tombol}\" di pop-up tidak muncul.");
    }

    /**
     * Membuat penerbitan surat gagal (SRS B.5: "penerbitan dokumen gagal diproses sistem").
     * Caranya memasang trigger MySQL di database TEST yang menolak status "Terbit".
     * Trigger ikut terhapus saat database di-reset sebelum skenario berikutnya.
     *
     * @Given /^penerbitan dokumen akan gagal diproses sistem$/
     */
    public function penerbitanDokumenAkanGagal(): void
    {
        $this->pastikanDatabaseTest();

        $this->koneksiDatabase()->exec("
            CREATE TRIGGER behat_gagal_terbit
            BEFORE UPDATE ON surat_teguran_laporan_polisi
            FOR EACH ROW
            BEGIN
                IF NEW.status_penerbitan = 'Terbit' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Simulasi penerbitan gagal (Behat)';
                END IF;
            END
        ");
    }

    // ─── Fungsi bantu ─────────────────────────────────────────────────────

    /** Klik tautan pertama yang alamatnya cocok dengan pola (regex). */
    private function klikTautanYangCocok(string $pola, string $tiket): void
    {
        foreach ($this->getSession()->getPage()->findAll('css', 'a[href]') as $tautan) {
            if (preg_match($pola, parse_url($tautan->getAttribute('href'), PHP_URL_PATH) ?? '')) {
                $tautan->click();
                return;
            }
        }

        throw new RuntimeException("Kasus dengan tiket {$tiket} tidak ditemukan di daftar.");
    }

    /** Pengaman: database di .env.behat harus database khusus test. */
    private function pastikanDatabaseTest(): void
    {
        $database = $this->env('DB_DATABASE');

        if (! str_ends_with($database, '_testing')) {
            throw new RuntimeException("DB_DATABASE di .env.behat harus diakhiri \"_testing\" (sekarang: \"{$database}\"). "
                . 'Test BDD menghapus isi database ini.');
        }
    }

    /**
     * Pengaman: server di base_url harus berjalan dengan .env.behat.
     * Halaman lupa password memakai judul dari APP_NAME ("LokerLens BEHAT").
     */
    private function pastikanServerBehat(): void
    {
        $url = rtrim($this->getMinkParameter('base_url'), '/') . '/forgot-password';
        $html = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5]]));

        if ($html === false) {
            throw new RuntimeException("Server test tidak menjawab di {$url}.\n"
                . 'Nyalakan dulu dari folder web/: $env:APP_ENV="behat"; php artisan serve --port=8010');
        }

        if (! str_contains($html, '<title>LokerLens BEHAT</title>')) {
            throw new RuntimeException('Server di ' . $this->getMinkParameter('base_url') . ' TIDAK memakai .env.behat. '
                . 'Test dihentikan supaya database biasa tidak tersentuh. '
                . 'Matikan server itu, lalu jalankan: $env:APP_ENV="behat"; php artisan serve --port=8010');
        }
    }

    private function koneksiDatabase(): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $this->env('DB_HOST'), $this->env('DB_PORT'), $this->env('DB_DATABASE'));

        return new PDO($dsn, $this->env('DB_USERNAME'), $this->env('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    /** Baca satu nilai dari web/.env.behat (format KEY=nilai). */
    private function env(string $kunci): string
    {
        static $isi = null;

        if ($isi === null) {
            $file = self::FOLDER_WEB . '/.env.behat';
            if (! is_file($file)) {
                throw new RuntimeException('web/.env.behat belum ada. Salin dari web/.env.behat.example.');
            }
            $isi = [];
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $baris) {
                if (str_starts_with(trim($baris), '#') || ! str_contains($baris, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $baris, 2);
                $isi[trim($k)] = trim($v, " \"'");
            }
        }

        return $isi[$kunci] ?? '';
    }
}
