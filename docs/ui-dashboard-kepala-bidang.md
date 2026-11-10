# Spesifikasi UI/UX — Dashboard Kepala Bidang (LokerLens)

**Acuan Dokumen:** `docs/design.md` §3.5, `docs/requirements.md` §5, SRS NFR-SEC-02, Appendix C no. 7.  
**Aktor Berwenang:** Kepala Bidang Pengawasan Ketenagakerjaan (`role: Kepala Bidang`).  
**Status:** Sprint 2.

---

## 1. Tujuan & Sasaran Fitur
Halaman **Dashboard Kepala Bidang** berfungsi sebagai pusat eksekutif pengawasan (*executive supervisory dashboard*) bagi Kepala Bidang Ketenagakerjaan untuk memantau:
1. **Antrean Keputusan Kasus:** Jumlah kasus yang telah lolos verifikasi Analis dan memerlukan keputusan eskalasi hukum (*Menunggu Keputusan*).
2. **Rekapitulasi Keputusan Eksekutif:** Jumlah kasus yang disetujui untuk ditindaklanjuti (*Tindak Lanjut Disetujui*) dan kasus yang diarsipkan (*Tidak Ditindaklanjuti*).
3. **Akuntabilitas & Output Hukum:** Jumlah dokumen resmi yang telah berhasil diterbitkan (*Laporan Polisi Berhasil Diterbitkan / Status Selesai*).
4. **Pemberian Akses Cepat:** Kemudahan akses langsung satu-klik untuk membuat keputusan atau meninjau draf laporan polisi.

---

## 2. Struktur Tata Letak (Layout Architecture)

Sesuai `docs/design.md` §5 & §6:
- **Tema Warna:** Navy Dark Mode (`navy-900: #0b1c2c`, `navy-800: #1f2d3a`) dipadu kartu data kontras tinggi.
- **Tipografi:** Plus Jakarta Sans (body, metrik angka mono, navigasi) & Playfair Display (aksen judul serif editorial).
- **Navbar:** Kepala Bidang Navbar dengan item **Dashboard** aktif (highlight teal `text-teal-400 bg-navy-800`) serta badge notifikasi kasus menunggu keputusan pada menu Laporan.

### Diagram Blok Tata Letak:
```
+-----------------------------------------------------------------------------------+
| NAVBAR: Logo LokerLens | [Dashboard (Aktif)] [Laporan (Badge)] [Pemantauan] [Arsip] | Profil |
+-----------------------------------------------------------------------------------+
| HERO BANNER:                                                                      |
| - Label: "PUSAT PENGAMBILAN KEPUTUSAN PENGAWASAN"                                 |
| - Judul: "Dashboard Eksekutif Kepala Bidang"                                      |
| - Tombol Aksi: "Buka Antrean Keputusan (Laporan) →"                              |
+-----------------------------------------------------------------------------------+
| KARTU RINGKASAN METRIK (4 STAT CARDS):                                            |
| [ 1. Menunggu Keputusan ] [ 2. Tindak Lanjut Disetujui ] [ 3. Tidak Ditindaklanjuti ] [ 4. Selesai / Terbit ] |
|      (Amber / Urgent)             (Blue / Proses)                 (Gray / Diarsip)            (Green / Legal) |
+-----------------------------------------------------------------------------------+
| 2 KOLOM KONTEN UTAMA:                                                             |
| KOLOM KIRI (65%):                               | KOLOM KANAN (35%):              |
| - Antrean Kasus Menunggu Keputusan              | - Ringkasan Rasio Keputusan     |
|   Tabel/kartu 5 kasus prioritas tertinggi       |   Visual bar persentase alur    |
|   (Tiket, Posisi, Perusahaan, Hasil Analis, Aksi)| - Dokumen & Surat Terbit Terbaru|
| - Riwayat Kasus Ditindaklanjuti Terbaru         |   Daftar 3 surat polisi terbaru |
|                                                 | - Pintasan Navigasi Cepat       |
+-----------------------------------------------------------------------------------+
```

