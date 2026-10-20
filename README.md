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
| API ML (rencana) | FastAPI + Uvicorn |

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

Menjalankan test:

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

Kode training dan API belum dibuat. Rencananya ada di [docs/architecture.md §5](docs/architecture.md#5-rencana-integrasi-laravel--api-ml).

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
