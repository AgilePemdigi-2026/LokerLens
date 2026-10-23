"""
Scraper lowongan kerja palsu dari TurnBackHoax (kategori Lowongan).

Alur:
1. Kumpulkan link artikel dari semua halaman daftar kategori Lowongan.
2. Dari tiap artikel ambil judul, teks bagian "Narasi", dan gambar isi artikel.
3. Jalankan OCR (EasyOCR) pada gambar isi artikel.
4. Bersihkan teks dari penanda situs pemeriksa fakta, lalu simpan ke CSV.

Struktur HTML yang dipakai (dicek Oktober 2026):
- Link artikel di halaman daftar : div.tab-panel--content div.news-card-h-alt a[href*="/articles/"]
- Judul artikel                  : article h1
- Bagian Narasi                  : section.article-origin div.quoted
- Gambar isi artikel             : section.article--main > figure img  (gambar utama berisi
                                   tangkapan layar lowongan) dan img di dalam section.article-origin
- Diabaikan                      : logo (turnbackhoax.id/images/...), ikon footer/media sosial,
                                   thumbnail "Artikel terbaru" (section.article--sidebar)

Pemakaian (dari folder mana pun):
    python ml/scraping/scrape_turnbackhoax.py            # semua halaman
    python ml/scraping/scrape_turnbackhoax.py --pages 1  # uji coba 1 halaman
"""

import argparse
import csv
import gc
import json
import re
import sys
import time
import unicodedata
from datetime import date
from pathlib import Path
from urllib.parse import urljoin, urlparse

import numpy as np
import requests
from bs4 import BeautifulSoup
from PIL import Image

# ─── Lokasi file (relatif terhadap lokasi script) ─────────────────────────────
ML_DIR = Path(__file__).resolve().parent.parent
RAW_DIR = ML_DIR / "data" / "raw"
IMAGE_DIR = RAW_DIR / "images" / "turnbackhoax"
CSV_PATH = RAW_DIR / "lowongan_palsu_turnbackhoax.csv"
PROGRESS_PATH = RAW_DIR / "turnbackhoax_progress.jsonl"
ERROR_LOG_PATH = RAW_DIR / "turnbackhoax_errors.log"

# ─── Konfigurasi scraping ─────────────────────────────────────────────────────
BASE_URL = "https://turnbackhoax.id"
LIST_URL = BASE_URL + "/articles?category=lowongan&page={page}"
USER_AGENT = (
    "LokerLensDatasetBot/1.0 (proyek akademik deteksi lowongan kerja palsu; "
    "+https://github.com/AgilePemdigi-2026)"
)
REQUEST_DELAY = 2  # detik antar request
MAX_RETRIES = 3
SAVE_EVERY = 10  # tulis ulang CSV setiap N artikel baru

CSV_COLUMNS = [
    "id",
    "label",
    "nama_perusahaan",
    "posisi_lowongan",
    "deskripsi_lowongan",
    "informasi_tambahan",
    "teks_gabungan",
    "sumber_lowongan",
    "link_lowongan",
    "tanggal_ambil",
    "narasi_asli",
    "ocr_mentah",
    "perlu_cek_manual",
]

ARTICLE_LINK_RE = re.compile(r"/articles/\d+-")

