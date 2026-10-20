# Kebutuhan Sistem — LokerLens

Kode kebutuhan **sama persis dengan SRS LokerLens v1.0** (FR-PL, FR-ML, FR-TL, FR-ST, NFR-xxx, BR-xx). Tidak ada penomoran baru.
Dokumen terkait: [prd.md](prd.md) · [architecture.md](architecture.md) · [design.md](design.md)

**Arti status pada tabel pelacakan:**
- ✅ **Sudah**: kebutuhan sudah dikerjakan di kode dan sesuai pernyataan SRS (atau keputusan tim).
- 🟡 **Sebagian**: sudah ada, tapi ada bagian yang belum sesuai (lihat kolom catatan).
- ❌ **Belum**: belum dikerjakan.

Semua path controller relatif terhadap `web/app/Http/Controllers/`. Status ditulis sesuai kondisi kode di branch `docs-dan-perbaikan`.

---

## 1. Kebutuhan Fungsional

### 1.1 F-01 Periksa Lowongan Kerja (SRS Table 4.3) — Masyarakat

| Kode | Kebutuhan (SRS) | Route / Controller | Status | Catatan |
|---|---|---|---|---|
| FR-PL-01 | Sistem harus menyediakan form untuk memasukkan nama perusahaan, posisi lowongan, dan deskripsi lowongan. | `GET /periksa-lowongan` → `PeriksaLowonganController@index` | ✅ | |
| FR-PL-02 | Sistem harus memvalidasi kelengkapan data wajib sebelum proses pemeriksaan dilakukan. | `POST /periksa-lowongan` → `PeriksaLowonganController@store` | ✅ | Deskripsi minimal 50 karakter. |
| FR-PL-03 | Sistem harus menyimpan data Perusahaan dan Lowongan_Kerja yang diperiksa. | `PeriksaLowonganController@store` | ✅ | |
| FR-PL-04 | Sistem harus membuat data Pengaduan berdasarkan lowongan kerja yang diperiksa. | `PeriksaLowonganController@store`, `Pengaduan::generateNomorTiket()` | ✅ | Nomor tiket selalu unik, termasuk saat database kosong. |
| FR-PL-05 | Sistem harus melakukan *preprocessing* teks dan ekstraksi fitur menggunakan TF-IDF. | — (rencana: API ML di `ml/`) | ❌ | Saat ini masih simulasi kata kunci (`simulasiKlasifikasi`). |
| FR-PL-06 | Sistem harus mengklasifikasikan lowongan sebagai asli atau palsu dan menghasilkan *confidence score*. | `PeriksaLowonganController@store` | 🟡 | Skor dari simulasi, bukan model. `kategori_hasil` disimpan tapi tidak ditampilkan di view mana pun. |
| FR-PL-07 | Sistem harus menghasilkan Indikator_Mencurigakan berdasarkan hasil pemeriksaan. | `PeriksaLowonganController@store` | 🟡 | Dari aturan kata kunci + checklist, belum dari model. |
| FR-PL-08 | Sistem harus menyimpan Hasil_Klasifikasi dan Indikator_Mencurigakan. | `PeriksaLowonganController@store` | ✅ | |
| FR-PL-09 | Sistem harus menampilkan hasil pemeriksaan yang mencakup kategori hasil, *confidence score*, dan kata pemicu. | `GET /periksa-lowongan/hasil/{id}` → `PeriksaLowonganController@hasil` | 🟡 | Judul selalu "Lowongan berpotensi penipuan", berapa pun skornya. Kata pemicu hanya diambil dari kata pertama nama indikator. |
| FR-PL-10 | Sistem harus menampilkan pesan "Data belum lengkap, mohon lengkapi terlebih dahulu" apabila ada data wajib yang belum diisi. | `PeriksaLowonganController@store` | ✅ | |

### 1.2 F-02 Meninjau Lowongan Kerja dengan Prioritas Tinggi (SRS Table 4.5) — Analis

