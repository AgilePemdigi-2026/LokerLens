#!/usr/bin/env python3
"""
rapikan_dataset.py — merapikan dataset_lokerlens.csv untuk LokerLens

Input : dataset_lokerlens.csv (kolom asli: id, label, nama_perusahaan, posisi_lowongan,
        deskripsi_lowongan, informasi_tambahan, teks_gabungan, sumber_lowongan, link_lowongan,
        perlu_cek_manual, deskripsi_sebelum_koreksi, dikoreksi_ai)

Output: dataset_lokerlens_bersih.csv -> id, label, nama_perusahaan, posisi, deskripsi
        (laporan isian per kelas & akurasi ekstraksi dicetak ke layar)

Yang dilakukan:
  1. id diganti ID netral (LK-0001, ...) dan baris diacak, jadi tidak memuat label dan tidak berurutan per kelas.
  2. nama_perusahaan dan posisi DIISI ULANG dengan memindai teks deskripsi, memakai aturan yang SAMA
     untuk kelas asli dan palsu (simetris). Kalau tidak ketemu, dikosongkan (bukan ditebak).
  3. Kolom yang bocor (sumber, link, OCR mentah, dikoreksi_ai, teks_gabungan, ...) tidak ikut ke file bersih
     (file asli dataset_lokerlens.csv tetap ada sebagai arsip).

Pemakaian (dari folder mana saja):
    python ml/scraping/rapikan_dataset.py
    python ml/scraping/rapikan_dataset.py --input data.csv --outdir hasil --seed 7
"""
import argparse
import os
import re
from pathlib import Path

import numpy as np
import pandas as pd

# ----------------------------------------------------------------------------------------------
# KAMUS (silakan ditambah/diedit sesuai data kalian)
# ----------------------------------------------------------------------------------------------
BADAN_USAHA = r"(?:PT|CV|UD|PD|Perum|Perumda|Yayasan|Koperasi|Firma)"

# kata yang menandai nama perusahaan sudah selesai
STOP_NAMA = set("""
lowongan loker lowker kerja pekerjaan buka membuka dibuka dibukanya recruitment recruitement rekrutmen
rekruitmen penerimaan hiring karir karier career kualifikasi posisi persyaratan syarat pendidikan usia umur
gaji periode tahun tahap dengan yang untuk dan di ke dari adalah telah sedang menerima mencari membutuhkan
dibutuhkan tersedia kesempatan bergabung bergabunglah resmi segera info informasi pendaftaran daftar
registrasi open job vacancy we are ini itu pria wanita laki perempuan fresh graduate lulusan minimal
jasmani tes seleksi jadwal lokasi penempatan alamat jl cabang karyawan pegawai staff staf mencari
membuka kembali kini sebagai bagian lowongan! hadir menghadirkan lamaran anda kamu terbaru ke via lengkap beserta ijazah foto ktp menyertakan fc fotokopi scan pdf
""".split())

