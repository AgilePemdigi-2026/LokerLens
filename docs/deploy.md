# Menjalankan LokerLens dengan Docker

Panduan ini untuk pemula. Ada dua bagian:

1. **Mencoba di laptop** dengan Docker Desktop.
2. **Deploy ke server** Coolify (ringkasan, detailnya ada di [README.md bagian "Deploy ke Server"](../README.md#deploy-ke-server-coolify--docker)).

Dengan Docker, kita tidak perlu memasang PHP, Composer, Node, Python, atau MySQL satu per satu. Semuanya berjalan di dalam **container** (semacam komputer kecil yang terpisah).

LokerLens memakai 3 container:

| Nama | Isi | Alamat dari laptop |
|---|---|---|
| `web` | Aplikasi Laravel + Apache | http://localhost:8080 |
| `ml` | API klasifikasi lowongan (FastAPI) | tidak dibuka, hanya dipanggil `web` lewat `http://ml:8001` |
| `db` | Database MySQL 8.4 | `localhost:3307` (untuk HeidiSQL/DBeaver) |

---

## 1. Mencoba di Laptop

### Berkas yang dipakai

| Berkas | Fungsi |
|---|---|
| `docker-compose.yml` | File utama (dipakai juga di Coolify). **Jangan diubah untuk keperluan laptop.** |
| `docker-compose.local.yml` | Tambahan khusus laptop: port 8080 dan 3307, `APP_ENV=local`, cookie login tanpa https |
| `.env.docker-lokal.example` | Contoh isi file `.env` di root repo |

### Persiapan (sekali saja)

1. Pasang dan nyalakan **Docker Desktop**. Tunggu sampai tulisannya "Engine running".
2. Buka terminal di **folder root repo** (yang berisi `docker-compose.yml`, bukan folder `web/`).
3. Salin contoh pengaturan menjadi `.env` di root:
   ```bash
   cp .env.docker-lokal.example .env
   ```
   (PowerShell: `Copy-Item .env.docker-lokal.example .env`)
4. Buka `.env` tadi, lalu isi:
   - `APP_KEY`: buat dengan perintah di bawah (dari folder `web/`, butuh PHP dari Laragon), lalu salin hasilnya (diawali `base64:`).
     ```bash
     cd web
     php artisan key:generate --show
     cd ..
     ```
   - `DB_PASSWORD` dan `DB_ROOT_PASSWORD`: kata sandi bebas, misalnya `lokal123` dan `rootlokal123`.

   File `.env` di root **tidak ikut di-commit** (sudah ada di `.gitignore`). File ini berbeda dari `web/.env` yang dipakai Laragon.

### Menjalankan

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml up --build
```

- Build pertama cukup lama (sekitar 5–10 menit), karena Docker mengunduh PHP, Node, Python, dan MySQL. Build berikutnya jauh lebih cepat.
- Saat container `web` mulai, **migrasi database berjalan otomatis**.
- Perintah di atas menampilkan log terus-menerus di terminal. Kalau ingin terminal tetap bisa dipakai, tambahkan `-d` di akhir (berjalan di belakang layar):
  ```bash
  docker compose -f docker-compose.yml -f docker-compose.local.yml up --build -d
  ```

Cek apakah ketiga container sudah sehat:

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml ps
```

Kolom `STATUS` harus berisi `Up ... (healthy)` untuk `web`, `ml`, dan `db`. Container `web` butuh sekitar 30 detik sampai sehat.

> **Tips:** perintahnya panjang. Di PowerShell bisa disingkat dengan variabel lingkungan
> `$env:COMPOSE_FILE = "docker-compose.yml;docker-compose.local.yml"`. Setelah itu cukup ketik
> `docker compose up --build`, `docker compose ps`, dan seterusnya (berlaku sampai terminal ditutup).

### Mengisi data demo

Database awalnya kosong (belum ada akun). Isi akun demo dan kasus contoh dengan **DemoSeeder** di dalam container `web`:

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml exec web php artisan db:seed --class=DemoSeeder --force
```

Untuk mengosongkan database lalu mengisi ulang data demo dari awal:

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml exec web php artisan migrate:fresh --seeder=DemoSeeder --force
```

Akun demo (kata sandi semuanya `password123`):

| Peran | Email |
|---|---|
| Masyarakat | `adelulinn@gmail.com` |
| Analis | `budi.analis@lokerlens.go.id` |
| Kepala Bidang | `rizky.kb@lokerlens.go.id` |

### Membuka aplikasi

Buka **http://localhost:8080** di browser, lalu login dengan salah satu akun demo.

Untuk memastikan API ML terhubung: login sebagai Masyarakat, buka **Periksa Lowongan**, isi form, lalu kirim. Kalau halaman hasil menampilkan skor dan di log `ml` muncul `POST /predict ... 200 OK`, berarti web sudah memanggil API ML.

### Melihat log

```bash
# Semua container, terus mengikuti log terbaru (berhenti dengan Ctrl+C)
docker compose -f docker-compose.yml -f docker-compose.local.yml logs -f

# Hanya satu container, 50 baris terakhir
docker compose -f docker-compose.yml -f docker-compose.local.yml logs web --tail 50
docker compose -f docker-compose.yml -f docker-compose.local.yml logs ml --tail 50
```

Log Laravel juga dikirim ke log container `web` (`LOG_CHANNEL=stderr`), jadi tidak perlu membuka `storage/logs`.

### Menjalankan perintah artisan lain

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml exec web php artisan <perintah>
```

Contoh: `... exec web php artisan lokerlens:buat-petugas` untuk membuat akun petugas baru.

### Mematikan

```bash
# Matikan dan hapus container. Data database dan berkas bukti TETAP tersimpan di volume.
docker compose -f docker-compose.yml -f docker-compose.local.yml down

# Matikan DAN hapus semua data (database kosong lagi saat dinyalakan berikutnya)
docker compose -f docker-compose.yml -f docker-compose.local.yml down -v
```

### Setelah mengubah kode

Container memakai salinan kode saat build, bukan folder laptop secara langsung. Setelah mengubah kode, jalankan lagi perintah `up --build` supaya image dibuat ulang.

Untuk mengembangkan fitur sehari-hari, tetap lebih praktis memakai Laragon + `php artisan serve` seperti di README. Docker dipakai untuk mencoba kondisi yang mirip server.

---

## 2. Deploy ke Coolify (ringkasan)

Langkah lengkap ada di [README.md bagian "Deploy ke Server (Coolify + Docker)"](../README.md#deploy-ke-server-coolify--docker). Ringkasnya:

1. Di Coolify: **New Resource** → pilih repo GitHub → build pack **Docker Compose**, file `docker-compose.yml` di root.
   - Coolify hanya membaca `docker-compose.yml`. File `docker-compose.local.yml` **tidak** dipakai di server.
   - MySQL sudah termasuk di `docker-compose.yml` (service `db`), jadi **tidak perlu** menambah database terpisah di Coolify.
2. Isi **Environment Variables** (contoh di `web/.env.production.example`):
   - `APP_KEY`: buat dengan `php artisan key:generate --show`. **Jangan** pakai `APP_KEY` yang sama dengan laptop.
   - `APP_URL`: alamat https, misalnya `https://lokerlens.contoh.go.id`.
   - `DB_PASSWORD` dan `DB_ROOT_PASSWORD`: kata sandi kuat.
   - Opsional: pengaturan `MAIL_*` untuk email Lupa Password.
3. Atur **domain hanya untuk service `web`** (port 80). Jangan beri domain atau port untuk `ml` dan `db`.
4. Klik **Deploy**. Migrasi database berjalan otomatis.
5. Buat akun petugas lewat menu **Terminal** container `web` di Coolify: `php artisan lokerlens:buat-petugas`.

**Jangan** menjalankan `DemoSeeder` di server produksi. Seeder itu memang menolak berjalan jika `APP_ENV=production`.

---

## 3. Masalah yang Sering Muncul

| Gejala | Penyebab | Solusi |
|---|---|---|
| `required variable APP_KEY is missing a value` (atau `DB_PASSWORD`, `DB_ROOT_PASSWORD`) | File `.env` di root belum ada atau nilainya kosong | Ikuti langkah **Persiapan** di atas. Pastikan file `.env` ada di root repo, bukan di `web/`. |
| `Bind for 0.0.0.0:8080 failed: port is already allocated` | Port 8080 sudah dipakai program lain | Matikan program itu, atau ganti `"8080:80"` di `docker-compose.local.yml` menjadi misalnya `"8090:80"`, lalu buka `http://localhost:8090` dan ubah `APP_URL` di `.env`. |
| Port 3307 bentrok | Ada MySQL lain di 3307 | Ganti `"3307:3306"` di `docker-compose.local.yml` dengan port lain yang kosong. |
| `error during connect` / `Cannot connect to the Docker daemon` | Docker Desktop belum menyala | Buka Docker Desktop dan tunggu sampai "Engine running". |
| Login selalu kembali ke halaman login (419 / sesi hilang) | Membuka aplikasi lewat https, atau perintah `up` tanpa `-f docker-compose.local.yml` (cookie login hanya dikirim lewat https) | Buka `http://localhost:8080` dan pastikan kedua file `-f` dipakai. |
| Email/kata sandi demo ditolak | Data demo belum diisi | Jalankan perintah **Mengisi data demo**. |
| `Data demo tidak boleh dijalankan di produksi` | Perintah dijalankan tanpa `docker-compose.local.yml`, jadi `APP_ENV=production` | Pakai kedua file `-f`. Di server memang tidak boleh. |
| Container `web` terus restart, log berisi `Migrasi gagal setelah 10 kali percobaan` | Database belum siap atau kata sandi DB berbeda dengan saat volume dibuat | Kata sandi MySQL hanya diatur saat volume `db-data` pertama dibuat. Jika `DB_PASSWORD` diubah setelahnya, kembalikan nilai lama, atau hapus data dengan `down -v` (data hilang). |
| Hasil Periksa Lowongan tidak muncul atau error ML | Container `ml` belum sehat | Cek `ps` dan `logs ml`. `ML_API_URL` sudah otomatis `http://ml:8001`, jangan diganti `localhost`. |
| Perubahan kode tidak terlihat | Image belum dibuat ulang | Jalankan lagi `up --build`. |
| Build sangat lama atau disk penuh | Sisa image lama menumpuk | `docker system prune` (menghapus image/container yang tidak dipakai, volume tetap aman). |
| Pesan `AH00558: Could not reliably determine the server's fully qualified domain name` di log web | Hanya peringatan Apache | Aman, abaikan. |

---

## Hasil Uji Coba di Laptop (11 November 2026)

Diuji dengan Docker Desktop (Engine 29.9.3, Compose v5.7.0) di Windows 11:

- Ketiga container `healthy`. Migrasi berjalan otomatis, `DemoSeeder` berhasil.
- Login sebagai Masyarakat di http://localhost:8080 berhasil.
- Periksa Lowongan: web memanggil `POST http://ml:8001/predict` (200 OK), hasil tersimpan dengan metode "Logistic Regression TF-IDF" dan skor tampil di halaman hasil.

| | Ukuran image | RAM saat diam |
|---|---|---|
| `web` | 786 MB | ±54 MB |
| `ml` | 549 MB | ±96 MB |
| `db` (mysql:8.4) | 1,12 GB | ±504 MB |