| Kode | Kebutuhan (SRS) | Route / Controller | Status | Catatan |
|---|---|---|---|---|
| FR-ML-01 | Sistem harus hanya mengizinkan Analis yang sudah login mengakses fitur ini. | Middleware `auth` + `role:Analis` (`routes/web.php`) | ✅ | |
| FR-ML-02 | Sistem harus menampilkan Daftar Lowongan Prioritas Tinggi yang hanya memuat lowongan berstatus prioritas tinggi pada Hasil_Klasifikasi. | `GET /tinjau-lowongan` → `TinjauLowonganController@index` | ✅ | Ambang prioritas diambil dari `config/lokerlens.php` (default 70). Daftar hanya memuat kasus berstatus *Perlu Tinjauan* / *Sedang Ditinjau*. |
| FR-ML-03 | Sistem harus menampilkan daftar sesuai kriteria ketika Analis memilih filter skor dan urutan skor. | `TinjauLowonganController@index` | 🟡 | Urutan "Skor Terendah" dan "Terbaru" tidak berpengaruh karena urutan default (skor tertinggi) selalu dipasang lebih dulu. |
| FR-ML-04 | Sistem harus menampilkan lowongan yang sesuai ketika Analis memasukkan nomor tiket dan menekan "Cari". | `TinjauLowonganController@index` | ✅ | Juga bisa mencari nama perusahaan. |
| FR-ML-05 | Sistem harus menampilkan "Data lowongan tidak ditemukan" jika nomor tiket atau data lowongan tidak ditemukan. | `TinjauLowonganController@index`, `@detail` | 🟡 | Daftar sudah menampilkan pesan. Untuk detail dengan ID salah, sistem menampilkan 404, bukan notifikasi + kembali ke daftar (SRS UC-02 skenario alternatif). |
| FR-ML-06 | Sistem harus menampilkan informasi lowongan, *confidence score*, dan Indikator_Mencurigakan ketika Analis menekan "Lihat Detail". | `GET /tinjau-lowongan/detail/{id}` → `TinjauLowonganController@detail` | ✅ | Bagian "Ringkasan Analisis Sistem" dan "Bukti Pendukung" di view masih teks contoh tetap. |
| FR-ML-07 | Sistem harus menyediakan isian catatan tinjauan dan hasil tinjauan. | View `tinjau-lowongan/detail` | ✅ | Dua pilihan tetap sesuai keputusan tim: "Terkonfirmasi Mencurigakan" / "Tidak Terbukti" (`App\Enums\HasilTinjauan`). |
| FR-ML-08 | Sistem harus menyimpan catatan tinjauan, hasil tinjauan, dan tanggal tinjauan ke Tinjauan_Analis ketika Analis menekan "Lanjutkan", lalu menampilkan "Hasil Tinjauan Berhasil Disimpan". | `POST /tinjau-lowongan/detail/{id}/simpan` → `TinjauLowonganController@simpanTinjauan` | ✅ | |
| FR-ML-09 | Sistem harus menjalankan use case Menindaklanjuti Laporan Lowongan Kerja yang Mencurigakan setelah tinjauan tersimpan. | `TinjauLowonganController@simpanTinjauan` | ✅ | Hanya "Terkonfirmasi Mencurigakan" yang diteruskan (*Menunggu Keputusan*); "Tidak Terbukti" menutup kasus. Kasus di luar tahap tinjauan tidak bisa ditinjau ulang. |

### 1.3 F-03 Menindaklanjuti Laporan Lowongan Kerja yang Mencurigakan (SRS Table 4.7) — Kepala Bidang