# nama dikenal (perusahaan/instansi/merek yang sering muncul di lowongan dan penipuan lowongan)
LEKSIKON_NAMA = [
    "Pertamina", "PLN", "Telkomsel", "Telkom Indonesia", "Indosat", "XL Axiata", "Gojek", "Grab", "Shopee",
    "Tokopedia", "Lazada", "Bukalapak", "Traveloka", "J&T Express", "J&T", "JNE", "SiCepat", "Anteraja",
    "Indomaret", "Alfamart", "KFC", "McDonald's", "Hokben", "Kopi Kenangan", "Starbucks", "FamilyMart",
    "Circle K", "Astra", "Honda", "Toyota", "Freeport", "PT Freeport Indonesia", "Bea Cukai", "Kemenkeu",
    "Kejaksaan", "BNN", "Polri", "BKN", "OJK", "Bank Indonesia",
    "Jasa Raharja", "Jasa Marga", "Jasamarga", "Pelindo", "Peruri", "Pos Indonesia", "Garuda Indonesia",
    "Kereta Api Indonesia", "Bulog", "Pupuk Indonesia", "Semen Indonesia", "Wijaya Karya", "Waskita",
    "Adhi Karya", "Danantara", "Ombudsman", "Puskesmas", "Pegadaian", "FIFGROUP", "Adira", "Danone",
    "Unilever", "Indofood", "Nestle", "Cleo", "Mayora", "Gudang Garam", "Djarum", "Sampoerna",
    "Timah", "Antam", "Inalum", "Badak LNG", "Kaltim Prima Coal", "Bumi Resources", "Adaro", "Denso",
    "Yamaha", "Suzuki", "Mitsubishi", "Hyundai", "Samsung", "Sinar Mas", "Lippo", "Ciputra",
    "Bank Mandiri", "Bank Permata", "Bank Sinarmas", "Bank Danamon",
    "CIMB Niaga", "BSI", "Bank Syariah Indonesia", "Bank Jatim", "Bank DKI", "Bank Mega", "Panin Bank",
]
BANK_RE = re.compile(
    r"\bbank\s+(?:mandiri|bca|bri|bni|btn|permata|sinarmas|danamon|cimb(?:\s+niaga)?|bsi|mega|panin|ocbc|"
    r"btpn|jago|syariah\s+indonesia|central\s+asia|rakyat\s+indonesia|negara\s+indonesia|dki|jatim|jabar)\b",
    re.I)

# daftar jabatan umum (untuk mencari posisi bila tidak ada penanda eksplisit)
JABATAN = [
    "customer service", "cleaning service", "office boy", "office girl", "front office", "back office",
    "account officer", "relationship manager", "marketing executive", "sales executive", "warehouse staff",
    "staff administrasi", "staff kantor", "admin gudang", "admin cabang", "operator produksi", "operator alat berat",
    "tax supervisor", "field officer", "hub lead", "outlet crew", "store crew",
    "admin", "kasir", "kurir", "driver", "sopir", "supir", "operator", "helper",
    "satpam", "security", "barista", "teller", "marketing", "sales", "supervisor", "manager", "manajer",
    "engineer", "akuntan", "accounting", "perawat", "guru", "pengajar", "koki", "cook", "waiters", "waiter",
    "pelayan", "cleaner", "mekanik", "teknisi", "programmer", "designer", "sekretaris", "receptionist",
    "resepsionis", "ob", "tukang", "montir", "apoteker", "dokter", "bidan", "analis", "analyst", "auditor",
    "koordinator", "pramuniaga", "intern",
    "pelaksana proyek", "inspector", "surveyor", "pengawas", "content creator", "admin online", "drafter",
]
# kata yang menandai daftar posisi / nama posisi sudah selesai
STOP_POSISI = set("""
kualifikasi persyaratan syarat syarat-syarat pria wanita laki laki-laki perempuan cewek cowok usia umur gaji
pendidikan minimal maksimal pengalaman dengan yang untuk di dan ya tugas tanggung lokasi penempatan
ditempatkan alamat jl link daftar pendaftaran lamar melamar cara kirim fasilitas benefit jam tunjangan
sma smk smp sd d3 d4 s1 lulusan fresh graduate berpengalaman info resmi segera dibuka dibutuhkan
lowongan loker kerja pt cv tbk periode tahun jasmani sehat bersedia mampu memiliki akan ini juga mendukung bertanggung jawab bertugas membantu
""".split())

TRIGGER_POSISI = re.compile(
    r"(?:posisi(?:\s+(?:dibuka|tersedia|yang\s+(?:dibutuhkan|dibuka|tersedia)))?|"
    r"(?:untuk|sebagai)\s+(?:posisi\s+)?|formasi|jabatan|dibutuhkan(?:\s+segera)?|dibutuhkan|"
    r"lowongan\s+(?:kerja\s+)?untuk)\s*[:\-–]?\s+",
    re.I)

