# LokerLens

**Sistem Deteksi Lowongan Kerja Mencurigakan sebagai Dukungan Pengawasan Informasi Ketenagakerjaan Digital**

LokerLens adalah sistem berbasis web yang membantu **instansi pemerintah** mendeteksi, meninjau, dan menindaklanjuti lowongan kerja yang terindikasi mencurigakan. Masyarakat memeriksa teks lowongan dan langsung melihat **probabilitas penipuan (0–100%)** beserta **kata pemicu**. Lowongan berisiko tinggi masuk antrean Analis, lalu Kepala Bidang memutuskan tindak lanjutnya, sampai penerbitan Surat Teguran/Laporan Polisi.

Hasil klasifikasi bersifat **rekomendasi**, bukan penetapan otomatis bahwa suatu lowongan adalah penipuan. Keputusan akhir tetap berada pada manusia.

> Proyek mata kuliah **Pembangunan Perangkat Lunak**, Kelas **I4**, Program Studi S1 Sistem Informasi, Fakultas Sains dan Teknologi, Universitas Airlangga, 2026.

---

## Anggota Kelompok 4

| Nama                   | NIM       |
| ---------------------- | --------- |
| Adela Ulin Nadhroh     | 187241021 |
| Tan Steven Manuel      | 187241052 |
| Gendhis Putri Hatmanti | 187241076 |
| Reyhan Fachrizky Pasha | 187241113 |

---

## Alur Utama

1. **Masyarakat** memeriksa lowongan kerja dan langsung melihat probabilitas penipuan serta kata pemicu.
2. Lowongan dengan probabilitas di atas ambang (default 70%) masuk antrean **Analis**. Di bawah ambang, kasus selesai sebagai *Selesai - Risiko Rendah*.
3. **Analis** meninjau dan memilih *Terkonfirmasi Mencurigakan* (diteruskan ke Kepala Bidang) atau *Tidak Terbukti* (kasus ditutup).
4. **Kepala Bidang** memutuskan *Tindaklanjuti* atau *Tidak Ditindaklanjuti*.
5. Untuk kasus yang ditindaklanjuti, Kepala Bidang menerbitkan **Surat Teguran/Laporan Polisi**, dan kasus dinyatakan *Selesai*.

Penjelasan lengkap ada di [docs/prd.md](docs/prd.md).

---

## Struktur Folder

```
LokerLens/
├── web/     ← aplikasi web Laravel (semua perintah composer/artisan/npm dijalankan dari sini)
├── ml/      ← komponen machine learning (Python)
│   ├── data/        data latih (data/raw/ tidak masuk git)
│   ├── notebooks/   eksplorasi & perbandingan model
│   ├── src/         kode training & API
│   └── models/      model hasil training (*.pkl tidak masuk git)
├── docs/    ← dokumen proyek (docs/referensi/ tidak masuk git)
└── README.md
```

---

## Teknologi

| Bagian | Teknologi |
|---|---|
| Backend & frontend | Laravel 13 (PHP 8.3+), Blade, Laravel Breeze |
| Tampilan | Tailwind CSS, Alpine.js, Vite |
| Basis data | SQLite saat pengembangan (DBMS produksi masih didiskusikan) |
| Machine learning | Python, scikit-learn (TF-IDF; Naive Bayes, Logistic Regression, SVM, Random Forest) |
| API ML | FastAPI + Uvicorn (`ml/api/app.py`, port 8001) |

---

## Cara Menjalankan

### Aplikasi web (dari dalam `web/`)

Kebutuhan: PHP 8.3+, Composer, Node.js + npm.

```bash
cd web

# 1. Install dependensi
composer install
npm install

# 2. Siapkan konfigurasi
cp .env.example .env
php artisan key:generate

# 3. Siapkan database SQLite + data contoh
touch database/database.sqlite          # PowerShell: New-Item database/database.sqlite
php artisan migrate --seed

# 4. Build tampilan, lalu jalankan
npm run build                           # atau: npm run dev (mode pengembangan)
php artisan serve
```