| Kode | Kebutuhan (SRS) | Route / Controller | Status | Catatan |
|---|---|---|---|---|
| FR-TL-01 | Sistem harus membatasi akses fitur ini hanya untuk pengguna dengan peran Kepala Bidang. | Middleware `auth` + `role:Kepala Bidang` | ✅ | |
| FR-TL-02 | Sistem harus menampilkan detail laporan yang meliputi informasi lowongan, hasil pemeriksaan sistem (kategori hasil, *confidence score*, kata pemicu), dan hasil tinjauan Analis. | `GET /tindak-lanjut/{id}` → `TindakLanjutController@detail` | 🟡 | Kata pemicu di view masih ditulis tetap ("Biaya akomodasi", dll.), tidak diambil dari database. |
| FR-TL-03 | Sistem harus menyediakan dua pilihan keputusan, yaitu "Tindaklanjuti" dan "Tidak Ditindaklanjuti". | View `tindak-lanjut/detail` | ✅ | Dengan pop-up konfirmasi (Data Mockup Gambar 2.12). |
| FR-TL-04 | Sistem harus menyimpan keputusan Kepala Bidang beserta tanggal keputusan ke data Tindak Lanjut Kasus. | `POST /tindak-lanjut/{id}/keputusan` → `TindakLanjutController@simpanKeputusan` | ✅ | Hanya kasus *Menunggu Keputusan* yang bisa diputuskan, dan hanya sekali. |
| FR-TL-05 | Sistem harus menandai kasus "tidak dilanjuti", mengarsipkan laporan, dan menampilkan "Kasus ditandai tidak dilanjuti dan diarsipkan" ketika Kepala Bidang memilih "Tidak Ditindaklanjuti". | `TindakLanjutController@simpanKeputusan`, `@hasil` | ✅ | |
| FR-TL-06 | Sistem harus menampilkan halaman Buat Surat Teguran/Laporan Polisi ketika Kepala Bidang memilih "Tindaklanjuti". | `GET /tindak-lanjut/{id}/hasil` → tombol "Lanjut ke Pembuatan Surat" | ✅ | Mengikuti Data Mockup (Gambar 2.13): hasil keputusan dulu, baru tombol ke pembuatan surat. |
| FR-TL-07 | Sistem harus mencatat setiap perubahan status kasus akibat keputusan tindak lanjut ke dalam Riwayat Kasus. | `TindakLanjutController@simpanKeputusan` | ✅ | |

### 1.4 F-04 Buat Surat Teguran/Laporan Polisi (SRS Table 4.9) — Kepala Bidang

| Kode | Kebutuhan (SRS) | Route / Controller | Status | Catatan |
|---|---|---|---|---|
| FR-ST-01 | Sistem harus hanya mengizinkan Kepala Bidang yang sudah login mengakses halaman Buat Surat Teguran/Laporan Polisi. | Middleware `auth` + `role:Kepala Bidang` | ✅ | |
| FR-ST-02 | Sistem harus membuka halaman pembuatan surat hanya setelah Kepala Bidang memilih Tindaklanjuti. | `GET /buat-surat/{pengaduanId}` → `BuatSuratController@index` | ✅ | Ditolak 403 jika belum "Tindaklanjuti". |
| FR-ST-03 | Sistem harus menampilkan Data Kasus (nomor tiket kasus dan tanggal validasi) terisi otomatis dan hanya dapat dibaca. | View `buat-surat/index` | ✅ | |
| FR-ST-04 | Sistem harus menyediakan isian Nama Perusahaan (Terlapor), Kronologi Kejadian, Detail Pelanggaran, Bukti Pendukung, dan Tujuan Kepolisian. | View `buat-surat/index` | 🟡 | Pilihan "Detail Pelanggaran" (pasal) tidak disimpan karena belum ada kolomnya. Daftar bukti yang "terlampir" masih teks contoh tetap. |
| FR-ST-05 | Sistem harus menyimpan isian sebagai draf ketika Kepala Bidang menekan Simpan Draft, lalu menampilkan "Draf berhasil disimpan". | `POST /buat-surat/{pengaduanId}/draft` → `BuatSuratController@simpanDraft` | ✅ | Bukti `.jpeg` disimpan sebagai `jpg`. Draf tidak bisa disimpan lagi setelah surat terbit. |
| FR-ST-06 | Sistem harus menyimpan surat dan memperbarui status penerbitan menjadi terbit ketika Kepala Bidang menekan Terbitkan Laporan. | `POST /buat-surat/{pengaduanId}/terbitkan` → `BuatSuratController@terbitkan` | ✅ | Surat hanya bisa terbit satu kali (dicek di kode + constraint unique `id_tindaklanjut`). |
| FR-ST-07 | Sistem harus mencatat penerbitan dokumen ke Riwayat_Kasus. | `BuatSuratController@terbitkan` | ✅ | |
| FR-ST-08 | Sistem harus menandai kasus terdokumentasi otomatis dan kasus selesai setelah dokumen terbit. | `BuatSuratController@terbitkan`, `GET /buat-surat/{pengaduanId}/terbit/{suratId}` | ✅ | |
| FR-ST-09 | Sistem harus menampilkan "Gagal menerbitkan dokumen, draf telah disimpan" dan mempertahankan draf ketika penerbitan gagal. | `BuatSuratController@terbitkan` | ✅ | Isian disimpan sebagai draf lebih dulu; penerbitan memakai transaksi database, sehingga draf tetap ada bila gagal. |