URL_RE = re.compile(r"https?://\S+|www\.\S+")


# ----------------------------------------------------------------------------------------------
# FUNGSI BANTU
# ----------------------------------------------------------------------------------------------
def _bersih_spasi(s: str) -> str:
    return re.sub(r"\s+", " ", str(s)).strip()


def _strip_tanda(tok: str) -> str:
    return tok.strip(" ,;:.!?\"'“”‘’()[]{}-–•*·")


def _title_cerdas(nama: str) -> str:
    """Kalau nama kebanyakan huruf besar semua, rapikan jadi Title Case (singkatan pendek tetap kapital)."""
    huruf = [c for c in nama if c.isalpha()]
    if huruf and sum(c.isupper() for c in huruf) / len(huruf) >= 0.7:
        out = []
        for t in nama.split():
            low = t.lower()
            if low in {"tbk", "(persero)"}:
                out.append("Tbk" if low == "tbk" else "(Persero)")
            elif len(_strip_tanda(t)) <= 3 and t.isalpha():
                out.append(t.upper())
            else:
                out.append(t.capitalize())
        return " ".join(out)
    return nama


def cari_nama_perusahaan(teks: str):
    """Kembalikan (nama, metode) atau (None, None). Memindai TEKS saja."""
    t = _bersih_spasi(URL_RE.sub(" ", teks))
    kandidat = []   # (posisi_awal, nama, metode)

    # 1) Badan usaha: "PT Xxx Yyy Tbk", "CV Maju Jaya", ...
    for m in re.finditer(rf"\b({BADAN_USAHA})\.?\s+", t):
        sebelum = t[max(0, m.start() - 25):m.start()].lower()
        if re.search(r"kirim|lampir|sertak|attach|email|upload|unggah|melampirkan|berupa|buat|siapkan|menyertakan|fotokopi|bawa", sebelum):
            continue   # "kirim CV ke ..." bukan nama perusahaan
        awal = m.end()
        toks = t[awal:].split(" ")
        nama_toks = []
        for tok in toks[:7]:
            bersih = _strip_tanda(tok)
            low = bersih.lower()
            if not bersih or low in STOP_NAMA or bersih[0].isdigit() or tok.startswith("("):
                if low == "(persero)" or tok.lower().startswith("(persero"):
                    nama_toks.append("(Persero)")
                break
            nama_toks.append(bersih)
            if low == "tbk":
                break
            if tok != bersih and tok[-1] in ",;:.!?":   # tanda baca di ujung = nama selesai
                break
        if nama_toks and any(len(x) > 1 for x in nama_toks):
            badan = m.group(1).upper()
            nama = _title_cerdas(f"{badan} " + " ".join(nama_toks))
            kandidat.append((m.start(), nama, "badan_usaha"))
            break

    # 2) Bank dikenal
    kepala = t[:150]
    mb = BANK_RE.search(kepala)
    if mb:
        kandidat.append((mb.start(), _title_cerdas(mb.group(0)), "bank"))

    # 3) Leksikon nama dikenal
    for nm in sorted(LEKSIKON_NAMA, key=len, reverse=True):
        pat = r"(?<![A-Za-z0-9])" + re.escape(nm) + r"(?![A-Za-z0-9])"
        ml = re.search(pat, kepala, flags=re.I)
        if ml:
            kandidat.append((ml.start(), nm, "leksikon"))

    if not kandidat:
        return None, None
    # prioritas: badan usaha > bank > leksikon; bila beda, ambil yang paling awal muncul di antara yang sama prioritas
    prio = {"badan_usaha": 0, "bank": 1, "leksikon": 2}
    kandidat.sort(key=lambda k: (prio[k[2]], k[0]))
    _, nama, metode = kandidat[0]
    return nama, metode


