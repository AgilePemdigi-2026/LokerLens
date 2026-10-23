"""
Gabungkan data lowongan palsu (TurnBackHoax) dan asli (KarirHub) menjadi satu dataset latih.

Masukan (tidak diubah):
- ml/data/raw/lowongan_palsu_turnbackhoax.csv (label 1)
- ml/data/raw/lowongan_asli_karirhub.csv      (label 0)

Keluaran:
- ml/data/processed/dataset_lokerlens.csv

Langkah:
0. Data palsu: buang id yang bukan lowongan (DIBUANG_MANUAL) dan rapikan nama perusahaan
   tertentu (NAMA_PERUSAHAAN_MANUAL).
1. Bersihkan sisa watermark OCR TurnBackHoax/Mafindo di data palsu.
2. Ubah HTML entity menjadi karakter biasa.
3. Buang kalimat jurnalis yang tersisa di data palsu ("Pengunggah", "mengunggah", "unggahan"),
   pengantar seperti "Berikut narasi lengkapnya:" / "NARASI:", klausa "Terdapat tautan ..."
   (link-nya tetap disimpan), dan kalimat klarifikasi resmi ("Masyarakat perlu waspada ...").
3b. Data palsu yang kata rusaknya lebih dari 50% memakai narasi_asli yang kalimat
    jurnalisnya sudah dibuang (ditandai perlu_cek_manual = True).
4. Buang baris yang deskripsi_lowongan-nya kosong atau kurang dari 5 kata.
5. Buat ulang teks_gabungan = nama_perusahaan + deskripsi_lowongan + informasi_tambahan
   (posisi_lowongan tidak ikut).
6. Gabungkan, hapus duplikat teks_gabungan, lalu acak (random_state=42).
7. Simpan kolom yang dipakai saja.
8. Tampilkan ringkasan.

Kata rusak (lihat kata_rusak): kata yang mengandung karakter asing, kapitalisasi acak
(AdalaH), huruf-angka-huruf (Fo1i), lebih dari 20 huruf, tanpa vokal, atau kata huruf saja
(3+ huruf) yang tidak ada di kosakata teks ketikan manusia (deskripsi KarirHub dan
narasi_asli TurnBackHoax). Karena kosakata diambil dari deskripsi KarirHub, persentase data
asli hampir selalu rendah; angka ini terutama berguna untuk data palsu.

Catatan soal file mentah:
CSV mentah bisa berupa keluaran asli scraper (pemisah koma) atau hasil simpan ulang dari
Excel (pemisah titik koma). Pada hasil simpan ulang Excel, kolom yang berisi baris baru
(narasi_asli, ocr_mentah) pecah menjadi banyak baris "sisa". Script ini hanya mengambil
baris yang id-nya valid (PALSU-xxxx / ASLI-xxxx) dan menandai baris yang rusak
(link_lowongan hilang) dengan perlu_cek_manual = True.

Pemakaian (dari folder mana pun):
    python ml/scraping/gabung_dataset.py
"""

import html
import io
import re
import sys
from collections import Counter
from pathlib import Path

import pandas as pd

# ─── Lokasi file (relatif terhadap lokasi script) ─────────────────────────────
ML_DIR = Path(__file__).resolve().parent.parent
RAW_DIR = ML_DIR / "data" / "raw"
PROCESSED_DIR = ML_DIR / "data" / "processed"
PALSU_PATH = RAW_DIR / "lowongan_palsu_turnbackhoax.csv"
ASLI_PATH = RAW_DIR / "lowongan_asli_karirhub.csv"
OUTPUT_PATH = PROCESSED_DIR / "dataset_lokerlens.csv"

OUTPUT_COLUMNS = [
    "id", "label", "nama_perusahaan", "posisi_lowongan", "deskripsi_lowongan",
    "informasi_tambahan", "teks_gabungan", "sumber_lowongan", "link_lowongan",
    "perlu_cek_manual",
]
TEXT_COLUMNS = ["nama_perusahaan", "posisi_lowongan", "deskripsi_lowongan", "informasi_tambahan"]
MIN_KATA_DESKRIPSI = 5
RANDOM_STATE = 42

# ─── Koreksi manual data palsu ────────────────────────────────────────────────
DIBUANG_MANUAL = {"PALSU-0200", "PALSU-0288", "PALSU-0322", "PALSU-0331", "PALSU-0239"}
NAMA_PERUSAHAAN_MANUAL = {"PALSU-0231": "BNN", "PALSU-0233": "Jasa Raharja"}
BATAS_KATA_RUSAK = 50  # persen

