# Aturan Kerja LokerLens

Aturan ini berlaku untuk **semua anggota tim dan AI** yang mengerjakan repo ini.

## 1. Sebelum mulai
- Project ini adalah **LokerLens**, sistem deteksi lowongan kerja mencurigakan untuk pengawasan pemerintah.
- **Baca `README.md` dan semua dokumen di `docs/`** sebelum mengerjakan apa pun (`prd.md`, `requirements.md`, `architecture.md`, `design.md`).
- Dokumen sumber (SRS, Data Mockup, Sprint Planning) ada di `docs/referensi/`. Folder ini tidak masuk git, jadi minta salinannya ke anggota tim.

## 2. Struktur folder
- `web/`: aplikasi Laravel. **Semua perintah `php artisan`, `composer`, dan `npm` dijalankan dari dalam `web/`.**
- `ml/`: komponen machine learning (Python), termasuk *scraping* data latih.

## 3. Cakupan pekerjaan
- Kerjakan **hanya fitur sesuai issue dan branch yang sedang aktif** (misalnya `feat/5-periksa-lowongan` untuk issue #5).
- Jangan mengubah fitur atau file milik issue lain.
- Ikuti **alur status kasus dan daftar status yang sudah ada di kode** (`web/app/Enums/StatusKasus.php`, dijelaskan di `docs/requirements.md` §5). **Jangan membuat status baru.**
- Ikuti **desain di Data Mockup** dan **istilah serta teks di SRS** (teks resmi ada di `web/app/Constants/LokerLensCopy.php`).

## 4. Database
- **Jangan mengubah migration lama.**
- Kalau perlu mengubah tabel, buat **migration baru**, dan **tanya tim dulu** sebelum membuatnya.

## 5. File yang tidak boleh di-commit
Jangan pernah commit:
- `.env`
- `vendor/` dan `node_modules/`
- `ml/.venv/`
- data mentah (`ml/data/raw/`)
- file model `.pkl`

Cek `git status` sebelum commit, dan hindari `git add .` kalau belum yakin isinya.

## 6. Git dan Pull Request
- **Jangan push ke `main`.** Semua perubahan masuk lewat **Pull Request**.
- Di deskripsi PR, tulis `Closes #<nomor-issue>`.
- Pesan commit memakai **bahasa Indonesia yang jelas**, misalnya: `Tambah filter skor terendah di daftar Analis`.
- **Jangan menambahkan nama AI atau baris `Co-Authored-By`** di pesan commit.

## 7. Kalau ragu
**Tanya dulu sebelum mengubah.** Hal yang belum diputuskan dicatat di issue berlabel `diskusi` (#16–#20).