---

## 2. Kebutuhan Non-Fungsional

### 2.1 Performa (SRS Table 5.1)

| Kode | Kebutuhan (SRS) | Bagian kode | Status | Catatan |
|---|---|---|---|---|
| NFR-PERF-01 | Daftar Lowongan Prioritas Tinggi tampil maksimal 3 detik pada 100 data. | `TinjauLowonganController@index` (paginasi 10) | ❌ | Belum diuji. |
| NFR-PERF-02 | Pemeriksaan lowongan selesai maksimal 5 detik (minimal 95% dari 30 percobaan). | `PeriksaLowonganController@store` + API ML | ❌ | Belum diuji; API ML belum ada. |
| NFR-PERF-03 | Melayani 20 pengguna bersamaan tanpa kegagalan proses. | — | ❌ | Belum diuji (rencana: Apache JMeter). |

### 2.2 Keamanan (SRS Table 5.2)

| Kode | Kebutuhan (SRS) | Bagian kode | Status | Catatan |
|---|---|---|---|---|
| NFR-SEC-01 | Mewajibkan login sebelum mengakses fitur Section 4. | Middleware `auth` di `routes/web.php` | ✅ | |
| NFR-SEC-02 | Membatasi fitur sesuai peran: Masyarakat hanya Periksa Lowongan, Analis hanya Meninjau, Kepala Bidang hanya Menindaklanjuti dan Membuat Laporan Polisi. | `app/Http/Middleware/CheckRole.php` (alias `role`) | ✅ | Pembatasan per peran diuji di `HakAksesPeranTest`. Halaman terbit memastikan surat milik pengaduan yang dibuka; detail Kepala Bidang hanya untuk kasus yang sudah diteruskan Analis. |
| NFR-SEC-03 | Pesan gagal login sama untuk email tidak terdaftar dan kata sandi salah. | `Auth/AuthenticatedSessionController`, `Requests/Auth/LoginRequest` | ✅ | Memakai `LokerLensCopy::GAGAL_LOGIN`. |
| NFR-SEC-04 | Kata sandi disimpan ter-hash. | Model `User` (cast `hashed`), bcrypt bawaan Laravel | ✅ | Algoritma di SRS masih TBD; sementara memakai bcrypt. |
| NFR-SEC-05 | Data pribadi pengguna hanya untuk pemilik akun dan peran berwenang. | `PeriksaLowonganController@hasil` (filter pemilik) | 🟡 | **[PERLU DISKUSI]** Peran mana yang berwenang melihat nama/email pelapor? Saat ini Kepala Bidang melihat email pelapor di halaman detail. |
| NFR-SEC-06 | Mengenkripsi komunikasi peramban–server. | — (konfigurasi server) | ❌ | Bergantung pada lingkungan *deploy* (HTTPS). |
| NFR-SEC-07 | Bukti Pendukung hanya pdf, jpg, atau png. | `BuatSuratController@simpanDraft` | 🟡 | Validasi `mimes:pdf,jpg,jpeg,png`, maks 10 MB; `.jpeg` disimpan sebagai `jpg`. Batas ukuran di SRS masih TBD. |

### 2.3 Keselamatan (SRS Table 5.3)