def _ambil_frasa_posisi(t: str, awal: int, maks_token=5):
    toks = t[awal:].split(" ")
    while toks and _strip_tanda(toks[0]).lower() in {"posisi", "sebagai", "untuk", "yang", "tersedia", "dibuka"}:
        toks = toks[1:]
    out = []
    for tok in toks[:maks_token + 3]:
        bersih = _strip_tanda(tok)
        low = bersih.lower()
        if not bersih:
            if out: break
            continue
        if low in STOP_POSISI or bersih[0].isdigit():
            break
        out.append(bersih)
        if tok != bersih and tok[-1] in ",;:.!?" or len(out) >= maks_token:
            break
    return " ".join(out)


def cari_posisi(teks: str):
    """Kembalikan (posisi, metode) atau (None, None). Memindai TEKS saja."""
    t = _bersih_spasi(URL_RE.sub(" ", teks))

    # 1) Penanda eksplisit ("Posisi: Kurir ...", "dibutuhkan Admin ...", "untuk posisi Perawat ...")
    for m in TRIGGER_POSISI.finditer(t):
        frasa = _ambil_frasa_posisi(t, m.end())
        if frasa and len(frasa) >= 3:
            # hanya terima kalau mengandung jabatan dikenal (hindari "dibutuhkan segera karyawan ...")
            if any(re.search(rf"(?<![A-Za-z]){re.escape(j)}(?![A-Za-z])", frasa, re.I) for j in JABATAN):
                return _title_cerdas(frasa), "penanda"

    # 2) Jabatan dikenal di 200 karakter pertama (judul/pembuka iklan)
    awal = t[:200]
    ketemu = []
    for j in sorted(JABATAN, key=len, reverse=True):
        mj = re.search(rf"(?<![A-Za-z]){re.escape(j)}(?![A-Za-z])", awal, re.I)
        if mj and not any(mj.start() >= s and mj.end() <= e for s, e, _ in ketemu):
            ketemu.append((mj.start(), mj.end(), j))
    if ketemu:
        ketemu.sort()
        uniq = []
        for _, _, j in ketemu:
            if j.lower() not in [u.lower() for u in uniq]:
                uniq.append(j)
        return " / ".join(x.title() for x in uniq[:2]), "jabatan_dikenal"
    return None, None


def cocok(a, b) -> bool:
    """Perbandingan longgar dua string (untuk menguji ekstraksi terhadap kolom asli)."""
    norm = lambda s: re.sub(r"[^a-z0-9 ]", " ", str(s).lower())
    drop = {"pt", "cv", "ud", "pd", "tbk", "persero", "the"}
    ta = {w for w in norm(a).split() if w not in drop}
    tb = {w for w in norm(b).split() if w not in drop}
    if not ta or not tb:
        return False
    return len(ta & tb) / min(len(ta), len(tb)) >= 0.5


