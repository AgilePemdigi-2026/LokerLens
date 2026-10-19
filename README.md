# LokerLens

**Sistem Deteksi Lowongan Kerja Mencurigakan sebagai Dukungan Pengawasan Informasi Ketenagakerjaan Digital**

LokerLens adalah sistem berbasis web yang membantu instansi pemerintah mendeteksi, meninjau, dan menindaklanjuti lowongan kerja yang terindikasi mencurigakan. Teks lowongan dianalisis menggunakan _Natural Language Processing_ (NLP) dan _machine learning_ untuk menghasilkan kategori hasil (asli/palsu), _confidence score_, dan kata pemicu yang paling memengaruhi hasil tersebut.

Hasil klasifikasi bersifat **rekomendasi**, bukan penetapan otomatis bahwa suatu lowongan adalah penipuan. Keputusan akhir tetap berada pada Kepala Bidang.

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

## Alur Utama Sistem

1. **Masyarakat** memeriksa lowongan kerja yang dicurigai. Sistem mengklasifikasikan lowongan dan menampilkan hasil pemeriksaan.
2. Lowongan dengan tingkat kecurigaan tinggi ditandai sebagai **prioritas tinggi**.
3. **Analis** meninjau lowongan berprioritas tinggi, lalu mengisi catatan dan hasil tinjauan.
4. **Kepala Bidang** memutuskan laporan _Tindaklanjuti_ atau _Tidak Ditindaklanjuti_.
5. Untuk kasus yang ditindaklanjuti, Kepala Bidang menerbitkan **Surat Teguran/Laporan Polisi**, dan kasus dinyatakan selesai.

---

## Aktor

| Aktor         | Peran                                                            |
| ------------- | ---------------------------------------------------------------- |
| Masyarakat    | Memeriksa lowongan kerja dan memantau status pengaduan           |
| Analis        | Meninjau lowongan kerja berprioritas tinggi                      |
| Kepala Bidang | Menetapkan keputusan tindak lanjut dan menerbitkan dokumen resmi |

---

## Fitur

### Fitur Utama

| Fitur                                                    | Aktor         |
| -------------------------------------------------------- | ------------- |
| Periksa Lowongan Kerja                                   | Masyarakat    |
| Meninjau Lowongan Kerja dengan Prioritas Tinggi          | Analis        |
| Menindaklanjuti Laporan Lowongan Kerja yang Mencurigakan | Kepala Bidang |
| Buat Surat Teguran/Laporan Polisi                        | Kepala Bidang |

### Fitur Pendukung

| Fitur                              | Aktor         |
| ---------------------------------- | ------------- |
| Register, Login, dan Lupa Password | Semua aktor   |
| Beranda                            | Masyarakat    |
| Status Pengaduan                   | Masyarakat    |
| Riwayat Pengaduan                  | Masyarakat    |
| Dashboard Analis                   | Analis        |
| Dashboard Kepala Bidang            | Kepala Bidang |
| Arsip Kasus                        | Kepala Bidang |

---

## Komponen Machine Learning

| Tahap                   | Keterangan                                                                    |
| ----------------------- | ----------------------------------------------------------------------------- |
| Pengumpulan data        | _Scraping_ data lowongan kerja, lalu pembersihan dan pelabelan (asli/palsu)   |
| Praproses               | Pembersihan teks dan ekstraksi fitur dengan TF-IDF                            |
| Model yang dibandingkan | Naive Bayes, Logistic Regression, Support Vector Machine (SVM), Random Forest |
| Evaluasi                | Accuracy, precision, recall, dan F1-score                                     |
| Output                  | Kategori hasil, _confidence score_, dan kata pemicu                           |

Model dengan performa terbaik dipilih untuk diimplementasikan pada sistem.

---

## Teknologi

| Bagian           | Teknologi                                                           |
| ---------------- | ------------------------------------------------------------------- |
| Frontend         | _Akan diperbarui_                                                   |
| Backend          | _Akan diperbarui_                                                   |
| Basis data       | Basis data relasional (sistem pengelola basis data akan diperbarui) |
| Machine learning | Python                                                              |

---

## Rencana Sprint

| Sprint   | Minggu | Fokus                                                                                    |
| -------- | ------ | ---------------------------------------------------------------------------------------- |
| Sprint 1 | 9–10   | Scraping data, pelatihan model, Register, Login, dan seluruh fitur utama                 |
| Sprint 2 | 11–12  | Fitur pendukung dan penyelesaian fitur Sprint 1 yang belum memenuhi _Definition of Done_ |
| Sprint 3 | 13–14  | Pengujian fungsional, pengujian model, pengujian non-fungsional, UAT, dan perbaikan bug  |

---

## Cara Menjalankan

_Bagian ini akan diperbarui setelah teknologi dan struktur proyek ditetapkan._

```bash
# 1. Clone repository
git clone <url-repository>
cd <nama-folder>

# 2. Instalasi dependensi
# (akan diperbarui)

# 3. Menjalankan aplikasi
# (akan diperbarui)
```

---

## Dokumen Proyek

| Dokumen                                   | Keterangan                                                      |
| ----------------------------------------- | --------------------------------------------------------------- |
| Software Requirements Specification (SRS) | Kebutuhan fungsional, non-fungsional, dan model analisis sistem |
| Data Mockup                               | Rancangan _high-fidelity_ yang berisi data contoh               |
| Progress Sprint                           | Effort estimation, sprint planning, dan stand up mingguan       |