| Kode | Kebutuhan (SRS) | Bagian kode | Status | Catatan |
|---|---|---|---|---|
| NFR-SAF-01 | Hasil Pemeriksaan ditampilkan sebagai prediksi dengan *confidence score*, tidak menetapkan penipuan otomatis. | View `periksa-lowongan/hasil` | 🟡 | Catatan "rekomendasi pendukung keputusan" sudah ada, tapi judul selalu "Lowongan berpotensi penipuan". |
| NFR-SAF-02 | Hanya Kepala Bidang yang bisa menetapkan keputusan tindak lanjut. | Middleware `role:Kepala Bidang` | ✅ | |
| NFR-SAF-03 | Halaman Buat Laporan Polisi hanya terbuka setelah "Tindaklanjuti"; tidak ada dokumen terbit tanpa tindakan Kepala Bidang. | `BuatSuratController` | ✅ | Halaman surat hanya setelah "Tindaklanjuti"; surat tidak bisa terbit dua kali. |
| NFR-SAF-04 | Jika simpan gagal, data isian dipertahankan dan tidak ada data tersimpan sebagian. | Semua controller penyimpan | 🟡 | Alur kasus (periksa, tinjau, keputusan, terbit) sudah memakai transaksi database. Form lain (profil, upload bukti) belum. |
| NFR-SAF-05 | Menolak pemeriksaan dengan field wajib kosong dan menampilkan pesan resmi. | `PeriksaLowonganController@store` | ✅ | |
| NFR-SAF-06 | Mencatat setiap perubahan status kasus ke Riwayat Kasus beserta waktunya. | `RiwayatKasus::create` di tiap controller | ✅ | Semua perubahan status lewat `Pengaduan::ubahStatus()` yang otomatis mencatat Riwayat Kasus. |

### 2.4 Atribut Kualitas (SRS Table 5.4)

| Kode | Kebutuhan (SRS) | Bagian kode | Status | Catatan |
|---|---|---|---|---|
| NFR-QUA-01 | Teks tombol, label, status, dan pesan seragam sesuai daftar teks resmi. | `app/Constants/LokerLensCopy.php` | 🟡 | Konstanta sudah ada, tapi banyak view masih menulis teks langsung. |
| NFR-QUA-02 | Masyarakat dapat menyelesaikan pemeriksaan tanpa bantuan dalam waktu [TBD]. | — | ❌ | **[PERLU DISKUSI]** Target waktu dan jumlah responden. |
| NFR-QUA-03 | Sistem dapat diakses minimal [TBD]% waktu operasional. | — | ❌ | **[PERLU DISKUSI]** Target ketersediaan. |
| NFR-QUA-04 | Model mencapai accuracy, precision, recall, dan F1 minimal [TBD] pada data uji. | `ml/` | ❌ | Model belum dilatih. **[PERLU DISKUSI]** Target metrik. |
| NFR-QUA-05 | Kategori hasil dan *confidence score* yang tampil sama dengan nilai di Hasil_Klasifikasi. | View `periksa-lowongan/hasil` | 🟡 | Skor sama dengan database, tapi kategori yang tampil tidak diambil dari `kategori_hasil`. |
| NFR-QUA-06 | Menampilkan kata/frasa pemicu yang paling memengaruhi hasil, minimal [TBD] kata. | View `periksa-lowongan/hasil` (elemen `kata-pemicu`) | 🟡 | Masih dari simulasi. **[PERLU DISKUSI]** Jumlah minimal kata. |
| NFR-QUA-07 | Dokumen rancangan konsisten satu sama lain dan dengan implementasi. | `docs/` | 🟡 | Dokumen ini sudah mencatat perbedaan yang ditemukan. |
| NFR-QUA-08 | Berfungsi pada peramban [TBD]. | — | ❌ | **[PERLU DISKUSI]** Daftar peramban dan versinya. |

---

## 3. Aturan Bisnis (SRS Table 5.5)