# ----------------------------------------------------------------------------------------------
# PROSES UTAMA
# ----------------------------------------------------------------------------------------------
def main():
    proc = Path(__file__).resolve().parents[1] / "data" / "processed"
    ap = argparse.ArgumentParser()
    ap.add_argument("--input", default=str(proc / "dataset_lokerlens.csv"))
    ap.add_argument("--outdir", default=str(proc))
    ap.add_argument("--seed", type=int, default=42)
    args = ap.parse_args()

    df = pd.read_csv(args.input)
    wajib = {"label", "deskripsi_lowongan"}
    assert wajib <= set(df.columns), f"Kolom wajib tidak ada: {wajib - set(df.columns)}"
    os.makedirs(args.outdir, exist_ok=True)

    df["deskripsi"] = df["deskripsi_lowongan"].fillna("").astype(str).map(_bersih_spasi)
    df = df[df["deskripsi"].str.len() > 0].copy()

    # ---- ekstraksi (aturan SAMA untuk kedua kelas) ----
    hasil_nama = df["deskripsi"].map(cari_nama_perusahaan)
    hasil_pos = df["deskripsi"].map(cari_posisi)
    df["nama_perusahaan_baru"] = [h[0] for h in hasil_nama]
    df["metode_nama"] = [h[1] for h in hasil_nama]
    df["posisi_baru"] = [h[0] for h in hasil_pos]
    df["metode_posisi"] = [h[1] for h in hasil_pos]

    # ---- id netral + acak urutan ----
    df = df.sample(frac=1, random_state=args.seed).reset_index(drop=True)
    df["id_baru"] = [f"LK-{i + 1:04d}" for i in range(len(df))]

    bersih = df[["id_baru", "label", "nama_perusahaan_baru", "posisi_baru", "deskripsi"]].rename(
        columns={"id_baru": "id", "nama_perusahaan_baru": "nama_perusahaan", "posisi_baru": "posisi"})
    p_bersih = os.path.join(args.outdir, "dataset_lokerlens_bersih.csv")
    bersih.to_csv(p_bersih, index=False, encoding="utf-8-sig")

    # ---- laporan ----
    L = []
    kls = df["label"].map({0: "asli", 1: "palsu"})
    L.append(f"Baris diproses : {len(df)}  (asli={int((df.label == 0).sum())}, palsu={int((df.label == 1).sum())})")
    L.append("\nPERSEN TERISI per kelas (aturan ekstraksi sama untuk kedua kelas):")
    isi = pd.DataFrame({
        "nama_perusahaan": df["nama_perusahaan_baru"].notna().groupby(kls).mean() * 100,
        "posisi": df["posisi_baru"].notna().groupby(kls).mean() * 100}).round(1)
    L.append(isi.to_string())
    gap_n = abs(isi.loc["asli", "nama_perusahaan"] - isi.loc["palsu", "nama_perusahaan"])
    gap_p = abs(isi.loc["asli", "posisi"] - isi.loc["palsu", "posisi"])
    if max(gap_n, gap_p) > 15:
        L.append("\n[!] Persen terisi berbeda cukup jauh antar kelas. JANGAN pakai 'terisi/kosong' atau isi kolom ini sebagai fitur "
                 "model (bisa membocorkan label). Pakai hanya untuk tampilan/aturan rule-based.")

    if {"nama_perusahaan", "posisi_lowongan"} <= set(df.columns):
        a = df[df.label == 0]
        L.append("\nAKURASI EKSTRAKSI diuji pada data ASLI (punya kolom asli sebagai kunci jawaban):")
        for nm, kolom_baru, kolom_asli in [("nama_perusahaan", "nama_perusahaan_baru", "nama_perusahaan"),
                                           ("posisi", "posisi_baru", "posisi_lowongan")]:
            terisi = a[a[kolom_baru].notna()]
            benar = sum(cocok(x, y) for x, y in zip(terisi[kolom_baru], terisi[kolom_asli]))
            L.append(f"  {nm:16s}: terisi {len(terisi)}/{len(a)} ({len(terisi) / len(a) * 100:.0f}%), "
                     f"cocok dgn kolom asli {benar}/{max(1, len(terisi))} ({benar / max(1, len(terisi)) * 100:.0f}%)")
        L.append("  (cocok = kata-katanya beririsan >= 50%; hanya ukuran kasar)")

    L.append("\nMETODE yang dipakai:")
    L.append(df.groupby(kls)["metode_nama"].value_counts(dropna=False).to_string())
    L.append(df.groupby(kls)["metode_posisi"].value_counts(dropna=False).to_string())

    L.append("\nCONTOH HASIL (periksa manual, 12 baris acak):")
    ctoh = df.sample(12, random_state=1)
    for _, r in ctoh.iterrows():
        L.append(f"  [{'palsu' if r.label == 1 else 'asli '}] nama={r.nama_perusahaan_baru!s:32.32} | posisi={r.posisi_baru!s:28.28} | {r.deskripsi[:70]}")
    laporan = "\n".join(L)
    print(laporan)
    print(f"\nTersimpan: {p_bersih}")


if __name__ == "__main__":
    main()