# ─── Pola watermark OCR ───────────────────────────────────────────────────────
# Kata "turn" dan "hoax/hoaks" yang berdiri sendiri, termasuk salah baca OCR-nya
# (iurn, Jurn, urn, HOAx, HOAXII, Hoaz, Honx, Hoxà, Ihrioax, ...).
TURN_HOAX = re.compile(
    r"(?<![\w-])(?:[tij]?urn|hoa(?:x|ks)\w*|hoa[asyz]?|ho[nñ]x|hox\w?|ihrioax)(?![\w-])",
    re.IGNORECASE,
)

# Lencana "Mafindo" dan salah bacanya: afiroo, afinpo, afinco, arinoo, maftnbo, Mfirse,
# MnFinpo, Mifirge, Mafirpo, Maarinco, Matimos, Mntinos, ...
# Kata asli yang kebetulan cocok dengan pola dikecualikan.
MAFINDO_PENGECUALIAN = {"marine", "morino", "artono"}
MAFINDO = re.compile(
    r"\(?(?<![\w-])(?:m[a-z]{0,2}|a)[frt][i1lt][a-z]{1,3}[oe]s?(?![\w-])",
    re.IGNORECASE,
)

# Teks "anil fuinak / elinah / flinah" dan variasinya (ANIl (EuInak, AN CuNAH, ANIL SUIMAN, ...).
ANIL_FINAH = re.compile(
    r"(?<![\w-])(?:an[il1]{0,2}|kai|ail)\s+\(?[a-z]{0,2}[ui1l]?[nm]a[hkmn](?![\w-])"
    r"|\(?(?<![\w-])[fe][lvu]?[iu1l]?[nm]a[hk](?![\w-])",
    re.IGNORECASE,
)

# Cap pemeriksa fakta lain yang ikut terbaca OCR.
CAP_PEMERIKSA = re.compile(
    r"(?<![\w-])(?:fabricated content|impostor content|contains ai-generated"
    r"|s[cg][rf]e+[a-z]*sho+t?|screen?shot)(?![\w-])",
    re.IGNORECASE,
)

# Token pendek kode watermark: MPA8, H8A8, H8ñ9, H8xr, X2A9, M2A8, 42A9, 4PA9, XPA&, Hoñ7, #8x9, ...
KODE_WATERMARK = re.compile(r"#?[HMX4]?[2348BPO][AXÑÄ&0-9][0-9A-ZÑÄÀ&!]?", re.IGNORECASE)
# Varian tanpa angka yang muncul di samping HOAX: XBAa, XIAa, MPAa, ASAa, X?Aa, Y(AE, y?AI
KODE_TANPA_ANGKA = re.compile(r"[A-Z][A-Z?(]A[a&]|[A-Za-z][?(]A[A-Z]")
UKURAN_FOTO = re.compile(r"\d+x\d+", re.IGNORECASE)


def kode_watermark(token):
    """True jika token terlihat seperti potongan kode watermark OCR."""
    if KODE_TANPA_ANGKA.fullmatch(token):
        return True
    if len(token) < 3 or token.lower() == "p3k" or UKURAN_FOTO.fullmatch(token):
        return False
    if not KODE_WATERMARK.fullmatch(token):
        return False
    # Harus campuran: ada huruf, dan ada angka atau karakter aneh (bukan angka murni seperti usia "35").
    ada_huruf = any(ch.isalpha() for ch in token)
    return ada_huruf and any(ch.isdigit() or ch in "ñäÑÄ&!" for ch in token)


CEK_SISA = re.compile(r"(?<![\w-])(?:hoax\w*|hoaks\w*|turn)(?![\w-])", re.IGNORECASE)
KALIMAT_JURNALIS = re.compile(r"pengunggah|mengunggah|unggahan", re.IGNORECASE)
# Kalimat pemeriksa fakta di narasi_asli (lihat ml/instruksi_perbaikan.md).
KALIMAT_JURNALIS_NARASI = re.compile(
    r"pengunggah|mengunggah|unggahan|beredar|akun (?:tiktok|instagram|facebook)|tanda suka"
    r"|dibagikan|membagikan|cukilan|pemeriksa fakta|turnbackhoax|mafindo|hingga (?:senin|selasa|rabu|kamis|jumat|sabtu|minggu)",
    re.IGNORECASE,
)
# Pengantar jurnalis di awal teks: "Berikut narasi lengkapnya:", "NARASI:", "=== NARASI:", ...
PENGANTAR_JURNALIS = re.compile(
    r"^\W*(?:NARASI|Berikut(?: adalah)? narasi\w*(?: lengkapnya| yang tertera[^:]*)?)\s*:\s*",
    re.IGNORECASE,
)
# Klausa jurnalis yang menjelaskan tautan/kontak di unggahan. Link di dalamnya tetap disimpan
# karena link adalah ciri penting lowongan palsu.
KLAUSA_TAUTAN = re.compile(
    r"\b(?:terdapat tautan|adapun tautan|ada juga nomor)\b.*?(?:[.!?](?=\s|$)|$)",
    re.IGNORECASE,
)
LINK = re.compile(r"\S*(?:https?:|www\.|\[dot\]|\.(?:com|id|net|org|co|ly|me|app|dev|xyz)\b)\S*", re.IGNORECASE)
# Kalimat klarifikasi resmi (bukan isi lowongan); dibuang beserta link resminya.
KALIMAT_KLARIFIKASI = re.compile(
    r"hanya dilakukan melalui (?:website|situs|laman|kanal) resmi|\bmasyarakat (?:perlu|juga harus|diimbau|diminta)\b"
    r"|tanda centang biru",
    re.IGNORECASE,
)