| Kode | Aturan (SRS) | Bagian kode | Status | Catatan |
|---|---|---|---|---|
| BR-01 | Hanya Kepala Bidang yang berwenang memutuskan tindak lanjut. | `role:Kepala Bidang` | ✅ | |
| BR-02 | Keputusan hanya Tindaklanjuti atau Tidak Ditindaklanjuti. | Validasi `in:` di `TindakLanjutController` + enum DB | ✅ | |
| BR-03 | Laporan Tidak Ditindaklanjuti ditandai tidak dilanjuti dan diarsipkan. | `TindakLanjutController@simpanKeputusan` | ✅ | |
| BR-04 | Hasil pemeriksaan sistem adalah prediksi pendukung keputusan, bukan penetapan penipuan. | View hasil | 🟡 | Lihat NFR-SAF-01. |
| BR-05 | Hanya lowongan berprioritas tinggi yang masuk daftar tinjauan Analis. Ambang batas TBD. | `PeriksaLowonganController@store` (`status_prioritas`) | 🟡 | Ambang di `config/lokerlens.php` (`LOKERLENS_AMBANG_PRIORITAS`, default 70). **[PERLU DISKUSI]** Nilai final. |
| BR-06 | *(SRS)* Analis hanya memberikan catatan dan hasil tinjauan, dan tidak membuat keputusan akhir. | `TinjauLowonganController@simpanTinjauan` | ✅ | Diterapkan sesuai keputusan tim (lihat §4): Analis menutup kasus "Tidak Terbukti". |
| BR-07 | *(SRS)* Laporan yang telah selesai ditinjau Analis diteruskan ke proses tindak lanjut oleh Kepala Bidang. | `TinjauLowonganController@simpanTinjauan` | ✅ | Diterapkan sesuai keputusan tim (lihat §4): hanya "Terkonfirmasi Mencurigakan" yang diteruskan. |
| BR-08 | Surat hanya dibuat dan diterbitkan Kepala Bidang, dan hanya untuk laporan Tindaklanjuti. | `BuatSuratController` + `role:Kepala Bidang` | ✅ | |
| BR-09 | Draf belum dianggap terbit; resmi setelah diterbitkan. | Kolom `status_penerbitan` (Draft/Terbit) | ✅ | |
| BR-10 | Kasus selesai dan terdokumentasi setelah dokumen resmi terbit. | `BuatSuratController@terbitkan` | ✅ | |
| BR-11 | Setiap perubahan status kasus dicatat dalam Riwayat Kasus. | `RiwayatKasus::create` | ✅ | Lihat NFR-SAF-06. |

---

## 4. Perbedaan dengan SRS

### 4.1 BR-06 dan BR-07: wewenang Analis

**Keputusan tim (20 Oktober 2026):** Analis meninjau lowongan berprioritas tinggi untuk memastikan apakah benar penipuan. Hasil tinjauan hanya ada dua:
- **Terkonfirmasi Mencurigakan**: diteruskan ke Kepala Bidang (status *Menunggu Keputusan*).
- **Tidak Terbukti**: kasus **langsung ditutup oleh Analis** (status akhir *Tidak Terbukti*) dan tidak diteruskan ke Kepala Bidang.

| | Kalimat SRS v1.0 | Usulan kalimat baru |
|---|---|---|
| **BR-06** | Analis hanya memberikan catatan dan hasil tinjauan, dan tidak membuat keputusan akhir. | Analis memberikan catatan dan hasil tinjauan berupa "Terkonfirmasi Mencurigakan" atau "Tidak Terbukti". Analis berwenang menutup kasus yang hasil tinjauannya "Tidak Terbukti", tetapi tidak membuat keputusan tindak lanjut atas kasus yang terkonfirmasi mencurigakan. |
| **BR-07** | Laporan yang telah selesai ditinjau Analis diteruskan ke proses tindak lanjut oleh Kepala Bidang. | Hanya laporan dengan hasil tinjauan "Terkonfirmasi Mencurigakan" yang diteruskan ke proses tindak lanjut oleh Kepala Bidang. Laporan dengan hasil tinjauan "Tidak Terbukti" ditutup dan tidak diteruskan. |

**Dampak ke bagian SRS lain** (perlu diselaraskan saat SRS direvisi):
- **FR-ML-07**: isian hasil tinjauan berupa dua pilihan tetap, bukan isian bebas.
- **FR-ML-09**: use case tindak lanjut hanya dijalankan untuk hasil "Terkonfirmasi Mencurigakan".
- **SRS Table B.3** (skenario BDD): `select "Mencurigakan" from "hasil_tinjauan"` perlu diganti menjadi `"Terkonfirmasi Mencurigakan"`.
- **BR-01** tetap berlaku. Menutup kasus "Tidak Terbukti" bukan keputusan tindak lanjut (Tindaklanjuti/Tidak Ditindaklanjuti), jadi wewenang Kepala Bidang tidak berkurang.
- **NFR-SAF-01**: tetap terpenuhi, karena penutupan "Tidak Terbukti" dilakukan manusia (Analis), bukan sistem.