# Penanda dari situs pemeriksa fakta (cap/label di gambar dan judul).
# Kata seperti "penipuan", "salah", atau "hoaks" di dalam kalimat biasa tidak dihapus;
# yang dihapus hanya label, baris berisi cap saja, dan lencana Mafindo.
FACTCHECK_LABEL_RE = re.compile(r"\[\s*[A-Za-z ]+\s*\]")  # mis. [PENIPUAN], [SALAH]
# Cap berbahasa Inggris dan lencana Mafindo tidak pernah muncul di isi lowongan,
# jadi aman dihapus di mana pun posisinya.
STAMP_TEXT = (
    r"(?:fabricated|impost[eo]r|misleading|manipulated)\s+content"
    r"|false\s+(?:context|connection)|satire(?:\s+or\s+parody)?"
    r"|masyarakat\s+anti\s+fitnah\s+indonesia|turn\s*back\s*hoa[xk]s?"
)
FACTCHECK_STAMP_RE = re.compile(r"\b(?:" + STAMP_TEXT + r")\b", re.IGNORECASE)
# Lencana "TURN BACK HOAX / MAFINDO" yang salah dibaca OCR, mis. "IURN",
# "#8n9 Mafinoo", "#g88 MaFin6o".
FACTCHECK_BADGE_RE = re.compile(r"\b[IT]URN\b|(?:#\w{2,4}\s+)?\b(?i:maf[i1l]n\w{0,2})\b")
# Baris yang isinya hanya cap, mis. "PENIPUAN", "SALAH", "HOAKS".
FACTCHECK_LINE_RE = re.compile(
    r"^\W*(?:(?:penipuan|salah|hoaks|hoax|disinformasi|(?:cek|periksa)\s+fakta|" + STAMP_TEXT + r")\W*)+$",
    re.IGNORECASE,
)
# Kalimat Narasi yang ditulis pemeriksa fakta (bukan isi lowongan).
FACTCHECK_SENTENCE_RE = re.compile(
    r"\bberedar|\bakun\s+(?:tiktok|instagram|facebook|x|twitter|youtube|threads)\b|\bunggahan"
    r"|\btanda\s+suka|\bdisukai|\bdibagikan|\bkomentar|\bpemeriksa\s+fakta|\barsip\b|\bnarasi\s+lengkap"
    r"|\b(?:hingga|per|sampai)\s+(?:senin|selasa|rabu|kamis|jum'?at|sabtu|minggu)\b",
    re.IGNORECASE,
)
MIN_DESKRIPSI = 50
# Link yang disamarkan pemeriksa fakta, mis. "situs[dot]com" -> "situs.com".
DEFANG_DOT_RE = re.compile(r"\s*\[\s*(?:dot|\.)\s*\]\s*", re.IGNORECASE)

# Kata pembuka judul yang bukan bagian dari nama perusahaan.
TITLE_PREFIX_WORDS = {
    "tautan", "link", "foto", "gambar", "email", "surel", "pesan", "akun", "situs",
    "website", "laman", "video", "unggahan", "poster", "pamflet", "pengumuman",
    "informasi", "info", "pendaftaran", "lowongan", "kerja", "loker", "rekrutmen",
    "rekrutasi", "penerimaan", "magang", "pegawai", "karyawan", "calon", "di",
    "untuk", "dari", "open", "recruitment", "seleksi", "tes", "undangan",
}

URL_RE = re.compile(
    r"(?:https?://|www\.)[^\s,;\"'<>()]+"
    r"|\b(?:bit\.ly|s\.id|tinyurl\.com|linktr\.ee|forms\.gle|wa\.me|t\.me|shorturl\.at|cutt\.ly)/[^\s,;\"'<>()]+",
    re.IGNORECASE,
)
EMAIL_RE = re.compile(r"[\w.+-]+@[\w-]+(?:\.[\w-]+)+")
PHONE_RE = re.compile(r"(?:\+?62[\s-]?|\b0)8[\d\s.-]{7,14}\d")


# ─── Utilitas ─────────────────────────────────────────────────────────────────
class Fetcher:
    """Session HTTP dengan jeda tetap antar request dan retry sederhana."""

    def __init__(self):
        self.session = requests.Session()
        self.session.headers["User-Agent"] = USER_AGENT
        self._last_request = 0.0

    def get(self, url):
        last_error = None
        for attempt in range(1, MAX_RETRIES + 1):
            wait = REQUEST_DELAY - (time.monotonic() - self._last_request)
            if wait > 0:
                time.sleep(wait)
            try:
                response = self.session.get(url, timeout=30)
                self._last_request = time.monotonic()
                response.raise_for_status()
                return response
            except requests.RequestException as error:
                self._last_request = time.monotonic()
                last_error = error
                print(f"    ! percobaan {attempt}/{MAX_RETRIES} gagal: {error}")
        raise last_error


def log_error(url, error):
    with ERROR_LOG_PATH.open("a", encoding="utf-8") as file:
        file.write(f"{date.today().isoformat()}\t{url}\t{type(error).__name__}: {error}\n")


def normalize_text(text):
    """Seragamkan unicode, tanda kutip, dan link yang disamarkan."""
    text = unicodedata.normalize("NFKC", text or "")
    text = text.translate(str.maketrans({"“": '"', "”": '"', "‘": "'", "’": "'", "–": "-", "—": "-", "•": "-"}))
    return DEFANG_DOT_RE.sub(".", text)


