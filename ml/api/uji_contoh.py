"""Uji cepat API klasifikasi dengan 3 contoh lowongan asli dan 3 palsu.

Jalankan API dulu (uvicorn api.app:app --port 8001), lalu dari folder ml/:
    python api/uji_contoh.py
"""
import json
import urllib.request

URL = "http://localhost:8001/predict"

CONTOH = [
    ("asli", "Dibutuhkan Akuntan Junior, minimal S1 Akuntansi, pengalaman 1 tahun, menguasai Microsoft Excel "
             "dan software akuntansi. Gaji sesuai UMR Surabaya, BPJS, tunjangan makan. Wawancara di kantor kami."),
    ("asli", "Kami mencari Sales Executive yang bertanggung jawab mencapai target penjualan produk di area Jawa Timur. "
             "Kualifikasi: minimal D3 semua jurusan, memiliki pengalaman di bidang penjualan minimal 1 tahun, "
             "mampu berkomunikasi dengan baik, memiliki kendaraan sendiri dan SIM C. Benefit: gaji pokok, insentif, BPJS."),
    ("asli", "Posisi Customer Service. Tanggung jawab: melayani pelanggan melalui telepon dan email, mencatat keluhan "
             "pelanggan, dan memastikan setiap masalah terselesaikan. Persyaratan: minimal SMA/SMK, memiliki kemampuan "
             "komunikasi yang baik, bersedia bekerja shift, berpengalaman sebagai customer service menjadi nilai tambah."),
    ("palsu", "PT Mandiri Sukses Global membuka lowongan Staff Administrasi. Gaji Rp8.000.000-Rp12.000.000 per bulan, "
              "tanpa pengalaman. Kandidat diminta membayar biaya administrasi Rp150.000 sebelum wawancara."),
    ("palsu", "LOWONGAN KERJA BUMN TERBARU 2026! Dibuka rekrutmen besar-besaran lulusan SMA/SMK/D3/S1 semua jurusan. "
              "Pendaftaran melalui link resmi di bio. Peserta yang lolos wajib transfer biaya pendaftaran Rp250.000 "
              "untuk seragam dan tes kesehatan. Info hubungi WA 0812-3456-7890."),
    ("palsu", "Info loker PT Pertamina Persero, dibutuhkan staff gudang dan admin, gaji Rp7.500.000 + tunjangan. "
              "Persyaratan mudah, tanpa tes. Kirim CV ke rekrutmen.pertamina@gmail.com. Interview dilaksanakan di "
              "hotel, peserta wajib membayar biaya akomodasi melalui agen travel yang ditunjuk."),
]

benar = 0
for i, (harapan, teks) in enumerate(CONTOH, start=1):
    req = urllib.request.Request(URL, data=json.dumps({"deskripsi": teks}).encode(),
                                 headers={"Content-Type": "application/json"})
    hasil = json.load(urllib.request.urlopen(req))
    skor = hasil["probabilitas_penipuan"] * 100
    cocok = hasil["label"] == harapan
    benar += cocok
    print(f"{i}. harapan={harapan:5} | hasil={hasil['label']:5} | skor={skor:5.1f} | "
          f"prioritas_tinggi={'ya' if skor >= 70 else 'tidak':5} | {'OK' if cocok else 'SALAH'}")
    print("   kata pemicu:", ", ".join(k["kata"] for k in hasil["kata_pemicu"]) or "-")

print(f"\nBenar {benar} dari {len(CONTOH)}")