Buka `http://localhost:8000`. Akun contoh untuk ketiga peran ada di `web/database/seeders/LokerLensSeeder.php`.

**Mode preview (khusus laptop pengembang).** Untuk mengecek tampilan dengan cepat, tambahkan `DEV_PREVIEW=true` di `web/.env` (pastikan `APP_ENV=local`), jalankan `php artisan migrate:fresh --seed`, lalu buka `http://localhost:8000/dev`. Di sana ada tombol "Masuk sebagai Masyarakat/Analis/Kepala Bidang" (login dengan akun demo seeder) dan daftar tautan ke semua halaman per peran, memakai ID kasus dari seeder untuk setiap status. Aturan login dan hak akses tetap berlaku, jadi halaman peran lain tetap ditolak. Jika `DEV_PREVIEW` bukan `true` atau `APP_ENV` bukan `local`, route `/dev` sama sekali tidak terdaftar. Jangan aktifkan di server sungguhan.

Menjalankan test PHPUnit (test BDD ada di bagian [Menjalankan Test BDD](#menjalankan-test-bdd-behat)):

```bash
cd web
php artisan test
```

### Komponen ML (dari dalam `ml/`)

Kebutuhan: Python 3.13.

```bash
cd ml
python -m venv .venv
.venv\Scripts\activate                  # macOS/Linux: source .venv/bin/activate
pip install -r requirements.txt
```

Model dilatih di `ml/notebooks/LokerLens_Training_Model.ipynb`. Hasilnya `ml/models/model_lokerlens.joblib`. Jangan memasang `Sastrawi`, karena model dilatih tanpa Sastrawi dan API akan menolak menyala kalau Sastrawi terpasang.

### Menjalankan API ML dan Laravel bersamaan

Fitur **Periksa Lowongan Kerja** memanggil API ML. Buka **dua terminal**:

```bash
# Terminal 1: API ML (dari dalam ml/, venv aktif)
cd ml
.venv\Scripts\activate                  # macOS/Linux: source .venv/bin/activate
uvicorn api.app:app --port 8001
# tunggu sampai muncul "Uvicorn running on http://127.0.0.1:8001"

# Terminal 2: Laravel (dari dalam web/)
cd web
php artisan serve
```

Pastikan `web/.env` berisi `ML_API_URL=http://localhost:8001` (sudah ada di `.env.example`). Format request/response API ada di [docs/architecture.md §5](docs/architecture.md#5-rencana-integrasi-laravel--api-ml).

Cek API (dari dalam `ml/`):

```bash
curl http://localhost:8001/health       # {"status":"ok", ...}
python api/uji_contoh.py                # uji 3 lowongan asli + 3 palsu
```

**Jika API ML mati atau timeout**, pengaduan tetap tersimpan dengan tampilan *Menunggu klasifikasi*. Setelah API menyala lagi, jalankan dari dalam `web/`:

```bash
php artisan lokerlens:klasifikasi-ulang
```

Ambang prioritas tinggi (default 70) diatur di `LOKERLENS_AMBANG_PRIORITAS` (`web/.env`). Kalau diubah, samakan juga `ambang_prioritas_tinggi` di `ml/models/model_lokerlens_meta.json`.

### Menjalankan Test BDD (Behat)

47 skenario ada di `web/bdd/features/`, satu file per fitur:
- **15 skenario Sprint 1** dari SRS Appendix B (Table B.2–B.5);
- **32 skenario Sprint 2** dari user story baru US-05 s.d. US-12 (Table B.6–B.13), yang perlu ditambahkan ke SRS. Daftarnya ada di [docs/laporan_kondisi_bdd.md bagian 8](docs/laporan_kondisi_bdd.md).

Behat dipasang sebagai project Composer terpisah di `web/bdd/`, supaya paket aplikasi Laravel tidak berubah. Perbedaan kalimat skenario dengan SRS dijelaskan di [docs/laporan_kondisi_bdd.md bagian 6](docs/laporan_kondisi_bdd.md).

**Persiapan (sekali saja):**

1. Nyalakan MySQL (Laragon: Start All, XAMPP: Start MySQL), lalu buat database kosong **`lokerlens_testing`** dengan collation `utf8mb4_unicode_ci` (HeidiSQL/phpMyAdmin). Isinya **dihapus dan diisi seeder** sebelum setiap skenario, jadi jangan pakai database lain.
2. Dari dalam `web/`:
   ```bash
   composer install --working-dir=bdd
   copy .env.behat.example .env.behat          # macOS/Linux: cp
   php artisan key:generate --env=behat
   ```
   Kalau user/password MySQL Anda bukan `root`/kosong, ubah di `web/.env.behat`.

**Setelah `git pull`:** paket BDD bisa bertambah (misalnya `symfony/mime`, dibutuhkan saat Behat mengirim form). Jalankan lagi dari dalam `web/`:

```bash
composer install --working-dir=bdd
```

Kalau langkah ini terlewat, skenario yang login atau mengirim form gagal dengan pesan *"Mime component is not installed"*.

**Catatan untuk pengguna XAMPP (phpMyAdmin):**

- *Membuat database:* buka XAMPP Control Panel, tekan **Start** pada Apache dan MySQL, lalu buka `http://localhost/phpmyadmin`. Klik **New** (Baru) di panel kiri, isi nama database `lokerlens_testing`, pilih collation **`utf8mb4_unicode_ci`**, lalu klik **Create** (Buat). Database cukup dibuat kosong. Tabel dan datanya diisi otomatis oleh test.
- *MySQL memakai password:* isi di `web/.env.behat` (bukan `web/.env`), lalu simpan:
  ```dotenv
  DB_USERNAME=root
  DB_PASSWORD=password_mysql_anda
  ```
  Kalau password mengandung spasi atau `#`, beri tanda kutip, misalnya `DB_PASSWORD="kata sandi#1"`. File `.env.behat` tidak ikut ter-commit, jadi password Anda tidak tersebar.

**Setiap kali menjalankan (buka terminal terpisah):**

```powershell
# Terminal 1 (dari web/): server Laravel khusus test, memakai .env.behat
$env:APP_ENV="behat"; php artisan serve --port=8010
#   macOS/Linux: APP_ENV=behat php artisan serve --port=8010

# Terminal 2 (dari ml/, venv aktif): hanya untuk skenario @ml
uvicorn api.app:app --port 8001

# Terminal 3: hanya untuk skenario @javascript (Chrome tanpa jendela)
& "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" --headless=new --remote-debugging-port=9222 --user-data-dir="$env:TEMP\chrome-behat"
#   Sesuaikan lokasi chrome.exe. macOS: "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" ...

# Terminal 4 (dari web/bdd/): cara yang disarankan
composer test-bdd                                        # semua 47 skenario + laporan HTML
composer test-bdd -- --tags="~@javascript&&~@ml"         # 43 skenario: hanya butuh Terminal 1
composer test-bdd -- --tags="@javascript"                # 3 skenario: butuh Terminal 1 + 3
composer test-bdd -- --tags="@ml"                        # 1 skenario: butuh Terminal 1 + 2
composer test-bdd -- features/buat_surat.feature         # satu fitur saja
```

Argumen setelah `--` diteruskan ke Behat. **Jalankan lewat `composer test-bdd`, jangan memanggil `behat` langsung**, supaya laporan HTML diberi nama yang jelas dan laporan lama dibersihkan otomatis.

**Laporan HTML.** Selain hasil di terminal, setiap run membuat laporan HTML (paket `emuse/behat-html-formatter`, sudah ikut terpasang lewat `composer install --working-dir=bdd`).

- Lokasi: `web/bdd/laporan/hasil-test-bdd_<tanggal>_<jam>.html`, misalnya `hasil-test-bdd_2026-10-28_15-38.html`. Setiap run membuat file baru. Kalau ada dua run di menit yang sama, detiknya ikut ditambahkan ke nama file.
- Isi: ringkasan jumlah Feature, skenario, dan langkah yang lulus/gagal (dengan grafik), lalu daftar per Feature dan per skenario beserta langkah-langkahnya.
- Buka file tersebut di browser (klik dua kali di File Explorer). Tampilan laporan memuat Bootstrap dan Chart.js dari internet, jadi butuh koneksi supaya rapi.
- `composer test-bdd` menjalankan `web/bdd/ganti_nama_laporan.php`, yang menjalankan Behat lalu mengganti nama laporan terbaru. Laporan tetap diganti nama walaupun ada skenario yang gagal.
- **Laporan tidak menumpuk:** setelah ganti nama, hanya **5 laporan terbaru** yang disimpan. Laporan yang lebih lama dan sisa file `Twig_*.html` dihapus otomatis. Laporan final yang di-track Git tidak pernah dihapus.
- Kalau terlanjur menjalankan Behat langsung, laporannya bernama `Twig_<tanggaljam>.html`. Ganti namanya dengan `php ganti_nama_laporan.php` dari `web/bdd/`.
- Folder `web/bdd/laporan/` tidak ikut di-commit (lihat `.gitignore`), kecuali `assets/` dan laporan final sprint `hasil-test-bdd_2026-10-28_15-38.html`.
- Pengaturannya ada di bagian `formatters` dan `extensions` di `web/bdd/behat.yml`.

**Mode demo (menonton skenario berjalan).** Untuk presentasi atau mencari penyebab skenario gagal, semua skenario bisa dijalankan di Chrome yang **terlihat**, dengan jeda antar langkah. Profilnya `demo` di `web/bdd/behat.yml`. Profil biasa (`composer test-bdd`) tetap cepat dan tanpa jendela.

1. Nyalakan server test (Terminal 1) dan API ML (Terminal 2) seperti biasa.
2. Kalau Chrome headless dari Terminal 3 masih menyala, tutup dulu (keduanya memakai port 9222).
3. Buka Chrome yang **terlihat**, yaitu **tanpa** `--headless`, dengan remote debugging port 9222:
   ```powershell
   & "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" --remote-debugging-port=9222 --user-data-dir="$env:TEMP\chrome-behat-demo" --no-first-run --password-store=basic --disable-features=PasswordLeakDetection,PasswordManagerOnboarding,AutofillServerCommunication
   #   Sesuaikan lokasi chrome.exe (kadang di "C:\Program Files\Google\Chrome\Application\chrome.exe").
   #   macOS: "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --remote-debugging-port=9222 --user-data-dir=/tmp/chrome-behat-demo --no-first-run --password-store=basic --disable-features=PasswordLeakDetection,PasswordManagerOnboarding,AutofillServerCommunication
   ```
   - `--user-data-dir` membuat profil Chrome terpisah, jadi Chrome pribadi Anda tidak terganggu.
   - `--password-store=basic` dan `--disable-features=...` mematikan pengelola kata sandi Chrome, supaya tidak ada pop-up "Simpan sandi?" atau isian otomatis yang mengganggu form login saat demo.
   - Kalau ketikan tidak masuk ke isian (bisa terjadi di Chrome yang terlihat), test mengisinya lewat JavaScript lalu memeriksanya lagi. Kalau tetap kosong, langkah gagal dengan pesan *"Isian ... tetap kosong setelah diisi"*.
   - Biarkan jendela Chrome itu terbuka sampai test selesai. Kalau ditutup, skenario berikutnya gagal dengan pesan *"Connection has unexpectedly closed"*.
4. Dari `web/bdd/`:
   ```powershell
   composer test-bdd-demo                                  # semua skenario, jeda 1 detik per langkah (±11 menit)
   $env:BDD_JEDA="0"; composer test-bdd-demo               # tanpa jeda, untuk cek cepat (±7 menit)
   $env:BDD_JEDA="2"; composer test-bdd-demo               # jeda 2 detik (boleh pecahan, mis. 0.5)
   composer test-bdd-demo -- features/beranda.feature      # satu fitur saja
   #   macOS/Linux: BDD_JEDA=2 composer test-bdd-demo
   ```
   Laporan HTML tetap dibuat dan diganti namanya seperti `composer test-bdd`.

Kalau Chrome di port 9222 ternyata headless, mode demo berhenti dengan pesan yang menjelaskan cara membuka Chrome yang terlihat.

**Screenshot saat gagal.** Kalau ada langkah yang gagal, tampilan halaman disimpan otomatis ke `web/bdd/laporan/screenshot/` (tidak ikut di-commit). Nama filenya berisi tanggal-jam, nama fitur, dan baris langkah, misalnya `2026-11-10_13-37-04_beranda_baris-11.png`.
- Di mode demo dan skenario `@javascript`, hasilnya gambar `.png`.
- Di mode biasa (tanpa browser), hasilnya file `.html` yang bisa dibuka di browser.

**Pengaman.** Test berhenti sebelum menyentuh data jika `DB_DATABASE` di `.env.behat` tidak diakhiri `_testing`, atau jika server di port 8010 tidak memakai `.env.behat`. Pengecekannya memakai `APP_KEY`: cookie dari server harus sah menurut `APP_KEY` di `.env.behat`, jadi `APP_KEY` di `.env.behat` wajib berbeda dari `.env` (buat dengan `php artisan key:generate --env=behat`). Skenario `@ml` berhenti dengan pesan jelas jika API ML belum menyala.

---

## Catatan untuk Anggota yang Sudah Clone Sebelumnya

Pada 20 Oktober 2026 (PR #1), aplikasi Laravel dipindah dari root repo ke folder `web/`. Setelah `git pull`:

1. **Pindahkan atau hapus file lokal lama di root.** File-file ini tidak dilacak git, jadi tidak ikut pindah:
   - `vendor/` dan `node_modules/` di root: **hapus**, lalu install ulang di `web/` (`composer install`, `npm install`).
   - `.env` di root: **pindahkan** ke `web/.env`.
   - `database/database.sqlite` di root: **pindahkan** ke `web/database/` kalau datanya masih dibutuhkan. Kalau tidak, buat baru dengan `php artisan migrate --seed`.
2. **Jangan menjalankan `git add .` di root sebelum langkah 1 selesai.** Aturan `.gitignore` sekarang hanya mengabaikan `web/vendor`, `web/node_modules`, dan `web/.env`, sehingga `vendor/` atau `.env` lama di root bisa ikut ter-commit.
3. Semua perintah `composer`, `php artisan`, dan `npm` sekarang dijalankan dari dalam `web/`.
4. Tutup lalu buka ulang folder proyek di VS Code supaya ekstensi PHP mengenali lokasi baru.

---

## Dokumen

| Dokumen | Isi |
|---|---|
| [docs/prd.md](docs/prd.md) | Masalah, tujuan, aktor, fitur, alur kasus, cakupan, ukuran keberhasilan |
| [docs/requirements.md](docs/requirements.md) | Kebutuhan fungsional & non-fungsional (kode SRS), tabel pelacakan ke kode, perbedaan dengan SRS, daftar hal yang perlu didiskusikan |
| [docs/architecture.md](docs/architecture.md) | Hubungan web, ML, dan database; tabel & relasi; rencana API ML; risiko model |
| [docs/design.md](docs/design.md) | Halaman per aktor, alur antar halaman, teks tombol/pesan, warna & font, perbedaan kode dengan mockup |

Dokumen sumber (SRS dan Data Mockup) disimpan di `docs/referensi/` dan **tidak masuk git** karena berisi data pribadi anggota. Mintalah salinannya ke anggota tim.