def rapikan_spasi(teks):
    return re.sub(r"\s+", " ", teks).strip()


def bersihkan_watermark(teks):
    teks = CAP_PEMERIKSA.sub(" ", teks)
    teks = ANIL_FINAH.sub(" ", teks)
    teks = MAFINDO.sub(lambda m: m.group(0) if m.group(0).strip("(").lower() in MAFINDO_PENGECUALIAN else " ", teks)
    teks = TURN_HOAX.sub(" ", teks)
    teks = " ".join(tok for tok in teks.split() if not kode_watermark(tok))
    return rapikan_spasi(teks)


def buang_kalimat_jurnalis(teks):
    kalimat = re.split(r"(?<=[.!?])\s+", teks)
    return rapikan_spasi(" ".join(k for k in kalimat if not KALIMAT_JURNALIS.search(k)))


def buang_kalimat_jurnalis_narasi(teks):
    kalimat = re.split(r"(?<=[.!?])\s+|\n+", teks)
    return buang_pengantar_jurnalis(" ".join(k for k in kalimat if not KALIMAT_JURNALIS_NARASI.search(k)))


def buang_pengantar_jurnalis(teks):
    """Buang pengantar, klausa tautan, dan kalimat klarifikasi jurnalis; link di klausa tautan disimpan."""
    teks = PENGANTAR_JURNALIS.sub("", rapikan_spasi(teks))
    teks = KLAUSA_TAUTAN.sub(lambda m: " ".join(l.strip("“”\"'") for l in LINK.findall(m.group(0))), teks)
    kalimat = re.split(r"(?<=[.!?])\s+", teks)
    return rapikan_spasi(" ".join(k for k in kalimat if not KALIMAT_KLARIFIKASI.search(k)))


def jumlah_kata(teks):
    return len(teks.split())


# ─── Kata rusak ───────────────────────────────────────────────────────────────
TEPI_KATA = ".,;:!?\"'()[]{}<>*•-–—/|“”‘’…#"
KARAKTER_ASING = re.compile(r"[^0-9A-Za-z.,:/&@%'’\-_+()]")
VOKAL = set("aiueoAIUEO")


def kosakata(*daftar_teks):
    kata = set()
    for teks in daftar_teks:
        for t in teks:
            kata.update(w.lower() for w in re.findall(r"[A-Za-z]+", t))
    return kata


def kata_rusak(token, kosa):
    """True jika token adalah kata rusak, None jika token hanya tanda baca."""
    kata = token.strip(TEPI_KATA)
    if not kata:
        return None
    if KARAKTER_ASING.search(kata):
        return True
    huruf = re.sub(r"[^A-Za-z]", "", kata)
    if not huruf:
        return False
    if re.search(r"[a-z][A-Z]", kata):
        return True
    if re.search(r"[A-Za-z]\d+[A-Za-z]", kata) and not re.fullmatch(r"[A-Z]+\d+[A-Z]*", kata):
        return True
    if len(huruf) > 20:
        return True
    if len(huruf) >= 4 and not set(huruf) & VOKAL and not huruf.isupper():
        return True
    return bool(re.fullmatch(r"[A-Za-z]{3,}", kata)) and kata.lower() not in kosa


def persen_kata_rusak(teks, kosa):
    hasil = [r for r in (kata_rusak(tok, kosa) for tok in teks.split()) if r is not None]
    return 100 * sum(hasil) / len(hasil) if hasil else 0.0


# ─── Baca CSV mentah ──────────────────────────────────────────────────────────
# HTML entity (&amp; &#039; ...) yang titik komanya bisa terbaca sebagai pemisah kolom.
HTML_ENTITY = re.compile(r"&(#\d+|#x[0-9a-f]+|amp|quot|apos|lt|gt|nbsp);", re.IGNORECASE)


