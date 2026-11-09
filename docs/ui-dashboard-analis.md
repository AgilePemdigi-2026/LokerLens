# Spesifikasi UI/UX — Dashboard Analis (LokerLens)

**Acuan Dokumen:** `docs/design.md` §3.4, `docs/requirements.md` §5, SRS NFR-SEC-02, Appendix C no. 7.  
**Aktor Berwenang:** Analis (`role: Analis`).  
**Status:** Sprint 2.

---

## 1. Tujuan & Sasaran Fitur
Halaman **Dashboard Analis** berfungsi sebagai pusat kendali (*command center*) bagi tim Analis Pengawasan Ketenagakerjaan untuk memantau:
1. **Beban Antrean Aktif:** Jumlah kasus prioritas tinggi yang perlu segera diverifikasi (*Perlu Tinjauan*) dan yang sedang dalam proses investigasi (*Sedang Ditinjau*).
2. **Kinerja & Hasil Tinjauan:** Rekapitulasi hasil keputusan audit investigasi Analis (*Terkonfirmasi Mencurigakan* yang diteruskan ke Kepala Bidang, serta *Tidak Terbukti* yang ditutup).
3. **Pemberian Akses Cepat (*Quick Actions*):** Akses langsung satu-klik ke antrean kasus berisiko paling tinggi untuk mempercepat respon investigasi (mendukung NFR-PERF-01).

---

## 2. Struktur Tata Letak (Layout Architecture)

Sesuai `docs/design.md` §5 & §6:
- **Tema Warna:** Navy Dark Mode (`navy-900: #0b1c2c`, `navy-800: #1f2d3a`) dipadu kartu putih kontras tinggi untuk data tabel/daftar.
- **Tipografi:** Plus Jakarta Sans (body, metrik, UI control) & Playfair Display (aksen judul serif editorial).
- **Navbar:** Analis Navbar dengan item **Dashboard** aktif (highlight teal `text-teal-400 bg-navy-800`).

### Diagram Blok Tata Letak:
```
+-----------------------------------------------------------------------------------+
| NAVBAR: Logo LokerLens | [Dashboard (Aktif)] [Laporan] [Pemantauan] [Arsip] | Profil |
+-----------------------------------------------------------------------------------+
| HERO BANNER:                                                                      |
| - Label: "PUSAT KENDALI ANALISIS & PENGAWASAN"                                    |
| - Judul: "Dashboard Monitoring Analis"                                            |
| - Quick action button: "Buka Antrean Pemantauan →"                                |
+-----------------------------------------------------------------------------------+
| KARTU RINGKASAN METRIK (4 STAT CARDS):                                            |
| [ 1. Perlu Tinjauan ]    [ 2. Sedang Ditinjau ]  [ 3. Terkonfirmasi ]  [ 4. Aman ]|
|   (Kuning / Prioritas)     (Teal / In Progress)    (Merah / Blacklist)   (Hijau)  |
+-----------------------------------------------------------------------------------+
| 2 KOLOM KONTEN UTAMA:                                                             |
| KOLOM KIRI (65%):                               | KOLOM KANAN (35%):              |
| - Kasus Antrean Mendesak (Urgent Queue)         | - Distribusi & Ringkasan Status |
|   Tabel/kartu 5 kasus skor tertinggi            |   Visual bar persentase status  |
|   (Nomor Tiket, Perusahaan, Skor, Aksi)         | - Panduan Cepat Investigasi     |
|                                                 |   Checklist 4 indikator utama   |
| - Riwayat Tinjauan Terakhir Selesai             | - Pintasan Filter               |
|   Daftar 3 kasus yang baru selesai diputuskan   |   (Skor ≥ 85%, Email Gratis, dl)|
+-----------------------------------------------------------------------------------+
```

---

## 3. Rincian Komponen Antarmuka

### 3.1 Banner Header (Hero)
- **Label Kategori:** `text-xs uppercase font-bold text-teal-400 tracking-widest` — `PUSAT PENGAWASAN KETENAGAKERJAAN`
- **Judul Editorial:** `text-3xl font-extrabold text-white` — `Dashboard <span class="font-serif italic text-teal-300">Monitoring Analis</span>`
- **Deskripsi:** Teks ringkas peran Analis dalam verifikasi manual lowongan prioritas tinggi sebelum diteruskan ke Kepala Bidang.
- **Badge Waktu Operasional:** Informasi waktu sinkronisasi sistem terbaru.

### 3.2 4 Kartu Metrik Statistik Utama (Stat Cards)
1. **Perlu Tinjauan (Antrean Baru):**
   - Nilai: Jumlah pengaduan dengan status `Perlu Tinjauan`
   - Ikon: Jam / Alert (Kuning Amber `#d97706`)
   - Indikator: "Menunggu tindakan pertama"
   - Link cepat: Filter ke antrean `/tinjau-lowongan?status=Perlu+Tinjauan`
2. **Sedang Ditinjau (Proses Investigasi):**
   - Nilai: Jumlah pengaduan dengan status `Sedang Ditinjau`
   - Ikon: Kaca Pembesar / Dokumen Terbuka (Teal `#1f8a7a`)
   - Indikator: "Sedang dipelajari Analis"
   - Link cepat: Filter ke antrean `/tinjau-lowongan?status=Sedang+Ditinjau`
3. **Terkonfirmasi Mencurigakan:**
   - Nilai: Jumlah tinjauan dengan hasil `Terkonfirmasi Mencurigakan`
   - Ikon: Perisai Peringatan (Merah `#c0392b`)
   - Indikator: "Diteruskan ke Kepala Bidang"
4. **Tidak Terbukti (Aman):**
   - Nilai: Jumlah tinjauan dengan hasil `Tidak Terbukti`
   - Ikon: Centang Hijau (`#1e8449`)
   - Indikator: "Kasus ditutup & diarsipkan"

### 3.3 Tabel Antrean Mendesak (Urgent Priority Queue)
- Menampilkan 5 kasus prioritas tertinggi yang membutuhkan tinjauan segera (`confidence_score` tertinggi).
- Menampilkan kolom: ID Kasus / Tiket, Tanggal Masuk, Perusahaan & Posisi, Skor Keyakinan AI, Status Kasus, dan Tombol `Tinjau Kasus`.

### 3.4 Ringkasan Distribusi & Riwayat Keputusan Terakhir
- Menampilkan perbandingan rasio kasus mencurigakan vs tidak terbukti.
- Menampilkan kartu riwayat keputusan terbaru yang telah dievaluasi oleh Analis lengkap dengan catatan audit singkat.

---

## 4. Keamanan & Hak Akses (NFR-SEC-02)
- **Role Guard:** Hanya pengguna dengan `role === 'Analis'` yang dapat mengakses route `/dashboard-analis`.
- **Masyarakat:** Ditolak dengan respon HTTP `403 Forbidden`.
- **Kepala Bidang:** Ditolak dengan respon HTTP `403 Forbidden`.
- **Tamu (Unauthenticated):** Dialihkan ke route `/login` (HTTP 302).
- **Perlindungan Privasi (NFR-SEC-05):** Dashboard tidak menampilkan data sensitif pelapor (nama pelapor, email, telepon).

