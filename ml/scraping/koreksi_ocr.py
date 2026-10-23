"""
Koreksi salah ketik hasil OCR di data palsu (label 1) pada dataset gabungan.

Masukan/keluaran:
- ml/data/processed/dataset_lokerlens.csv (diperbarui di tempat)
- ml/data/koreksi/koreksi_ocr_palsu.json (daftar koreksi: id -> {"sebelum", "sesudah"})

Koreksi ditulis oleh AI/manusia ke file JSON, lalu script ini menerapkannya. Aturan koreksi:
1. Hanya betulkan kata yang salah eja karena OCR ("SMAISMK" -> "SMA/SMK", "Tidok" -> "Tidak").
2. Hapus potongan teks yang tidak terbaca (huruf acak, simbol, sisa watermark).
3. Jangan menambah kalimat, meringkas, mengubah susunan, atau mengubah gaya bahasa.
   Kalau ragu, biarkan atau hapus, jangan ditebak.
4. Pertahankan huruf besar-kecil, nomor telepon, link, tanggal, dan nominal uang
   (termasuk link dan nominal yang rusak karena OCR).
5. Data asli (label 0) tidak disentuh.
6. Hapus teks tampilan media sosial dari tangkapan layar (hanya ada di data palsu): jumlah
   pengikut/diikuti/kiriman/postingan, "Follow", "Send message", "See more", "See translation",
   "POSTINGAN DITANDAI", nama akun pengunggah di awal teks, dan cap waktu unggahan
   ("May at 19:24", "13 Juni pukul 19.56"). Hashtag isi postingan tetap dibiarkan.

Script memeriksa aturan 3 dan 4 secara otomatis: koreksi ditolak jika ada kata yang
disisipkan (bukan pengganti kata lama) atau ada link/nomor telepon/tanggal/nominal yang
hilang atau berubah. Tanggal di dalam cap waktu unggahan (aturan 6) boleh dihapus.

Kolom yang ditambahkan: deskripsi_sebelum_koreksi, dikoreksi_ai.
deskripsi_lowongan dan teks_gabungan diperbarui.

Catatan: gabung_dataset.py membuat ulang dataset tanpa kolom ini, jadi jalankan
"terapkan" lagi setelah gabung_dataset.py.

Pemakaian:
    python ml/scraping/koreksi_ocr.py siapkan --jumlah 10   # tambah 10 baris berikutnya ke JSON
    python ml/scraping/koreksi_ocr.py terapkan              # terapkan semua koreksi yang sudah diisi
"""

import argparse
import difflib
import html
import json
import re
import sys
from pathlib import Path

import pandas as pd

from gabung_dataset import PALSU_PATH, kosakata, persen_kata_rusak

ML_DIR = Path(__file__).resolve().parent.parent
DATASET_PATH = ML_DIR / "data" / "processed" / "dataset_lokerlens.csv"
KOREKSI_PATH = ML_DIR / "data" / "koreksi" / "koreksi_ocr_palsu.json"
RINGKASAN_PATH = ML_DIR / "data" / "processed" / "perbandingan_koreksi_ocr.txt"

BULAN = (
    r"jan(?:uari)?|feb(?:ruari)?|mar(?:et)?|apr(?:il)?|mei|jun(?:i)?|jul(?:i)?|agu(?:stus)?"
    r"|agt|sep(?:tember)?|okt(?:ober)?|nov(?:ember)?|des(?:ember)?|may|aug|oct|dec"
)
# Bagian teks yang harus tetap utuh (aturan 4).
POLA_DILINDUNGI = [
    ("link", re.compile(r"\S*(?:https?:|www\.|\[dot\]|\.(?:com|id|net|org|co|ly|me)\b)\S*", re.IGNORECASE)),
    ("telepon", re.compile(r"(?:\+?62|\b0)8\d{2,}(?:[\s-]\d{3,})*")),
    ("nominal", re.compile(r"\bRp\.?\s?[\d.,]+(?:\s?(?:juta|ribu|rb|jt))?", re.IGNORECASE)),
    ("tanggal", re.compile(rf"\b\d{{1,2}}[/-]\d{{1,2}}[/-]\d{{2,4}}\b|\b\d{{1,2}}\s(?:{BULAN})\b", re.IGNORECASE)),
]
# Cap waktu unggahan media sosial (aturan 6): "13 Juni pukul 19.56", "May at 19:24".
WAKTU_UNGGAH = re.compile(r"(?:\b\d{1,2}\s)?\w+(?:\s\d{1,4})?\s(?:pukul|at)\s\d{1,2}[.:]\d{2}\b", re.IGNORECASE)