def baca_mentah(path, prefix_id, label):
    """Baca CSV mentah (pemisah koma atau titik koma) dan ambil baris dengan id valid."""
    isi = path.read_text(encoding="utf-8-sig")
    header = isi.split("\n", 1)[0]
    sep = ";" if header.count(";") > header.count(",") else ","
    # Langkah 2: ubah HTML entity sebelum parsing supaya kolom tidak bergeser.
    isi = HTML_ENTITY.sub(lambda m: html.unescape(m.group(0)).replace('"', "'"), isi)
    df = pd.read_csv(io.StringIO(isi), sep=sep, dtype=str, keep_default_na=False)
    df = df.loc[:, ~df.columns.str.startswith("Unnamed")]
    total_baris_fisik = len(df)
    df = df[df["id"].str.fullmatch(rf"{prefix_id}-\d+") & (df["label"] == str(label))].copy()
    df = df.drop_duplicates(subset="id")
    for col in OUTPUT_COLUMNS:
        if col not in df.columns:
            df[col] = ""

    rusak = df["link_lowongan"].str.strip() == ""
    df["perlu_cek_manual"] = df["perlu_cek_manual"].str.strip().str.upper().isin(["TRUE", "1"]) | rusak
    info = {
        "baris_fisik": total_baris_fisik,
        "record_valid": len(df),
        "record_rusak": int(rusak.sum()),
        "pemisah": sep,
    }
    return df, info


# ─── Proses ───────────────────────────────────────────────────────────────────
def ganti_dengan_narasi(df, kosa):
    """Pakai narasi_asli (tanpa kalimat jurnalis) untuk baris yang kata rusaknya di atas batas."""
    df = df.copy()
    narasi = df["narasi_asli"].fillna("").map(html.unescape).map(buang_kalimat_jurnalis_narasi)
    rusak = df["deskripsi_lowongan"].map(lambda t: persen_kata_rusak(t, kosa)) > BATAS_KATA_RUSAK
    pakai = rusak & (narasi.map(jumlah_kata) >= MIN_KATA_DESKRIPSI)
    df.loc[pakai, "deskripsi_lowongan"] = narasi[pakai]
    df.loc[pakai, "perlu_cek_manual"] = True
    return df, int(pakai.sum()), int((rusak & ~pakai).sum())


def proses(df, palsu):
    df = df.copy()
    for col in TEXT_COLUMNS:
        df[col] = df[col].fillna("").astype(str)
        if palsu:
            df[col] = df[col].map(bersihkan_watermark)
        df[col] = df[col].map(html.unescape).map(rapikan_spasi)
    if palsu:
        df["deskripsi_lowongan"] = df["deskripsi_lowongan"].map(buang_kalimat_jurnalis).map(buang_pengantar_jurnalis)
    return df


def persen_sisa_watermark(df):
    teks = df["deskripsi_lowongan"] + " " + df["informasi_tambahan"] + " " + df["nama_perusahaan"]
    return 100 * teks.map(lambda t: bool(CEK_SISA.search(t))).mean() if len(df) else 0.0


def buat_teks_gabungan(row):
    bagian = [row["nama_perusahaan"], row["deskripsi_lowongan"], row["informasi_tambahan"]]
    return rapikan_spasi(" ".join(b for b in bagian if b))