def clean_text(text):
    """Hapus cap/label pemeriksa fakta, karakter aneh, dan spasi ganda."""
    text = normalize_text(text)
    lines = [line for line in text.splitlines() if not FACTCHECK_LINE_RE.match(line)]
    text = "\n".join(lines)
    for pattern in (FACTCHECK_LABEL_RE, FACTCHECK_STAMP_RE, FACTCHECK_BADGE_RE):
        text = pattern.sub(" ", text)
    # Sisakan huruf, angka, dan tanda baca yang umum di lowongan.
    text = re.sub(r"[^\w\s.,:;!?@#&%+=/()\"'\-]", " ", text)
    text = re.sub(r"_{2,}", " ", text)
    # Tanda baca yang tertinggal sendirian (sisa label yang dihapus).
    text = re.sub(r"(?<!\S)[^\w\s@#]+(?!\S)", " ", text)
    return re.sub(r"\s+", " ", text).strip()


def strip_factcheck_sentences(narasi):
    """Buang kalimat Narasi yang ditulis pemeriksa fakta, mis. 'Beredar foto dari akun TikTok ...'."""
    sentences = re.split(r"(?<=[.!?])\s+|\n", normalize_text(narasi))
    return " ".join(s for s in sentences if s.strip() and not FACTCHECK_SENTENCE_RE.search(s))


def company_from_title(title):
    """'[PENIPUAN] Tautan Lowongan Kerja PLN' -> 'PLN'."""
    title = re.sub(r"^\s*\[[^\]]*\]\s*", "", title or "")
    words = title.split()
    while words and words[0].lower().strip(":,-") in TITLE_PREFIX_WORDS:
        words.pop(0)
    return " ".join(words).strip(" -:,")


def extract_contacts(text):
    """Ambil link, email, dan nomor telepon/WA yang muncul di teks."""
    text = normalize_text(text)
    found = []
    for match in URL_RE.findall(text):
        found.append(match.rstrip(".,:;!?"))
    found += EMAIL_RE.findall(text)
    for match in PHONE_RE.findall(text):
        found.append(re.sub(r"[\s.-]", "", match))
    seen, result = set(), []
    for item in found:
        if item.lower() not in seen:
            seen.add(item.lower())
            result.append(item)
    return "; ".join(result)


def element_text(element):
    """Teks per paragraf, dipisah baris baru."""
    parts = []
    for block in element.find_all(["p", "li", "blockquote"]) or [element]:
        text = block.get_text(" ", strip=True)
        if text:
            parts.append(text)
    return "\n".join(parts)


# ─── Halaman daftar ───────────────────────────────────────────────────────────
def parse_list_page(html):
    """Hasil: (daftar link artikel, jumlah halaman menurut situs atau None)."""
    soup = BeautifulSoup(html, "html.parser")
    anchors = soup.select('div.tab-panel--content div.news-card-h-alt a[href*="/articles/"]')
    if not anchors:  # cadangan jika kelas CSS berubah
        anchors = soup.find_all("a", href=ARTICLE_LINK_RE)
    page_links = []
    for anchor in anchors:
        href = urljoin(BASE_URL, anchor["href"]).split("#")[0]
        if ARTICLE_LINK_RE.search(href) and href not in page_links:
            page_links.append(href)
    navigation = soup.select_one("[data-total-pages]")
    total_pages = int(navigation["data-total-pages"]) if navigation else None
    return page_links, total_pages


def collect_article_links(fetcher, max_pages=None):
    links, seen = [], set()
    total_pages = None
    page = 1
    while max_pages is None or page <= max_pages:
        url = LIST_URL.format(page=page)
        page_links = []
        # Situs kadang mengirim halaman kosong sesaat; coba ulang selama nomor
        # halaman masih di bawah jumlah halaman yang disebut situs.
        for attempt in range(1, MAX_RETRIES + 1):
            try:
                page_links, reported = parse_list_page(fetcher.get(url).text)
            except requests.RequestException as error:
                print(f"Halaman {page}: gagal diambil ({error})")
                log_error(url, error)
                page_links, reported = [], None
            total_pages = reported or total_pages
            if page_links or (total_pages is not None and page > total_pages):
                break
            if attempt < MAX_RETRIES:
                print(f"Halaman {page}: kosong, coba lagi ({attempt}/{MAX_RETRIES - 1})...")
                time.sleep(REQUEST_DELAY * 5)

        new_links = [link for link in page_links if link not in seen]
        print(f"Halaman {page}: {len(page_links)} artikel ditemukan ({len(new_links)} baru)")
        if not page_links or not new_links:
            print("Tidak ada artikel baru, pengumpulan link selesai.")
            break
        links += new_links
        seen.update(new_links)
        page += 1
    pages_read = page - 1 if links else 0
    print(f"Total: {len(links)} artikel dari {pages_read} halaman\n")
    return links, pages_read