---

## 3. Rincian Komponen Antarmuka

### 3.1 Banner Header (Hero)
- **Label Kategori:** `text-xs uppercase font-bold text-teal-400 tracking-widest` — `PUSAT PENGAMBILAN KEPUTUSAN & PENEGAKAN HUKUM`
- **Judul Editorial:** `text-3xl font-extrabold text-white` — `Dashboard <span class="font-serif italic text-teal-300">Kepala Bidang</span>`
- **Deskripsi:** Evaluasi hasil audit Analis, tetapkan persetujuan penindakan hukum ke Kepolisian, dan pantau penerbitan dokumen resmi.
- **Badge Waktu:** Indikator data real-time dari database.

### 3.2 4 Kartu Metrik Statistik Utama (Stat Cards)
1. **Menunggu Keputusan (Urgent):**
   - Nilai: Pengaduan dengan status `Menunggu Keputusan`
   - Warna: Border Amber (`#d97706`), background amber-50
   - Indikator: "Memerlukan penetapan persetujuan tindak lanjut"
   - Link cepat: Filter ke `/tindak-lanjut?status=Menunggu+Keputusan`
2. **Tindak Lanjut Disetujui (Dalam Proses Hukum):**
   - Nilai: Pengaduan dengan status `Tindak Lanjut Disetujui`
   - Warna: Border Biru Steel (`#2f5d7c`), background blue-50
   - Indikator: "Siap atau sedang dibuatkan draft laporan polisi"
   - Link cepat: Filter ke `/tindak-lanjut?status=Tindak+Lanjut+Disetujui`
3. **Tidak Ditindaklanjuti (Diarsipkan):**
   - Nilai: Pengaduan dengan status `Tidak Ditindaklanjuti`
   - Warna: Border Gray-300, background gray-50
   - Indikator: "Kasus tidak memenuhi unsur pidana dan diarsipkan"
   - Link cepat: Filter ke `/tindak-lanjut?status=Tidak+Ditindaklanjuti`
4. **Kasus Selesai (Surat Polisi Terbit):**
   - Nilai: Pengaduan dengan status `Selesai`
   - Warna: Border Emerald (`#1e8449`), background emerald-50
   - Indikator: "Laporan polisi resmi berhasil diterbitkan"
   - Link cepat: Filter ke `/tindak-lanjut?status=Selesai`

### 3.3 Tabel Kasus Menunggu Keputusan (Decision Queue)
- Menampilkan 5 pengaduan teratas berstatus `Menunggu Keputusan` yang memerlukan evaluasi Kepala Bidang.
- Memuat: Nomor Tiket, Tanggal Masuk, Nama Perusahaan, Posisi Lowongan, Catatan Analis Singkat, dan Tombol `Beri Keputusan` yang langsung menuju `route('tindak-lanjut.detail', $id)`.

### 3.4 Panel Dokumen Surat Terbit & Ringkasan Rasio Keputusan
- Menampilkan daftar surat laporan polisi resmi yang telah diterbitkan (Nomor Dokumen, Tiket, Terlapor, Tanggal Terbit).
- Menampilkan visual rasio keputusan persetujuan: Ditindaklanjuti vs Tidak Ditindaklanjuti.

---

## 4. Keamanan & Hak Akses (NFR-SEC-02)
- **Role Guard:** Hanya pengguna dengan `role === 'Kepala Bidang'` yang dapat mengakses route `/dashboard-kepala-bidang`.
- **Masyarakat:** Ditolak dengan respon HTTP `403 Forbidden`.
- **Analis:** Ditolak dengan respon HTTP `403 Forbidden`.
- **Tamu (Unauthenticated):** Dialihkan ke route `/login` (HTTP 302).
- **Perlindungan Privasi Pelapor (NFR-SEC-05):** Dashboard tidak menampilkan data identitas personal pelapor.

