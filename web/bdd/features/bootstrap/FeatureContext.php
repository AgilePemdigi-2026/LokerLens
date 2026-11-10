<?php

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Testwork\Tester\Result\TestResult;

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
        // Masyarakat tanpa pengaduan (seeder), untuk tampilan kosong
        'Masyarakat Baru' => 'masyarakat.baru@lokerlens.test',
    ];
    private const PASSWORD = 'password123';

    /** Folder screenshot halaman saat langkah gagal (diabaikan git lewat /web/bdd/laporan/*). */
    private const FOLDER_SCREENSHOT = __DIR__ . '/../../laporan/screenshot';

    /** Sudah dicek bahwa Chrome terlihat (mode demo), cukup sekali per run. */
    private static bool $chromeTerlihatDicek = false;

    /**
     * @param bool $modeDemo true pada profil "demo" (behat.yml): semua skenario berjalan
     *                       di Chrome yang terlihat dan ada jeda antar langkah (BDD_JEDA).
     */
    public function __construct(private bool $modeDemo = false)
    {
    }

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
        exec(escapeshellarg(PHP_BINARY) . ' artisan migrate:fresh --seeder=DemoSeeder --force --env=behat 2>&1', $keluaran, $kode);
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

    // ─── Mode demo & screenshot ───────────────────────────────────────────

    /**
     * Mode demo: pastikan Chrome di port 9222 TERLIHAT (bukan headless), supaya skenario bisa ditonton.
     *
     * @BeforeScenario
     */
    public function pastikanChromeTerlihat(): void
    {
        if (! $this->modeDemo || self::$chromeTerlihatDicek) {
            return;
        }

        $versi = @file_get_contents('http://127.0.0.1:9222/json/version', false, stream_context_create(['http' => ['timeout' => 3]]));
        if ($versi === false) {
            throw new RuntimeException("Mode demo butuh Chrome yang terlihat di port 9222.\n"
                . 'Buka dulu (tanpa --headless): & "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" '
                . '--remote-debugging-port=9222 --user-data-dir="$env:TEMP\chrome-behat-demo" --no-first-run '
                . '--password-store=basic --disable-features=PasswordLeakDetection,PasswordManagerOnboarding,AutofillServerCommunication');
        }

        // Chrome tanpa jendela memuat kata "HeadlessChrome" (di "Browser" untuk --headless lama,
        // di "User-Agent" untuk --headless=new)
        $info = json_decode($versi, true) ?? [];
        if (str_contains(($info['Browser'] ?? '') . ' ' . ($info['User-Agent'] ?? ''), 'Headless')) {
            throw new RuntimeException("Chrome di port 9222 berjalan headless (tanpa jendela), jadi tidak bisa ditonton.\n"
                . 'Tutup Chrome itu, lalu buka lagi tanpa --headless (lihat README, bagian "Mode demo").');
        }

        self::$chromeTerlihatDicek = true;
    }

    /**
     * Mode demo: jeda setelah setiap langkah supaya mudah diikuti.
     * Lama jeda (detik) dari variabel BDD_JEDA, default 1. Contoh: $env:BDD_JEDA="2"
     *
     * @AfterStep
     */
    public function jedaModeDemo(): void
    {
        if (! $this->modeDemo) {
            return;
        }

        $jeda = getenv('BDD_JEDA');
        $detik = is_numeric($jeda) ? max(0, (float) $jeda) : 1.0;
        usleep((int) ($detik * 1_000_000));
    }

    /**
     * Langkah gagal: simpan tampilan halaman ke laporan/screenshot/.
     * Di Chrome berupa gambar .png. Di mode cepat (tanpa browser) berupa .html,
     * karena driver browserkit tidak bisa mengambil gambar.
     *
     * @AfterStep
     */
    public function screenshotJikaGagal(AfterStepScope $scope): void
    {
        if ($scope->getTestResult()->getResultCode() !== TestResult::FAILED) {
            return;
        }

        try {
            $sesi = $this->getSession();
            if (! $sesi->isStarted()) {
                return;
            }

            if (! is_dir(self::FOLDER_SCREENSHOT)) {
                mkdir(self::FOLDER_SCREENSHOT, 0777, true);
            }

            // Nama file: tanggal-jam, nama fitur, dan baris langkah yang gagal
            $nama = date('Y-m-d_H-i-s') . '_' . basename($scope->getFeature()->getFile(), '.feature')
                . '_baris-' . $scope->getStep()->getLine();

            if ($sesi->getDriver() instanceof BrowserKitDriver) {
                file_put_contents(self::FOLDER_SCREENSHOT . "/{$nama}.html", $sesi->getPage()->getContent());
                echo "Halaman saat gagal disimpan: laporan/screenshot/{$nama}.html" . PHP_EOL;
            } else {
                file_put_contents(self::FOLDER_SCREENSHOT . "/{$nama}.png", $sesi->getScreenshot());
                echo "Screenshot disimpan: laporan/screenshot/{$nama}.png" . PHP_EOL;
            }
        } catch (Throwable $e) {
            // Screenshot hanya pelengkap; jangan menutupi penyebab gagal yang sebenarnya
            echo 'Screenshot gagal dibuat: ' . $e->getMessage() . PHP_EOL;
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
        $this->isiFormLogin(self::AKUN[$peran]);
        $this->pressButton('Masuk'); // menunggu halaman tujuan selesai dimuat (lihat pressButton)

        // Masih di halaman login berarti login gagal
        if (str_contains($this->getSession()->getCurrentUrl(), '/login')) {
            throw new RuntimeException("Login sebagai {$peran} gagal.");
        }
    }

    /**
     * Langkah "I press ..." (anotasi tetap dari MinkContext; jangan disalin, nanti terdaftar dua kali).
     * Bedanya: di Chrome menunggu halaman berikutnya selesai dimuat.
     */
    public function pressButton($button)
    {
        $this->tungguSetelah(fn () => parent::pressButton($button));
    }

    /**
     * Langkah "I follow ..." (anotasi tetap dari MinkContext).
     * Bedanya: di Chrome menunggu halaman berikutnya selesai dimuat.
     */
    public function clickLink($link)
    {
        $this->tungguSetelah(fn () => parent::clickLink($link));
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
                $this->tungguSetelah(fn () => $button->press());
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

    /**
     * Klik tautan di baris tabel yang memuat nomor tiket tertentu,
     * mis. When I follow "Lihat Detail" in the row for ticket "#LKR-2025-0842"
     *
     * @When /^I follow "(?P<tautan>[^"]+)" in the row for ticket "(?P<tiket>[^"]+)"$/
     */
    public function iFollowInTheRowForTicket(string $tautan, string $tiket): void
    {
        foreach ($this->getSession()->getPage()->findAll('css', 'tr') as $baris) {
            if (str_contains($baris->getText(), $tiket)) {
                $link = $baris->findLink($tautan);
                if ($link !== null) {
                    $this->tungguSetelah(fn () => $link->click());
                    return;
                }
            }
        }

        throw new RuntimeException("Tautan \"{$tautan}\" untuk tiket {$tiket} tidak ditemukan di tabel.");
    }

    /**
     * Membuka halaman atur ulang kata sandi dengan tautan yang sah, tanpa email sungguhan.
     * Token dibuat langsung di tabel password_reset_tokens database TEST
     * (cara yang sama dengan Laravel: token disimpan dalam bentuk hash bcrypt).
     *
     * @Given /^I am on a valid password reset page for "(?P<email>[^"]+)"$/
     */
    public function iAmOnAValidPasswordResetPageFor(string $email): void
    {
        $this->pastikanDatabaseTest();

        $token = bin2hex(random_bytes(32));
        $db = $this->koneksiDatabase();
        $db->prepare('DELETE FROM password_reset_tokens WHERE email = ?')->execute([$email]);
        // created_at harus memakai zona waktu aplikasi (APP_TIMEZONE, bawaan Asia/Jakarta di config/app.php).
        // Kalau memakai UTC, token terlihat 7 jam lebih tua dan dianggap kedaluwarsa (batas 60 menit).
        $zonaWaktu = new DateTimeZone($this->env('APP_TIMEZONE') ?: 'Asia/Jakarta');
        $sekarang  = (new DateTimeImmutable('now', $zonaWaktu))->format('Y-m-d H:i:s');
        $db->prepare('INSERT INTO password_reset_tokens (email, token, created_at) VALUES (?, ?, ?)')
            ->execute([$email, password_hash($token, PASSWORD_BCRYPT), $sekarang]);

        $this->visitPath('/reset-password/' . $token . '?email=' . urlencode($email));
    }

    // ─── Fungsi bantu ─────────────────────────────────────────────────────

    /**
     * Isi email dan kata sandi di halaman login.
     * Di Chrome, tunggu dulu sampai yang tampil benar-benar halaman /login (bukan halaman
     * skenario sebelumnya). Setiap isian diperiksa di fillField() di bawah.
     */
    private function isiFormLogin(string $email): void
    {
        $sesi = $this->getSession();

        if (! $sesi->getDriver() instanceof BrowserKitDriver) {
            $sesi->wait(5000, "location.pathname === '/login' && document.readyState === 'complete'"
                . " && document.querySelector('#email') !== null");
        }

        $this->fillField('email', $email);
        $this->fillField('password', self::PASSWORD);
    }

    /** Jeda setelah halaman selesai dimuat di Chrome, sebelum langkah berikutnya mengisi form (mikrodetik). */
    private const JEDA_HALAMAN_TENANG = 500_000;

    /** Jeda sebelum memeriksa apakah isian form masih ada (mikrodetik). */
    private const JEDA_CEK_ISIAN = 300_000;

    /**
     * Sama dengan visitPath bawaan, tetapi di Chrome menunggu halaman selesai dimuat lalu jeda singkat.
     *
     * Temuan saat mode demo dengan BDD_JEDA=0: isian yang diisi tepat setelah halaman terbuka
     * (halaman dengan isian autofocus seperti /login dan /forgot-password) kadang terhapus lagi,
     * sehingga form terkirim kosong. Dengan jeda >= 0,2 detik setelah halaman terbuka, masalah ini
     * tidak muncul (47/47 lulus). Jeda ini hanya berlaku di Chrome, tidak di mode cepat (browserkit).
     */
    public function visitPath($path, $sessionName = null)
    {
        parent::visitPath($path, $sessionName);

        $sesi = $this->getSession($sessionName);
        if (! $sesi->getDriver() instanceof BrowserKitDriver) {
            $sesi->wait(5000, "document.readyState === 'complete'");
            usleep(self::JEDA_HALAMAN_TENANG);
        }
    }

    /**
     * Langkah "I fill in ..." (anotasi tetap dari MinkContext).
     * Bedanya, di Chrome nilai isian diperiksa setelah diisi:
     * 1. Isi seperti biasa (driver Chrome mengetik ke isian).
     * 2. Jika nilainya tidak ada, isi lewat JavaScript lalu kirim event input/change seperti
     *    ketikan pengguna. Temuan di mode demo: ketikan driver kadang tidak masuk sama sekali ke
     *    Chrome yang terlihat (isian tetap kosong), sedangkan di Chrome headless selalu masuk.
     * 3. Jika tetap kosong, langkah gagal dengan pesan jelas, bukan form terkirim kosong.
     */
    public function fillField($field, $value)
    {
        parent::fillField($field, $value);

        $sesi = $this->getSession();
        if ($sesi->getDriver() instanceof BrowserKitDriver) {
            return;
        }

        $nilai = $this->fixStepArgument($value);
        $elemen = $sesi->getPage()->findField($this->fixStepArgument($field));
        usleep(self::JEDA_CEK_ISIAN);
        if ($elemen === null || $elemen->getValue() === $nilai) {
            return;
        }

        // Cadangan: isi lewat JavaScript, lalu periksa lagi
        $sesi->executeScript(sprintf(
            '(function () {'
            . ' var el = document.evaluate(%s, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;'
            . ' if (!el) { return; }'
            . ' el.focus(); el.value = %s;'
            . " el.dispatchEvent(new Event('input', { bubbles: true }));"
            . " el.dispatchEvent(new Event('change', { bubbles: true }));"
            . ' })()',
            json_encode($elemen->getXpath()),
            json_encode($nilai)
        ));
        usleep(self::JEDA_CEK_ISIAN);

        if ($elemen->getValue() !== $nilai) {
            throw new RuntimeException("Isian \"{$field}\" tetap kosong setelah diisi (juga lewat JavaScript).");
        }
    }

    /** Batas waktu menunggu halaman berikutnya selesai dimuat (detik). */
    private const BATAS_TUNGGU_HALAMAN = 15;

    /**
     * Jalankan aksi (klik tombol/tautan), lalu tunggu sampai halaman berikutnya selesai dimuat.
     *
     * Kenapa perlu: setelah klik, driver Chrome hanya menunggu document.readyState "complete".
     * Saat form baru dikirim, halaman LAMA masih "complete", sehingga langkah berikutnya
     * memeriksa halaman lama (mis. login dianggap gagal karena URL masih /login).
     *
     * Caranya: sebelum klik, halaman lama diberi penanda. Halaman dianggap sudah berganti
     * jika penanda hilang (dokumen baru) dan dokumen baru selesai dimuat.
     * Aksi yang tidak berpindah halaman (membuka pop-up, Reset Form) hanya menunggu sebentar.
     * Tanpa browser (browserkit) aksi sudah selesai saat klik, jadi tidak perlu menunggu.
     */
    private function tungguSetelah(callable $aksi): void
    {
        $sesi = $this->getSession();
        if ($sesi->getDriver() instanceof BrowserKitDriver) {
            $aksi();
            return;
        }

        $penanda = bin2hex(random_bytes(8));
        // beforeunload terpanggil begitu halaman mulai berpindah (termasuk saat form dikirim)
        $sesi->executeScript("window.__behatPenanda = '{$penanda}'; window.__behatPergi = false;"
            . " window.addEventListener('beforeunload', function () { window.__behatPergi = true; });");

        $aksi();

        // 1. Apakah aksi ini berpindah halaman? Cek selama 0,5 detik.
        $pindah = false;
        for ($i = 0; $i < 5 && ! $pindah; $i++) {
            usleep(100_000);
            $pindah = $this->cekScript("window.__behatPergi === true || window.__behatPenanda !== '{$penanda}'", true);
        }
        if (! $pindah) {
            return; // tidak berpindah halaman, mis. tombol yang membuka pop-up
        }

        // 2. Tunggu dokumen baru (tanpa penanda) selesai dimuat
        $batas = microtime(true) + self::BATAS_TUNGGU_HALAMAN;
        while (microtime(true) < $batas) {
            if ($this->cekScript("window.__behatPenanda === undefined && document.readyState === 'complete'", false)) {
                return;
            }
            usleep(100_000);
        }

        throw new RuntimeException('Halaman berikutnya tidak selesai dimuat dalam ' . self::BATAS_TUNGGU_HALAMAN
            . ' detik (URL sekarang: ' . $sesi->getCurrentUrl() . ').');
    }

    /**
     * Jalankan ekspresi JavaScript yang menghasilkan true/false.
     * Saat halaman sedang berganti, Chrome bisa menolak menjalankan script; hasilnya $jikaGagal.
     */
    private function cekScript(string $ekspresi, bool $jikaGagal): bool
    {
        try {
            return (bool) $this->getSession()->evaluateScript($ekspresi);
        } catch (Throwable) {
            return $jikaGagal;
        }
    }

    /** Klik tautan pertama yang alamatnya cocok dengan pola (regex). */
    private function klikTautanYangCocok(string $pola, string $tiket): void
    {
        foreach ($this->getSession()->getPage()->findAll('css', 'a[href]') as $tautan) {
            if (preg_match($pola, parse_url($tautan->getAttribute('href'), PHP_URL_PATH) ?? '')) {
                $this->tungguSetelah(fn () => $tautan->click());
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
     *
     * Caranya tidak bergantung pada tampilan halaman (judul/teks bisa berubah saat desain diubah):
     * Laravel mengenkripsi cookie XSRF-TOKEN dengan APP_KEY server. Kalau cookie itu sah
     * menurut APP_KEY di .env.behat, berarti server membaca .env.behat, sehingga database yang
     * dipakai adalah DB_DATABASE dari .env.behat (sudah dicek berakhiran "_testing").
     * Supaya pembuktian ini berarti, APP_KEY .env.behat harus berbeda dari APP_KEY .env biasa.
     */
    private function pastikanServerBehat(): void
    {
        $kunciBehat = $this->kunciAplikasi($this->env('APP_KEY'));
        $kunciBiasa = $this->kunciAplikasi($this->nilaiDariFile(self::FOLDER_WEB . '/.env', 'APP_KEY'));

        if ($kunciBehat === '') {
            throw new RuntimeException('APP_KEY di web/.env.behat masih kosong. Jalankan dari folder web/: php artisan key:generate --env=behat');
        }

        if ($kunciBehat === $kunciBiasa) {
            throw new RuntimeException('APP_KEY di web/.env.behat sama dengan web/.env, jadi server test tidak bisa dibedakan dari server biasa. '
                . 'Buat kunci baru dari folder web/: php artisan key:generate --env=behat');
        }

        $url = rtrim($this->getMinkParameter('base_url'), '/') . '/login';
        $html = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5]]));

        if ($html === false) {
            throw new RuntimeException("Server test tidak menjawab di {$url}.\n"
                . 'Nyalakan dulu dari folder web/: $env:APP_ENV="behat"; php artisan serve --port=8010');
        }

        $cookie = $this->ambilCookie(http_get_last_response_headers() ?? [], 'XSRF-TOKEN');

        if ($cookie === null || ! $this->cookieDariKunci($cookie, $kunciBehat)) {
            throw new RuntimeException('Server di ' . $this->getMinkParameter('base_url') . ' TIDAK memakai .env.behat. '
                . 'Test dihentikan supaya database biasa tidak tersentuh. '
                . 'Matikan server itu, lalu jalankan: $env:APP_ENV="behat"; php artisan serve --port=8010');
        }
    }

    /** Ubah APP_KEY ("base64:...") menjadi kunci biner yang dipakai Laravel. */
    private function kunciAplikasi(string $appKey): string
    {
        return str_starts_with($appKey, 'base64:') ? (string) base64_decode(substr($appKey, 7)) : $appKey;
    }

    /** Ambil nilai cookie dari header "Set-Cookie" jawaban server. */
    private function ambilCookie(array $header, string $nama): ?string
    {
        foreach ($header as $baris) {
            if (preg_match('/^Set-Cookie:\s*' . preg_quote($nama, '/') . '=([^;]+)/i', $baris, $cocok)) {
                return urldecode($cocok[1]);
            }
        }

        return null;
    }

    /**
     * Cek apakah cookie terenkripsi dibuat dengan kunci ini.
     * Cookie Laravel (cipher AES-256-CBC) berisi iv, value, dan mac = HMAC-SHA256(iv . value, kunci).
     */
    private function cookieDariKunci(string $cookie, string $kunci): bool
    {
        $data = json_decode((string) base64_decode($cookie), true);

        if (! is_array($data) || ! is_string($data['iv'] ?? null) || ! is_string($data['value'] ?? null) || ! is_string($data['mac'] ?? null)) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $data['iv'] . $data['value'], $kunci), $data['mac']);
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
        $file = self::FOLDER_WEB . '/.env.behat';
        if (! is_file($file)) {
            throw new RuntimeException('web/.env.behat belum ada. Salin dari web/.env.behat.example.');
        }

        return $this->nilaiDariFile($file, $kunci);
    }

    /** Baca satu nilai dari file env (format KEY=nilai). File tidak ada = nilai kosong. */
    private function nilaiDariFile(string $file, string $kunci): string
    {
        static $cache = [];

        if (! isset($cache[$file])) {
            $cache[$file] = [];
            foreach (is_file($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [] as $baris) {
                if (str_starts_with(trim($baris), '#') || ! str_contains($baris, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $baris, 2);
                $cache[$file][trim($k)] = trim($v, " \"'");
            }
        }

        return $cache[$file][$kunci] ?? '';
    }
}