# ─── Halaman artikel ──────────────────────────────────────────────────────────
def parse_article(html):
    soup = BeautifulSoup(html, "html.parser")

    h1 = soup.select_one("article h1") or soup.find("h1")
    title = h1.get_text(" ", strip=True) if h1 else ""

    narasi = ""
    origin = soup.select_one("section.article-origin")
    if origin:
        body = origin.select_one("div.quoted") or origin
        narasi = element_text(body)
        if body is origin:
            narasi = re.sub(r"^\s*Narasi\s*", "", narasi)
    else:
        # Cadangan: ambil elemen setelah judul "Narasi" sampai "Penjelasan".
        heading = soup.find(lambda tag: tag.name in ("strong", "h2", "h3", "b") and tag.get_text(strip=True).lower() == "narasi")
        if heading:
            parts = []
            for sibling in heading.find_all_next():
                if sibling.get_text(strip=True).lower().startswith("penjelasan"):
                    break
                if sibling.name == "p":
                    parts.append(sibling.get_text(" ", strip=True))
            narasi = "\n".join(p for p in parts if p)

    image_urls = []
    main = soup.select_one("section.article--main")
    candidates = []
    if main:
        candidates += main.select(":scope > figure img")
    if origin:
        candidates += origin.find_all("img")
    for img in candidates:
        src = img.get("data-src") or img.get("src")
        if not src:
            continue
        src = urljoin(BASE_URL, src)
        path = urlparse(src).path
        # Lewati logo dan ikon situs (selalu di /images/ milik turnbackhoax.id).
        if path.startswith("/images/") or path.lower().endswith(".svg"):
            continue
        if src not in image_urls:
            image_urls.append(src)

    return title, narasi, image_urls


# ─── OCR ──────────────────────────────────────────────────────────────────────
OCR_SCALE = 2
MAX_OCR_WIDTH = 2400  # batas lebar setelah diperbesar, supaya RAM tidak habis

# Reader EasyOCR dibuat sekali dan dipakai untuk semua gambar.
_reader = None


def get_reader():
    global _reader
    if _reader is None:
        import easyocr

        print("Memuat model EasyOCR (pertama kali bisa lama)...")
        _reader = easyocr.Reader(["id", "en"], gpu=False)
    return _reader


def preprocess_image(image_path):
    """Perbesar 2x (lebar maksimal MAX_OCR_WIDTH) dan ubah ke grayscale supaya teks kecil lebih terbaca OCR."""
    with Image.open(image_path) as image, image.convert("L") as gray:
        scale = min(OCR_SCALE, MAX_OCR_WIDTH / gray.width)
        size = (round(gray.width * scale), round(gray.height * scale))
        with gray.resize(size, Image.Resampling.LANCZOS) as resized:
            return np.array(resized)


def ocr_images(fetcher, image_urls, row_id):
    """Unduh dan OCR semua gambar. Hasil: (teks OCR gabungan, jumlah gambar berhasil)."""
    IMAGE_DIR.mkdir(parents=True, exist_ok=True)
    texts, success = [], 0
    for index, url in enumerate(image_urls, start=1):
        suffix = Path(urlparse(url).path).suffix.lower() or ".jpg"
        image_path = IMAGE_DIR / f"{row_id}_{index}{suffix}"
        try:
            if not image_path.exists():
                image_path.write_bytes(fetcher.get(url).content)
            pixels = preprocess_image(image_path)
            lines = get_reader().readtext(pixels, detail=0, paragraph=True)
            text = "\n".join(line.strip() for line in lines if line.strip())
            if text:
                texts.append(text)
                success += 1
        except Exception as error:  # satu gambar gagal tidak menggagalkan artikel
            print(f"    ! gambar {index} gagal: {error}")
            log_error(url, error)
        finally:
            # Bebaskan memori gambar sebelum lanjut ke gambar berikutnya.
            pixels = None
            gc.collect()
    return "\n\n".join(texts), success


# ─── Penyusunan baris ─────────────────────────────────────────────────────────
def build_row(row_id, url, title, narasi, ocr_raw):
    return derive_fields({
        "id": row_id,
        "label": 1,
        "nama_perusahaan": company_from_title(title),
        "posisi_lowongan": "",
        "sumber_lowongan": "turnbackhoax",
        "link_lowongan": url,
        "tanggal_ambil": date.today().isoformat(),
        "narasi_asli": narasi,
        "ocr_mentah": ocr_raw,
    })


