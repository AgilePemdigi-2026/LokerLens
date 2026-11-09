# Spesifikasi UI/UX — Arsip Kasus (Kepala Bidang)

**Acuan Dokumen:** SRS BR-03, BR-10, BR-11, FR-TL-05, Data Mockup 2.6, Appendix C no. 7, `docs/design.md` §3.5 & §5.  
**Aktor Berwenang:** Kepala Bidang Pengawasan Ketenagakerjaan (`role: Kepala Bidang`).  
**Status:** Sprint 2.

---

## 1. Tujuan & Sasaran Fitur
Fitur **Arsip Kasus** berfungsi sebagai repositori permanen (*permanent case records repository*) bagi Kepala Bidang untuk meninjau kembali seluruh kasus laporan penipuan lowongan kerja yang telah selesai diproses dan mencapai status akhir.

Sasaran utama fitur:
1. **Penyimpanan Kasus Status Akhir:** Menampilkan kasus yang telah ditutup atau selesai (`Selesai`, `Tidak Ditindaklanjuti`, dan `Tidak Terbukti`).
2. **Akuntabilitas & Audit Trail (BR-11):** Menyajikan kronologi lengkap perjalanan kasus melalui *Riwayat Kasus* dari pelaporan awal, analisis, hingga keputusan dan penerbitan dokumen resmi.
3. **Akses Dokumen Hukum Terbit:** Memudahkan Kepala Bidang melihat kembali surat laporan polisi atau surat dinas resmi yang telah diterbitkan untuk kasus terkait.
4. **Pencarian & Penyaringan Efisien:** Memfasilitasi filter berdasarkan kategori status akhir dan pencarian cepat berbasis nomor tiket (#LKR-...), nama perusahaan, posisi lowongan, maupun nomor surat resmi (#LP-...).

---

## 2. Struktur Tata Letak (Layout Architecture)

Sesuai panduan desain (`docs/design.md` §5 & §6):
- **Tema Warna:** Navy Dark Mode (`navy-900: #0b1c2c`, `navy-800: #1f2d3a`) pada header & aksen, dipadu kartu latar putih bersih (`#ffffff`) dengan border halus (`border-gray-100` / `border-gray-200`).
- **Tipografi:** Plus Jakarta Sans untuk teks tubuh, antarmuka, dan angka mono; Playfair Display untuk aksen judul editorial (*italic serif*).
- **Navbar:** Menu **Arsip Kasus** aktif (`text-teal-400 bg-navy-800 font-semibold`) saat berada di rute `arsip-kasus.*`.

---

## 3. Rincian Antarmuka Halaman

### 3.1 Halaman Daftar Arsip (`/arsip-kasus`)
1. **Hero Header:**
   - Label: `text-xs uppercase font-bold text-teal-400 tracking-widest` — `REPOSITORI PENGAWASAN KETENAGAKERJAAN`
   - Judul: `Arsip <span class="font-serif italic text-teal-300">Kasus Pengawasan</span>`
   - Ringkasan statistik cepat: Total Kasus Diarsipkan, Kasus Selesai (Surat Terbit), Kasus Tidak Ditindaklanjuti, dan Kasus Tidak Terbukti.
2. **Bilah Pencarian & Filter Status:**
   - Input pencarian: Nomor tiket, nama perusahaan, posisi, atau nomor surat.
   - Pilihan filter status:
     - `Semua Status Akhir`
     - `Selesai` (Dokumen Polisi Terbit)
     - `Tidak Ditindaklanjuti` (Diarsipkan)
     - `Tidak Terbukti` (Ditutup Analis)
3. **Tabel / Daftar Kasus:**
   - Kolom: Nomor Tiket, Tanggal Selesai/Update, Perusahaan & Posisi, Status Akhir (badge terstandar), Informasi Dokumen Surat, dan Tombol Aksi "Buka Detail Arsip".
4. **Paginasi:** 10 kasus per halaman dengan retensi parameter pencarian & filter (`withQueryString()`).
5. **State Antarmuka:**
   - *State Normal:* Daftar kasus dengan data lengkap dan tautan ke detail.
   - *State Filter Nihil:* Ilustrasi pencarian kosong, pesan ramah, dan tombol reset filter.
   - *State Kosong (Empty):* Pesan jika belum ada kasus yang mencapai status akhir.

### 3.2 Halaman Detail Arsip (`/arsip-kasus/{pengaduan}`)
1. **Breadcrumb & Tombol Kembali:** Tautan kembali ke `/arsip-kasus`.
2. **Header Kasus:** Nomor tiket resmi, nama perusahaan, posisi lowongan, dan badge status akhir permanen.
3. **Ringkasan Keputusan & Hasil Investigasi:**
   - Keputusan akhir yang ditetapkan (Tindaklanjuti / Selesai vs Tidak Ditindaklanjuti / Tidak Terbukti).
   - Catatan keputusan dari Kepala Bidang dan catatan audit investigasi dari Analis.
   - Pejabat pengambil keputusan & tanggal penetapan.
4. **Dokumen Surat yang Diterbitkan (Jika Ada):**
   - Nomor surat resmi (#LP-2026/POLRI/...), tanggal terbit, instansi kepolisian tujuan, dan tombol "Lihat / Unduh Dokumen Resmi".
   - Jika kasus berstatus *Tidak Ditindaklanjuti* atau *Tidak Terbukti*, bagian ini menampilkan keterangan bahwa tidak ada surat yang diterbitkan.
5. **Garis Waktu Kronologis Riwayat Kasus (BR-11):**
   - Timeline vertikal menampilkan setiap langkah status dari awal pengaduan hingga status akhir lengkap dengan tanggal dan keterangan perubahan.
6. **Data Lowongan & Bukti Pendukung:**
   - Teks kutipan deskripsi lowongan kerja yang dilaporkan.
   - Daftar berkas digital bukti pendukung yang disertakan saat pemeriksaan.

---

## 4. Keamanan & Hak Akses (NFR-SEC-02)
- Rute dilindungi middleware `auth` dan `role:Kepala Bidang`.
- Masyarakat dan Analis yang mencoba mengakses langsung akan menerima respon `403 Forbidden`.
- Pengguna yang belum login dialihkan (302) ke halaman login.
- Percobaan membuka detail kasus yang belum berstatus akhir (misalnya masih *Menunggu Keputusan* atau *Sedang Ditinjau*) akan mengembalikan respon `404 Not Found`.

