# Laporan Kondisi Project untuk Pengujian BDD — LokerLens

Tanggal: 27 Oktober 2026

> **Catatan kondisi kode.** Laporan ini dibuat dari kode yang sudah berisi integrasi model ML (API FastAPI, `KlasifikasiService`, perintah `lokerlens:klasifikasi-ulang`, dan `KlasifikasiMlTest`). Integrasi ini sudah masuk `main` lewat PR #39 (commit `88f73ba`).
>
> **Pembaruan (issue #21):** bagian 1–5 menggambarkan kondisi **sebelum** test BDD dibuat. Test BDD (Behat + Mink) sekarang sudah ada di `web/bdd/`. Perbedaan skenario dengan SRS ada di **bagian 6**, hasilnya di **bagian 7**.
>
> **Pembaruan Sprint 2 (10 November 2026):** user story dan skenario BDD untuk fitur Sprint 2 (US-05 s.d. US-12, Table B.6–B.13) beserta hasil test-nya ada di **bagian 8**. Semuanya **baru** dan perlu ditambahkan ke SRS.

---

## 1. Skenario BDD yang sudah ada

**Lokasi:** `docs/referensi/SRS LokerLens.pdf`, **Appendix B.1 "User Story and User Story Scenarios"** (halaman 58–62), Table B.1 sampai B.5. Folder `docs/referensi/` tidak masuk git, jadi minta salinannya ke anggota tim.

Di repo tidak ada file `.feature` (Gherkin) atau skenario BDD lain. `docs/` hanya menyinggung skenario BDD di beberapa catatan (`requirements.md` §156, `design.md` §105 dan §183, `architecture.md` §317).

Kalimat skenario di SRS memakai gaya langkah **Behat + MinkExtension** (`I am on`, `I fill in`, `I press`, `I should see ... in the ... element`).

**User story (Table B.1):**

| ID | Aktor | Ringkasan |
|---|---|---|
| US-01 | Masyarakat | Memeriksa teks lowongan untuk tahu asli atau palsu |
| US-02 | Analis | Meninjau daftar dan detail lowongan prioritas tinggi |
| US-03 | Kepala Bidang | Meninjau laporan dan menetapkan keputusan tindak lanjut |
| US-04 | Kepala Bidang | Membuat dan menerbitkan surat teguran/laporan polisi |

**Skenario (15 skenario):** penomoran ID di bawah dibuat untuk laporan ini, misalnya B.2-1 = Table B.2 skenario ke-1.

| ID | Fitur | Aktor | Judul skenario | Given → When → Then |
|---|---|---|---|---|
| B.2-1 | Periksa Lowongan Kerja | Masyarakat | Pemeriksaan lowongan berhasil dijalankan | Di `/periksa-lowongan/` → isi nama perusahaan "PT Maju Bersama Sejahtera", posisi "Staff Administrasi", deskripsi "Dibutuhkan staff administrasi, wajib transfer biaya pendaftaran", tekan "Periksa Sekarang" → tampil "Hasil Pemeriksaan", ada elemen `kata-pemicu`, dan kata "biaya" ada di dalamnya |
| B.2-2 | Periksa Lowongan Kerja | Masyarakat | Gagal memeriksa karena field wajib kosong | Di `/periksa-lowongan/` → tekan "Periksa Sekarang" tanpa isi → tampil "Data belum lengkap, mohon lengkapi terlebih dahulu" dan tidak tampil "Hasil Pemeriksaan" |
| B.2-3 | Periksa Lowongan Kerja | Masyarakat | Mereset formulir | Di `/periksa-lowongan/` → isi nama "PT Coba Reset", tekan "Reset Form" → field `nama_perusahaan` kosong lagi |
| B.3-1 | Meninjau Lowongan Prioritas Tinggi | Analis | Menampilkan daftar prioritas tinggi | Di `/beranda/` → klik "Tinjau Lowongan" → tampil "Daftar Lowongan Prioritas Tinggi" |
| B.3-2 | Meninjau Lowongan Prioritas Tinggi | Analis | Memfilter berdasarkan skor | Di `/tinjau-lowongan/` → pilih "Skor Tinggi" di `filter_skor` dan "Skor Tertinggi" di `urutan` → tampil "Daftar Sesuai Kriteria Filter" |
| B.3-3 | Meninjau Lowongan Prioritas Tinggi | Analis | Mencari nomor tiket, data ditemukan | Di `/tinjau-lowongan/` → isi `nomor_tiket` yang terdaftar, tekan "Cari" → tampil "Hasil Pencarian" |
| B.3-4 | Meninjau Lowongan Prioritas Tinggi | Analis | Data lowongan tidak ditemukan | Di `/tinjau-lowongan/` → isi `nomor_tiket` yang tidak terdaftar, tekan "Cari" → tampil "Data Lowongan Tidak Ditemukan" |
| B.3-5 | Meninjau Lowongan Prioritas Tinggi | Analis | Meninjau detail lowongan | Di `/tinjau-lowongan/` → klik "Lihat Detail" → tampil "Detail Lowongan Terpilih", "Confidence Score", "Indikator Mencurigakan" |
| B.3-6 | Meninjau Lowongan Prioritas Tinggi | Analis | Mengisi catatan tinjauan dan melanjutkan | Di `/tinjau-lowongan/detail/` → isi `catatan_tinjauan`, pilih "Mencurigakan" di `hasil_tinjauan`, tekan "Lanjutkan" → tampil "Hasil Tinjauan Berhasil Disimpan" dan "Menindaklanjuti Laporan Lowongan Kerja yang Mencurigakan" |
| B.4-1 | Menindaklanjuti Laporan | Kepala Bidang | Meninjau detail laporan mencurigakan | Di `/tindak-lanjut/` → tampil "Detail Laporan Lowongan Mencurigakan", "Informasi Lowongan", "Ringkasan Laporan", "Terindikasi Anomali Tinggi" |
| B.4-2 | Menindaklanjuti Laporan | Kepala Bidang | Menandai laporan tidak ditindaklanjuti | Di `/tindak-lanjut/` → tekan "Tidak Ditindaklanjuti" → tampil "Kasus ditandai tidak dilanjuti dan diarsipkan" |
| B.4-3 | Menindaklanjuti Laporan | Kepala Bidang | Menindaklanjuti ke pembuatan surat | Di `/tindak-lanjut/` → tekan "Tindaklanjuti" → berada di `/buat-surat/` dan tampil "Buat Laporan Polisi" |
| B.5-1 | Buat Surat Teguran/Laporan Polisi | Kepala Bidang | Menerbitkan laporan polisi | Di `/buat-surat/` → isi `kronologi`, pilih "Subdit Siber Ditreskrimsus Polda Metro Jaya" di `tujuan_kepolisian`, tekan "Terbitkan Laporan" → tampil "Kasus terdokumentasi otomatis" dan "Kasus selesai" |
| B.5-2 | Buat Surat Teguran/Laporan Polisi | Kepala Bidang | Menyimpan surat sebagai draf | Di `/buat-surat/` → isi `kronologi`, tekan "Simpan Draft" → tampil "Draf berhasil disimpan" |
| B.5-3 | Buat Surat Teguran/Laporan Polisi | Kepala Bidang | Gagal menerbitkan dokumen | Di `/buat-surat/` → tekan "Terbitkan Laporan" dan sistem gagal memproses → tampil "Gagal menerbitkan dokumen, draf telah disimpan" dan "Buat Laporan Polisi" |

Tidak ada skenario BDD untuk fitur Sprint 2 (Register, Login, Lupa Password, Beranda, Status/Riwayat Pengaduan, Dashboard, Arsip Kasus).

---

## 2. Kondisi fitur

| Fitur | Sprint | Kondisi | Route / Controller | Catatan |
|---|---|---|---|---|
| Register | 1 | **Sudah jadi** | `GET/POST /register` → `Auth\RegisteredUserController` | Pendaftaran hanya untuk Masyarakat, lalu diarahkan ke login dengan pesan sukses (PR #37). |
| Login | 1 | **Sudah jadi** | `GET/POST /login` → `Auth\AuthenticatedSessionController`; middleware `role` (`CheckRole`) | Setelah login, tiap peran diarahkan ke halaman awalnya (`User::halamanAwal()`). |
| Lupa Password | 2 | **Setengah jadi** | `GET/POST /forgot-password`, `GET /reset-password/{token}`, `POST /reset-password` → `Auth\PasswordResetLinkController`, `Auth\NewPasswordController` | Masih bawaan Breeze. Teks halamannya masih bahasa Inggris dan belum mengikuti mockup. Pengiriman email butuh pengaturan mail. Link "Lupa kata sandi?" sudah ada di login. |
| Beranda | 2 | **Sudah jadi** (Masyarakat) | `GET /beranda` → `BerandaController@index` | Menampilkan 5 pengaduan terbaru milik pengguna. |
| Periksa Lowongan Kerja | 1 | **Sudah jadi** (memakai model ML, PR #39) | `GET/POST /periksa-lowongan`, `GET /periksa-lowongan/hasil/{id}` → `PeriksaLowonganController` | Butuh API ML (`uvicorn api.app:app --port 8001`). Kalau API mati, pengaduan tersimpan sebagai "Menunggu klasifikasi". |
| Status Pengaduan | 2 | **Setengah jadi** | Tidak punya route sendiri | Status hanya tampil sebagai badge di daftar 5 pengaduan terbaru di Beranda. Kartu "Status Pengaduan" hanya mengarah ke `#pengaduan-terbaru`. |
| Riwayat Pengaduan | 2 | **Setengah jadi** | Tidak punya route sendiri | Sama seperti Status: hanya 5 pengaduan terbaru di Beranda, belum ada halaman riwayat lengkap. |
| Dashboard Analis | 2 | **Belum ada** | — | Halaman awal Analis langsung `/tinjau-lowongan`. `resources/views/dashboard.blade.php` masih bawaan Breeze dan tidak punya route. |
| Meninjau Lowongan Prioritas Tinggi | 1 | **Sudah jadi** | `GET /tinjau-lowongan`, `GET /tinjau-lowongan/detail/{id}`, `POST /tinjau-lowongan/detail/{id}/simpan` → `TinjauLowonganController` | Filter "Skor Tinggi" masih memakai batas 75, padahal ambang prioritas 70. Urutan "Skor Terendah"/"Terbaru" belum berpengaruh (lihat `requirements.md` FR-ML-03). Halaman detail sudah memakai data asli. |
| Dashboard Kepala Bidang | 2 | **Belum ada** | — | Halaman awal Kepala Bidang langsung `/tindak-lanjut`. |
| Menindaklanjuti Laporan | 1 | **Sudah jadi** | `GET /tindak-lanjut`, `GET /tindak-lanjut/{id}`, `POST /tindak-lanjut/{id}/keputusan`, `GET /tindak-lanjut/{id}/hasil` → `TindakLanjutController` | Halaman detail masih berisi teks dummy (kata pemicu tetap, nama analis cadangan, kartu bukti palsu). Prioritas tinggi/sedang memakai batas 75. |
| Arsip Kasus | 2 | **Belum ada** | — | Branch `feat/15-arsip-kasus` tidak punya commit baru. Tombol "Lihat Arsip Kasus" di halaman terbit hanya kembali ke `/tindak-lanjut`. |
| Buat Surat Teguran/Laporan Polisi | 1–2 | **Sudah jadi** | `GET /buat-surat/{pengaduanId}`, `POST .../draft`, `POST .../terbitkan`, `GET .../terbit/{suratId}` → `BuatSuratController`; `/buat-surat` dialihkan ke `/tindak-lanjut` | Checkbox pasal hukum tidak disimpan. Masih ada label dummy ("KRITIS (Sindikat)", "Non-AHU", "Enkripsi SHA-256 Valid"). |

---

## 3. Kondisi testing

### Framework yang terpasang
| Alat | Versi | Keterangan |
|---|---|---|
| PHPUnit | 12.5.40 | Satu-satunya framework test. Dipakai lewat `php artisan test`. |
| Laravel HTTP testing | Laravel 13.37.0 | `$this->get()`, `->post()`, `assertSee()`. Tanpa browser dan tanpa JavaScript. |
| Mockery, Faker | dari `require-dev` | Untuk mock dan data palsu. |
| Pest, Laravel Dusk, Behat/Mink, Codeception | **tidak terpasang** | Belum ada alat BDD atau browser test. |
| Playwright/Cypress (npm) | **tidak terpasang** | |

Database test: SQLite di memori (`phpunit.xml`), dikosongkan tiap test (`RefreshDatabase`). Semua test **tidak boleh** memanggil API ML sungguhan (`Http::preventStrayRequests()` di `tests/TestCase.php`). Jawaban API dipalsukan dengan `palsukanApiMl()`.

### Daftar file test
| File | Jumlah | Yang diuji |
|---|---|---|
| `Feature/AlurKasusTest.php` | 9 | Alur kasus dari periksa → tinjau → keputusan → surat terbit dan riwayatnya. Aturan: status tidak bisa lompat/mundur, keputusan sekali, surat terbit sekali. |
| `Feature/KlasifikasiMlTest.php` | 10 | Integrasi API ML: skor di atas/bawah ambang, API mati/timeout/error, tampilan Hasil (asli/palsu), detail Analis memakai data asli, perintah klasifikasi ulang. |
| `Feature/FormPeriksaDanLoginTest.php` | 6 (+data) | Field form periksa tidak `disabled`, form lengkap berhasil, pengalihan login per peran. |
| `Feature/HakAksesPeranTest.php` | 3 (+data) | Tiap halaman hanya untuk peran yang berhak, wajib login. |
| `Feature/HalamanSeederTest.php` | 3 | Halaman tiap peran tampil dengan data seeder. |
| `Feature/DevPreviewTest.php` | 7 | Mode preview `/dev`. |
| `Feature/NomorTiketTest.php` | 3 | Nomor tiket `#LKR-xxxx` unik. |
| `Feature/StatusKasusTest.php` | 4 | Aturan perpindahan status kasus dan ambang di config. |
| `Feature/UploadBuktiTest.php` | 2 | Upload bukti di Buat Surat (hanya pdf/jpg/png). |
| `Feature/Auth/*` (6 file) | 18 | Bawaan Breeze: login, register, reset/konfirmasi/ubah password, verifikasi email. |
| `Feature/ProfileTest.php` | 5 | Bawaan Breeze: profil. |
| `Feature/ExampleTest.php`, `Unit/ExampleTest.php` | 1 + 1 | Contoh bawaan Laravel. |

### Cara menjalankan
```bash
cd web
php artisan test                          # semua test
php artisan test --filter=KlasifikasiMlTest   # satu file/kelas
```

### Hasil sekarang
**86 test, 85 lulus, 1 gagal** (481 assertion).

Yang gagal: `Auth/RegistrationTest::test_new_users_can_register`. **Test-nya yang sudah usang, bukan aplikasinya.** Test masih mengharapkan pengguna langsung login dan diarahkan ke `/beranda`, padahal sejak PR #37 pendaftaran diarahkan ke halaman login. Test ini sudah gagal sebelum integrasi ML dikerjakan.

### Akun contoh
Ada, di `database/seeders/LokerLensSeeder.php` (dipanggil `DatabaseSeeder`). Semua akun memakai password `password123`:

| Peran | Email |
|---|---|
| Masyarakat | `adelulinn@gmail.com` |
| Analis | `budi.analis@lokerlens.go.id` |
| Kepala Bidang | `rizky.kb@lokerlens.go.id` |

Seeder juga membuat kasus contoh untuk setiap status. Cara memakainya: `php artisan migrate:fresh --seed`. Mode `/dev` (`DEV_PREVIEW=true`) menyediakan tombol login cepat untuk ketiga akun.

`database/factories/UserFactory.php` ada, tapi tidak mengisi `role`. Di test, peran diisi manual: `User::factory()->create(['role' => 'Analis'])`.

---

## 4. Pemetaan skenario BDD ↔ test yang ada

Belum ada test BDD sungguhan. "Tercakup" di bawah berarti ada test PHPUnit yang memeriksa perilaku yang sama lewat HTTP, tanpa browser.

| ID | Tercakup? | Oleh test | Catatan |
|---|---|---|---|
| B.2-1 | Sebagian | `FormPeriksaDanLoginTest`, `KlasifikasiMlTest`, `HalamanSeederTest` | Jawaban API dipalsukan. Elemen `kata-pemicu` dan kata "biaya" belum diuji dengan model sungguhan. |
| B.2-2 | **Belum** | — | Belum ada test yang mengirim form kosong lalu memeriksa pesannya. |
| B.2-3 | **Belum** | — | Butuh JavaScript (tombol Reset memakai Alpine). |
| B.3-1 | Sebagian | `HalamanSeederTest` | Judul daftar diuji, tapi jalur "klik dari Beranda" tidak. |
| B.3-2 | **Belum** | — | |
| B.3-3 | **Belum** | — | |
| B.3-4 | **Belum** | — | |
| B.3-5 | Sebagian | `HalamanSeederTest`, `KlasifikasiMlTest` | "Detail Lowongan Terpilih" diuji. "Confidence Score" dan "Indikator Mencurigakan" belum. |
| B.3-6 | Sebagian | `AlurKasusTest` | Pesan sukses diuji lewat session, bukan teks di halaman. |
| B.4-1 | Sebagian | `HalamanSeederTest` | Hanya "Detail Laporan Lowongan Mencurigakan". |
| B.4-2 | Sebagian | `AlurKasusTest` | Keputusan tersimpan di database, tapi teks "Kasus ditandai tidak dilanjuti dan diarsipkan" belum diuji. |
| B.4-3 | Sebagian | `AlurKasusTest` | Halaman "Keputusan Berhasil Disimpan" diuji, tapi tidak sampai halaman Buat Laporan Polisi. |
| B.5-1 | **Ya** | `AlurKasusTest` | "Kasus terdokumentasi otomatis" dan "Kasus selesai" diuji. |
| B.5-2 | Sebagian | `AlurKasusTest`, `UploadBuktiTest` | Draf tersimpan, tapi teks "Draf berhasil disimpan" belum diuji. |
| B.5-3 | **Belum** | — | Butuh cara memaksa penerbitan gagal. |

**Ringkasnya: 1 tercakup, 9 sebagian, 5 belum.**

### Skenario yang tidak cocok dengan perilaku aplikasi sekarang

| ID | Masalah | Perilaku aplikasi sekarang |
|---|---|---|
| B.2-1 | **Cocok untuk saat ini.** Dicek langsung ke model: teks B.2 mendapat skor 79,5, dan kata pemicunya `pendaftaran, staff administrasi, dibutuhkan staff, biaya, transfer`. | "biaya" ada di urutan ke-4 dari 5, jadi rawan hilang kalau model dilatih ulang. Skenario ini juga **butuh API ML menyala**. Tanpa API, halaman menampilkan "Menunggu klasifikasi" tanpa elemen `kata-pemicu`. |
| B.2-2 | Kolom form punya atribut HTML `required`/`minlength`. | Di browser sungguhan (driver dengan JavaScript), browser menahan form sebelum terkirim, sehingga pesan server "Data belum lengkap…" **tidak pernah tampil**. Skenario ini hanya lolos dengan driver tanpa JavaScript. |
| B.2-3 | Tombol "Reset Form" memakai JavaScript (Alpine). | Hanya bisa diuji dengan driver browser. Ini **bertentangan** dengan B.2-2 kalau keduanya dijalankan dengan driver yang sama. |
| B.3-1 | Analis diminta mulai dari `/beranda/` lalu klik "Tinjau Lowongan". | `/beranda` hanya untuk Masyarakat (Analis ditolak). Menu Analis bertuliskan **"Pemantauan"**, bukan "Tinjau Lowongan". |
| B.3-2 | Tidak ada langkah "tekan Cari". "Skor Tinggi" diasumsikan sama dengan prioritas. | Filter baru berlaku setelah form dikirim. "Skor Tinggi" = skor ≥ 75, padahal ambang prioritas 70. Judul "Daftar Sesuai Kriteria Filter" hanya muncul kalau ada hasil. |
| B.3-3 / B.3-4 | Butuh nomor tiket yang "terdaftar" atau "tidak terdaftar". | Perlu data seeder, misalnya `#LKR-8821`. |
| B.3-5 | Klik "Lihat Detail". | Untuk kasus berstatus *Sedang Ditinjau*, teks tombolnya **"Sedang Ditinjau"**, bukan "Lihat Detail". |
| B.3-6 | Membuka `/tinjau-lowongan/detail/` tanpa ID. Memilih "Mencurigakan". Setelah simpan, tampil "Menindaklanjuti Laporan Lowongan Kerja yang Mencurigakan". | Route butuh ID (`/tinjau-lowongan/detail/{id}`). Pilihannya **"Terkonfirmasi Mencurigakan"** (keputusan tim, lihat `requirements.md`). Setelah simpan, aplikasi kembali ke **daftar**, yang tidak memuat teks "Menindaklanjuti Laporan…" (teks itu hanya ada di halaman detail). |
| B.4-1 | Teks detail diharapkan ada di `/tindak-lanjut/`. | `/tindak-lanjut` adalah **daftar** ("Daftar Laporan"). Teks yang diharapkan ada di `/tindak-lanjut/{id}`. "Terindikasi Anomali Tinggi" berupa teks tetap (masih didiskusikan di `design.md` §183). |
| B.4-2 / B.4-3 | Langsung menekan "Tidak Ditindaklanjuti"/"Tindaklanjuti" di `/tindak-lanjut/`. | Keputusan diambil di halaman detail: pilih radio, tekan "Konfirmasi Keputusan", lalu "Ya, Konfirmasi" di pop-up (butuh JavaScript). Setelah Tindaklanjuti, aplikasi menuju `/tindak-lanjut/{id}/hasil` ("Keputusan Berhasil Disimpan"), **bukan** `/buat-surat/`. Ke Buat Surat harus lewat tombol "Lanjut ke Pembuatan Surat". |
| B.5-1/2/3 | Membuka `/buat-surat/` tanpa ID. | `/buat-surat` dialihkan ke `/tindak-lanjut`. Halaman formulir ada di `/buat-surat/{pengaduanId}`, untuk kasus yang sudah *Tindak Lanjut Disetujui*. |
| B.5-1 | Memilih "Subdit Siber Ditreskrimsus Polda Metro Jaya". | Teks pilihan di aplikasi: "Subdit Siber Ditreskrimsus Polda Metro Jaya **/ Wilayah Terkait**". Langkah `select` di Mink butuh teks yang persis sama. |
| B.5-3 | "Penerbitan dokumen gagal diproses sistem". | Kode sudah menangani kegagalan (`try/catch` di `BuatSuratController@terbitkan`), tapi tidak ada cara memicunya dari antarmuka. Perlu langkah khusus di test, misalnya mock atau memaksa error. |
| Semua | Tidak ada langkah login. | Semua halaman wajib login dengan peran tertentu. Perlu langkah tambahan, misalnya `Given I am logged in as "Analis"`. |

---

## 5. Aturan yang berhubungan dengan testing/BDD

**`AGENTS.md`**
- Kerjakan hanya fitur sesuai issue/branch aktif. Pengujian Sprint 3 punya issue sendiri: #21 Pengujian Fungsional, #22 Pengujian Model Klasifikasi, #23 Pengujian Non-Fungsional, #24 UAT, #25 Perbaikan Bug.
- Ikuti istilah dan teks SRS. Teks resmi ada di `web/app/Constants/LokerLensCopy.php`.
- Jangan membuat status baru. Jangan mengubah migration lama. Tanya tim sebelum membuat migration.
- Jangan commit `.env`, `vendor/`, `node_modules/`, `ml/.venv/`, data mentah, atau `.pkl`. Jangan push ke `main` (lewat PR, tulis `Closes #...`). Pesan commit dalam bahasa Indonesia, tanpa nama AI atau `Co-Authored-By`.
- Kalau ragu, tanya dulu. Hal yang belum diputuskan dicatat di issue berlabel `diskusi` (#16–#20).

**Di dalam kode dan dokumen**
- `LokerLensCopy.php`: *"Teks ini digunakan sebagai selector pada pengujian BDD (Sprint 3). Jangan mengubah teks tanpa verifikasi ke dokumen proyek."*
- `routes/web.php`: *"Rute resmi dari skenario BDD — jangan diubah."*
- View Blade memakai penanda "BDD Hook", misalnya `id="kata-pemicu"`, `name="nomor_tiket"`, `name="catatan_tinjauan"`, `name="kronologi"`, `name="tujuan_kepolisian"`. Nama-nama ini jangan diubah.
- `docs/design.md` §105: daftar teks resmi adalah acuan uji BDD.
- `docs/requirements.md` §156: langkah B.3 `select "Mencurigakan"` perlu diganti `"Terkonfirmasi Mencurigakan"`.
- `docs/architecture.md` §317: munculnya kata "biaya" untuk contoh B.2 dijadikan salah satu uji model.
- `tests/TestCase.php`: test tidak boleh memanggil API ML sungguhan. Pakai `palsukanApiMl()`.

**`web/CLAUDE.md` / `web/AGENTS.md`**: hanya berisi instruksi pemasangan Laravel Boost, tidak ada aturan testing.

SRS tidak menyebut alat BDD tertentu. Yang disebut hanya **Apache JMeter** untuk uji performa (NFR-PERF-03).

---

## Hal yang perlu diputuskan sebelum menulis test BDD

1. **Alat BDD:** Behat + Mink (sesuai gaya kalimat SRS), Pest/PHPUnit dengan gaya Given–When–Then, atau Laravel Dusk (browser).
2. **Driver:** tanpa JavaScript (cepat, B.2-2 lolos, B.2-3 dan pop-up keputusan tidak bisa) atau dengan browser (B.2-3 bisa, B.2-2 tertahan validasi HTML). Mungkin perlu keduanya.
3. **Skenario yang tidak cocok** (B.3-1, B.3-6, B.4-x, B.5-x): ubah aplikasinya atau sesuaikan skenarionya (revisi SRS, issue #20).
4. **API ML saat test BDD:** dinyalakan sungguhan (B.2-1 menguji model asli) atau dipalsukan.
5. Perbarui `RegistrationTest` yang sudah usang.

**Keputusan (issue #21):**
1. Behat + Mink.
2. Tanpa browser, kecuali skenario `@javascript` (B.2-3 dan pop-up keputusan B.4) yang memakai Chrome.
3. Skenario disesuaikan, aplikasi tidak diubah. Pengecualiannya filter "Skor Tinggi" yang diperbaiki menjadi ≥ ambang prioritas (70).
4. B.2-1 memakai API ML sungguhan (`@ml`), skenario lain tidak memanggil API.
5. `RegistrationTest` belum disentuh.

---

## 6. Perbedaan skenario BDD dengan SRS (bahan revisi SRS)

Di file `web/bdd/features/*.feature`, setiap kalimat yang berbeda ditandai komentar `# BEDA SRS [Dn]`.

| Kode | Skenario | Di SRS | Di test BDD | Alasan |
|---|---|---|---|---|
| D1 | Semua | Tanpa login | `Given I am logged in as "<peran>"` (Background) | Semua halaman wajib login dengan peran tertentu (NFR-SEC-02). |
| D2 | B.2-1 | — | Tag `@ml` | Butuh API ML sungguhan. |
| D3 | B.2-1 | `"kata-pemicu"` | `"#kata-pemicu"` | Mink membaca nama elemen sebagai selector CSS. Elemen di halaman memakai `id="kata-pemicu"`. |
| D4 | B.2-3 | — | Tag `@javascript` | Tombol Reset berjalan dengan JavaScript (Alpine). |
| D5 | B.3-1 | Mulai dari `/beranda/`, klik "Tinjau Lowongan" | Langsung klik "Pemantauan" | `/beranda` khusus Masyarakat. Setelah login, Analis langsung masuk `/tinjau-lowongan`, dan menunya bernama "Pemantauan". |
| D6 | B.3-2 | Tanpa tombol | `And I press "Cari"` | Filter baru berlaku setelah form dikirim. |
| D7 | B.3-3/4 | "nomor tiket yang (tidak) terdaftar" | `#LKR-8819` / `#LKR-0000` | Test butuh data nyata dari seeder. |
| D8 | B.3-6 | `/tinjau-lowongan/detail/` | Detail tiket `#LKR-8819` | Route detail butuh ID kasus. |
| D9 | B.3-6 | "Mencurigakan" | "Terkonfirmasi Mencurigakan" | Pilihan resmi sesuai keputusan tim (`requirements.md`). |
| D10 | B.3-6 | Teks "Menindaklanjuti Laporan Lowongan Kerja yang Mencurigakan" | "Daftar Lowongan Prioritas Tinggi" | Setelah disimpan, aplikasi kembali ke daftar. Teks itu hanya ada di halaman detail. |
| D11 | B.4-1 | Teks detail di `/tindak-lanjut/` | Ditambah `When I follow "Lihat Detail"` | `/tindak-lanjut` adalah daftar. Detail ada di `/tindak-lanjut/{id}`. |
| D12 | B.4-2/3 | Tekan "Tidak Ditindaklanjuti"/"Tindaklanjuti" di `/tindak-lanjut/` | Di detail tiket `#LKR-2025-0842`: pilih keputusan, tekan "Konfirmasi Keputusan", lalu "Ya, Konfirmasi" di pop-up. Tag `@javascript` | Keputusan diambil lewat pilihan dan pop-up konfirmasi (Data Mockup 6.5.3). |
| D13 | B.4-3 | Langsung di `/buat-surat/` | "Keputusan Berhasil Disimpan", lalu klik "Lanjut ke Pembuatan Surat", lalu URL `/buat-surat/{id}` | Setelah keputusan, aplikasi menampilkan halaman hasil keputusan dulu (Data Mockup 6.5.4). |
| D14 | B.5-* | `/buat-surat/` | Halaman surat tiket `#LKR-8770` | Route butuh ID kasus. `/buat-surat` dialihkan ke daftar laporan. |
| D15 | B.5-1 | "Subdit Siber Ditreskrimsus Polda Metro Jaya" | "… / Wilayah Terkait" | Teks pilihan di aplikasi lebih panjang, dan Mink butuh teks yang persis sama. |
| D16 | B.5-3 | "When I press … And penerbitan gagal" | `Given penerbitan dokumen akan gagal diproses sistem` dipasang sebelum tombol ditekan | Kegagalan harus disiapkan dulu. Caranya dengan trigger MySQL di database test (`lokerlens_testing`) yang menolak status "Terbit". |

Skenario yang **tidak berubah** selain langkah login: B.2-2, B.3-5, dan B.5-2 (kecuali D14).

---

## 7. Hasil test BDD (27 Oktober 2026)

Dijalankan di Windows 11, PHP 8.5.4, MySQL 8.4.3 (Laragon), dan Chrome 156 (headless). Paket Behat: `behat/behat` 3.34.0, `friends-of-behat/mink-extension` 2.7.5, `behat/mink-browserkit-driver` 2.3.0, `dmore/chrome-mink-driver` 2.11.1.

| Perintah | Skenario | Hasil | Waktu |
|---|---|---|---|
| `--tags="~@javascript&&~@ml"` | 11 | 11 lulus | ±31 detik |
| `--tags="@javascript"` | 3 | 3 lulus | ±14 detik |
| `--tags="@ml"` | 1 | 1 lulus. Model sungguhan, "biaya" muncul di kata pemicu | ±5 detik |
| semua | **15** | **15 lulus (91 langkah)** | ±64 detik |

Sebagian besar waktu habis untuk reset database (sekitar 3 detik per skenario).

Test PHPUnit (`php artisan test`): 88 test, 87 lulus. Yang gagal tetap `RegistrationTest::test_new_users_can_register`, yang sudah usang sejak PR #37 (lihat bagian 3).

---

## 8. User story dan skenario BDD Sprint 2 (10 November 2026)

### 8.1 Dasar penyusunan

- **SRS Appendix B.1** hanya berisi US-01 s.d. US-04 dan Table B.2–B.5 (fitur Sprint 1). Fitur Sprint 2 **belum punya user story maupun skenario** di SRS.
- **SRS Appendix C no. 7** masih menandai *"Fitur Dashboard, Riwayat, dan Statistik: masuk SRS atau tidak"* sebagai **[TBD]**.
- **Data Mockup** hanya merancang Beranda (Gambar 2.4) dari fitur Sprint 2.
- **Sprint Planning** mencantumkan 7 fitur Sprint 2: Lupa Password, Beranda, Status Pengaduan, Riwayat Pengaduan, Dashboard Analis, Dashboard Kepala Bidang, dan Arsip Kasus. **Riwayat Tinjauan Analis** adalah fitur tambahan dari issue #25.

Karena itu user story dan skenario di bawah **semuanya BARU** dan perlu ditambahkan ke SRS Appendix B, melanjutkan penomoran US-05 dan Table B.6. Formatnya sama dengan SRS: *"As a …, I want to …, so that …"*, dan langkah skenarionya bergaya Behat + Mink.

Aturan penyusunan:
- hanya menguji perilaku yang sudah ada di aplikasi;
- teks yang diharapkan diambil dari `LokerLensCopy.php` atau teks yang tampil di halaman;
- data dari seeder yang sudah ada, tanpa perubahan seeder.

Setiap fitur punya minimal satu skenario berhasil, satu skenario kosong/gagal/validasi, dan satu skenario hak akses peran (kecuali Lupa Password, yang memang untuk tamu).

### 8.2 User story baru

| ID | Peran | User Story | Tabel | File |
|---|---|---|---|---|
| US-05 | Pengguna (Masyarakat, Analis, Kepala Bidang) | As a Pengguna, I want to mengatur ulang kata sandi melalui tautan yang dikirim ke email, so that saya dapat kembali masuk ke akun ketika lupa kata sandi. | B.6 | `lupa_password.feature` |
| US-06 | Masyarakat | As a Masyarakat, I want to melihat beranda berisi pintasan layanan dan pengaduan terbaru saya, so that saya dapat langsung melapor atau memantau pengaduan dari satu halaman. | B.7 | `beranda.feature` |
| US-07 | Masyarakat | As a Masyarakat, I want to memantau status dan tahapan penanganan pengaduan yang masih berjalan, so that saya mengetahui perkembangan laporan yang saya ajukan. | B.8 | `status_pengaduan.feature` |
| US-08 | Masyarakat | As a Masyarakat, I want to melihat kembali pengaduan yang sudah selesai beserta hasil akhirnya, so that saya dapat meninjau hasil pemeriksaan dan keputusan atas laporan saya. | B.9 | `riwayat_pengaduan.feature` |
| US-09 | Analis | As a Analis, I want to melihat ringkasan beban kerja dan antrean lowongan berisiko tinggi, so that saya dapat memprioritaskan lowongan yang perlu ditinjau. | B.10 | `dashboard_analis.feature` |
| US-10 | Analis | As a Analis, I want to melihat daftar lowongan yang sudah ditinjau beserta hasil akhir kasusnya, so that saya dapat menelusuri kembali hasil tinjauan dan keputusan lanjutannya. | B.11 | `riwayat_tinjauan.feature` |
| US-11 | Kepala Bidang | As a Kepala Bidang, I want to melihat ringkasan jumlah laporan berdasarkan status penanganan, so that saya dapat segera menemukan laporan yang menunggu keputusan. | B.12 | `dashboard_kepala_bidang.feature` |
| US-12 | Kepala Bidang | As a Kepala Bidang, I want to menelusuri arsip kasus yang sudah berstatus akhir, so that saya dapat melihat kembali dokumen dan riwayat penanganan kasus. | B.13 | `arsip_kasus.feature` |

### 8.3 Skenario baru (32 skenario, semua BARU untuk SRS)

Penomoran mengikuti bagian 1, misalnya B.6-1 = Table B.6 skenario ke-1. Langkah lengkapnya ada di file `.feature`.

| ID | Fitur | Aktor | Judul skenario | Given → When → Then |
|---|---|---|---|---|
| B.6-1 | Lupa Password | Tamu | Meminta tautan reset dengan email yang terdaftar | Di `/forgot-password` → isi `email` "adelulinn@gmail.com", tekan "Kirim Tautan Reset Kata Sandi" → tampil "Periksa email Anda" |
| B.6-2 | Lupa Password | Tamu | Email yang tidak terdaftar tetap mendapat pesan yang sama | Di `/forgot-password` → isi email yang tidak terdaftar, tekan "Kirim Tautan Reset Kata Sandi" → tampil "Periksa email Anda" (pesan netral, tidak membocorkan email terdaftar) |
| B.6-3 | Lupa Password | Tamu | Gagal meminta tautan karena email kosong | Di `/forgot-password` → tekan "Kirim Tautan Reset Kata Sandi" → tampil "Email wajib diisi." dan tidak tampil "Periksa email Anda" |
| B.6-4 | Lupa Password | Tamu | Mengatur kata sandi baru dengan tautan yang sah | Tautan reset sah untuk akun Masyarakat → isi `email`, `password`, `password_confirmation`, tekan "Simpan Kata Sandi Baru" → tampil "Kata sandi berhasil diubah"; login dengan sandi baru → berada di `/beranda` |
| B.6-5 | Lupa Password | Tamu | Gagal mengatur kata sandi karena tautan tidak sah | Di `/reset-password/token-tidak-sah` → isi isian, tekan "Simpan Kata Sandi Baru" → tampil "Tautan atur ulang kata sandi tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru." |
| B.7-1 | Beranda | Masyarakat | Menampilkan beranda dan pengaduan terbaru | Di `/beranda` → tampil "Satu tempat untuk melapor, memantau, dan meninjau.", "Pengaduan Terbaru Anda", "#LKR-8821" |
| B.7-2 | Beranda | Masyarakat | Membuka Status Pengaduan dari beranda | Di `/beranda` → klik "Cek Status Pengaduan" → berada di `/status-pengaduan`, tampil "Pengaduan Berjalan" |
| B.7-3 | Beranda | Masyarakat | Beranda akun yang belum punya pengaduan | Login akun Masyarakat tanpa pengaduan → di `/beranda` tampil "Belum ada pengaduan yang diajukan" |
| B.7-4 | Beranda | Analis | Peran lain tidak bisa membuka beranda Masyarakat | Login Analis → buka `/beranda` → kode respons 403 |
| B.8-1 | Status Pengaduan | Masyarakat | Menampilkan pengaduan yang masih berjalan | Di `/status-pengaduan` → tampil "Pengaduan Berjalan", "#LKR-8821"; tidak tampil "#LKR-8798" (sudah selesai) |
| B.8-2 | Status Pengaduan | Masyarakat | Melihat progres penanganan satu pengaduan | Di `/status-pengaduan` → klik kartu "#LKR-8821" → tampil "Progres Penanganan", "Ditinjau Analis" |
| B.8-3 | Status Pengaduan | Masyarakat | Tidak ada pengaduan yang sedang berjalan | Login akun Masyarakat tanpa pengaduan → tampil "Belum ada pengaduan berjalan" |
| B.8-4 | Status Pengaduan | Kepala Bidang | Peran lain tidak bisa membuka Status Pengaduan | Login Kepala Bidang → buka `/status-pengaduan` → 403 |
| B.9-1 | Riwayat Pengaduan | Masyarakat | Menampilkan pengaduan yang sudah selesai | Di `/riwayat-pengaduan` → tampil "Total Selesai", "#LKR-8798", "#LKR-8752"; tidak tampil "#LKR-8821" |
| B.9-2 | Riwayat Pengaduan | Masyarakat | Memfilter riwayat berdasarkan status akhir | Pilih "Tidak Terbukti" di `status`, tekan "Cari" → tampil "#LKR-8752"; tidak tampil "#LKR-8798" |
| B.9-3 | Riwayat Pengaduan | Masyarakat | Belum ada pengaduan yang selesai | Login akun Masyarakat tanpa pengaduan → tampil "Belum ada pengaduan yang selesai" |
| B.9-4 | Riwayat Pengaduan | Analis | Peran lain tidak bisa membuka Riwayat Pengaduan | Login Analis → buka `/riwayat-pengaduan` → 403 |
| B.10-1 | Dashboard Analis | Analis | Menampilkan dashboard Analis | Klik "Dashboard" di navbar → tampil "Dashboard Analis", "Pantau beban kerja dan prioritaskan lowongan berisiko tinggi." |
| B.10-2 | Dashboard Analis | Analis | Membuka antrean tinjauan dari dashboard | Di `/dashboard-analis` → klik "Lihat seluruh antrean" → tampil "Daftar Lowongan Prioritas Tinggi" |
| B.10-3 | Dashboard Analis | Analis | Periode tanpa antrean tinjauan | Di `/dashboard-analis` periode kustom 1–7 Januari 2020 → tampil "Belum ada antrean pada periode ini" |
| B.10-4 | Dashboard Analis | Masyarakat | Peran lain tidak bisa membuka dashboard Analis | Login Masyarakat → buka `/dashboard-analis` → 403 |
| B.11-1 | Riwayat Tinjauan | Analis | Menampilkan lowongan yang sudah ditinjau | Klik "Riwayat" di navbar → tampil "Riwayat Tinjauan Lowongan", "#LKR-8798"; tidak tampil "#LKR-8821" |
| B.11-2 | Riwayat Tinjauan | Analis | Memfilter riwayat berdasarkan hasil akhir | Pilih "Tidak Ditindaklanjuti" di `filter_hasil_akhir`, tekan "Cari" → tampil "#LKR-8781"; tidak tampil "#LKR-8798" |
| B.11-3 | Riwayat Tinjauan | Analis | Melihat detail tinjauan dalam mode hanya-baca | Klik "Lihat Detail" di baris "#LKR-2025-0842" → tampil "Hasil Akhir Kasus", "Menunggu keputusan Kepala Bidang"; tidak ada elemen `#catatan_tinjauan` (form tinjauan tidak tampil) |
| B.11-4 | Riwayat Tinjauan | Kepala Bidang | Peran lain tidak bisa membuka Riwayat Tinjauan | Login Kepala Bidang → buka `/riwayat-tinjauan` → 403 |
| B.12-1 | Dashboard Kepala Bidang | Kepala Bidang | Dashboard tampil setelah login | Login → berada di `/dashboard-kepala-bidang`, tampil "Dashboard Kepala Bidang", "Ringkasan jumlah laporan berdasarkan status penanganan." |
| B.12-2 | Dashboard Kepala Bidang | Kepala Bidang | Membuka laporan yang menunggu keputusan | Di dashboard → klik "Lihat semua" → tampil "Daftar Laporan", "#LKR-2025-0842" |
| B.12-3 | Dashboard Kepala Bidang | Analis | Peran lain tidak bisa membuka dashboard Kepala Bidang | Login Analis → buka `/dashboard-kepala-bidang` → 403 |
| B.13-1 | Arsip Kasus | Kepala Bidang | Menampilkan arsip kasus berstatus akhir | Klik "Arsip Kasus" di navbar → tampil "#LKR-8798", "#LKR-8781", "#LKR-8752"; tidak tampil "#LKR-2025-0842" |
| B.13-2 | Arsip Kasus | Kepala Bidang | Melihat detail arsip kasus | Di `/arsip-kasus` → klik "Buka Detail" di baris "#LKR-8798" → tampil "Dokumen Surat Resmi", "Status Akhir" |
| B.13-3 | Arsip Kasus | Kepala Bidang | Pencarian arsip tidak menemukan kasus | Isi `cari` "#LKR-0000", tekan "Cari" → tampil "Tidak Ada Kasus Sesuai Kriteria Filter" |
| B.13-4 | Arsip Kasus | Analis | Peran lain tidak bisa membuka Arsip Kasus | Login Analis → buka `/arsip-kasus` → 403 |

Skenario kosong untuk Dashboard Kepala Bidang ("Tidak ada laporan yang menunggu keputusan.") dan Riwayat Tinjauan ("Belum ada lowongan yang ditinjau") tidak dibuat, karena seeder selalu berisi kasus untuk keduanya dan data seeder lama tidak diubah. Kondisi kosong itu sudah diuji di PHPUnit (`DashboardKepalaBidangTest`, `RiwayatTinjauanTest`).

### 8.4 Langkah tambahan di `FeatureContext`

| Langkah / perubahan | Kegunaan |
|---|---|
| Akun `"Masyarakat Baru"` di `I am logged in as` | Akun seeder `masyarakat.baru@lokerlens.test` tanpa pengaduan, untuk tampilan kosong |
| `Given I am on a valid password reset page for "<email>"` | Membuat token reset langsung di tabel `password_reset_tokens` database test (hash bcrypt, sama dengan Laravel), jadi tidak bergantung pada email sungguhan. `.env.behat` memakai `MAIL_MAILER=log` |
| `When I follow "<tautan>" in the row for ticket "<tiket>"` | Klik tautan di baris tabel yang memuat nomor tiket tertentu |
| Profil `demo` (parameter `modeDemo`) | Semua skenario di Chrome yang terlihat, jeda antar langkah dari `BDD_JEDA` (default 1 detik), dan berhenti dengan pesan jelas kalau Chrome di port 9222 ternyata headless |
| Screenshot saat gagal | Halaman disimpan ke `web/bdd/laporan/screenshot/`: `.png` di Chrome, `.html` di mode tanpa browser |
| Menunggu halaman setelah klik (`tungguSetelah`) | Dipakai `I press`, `I follow`, tombol di pop-up, dan klik tautan baris tabel. Di Chrome, setelah klik langkah berikutnya baru jalan jika halaman baru selesai dimuat (maks. 15 detik). Sebelumnya driver Chrome hanya menunggu `document.readyState`, yang masih "complete" di halaman lama selama form dikirim, sehingga login di mode demo sering dianggap gagal karena URL masih `/login`. Tombol yang tidak berpindah halaman (membuka pop-up, Reset Form) hanya menunggu 0,5 detik |
| Menunggu halaman tenang setelah dibuka (`visitPath`) | Di Chrome, setelah membuka alamat, tunggu `document.readyState` "complete" lalu jeda 0,5 detik sebelum langkah berikutnya mengisi form |
| Isian diperiksa setelah diisi (`fillField`, dipakai `I fill in` dan login) | Di Chrome, nilai isian diperiksa setelah diisi. Di mode demo, ketikan driver kadang tidak masuk sama sekali ke Chrome yang terlihat (isian tetap kosong, form terkirim kosong: "Email wajib diisi."). Jika kosong, isian diisi lewat JavaScript beserta event `input`/`change`, lalu diperiksa lagi; jika tetap kosong, langkah gagal dengan pesan jelas. Form tetap dikirim oleh browser seperti biasa |
| Login menunggu halaman `/login` (`isiFormLogin`) | Sebelum mengisi, pastikan yang tampil benar-benar halaman `/login`, bukan halaman skenario sebelumnya |
| Token reset kata sandi memakai zona waktu aplikasi | `created_at` di `password_reset_tokens` memakai `APP_TIMEZONE` (Asia/Jakarta), bukan UTC. Sejak aplikasi memakai WIB (PR #66), token UTC terlihat 7 jam lebih tua dan dianggap kedaluwarsa |

### 8.5 Hasil test

Dijalankan 10 November 2026 di Windows 11, PHP 8.5.4, MySQL (Laragon), dan Chrome 157, dengan server test port 8010 dan API ML menyala. Kode sudah memuat perubahan PR #66 (zona waktu WIB, bahasa Indonesia, validasi form) dan PR #68 (menu HP, halaman awal Analis, Profil).

| Perintah (dari `web/bdd/`) | Skenario | Hasil | Waktu |
|---|---|---|---|
| `composer test-bdd` (Chrome headless untuk `@javascript`) | 47 (15 lama + 32 baru) | **47 lulus (235 langkah)** | 5 menit 1 detik |
| `BDD_JEDA=0 composer test-bdd-demo` (semua skenario di Chrome terlihat, tanpa jeda) | 47 (15 lama + 32 baru) | **47 lulus (235 langkah)** | 6 menit 36 detik |

Dengan jeda bawaan (`BDD_JEDA` = 1 detik), mode demo lebih lama sekitar 4 menit (235 langkah × 1 detik).

Test PHPUnit (`php artisan test`): **270 lulus, 0 gagal** (1767 assertion).

Tidak ada skenario yang gagal karena bug aplikasi. Kegagalan yang ditemukan selama pengujian berasal dari test BDD sendiri dan sudah diperbaiki di `FeatureContext` (lihat 8.4):
- **Mode demo, login dianggap gagal:** langkah berikutnya memeriksa halaman lama sebelum halaman baru selesai dimuat.
- **Mode demo, form terkirim kosong:** ketikan driver tidak masuk ke Chrome yang terlihat.
- **Skenario B.6-4 (atur ulang kata sandi), kedua mode:** token reset dibuat dengan jam UTC, sedangkan aplikasi sejak PR #66 memakai WIB, sehingga token dianggap kedaluwarsa.

Skenario Sprint 2 tidak perlu diubah karena perubahan PR #66 dan #68: semua langkahnya tetap lulus.

### 8.6 Catatan untuk ditindaklanjuti (tidak diubah di branch ini)

1. **Teks Lupa Password ditulis langsung di Blade**, bukan dari `LokerLensCopy`. Contohnya view menampilkan "Periksa Email Anda" dan "Kata Sandi Berhasil Diubah", sedangkan `LokerLensCopy::LUPA_TERKIRIM_JUDUL` dan `SANDI_BERHASIL_JUDUL` berisi "Periksa email Anda" dan "Kata sandi berhasil diubah". Skenario tetap lulus karena `I should see` di Mink tidak peka huruf besar, tetapi sumber teksnya sebaiknya disatukan ke `LokerLensCopy`.
2. ~~**Halaman awal setelah login tidak konsisten**~~ **Sudah diperbaiki (PR #68):** Analis kini masuk ke Dashboard Analis, sama seperti Kepala Bidang ke Dashboard Kepala Bidang (`User::halamanAwal()`). Skenario B.10-1 tetap membuka Dashboard Analis lewat menu "Dashboard" dan tetap lulus, jadi tidak diubah.