### 4.2 Perbedaan lain

| Bagian SRS | Isi SRS | Keputusan tim / temuan | Tindakan |
|---|---|---|---|
| SRS 2.1 (BPMN To-Be) | Kasus yang tidak disetujui "ditandai dipantau". | Keputusan tim memakai status *Tidak Ditindaklanjuti* (diarsipkan), sama dengan BR-03. | Selaraskan BPMN To-Be (Appendix C no. 6). |
| SRS 3.2, 6.2 | Output model: kategori asli/palsu + *confidence score* + kata pemicu. | *Confidence score* dimaknai sebagai **probabilitas penipuan 0–100%** (makin tinggi makin tidak aman). | Perjelas definisi *confidence score* di Glossary (Appendix A). |
| SRS 6.2 (Dataset) | Sumber, jumlah, dan bahasa dataset TBD. | Data palsu dari Kaggle (bahasa Indonesia), data asli dari hasil *scraping*. Jumlah data masih TBD. | Isi Table 6.2. |
| Data Mockup 2.3 | Sistem memproses deskripsi **dan** informasi tambahan. | Input model **hanya teks deskripsi lowongan**. | Lihat [architecture.md §5](architecture.md#5-rencana-integrasi-laravel--api-ml). |
| Appendix C no. 18 | "Ambang batas skor prioritas tinggi (**BR-09**)". | Rujukan seharusnya **BR-05**. | Perbaiki rujukan di Appendix C. |
| Appendix C no. 15 | Urutan status resmi kasus TBD. | Sudah ditetapkan tim (lihat §5). | Tandai selesai di Appendix C. |

---

## 5. Alur Status Kasus (keputusan tim)

| Status | Jenis | Siapa yang memicu | Status berikutnya yang boleh |
|---|---|---|---|
| Selesai - Risiko Rendah | **Akhir** | Sistem (skor < ambang prioritas) | — |
| Perlu Tinjauan | Awal | Sistem (skor ≥ ambang prioritas) | Sedang Ditinjau |
| Sedang Ditinjau | Proses | Analis membuka/meninjau kasus | Tidak Terbukti, Menunggu Keputusan |
| Tidak Terbukti | **Akhir** | Analis memilih "Tidak Terbukti" | — |
| Menunggu Keputusan | Proses | Analis memilih "Terkonfirmasi Mencurigakan" | Tindak Lanjut Disetujui, Tidak Ditindaklanjuti |
| Tindak Lanjut Disetujui | Proses | Kepala Bidang memilih "Tindaklanjuti" | Selesai |
| Tidak Ditindaklanjuti | **Akhir** | Kepala Bidang memilih "Tidak Ditindaklanjuti" | — |
| Selesai | **Akhir** | Kepala Bidang menerbitkan surat | — |

Aturan:
- Status hanya boleh maju sesuai kolom terakhir. Status tidak boleh melompat, mundur, atau berubah dari status akhir.
- Surat hanya bisa terbit satu kali.
- Setiap perubahan status dicatat di Riwayat Kasus.

**Implementasi:**
- Daftar status ada di satu tempat: `web/app/Enums/StatusKasus.php` (beserta aturan status berikutnya). Hasil tinjauan Analis ada di `web/app/Enums/HasilTinjauan.php`.
- Semua perubahan status lewat `Pengaduan::ubahStatus()`. Perubahan yang melompat, mundur, atau dari status akhir ditolak dengan pesan error, dan setiap perubahan otomatis dicatat ke Riwayat Kasus.
- Ambang prioritas ada di `web/config/lokerlens.php` (`LOKERLENS_AMBANG_PRIORITAS`, default 70).
- Migration `2026_10_20_000001_ubah_status_pengaduan_dan_unique_surat` mengganti nilai enum `status_pengaduan` dan memindahkan data lama: "Perlu Tinjauan Segera" → "Perlu Tinjauan", "Kasus selesai" / "Kasus terdokumentasi otomatis" → "Selesai", "Tidak dilanjuti" → "Tidak Ditindaklanjuti". Migration yang sama memasang constraint unique pada `surat_teguran_laporan_polisi.id_tindaklanjut`.
- Catatan lama di tabel `riwayat_kasus` **tidak diubah**, karena merupakan jejak audit dan boleh tetap memuat label lama.
- Teks "Kasus terdokumentasi otomatis" dan "Kasus selesai" tetap dipakai sebagai **pesan** di halaman hasil terbit (SRS FR-ST-08, BDD Table B.5), bukan sebagai status.

---

## 6. Daftar [PERLU DISKUSI]

Hal yang masih TBD di SRS (Appendix C) dan belum diputuskan tim:

| No | Asal | Pertanyaan |
|---|---|---|
| 1 | Appendix C no. 18, BR-05 | Berapa ambang final prioritas tinggi? (Sementara 70% di config.) |
| 2 | SRS 6.2 (Ambang batas) | Berapa ambang probabilitas untuk label "asli" vs "palsu"? Apakah sama dengan ambang prioritas? |
| 3 | NFR-QUA-04, Appendix C no. 17 | Berapa target minimum accuracy, precision, recall, dan F1-score? |
| 4 | NFR-QUA-06 | Minimal berapa kata pemicu yang ditampilkan per hasil? |
| 5 | SRS 6.2 (Dataset) | Berapa jumlah data latih (palsu dari Kaggle, asli dari *scraping*)? Situs apa saja yang di-*scrape*? |
| 6 | SRS 6.2 (Pembaruan model) | Bagaimana jadwal dan mekanisme pelatihan ulang model? |
| 7 | Appendix C no. 13 | Link lowongan, informasi tambahan, dan checklist sifatnya wajib atau opsional? Karena model hanya membaca deskripsi, apa fungsi checklist dan informasi tambahan: hanya disimpan, atau ikut memengaruhi skor? |
| 8 | Appendix C no. 4 | Apakah Surat Teguran termasuk cakupan, atau hanya Laporan Polisi? Apa nama resmi fiturnya? |
| 9 | Appendix C no. 7 | Apakah Dashboard, Status/Riwayat Pengaduan, Arsip Kasus, dan Statistik masuk SRS? |
| 10 | Appendix C no. 8 | Alur registrasi/login: halaman tujuan tiap peran sudah ada di kode (Masyarakat → Beranda, Analis → Tinjau Lowongan, Kepala Bidang → Tindak Lanjut). Apakah ini sudah final? |
| 11 | Appendix C no. 11 | Teks tombol: "Periksa" vs "Periksa Sekarang", "Tinjau Lowongan" vs "Tinjau Lowongan Kerja", "Terbitkan" vs "Terbitkan Laporan", "Draft" vs "draf". (Kode dan mockup memakai "Periksa Sekarang", "Terbitkan Laporan", "Simpan Draft".) |
| 12 | Appendix C no. 9 | Design system final (warna, font, ukuran, komponen). |
| 13 | Appendix C no. 12, SRS 2.4 | Peramban yang didukung, DBMS produksi (sementara SQLite), spesifikasi server, protokol komunikasi. |
| 14 | Appendix C no. 16, NFR-SEC-04/05/07 | Algoritma hash, peran yang boleh melihat data pribadi pelapor, batas ukuran bukti, masa berlaku sesi. |
| 15 | SRS 3.2 | Apakah dokumen yang terbit dikirim elektronik ke instansi tujuan, atau hanya tercatat di sistem? |
| 16 | Data Mockup 2.4 | Apa efek tombol "Tandai Terkonfirmasi Penipuan (Blacklist)" dan checkbox "Kirimkan notifikasi darurat"? Keduanya belum ada di SRS. |
| 17 | FR-ST-04 | Apakah pilihan "Detail Pelanggaran" (pasal) perlu disimpan ke database? Kalau ya, perlu kolom baru di tabel surat. |
| 18 | Appendix C no. 2, 3, 19, 21 | Nama resmi fitur, format penamaan class di diagram, dan nama proyek dalam bahasa Inggris. |
| 19 | Appendix C no. 20 | Master mock data (akun, perusahaan, nomor tiket, dll.). |
| 20 | Alur status | Kapan tepatnya status berubah menjadi "Sedang Ditinjau": saat Analis membuka detail, atau saat Analis menekan tombol khusus? (Sudah diterapkan sementara: saat Analis pertama kali membuka detail.) |