def main():
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8")

    palsu_raw, info_palsu = baca_mentah(PALSU_PATH, "PALSU", 1)
    asli_raw, info_asli = baca_mentah(ASLI_PATH, "ASLI", 0)
    kosa = kosakata(asli_raw["deskripsi_lowongan"], palsu_raw["narasi_asli"].map(html.unescape))

    # Langkah 0
    dibuang = Counter()
    manual = palsu_raw["id"].isin(DIBUANG_MANUAL)
    dibuang["palsu: dibuang manual"] += int(manual.sum())
    palsu_raw = palsu_raw[~manual].copy()
    for id_, nama in NAMA_PERUSAHAAN_MANUAL.items():
        palsu_raw.loc[palsu_raw["id"] == id_, "nama_perusahaan"] = nama
    rusak_sebelum = {
        nama: df["deskripsi_lowongan"].map(lambda t: persen_kata_rusak(t, kosa)).mean()
        for nama, df in [("palsu", palsu_raw), ("asli", asli_raw)]
    }
    if info_palsu["record_rusak"]:
        palsu_raw.loc[palsu_raw["sumber_lowongan"].str.strip() == "", "sumber_lowongan"] = "turnbackhoax"
    if info_asli["record_rusak"]:
        asli_raw.loc[asli_raw["sumber_lowongan"].str.strip() == "", "sumber_lowongan"] = "karirhub"

    # Langkah 1-3
    palsu = proses(palsu_raw, palsu=True)
    asli = proses(asli_raw, palsu=False)

    # Langkah 3b
    palsu, jumlah_narasi, rusak_tanpa_narasi = ganti_dengan_narasi(palsu, kosa)

    # Langkah 4
    hasil = []
    for nama, df in [("palsu", palsu), ("asli", asli)]:
        kosong = df["deskripsi_lowongan"].str.strip() == ""
        pendek = ~kosong & (df["deskripsi_lowongan"].map(jumlah_kata) < MIN_KATA_DESKRIPSI)
        dibuang[f"{nama}: deskripsi kosong"] += int(kosong.sum())
        dibuang[f"{nama}: deskripsi < {MIN_KATA_DESKRIPSI} kata"] += int(pendek.sum())
        hasil.append(df[~kosong & ~pendek])
    palsu, asli = hasil

    # Langkah 5
    data = pd.concat([palsu, asli], ignore_index=True)
    data["teks_gabungan"] = data.apply(buat_teks_gabungan, axis=1)

    # Langkah 6
    kunci = data["teks_gabungan"].str.lower()
    duplikat = kunci.duplicated(keep="first")
    for label, nama in [(1, "palsu"), (0, "asli")]:
        dibuang[f"{nama}: duplikat teks_gabungan"] += int((duplikat & (data["label"] == str(label))).sum())
    data = data[~duplikat]
    data = data.sample(frac=1, random_state=RANDOM_STATE).reset_index(drop=True)

    # Langkah 7
    data["label"] = data["label"].astype(int)
    data = data[OUTPUT_COLUMNS]
    PROCESSED_DIR.mkdir(parents=True, exist_ok=True)
    data.to_csv(OUTPUT_PATH, index=False, encoding="utf-8")

    # Langkah 8
    print("─── Pembacaan file mentah ───")
    for nama, info in [("palsu", info_palsu), ("asli", info_asli)]:
        print(
            f"{nama:5}: pemisah '{info['pemisah']}', {info['baris_fisik']} baris terbaca, "
            f"{info['record_valid']} record valid, {info['record_rusak']} record rusak "
            f"(link_lowongan hilang, ditandai perlu_cek_manual)"
        )

    print("\n─── Sisa kata hoax/hoaks/turn setelah dibersihkan ───")
    print(f"palsu: {persen_sisa_watermark(palsu):.2f}% baris")
    print(f"asli : {persen_sisa_watermark(asli):.2f}% baris")

    print("\n─── Rata-rata kata rusak deskripsi_lowongan per baris ───")
    for label, nama in [(1, "palsu"), (0, "asli")]:
        deskripsi = data.loc[data["label"] == label, "deskripsi_lowongan"]
        sesudah = deskripsi.map(lambda t: persen_kata_rusak(t, kosa)).mean()
        print(f"label {label} ({nama}): sebelum {rusak_sebelum[nama]:.2f}% → sesudah {sesudah:.2f}%")
    print(f"baris palsu memakai narasi_asli (kata rusak > {BATAS_KATA_RUSAK}%): {jumlah_narasi}")
    if rusak_tanpa_narasi:
        print(f"baris palsu rusak tanpa narasi_asli yang layak (tetap OCR): {rusak_tanpa_narasi}")

    print("\n─── Jumlah per label ───")
    for label, nama in [(1, "palsu"), (0, "asli")]:
        print(f"label {label} ({nama}): {(data['label'] == label).sum()}")
    print(f"total        : {len(data)}")

    print("\n─── Baris dibuang ───")
    for alasan, jumlah in dibuang.items():
        print(f"{alasan:35}: {jumlah}")
    print(f"{'total':35}: {sum(dibuang.values())}")

    print("\n─── Median jumlah kata teks_gabungan ───")
    for label, nama in [(1, "palsu"), (0, "asli")]:
        median = data.loc[data["label"] == label, "teks_gabungan"].map(jumlah_kata).median()
        print(f"label {label} ({nama}): {median:.0f}")

    print("\n─── 3 contoh baris palsu setelah dibersihkan ───")
    for _, row in data[data["label"] == 1].head(3).iterrows():
        print(f"[{row['id']}] {row['nama_perusahaan']}")
        print(f"  {row['teks_gabungan'][:600]}{'...' if len(row['teks_gabungan']) > 600 else ''}\n")

    print(f"Disimpan ke {OUTPUT_PATH}")


if __name__ == "__main__":
    main()