def kosakata_kata_rusak(df):
    """Kosakata yang sama dengan gabung_dataset.py (deskripsi KarirHub + narasi_asli TurnBackHoax)."""
    narasi = pd.read_csv(PALSU_PATH, dtype=str, keep_default_na=False)["narasi_asli"].map(html.unescape)
    return kosakata(df.loc[df["label"] == "0", "deskripsi_lowongan"], narasi)


def buat_teks_gabungan(row):
    bagian = [row["nama_perusahaan"], row["deskripsi_lowongan"], row["informasi_tambahan"]]
    return re.sub(r"\s+", " ", " ".join(b for b in bagian if b)).strip()


def baca_koreksi():
    if not KOREKSI_PATH.exists():
        return {}
    return json.loads(KOREKSI_PATH.read_text(encoding="utf-8"))


def simpan_koreksi(koreksi):
    KOREKSI_PATH.parent.mkdir(parents=True, exist_ok=True)
    KOREKSI_PATH.write_text(json.dumps(koreksi, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def baca_dataset():
    df = pd.read_csv(DATASET_PATH, dtype=str, keep_default_na=False)
    if "deskripsi_sebelum_koreksi" not in df.columns:
        df["deskripsi_sebelum_koreksi"] = ""
    if "dikoreksi_ai" not in df.columns:
        df["dikoreksi_ai"] = "False"
    return df


def teks_awal(row):
    """Teks OCR sebelum dikoreksi (tetap benar walau script dijalankan berulang)."""
    return row["deskripsi_sebelum_koreksi"] or row["deskripsi_lowongan"]


def perubahan_kata(sebelum, sesudah):
    """Daftar (jenis, kata_lama, kata_baru) antara dua teks, per kata."""
    a, b = sebelum.split(), sesudah.split()
    hasil = []
    op_sebelumnya = None
    for op, i1, i2, j1, j2 in difflib.SequenceMatcher(a=a, b=b, autojunk=False).get_opcodes():
        bersebelahan = {op_sebelumnya, op} == {"insert", "delete"}
        op_sebelumnya = op
        if op == "equal":
            continue
        lama, baru = " ".join(a[i1:i2]), " ".join(b[j1:j2])
        # insert + delete yang bersebelahan adalah satu penggantian; difflib kadang memecahnya
        # saat kata yang sama muncul dua kali (mis. "Program" di nama akun dan di isi).
        if bersebelahan:
            _, lama_sebelumnya, baru_sebelumnya = hasil.pop()
            lama = " ".join(x for x in (lama_sebelumnya, lama) if x)
            baru = " ".join(x for x in (baru_sebelumnya, baru) if x)
            op = "replace"
        hasil.append((op, lama, baru))
    return hasil


def periksa(sebelum, sesudah, boleh_dihapus=()):
    """Kembalikan daftar pelanggaran aturan 3 dan 4.

    boleh_dihapus: teks tampilan media sosial (aturan 6) yang kebetulan cocok dengan pola
    dilindungi, misalnya cap waktu tanpa jam ("9 Apr") atau nama akun ("Loker.Id.2026").
    Dicatat per baris di JSON sebagai "boleh_dihapus" supaya bisa diperiksa.
    """
    masalah = []
    for op, lama, baru in perubahan_kata(sebelum, sesudah):
        if op == "insert":
            masalah.append(f"kata disisipkan: {baru!r}")
    waktu_unggah = [m.span() for m in WAKTU_UNGGAH.finditer(sebelum)]
    for nama, pola in POLA_DILINDUNGI:
        for m in pola.finditer(sebelum):
            if any(a <= m.start() and m.end() <= b for a, b in waktu_unggah):
                continue
            if m.group(0).strip() in boleh_dihapus:
                continue
            if m.group(0).strip() not in sesudah:
                masalah.append(f"{nama} berubah/hilang: {m.group(0).strip()!r}")
    return masalah


def siapkan(jumlah):
    df = baca_dataset()
    koreksi = baca_koreksi()
    palsu = df[df["label"] == "1"]
    baru = [row for _, row in palsu.iterrows() if row["id"] not in koreksi][:jumlah]
    for row in baru:
        koreksi[row["id"]] = {"sebelum": teks_awal(row), "sesudah": None}
    simpan_koreksi(koreksi)
    sisa = len(palsu) - len(koreksi)
    print(f"{len(baru)} baris ditambahkan ke {KOREKSI_PATH} (sisa {sisa} baris palsu belum disiapkan)")


def terapkan():
    df = baca_dataset()
    koreksi = baca_koreksi()
    indeks = {id_: i for i, id_ in zip(df.index, df["id"])}
    laporan, ditolak, belum_diisi = [], [], 0

    for id_, isi in koreksi.items():
        if isi.get("sesudah") is None:
            belum_diisi += 1
            continue
        if id_ not in indeks:
            ditolak.append((id_, ["id tidak ada di dataset"]))
            continue
        i = indeks[id_]
        row = df.loc[i]
        if row["label"] != "1":
            ditolak.append((id_, ["bukan data palsu (label 1)"]))
            continue
        sebelum = teks_awal(row)
        if isi["sebelum"] != sebelum:
            ditolak.append((id_, ["teks di dataset berbeda dari 'sebelum' di JSON (dataset dibuat ulang?)"]))
            continue
        sesudah = re.sub(r"\s+", " ", isi["sesudah"]).strip()
        masalah = periksa(sebelum, sesudah, isi.get("boleh_dihapus", []))
        if masalah:
            # Kembalikan ke teks OCR asli supaya koreksi lama yang kini ditolak tidak tertinggal.
            df.loc[i, ["deskripsi_lowongan", "deskripsi_sebelum_koreksi", "dikoreksi_ai"]] = [sebelum, "", "False"]
            df.loc[i, "teks_gabungan"] = buat_teks_gabungan(df.loc[i])
            ditolak.append((id_, masalah))
            continue

        diubah = sesudah != sebelum
        df.loc[i, "deskripsi_sebelum_koreksi"] = sebelum if diubah else ""
        df.loc[i, "deskripsi_lowongan"] = sesudah
        df.loc[i, "dikoreksi_ai"] = str(diubah)
        df.loc[i, "teks_gabungan"] = buat_teks_gabungan(df.loc[i])
        laporan.append((id_, sebelum, sesudah))

    df.to_csv(DATASET_PATH, index=False, encoding="utf-8")

    baris = []
    for id_, sebelum, sesudah in laporan:
        perubahan = perubahan_kata(sebelum, sesudah)
        baris.append(f"===== {id_} ({len(perubahan)} perubahan)")
        baris.append(f"SEBELUM: {sebelum}")
        baris.append(f"SESUDAH: {sesudah}")
        for op, lama, baru in perubahan:
            baris.append(f"  - {lama!r} dihapus" if op == "delete" else f"  - {lama!r} -> {baru!r}")
        baris.append("")
    RINGKASAN_PATH.write_text("\n".join(baris), encoding="utf-8")

    jumlah_diubah = sum(s != t for _, s, t in laporan)
    print(f"Diterapkan: {len(laporan)} baris ({jumlah_diubah} dikoreksi, {len(laporan) - jumlah_diubah} tanpa perubahan)")
    print(f"Belum diisi: {belum_diisi} | Ditolak: {len(ditolak)}")
    for id_, masalah in ditolak:
        print(f"  DITOLAK {id_}: " + "; ".join(masalah))
    palsu = df[df["label"] == "1"]
    kosa = kosakata_kata_rusak(df)
    rusak_sebelum = palsu.apply(teks_awal, axis=1).map(lambda t: persen_kata_rusak(t, kosa)).mean()
    rusak_sesudah = palsu["deskripsi_lowongan"].map(lambda t: persen_kata_rusak(t, kosa)).mean()
    print(f"Total data palsu dikoreksi_ai = True: {(palsu['dikoreksi_ai'] == 'True').sum()} dari {len(palsu)}")
    print(f"Rata-rata kata rusak data palsu: sebelum {rusak_sebelum:.2f}% → sesudah {rusak_sesudah:.2f}%")
    print(f"Perbandingan sebelum-sesudah: {RINGKASAN_PATH}")
    print(f"Dataset diperbarui: {DATASET_PATH}")


def main():
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8")
    parser = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    sub = parser.add_subparsers(dest="perintah", required=True)
    p_siapkan = sub.add_parser("siapkan", help="tambahkan baris palsu berikutnya ke file koreksi")
    p_siapkan.add_argument("--jumlah", type=int, default=10)
    sub.add_parser("terapkan", help="terapkan koreksi yang sudah diisi ke dataset")
    args = parser.parse_args()
    if args.perintah == "siapkan":
        siapkan(args.jumlah)
    else:
        terapkan()


if __name__ == "__main__":
    main()