def derive_fields(row):
    """Hitung kolom turunan dari narasi_asli dan ocr_mentah.

    Dipanggil ulang setiap kali CSV ditulis, supaya perubahan aturan pembersihan
    langsung berlaku tanpa perlu mengunduh dan meng-OCR ulang.
    """
    narasi, ocr_raw = row["narasi_asli"], row["ocr_mentah"]
    # Deskripsi diambil dari OCR. Narasi hanya dipakai sebagai cadangan jika OCR
    # kosong atau terlalu pendek, dan barisnya ditandai untuk dicek manual.
    deskripsi = clean_text(ocr_raw)
    pakai_narasi = len(deskripsi) < MIN_DESKRIPSI
    if pakai_narasi:
        deskripsi = clean_text(f"{ocr_raw}\n{strip_factcheck_sentences(narasi)}")
    info = extract_contacts(f"{narasi}\n{ocr_raw}")
    parts = (row["nama_perusahaan"], row["posisi_lowongan"], deskripsi, info)
    row.update({
        "deskripsi_lowongan": deskripsi,
        "informasi_tambahan": info,
        "teks_gabungan": " ".join(part for part in parts if part),
        "perlu_cek_manual": pakai_narasi or len(deskripsi) < MIN_DESKRIPSI,
    })
    return row


def load_progress():
    rows = {}
    if PROGRESS_PATH.exists():
        with PROGRESS_PATH.open(encoding="utf-8") as file:
            for line in file:
                if line.strip():
                    row = json.loads(line)
                    rows[row["link_lowongan"]] = row
    return rows


def append_progress(row):
    with PROGRESS_PATH.open("a", encoding="utf-8") as file:
        file.write(json.dumps(row, ensure_ascii=False) + "\n")


def write_csv(rows):
    """Tulis CSV setelah membuang duplikat berdasarkan deskripsi_lowongan."""
    unique, seen = [], set()
    for row in sorted(rows, key=lambda r: r["id"]):
        row = derive_fields(dict(row))
        key = row["deskripsi_lowongan"].strip().lower()
        if key in seen:
            continue
        seen.add(key)
        unique.append({col: "" if row.get(col) is None else row[col] for col in CSV_COLUMNS})
    with CSV_PATH.open("w", encoding="utf-8", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=CSV_COLUMNS)
        writer.writeheader()
        writer.writerows(unique)
    return unique


# ─── Main ─────────────────────────────────────────────────────────────────────
def main():
    parser = argparse.ArgumentParser(description="Scraper lowongan palsu TurnBackHoax (kategori Lowongan)")
    parser.add_argument("--pages", type=int, default=None, help="batasi jumlah halaman daftar (untuk uji coba)")
    args = parser.parse_args()

    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8")
    RAW_DIR.mkdir(parents=True, exist_ok=True)

    fetcher = Fetcher()
    links, pages_read = collect_article_links(fetcher, args.pages)

    done = load_progress()
    next_number = max((int(r["id"].split("-")[1]) for r in done.values()), default=0) + 1
    failed = 0
    new_since_save = 0

    for position, url in enumerate(links, start=1):
        if url in done:
            print(f"[{position}/{len(links)}] sudah diproses, dilewati: {url}")
            continue
        print(f"[{position}/{len(links)}] {url}")
        row_id = f"PALSU-{next_number:04d}"
        try:
            title, narasi, image_urls = parse_article(fetcher.get(url).text)
            print(f"    judul : {title}")
            print(f"    narasi: {len(narasi)} karakter, gambar: {len(image_urls)}")
            ocr_raw, ocr_success = ocr_images(fetcher, image_urls, row_id)
            print(f"    OCR   : {ocr_success}/{len(image_urls)} gambar, {len(ocr_raw)} karakter")
            row = build_row(row_id, url, title, narasi, ocr_raw)
        except Exception as error:
            failed += 1
            print(f"    ! artikel gagal: {error}")
            log_error(url, error)
            continue

        append_progress(row)
        done[url] = row
        next_number += 1
        new_since_save += 1
        if new_since_save >= SAVE_EVERY:
            write_csv(done.values())
            new_since_save = 0
            print(f"    (progres disimpan: {len(done)} artikel)")

    saved = write_csv(done.values())
    manual = sum(1 for row in saved if row["perlu_cek_manual"])
    print("\n─── Ringkasan ───")
    print(f"Halaman daftar dibaca       : {pages_read}")
    print(f"Artikel ditemukan           : {len(links)}")
    print(f"Artikel gagal               : {failed}")
    print(f"Baris tersimpan (unik)      : {len(saved)}")
    print(f"Baris perlu cek manual      : {manual}")
    print(f"CSV                         : {CSV_PATH}")
    if failed:
        print(f"Log error                   : {ERROR_LOG_PATH}")


if __name__ == "__main__":
    main()
