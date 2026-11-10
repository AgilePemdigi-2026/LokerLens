#!/usr/bin/env python3
"""Scraper lowongan ASLI Karirhub Kemnaker untuk proyek LokerLens.

Output : ml/data/raw/lowongan_asli_karirhub.csv  (label 0 = asli)
State  : ml/data/raw/.karirhub_state/            (checkpoint, aman untuk resume)

Contoh pemakaian (jalankan dari root project):
    python ml/scraping/scrape_karirhub.py --max-items 10      # uji coba
    python ml/scraping/scrape_karirhub.py                     # penuh (default 1500)
    python ml/scraping/scrape_karirhub.py --fresh --max-items 10   # hapus state, mulai ulang

Catatan:
- Hanya membaca HTML publik (bukan /api/), jeda minimal 2 detik antar request.
- Pagination memakai ?page=N (server-side).
- Hanya mengambil: posisi, nama perusahaan, "Deskripsi Pekerjaan", "Persyaratan Khusus".
"""
from __future__ import annotations

import argparse
import csv
import json
import math
import random
import re
import shutil
import statistics
import sys
import time
from collections import Counter, deque
from datetime import date
from pathlib import Path
from urllib import robotparser

import requests
from bs4 import BeautifulSoup

# --------------------------------------------------------------------------- #
# Konfigurasi
# --------------------------------------------------------------------------- #
ROOT = Path(__file__).resolve().parents[1]  # folder ml/
RAW_DIR = ROOT / "data" / "raw"
OUT_CSV = RAW_DIR / "lowongan_asli_karirhub.csv"
STATE_DIR = RAW_DIR / ".karirhub_state"
LIST_STATE = STATE_DIR / "list_pages.jsonl"
DETAIL_STATE = STATE_DIR / "details.jsonl"

BASE = "https://karirhub.kemnaker.go.id"
LIST_URL = f"{BASE}/lowongan-dalam-negeri/lowongan"
UA = "LokerLens-AcademicBot/1.0 (proyek kuliah Universitas Airlangga; riset deteksi lowongan palsu)"
UUID_RE = re.compile(r"[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$")

COLUMNS = [
    "id", "label", "nama_perusahaan", "posisi_lowongan", "deskripsi_lowongan",
    "informasi_tambahan", "teks_gabungan", "sumber_lowongan", "link_lowongan",
    "tanggal_ambil", "narasi_asli", "ocr_mentah", "perlu_cek_manual",
]

NORMAL_CAP = 5     # maks lowongan per perusahaan (non-prioritas)
PRIO_CAP = 8       # maks lowongan per perusahaan (prioritas)
PAGES_PER_CAND = 18  # perkiraan kartu per halaman daftar
OVERSAMPLE = 2.5   # kandidat dikumpulkan lebih banyak dari target (ada filter/dedup)
SAVE_EVERY = 25    # simpan CSV parsial tiap N detail yang diambil

# Prioritas keragaman: perusahaan besar/BUMN/tambang/pabrik + posisi yang sering dipalsukan
PRIORITY_RE = re.compile(
    r"pertamina|\bpln\b|telkom|bank\s+(mandiri|bri|bni|btn|rakyat|negara)|\bbri\b|\bbni\b|pelindo|garuda|"
    r"kereta api|pupuk|semen|antam|bukit asam|inalum|\btimah\b|perhutani|pos indonesia|jasa marga|"
    r"waskita|wijaya karya|adhi karya|hutama|angkasa pura|bulog|pegadaian|krakatau|persero|\btbk\b|"
    r"astra|indofood|unilever|gudang garam|djarum|sampoerna|mayora|wings|nestle|sinar mas|"
    r"tambang|pertambangan|mining|nikel|batubara|coal|migas|minyak|mineral|resources|"
    r"pabrik|manufactur|industri|industries|tekstil|textile|garment|plastik|packaging|steel|baja|"
    r"otomotif|automotive|kimia|chemical|"
    r"staf+\s+admin|staff\s+admin|\badmin\b|administrasi|operator|customer service|gudang|warehouse|"
    r"produksi|data entry|sekretaris|\bkasir\b|\bhelper\b",
    re.I,
)

BOILERPLATE_RE = [re.compile(p, re.I) for p in (
    r"Lamar\s+Sekarang", r"Bagikan\s*:?", r"Diposting\s+.{0,40}?yang\s+lalu",
    r"Lihat\s+Profil\s+Perusahaan", r"Kementerian\s+Ketenagakerjaan(?:\s+(?:RI|Republik\s+Indonesia))?",
    r"Pusat\s+Pasar\s+Kerja", r"Karirhub", r"Kemnaker", r"Siap\s?Kerja",
    r"\b1500630\b", r"021[\s\-]?5255733", r"0811[\s\-]?871[\s\-]?2018",
    r"\S+@kemnaker\.go\.id", r"\[email\s*protected\]",
)]

ID_WORDS = set("""dan yang di ke dari untuk dengan pada dalam atau adalah akan serta dapat mampu memiliki tidak
sebagai juga oleh bagi agar minimal pendidikan pengalaman bekerja kemampuan tanggung jawab lowongan kandidat
mengelola melakukan membantu mengoperasikan komputer teliti rapi sama itu ini setiap secara maupun sehari hari
usia maksimal wanita pria laki perempuan sesuai bersedia menguasai diutamakan lulusan jujur""".split())
# Kata Inggris yang tegas saja; kata ambigu (a, an, as, on, or, be, good, team, work, dst.) sengaja
# dibuang karena sering muncul di deskripsi Indonesia yang bercampur istilah Inggris.
EN_WORDS = set("""the and with for of to in is are will you your our we their they this that
experience skills ability strong responsible requirements knowledge degree must have has
looking candidate candidates including excellent""".split())

TITLE_STOP = {"staff", "staf", "officer", "senior", "junior", "area", "dibutuhkan", "tbk", "indonesia", "group"}
ROLE_RE = re.compile(
    r"(?:posisi|dibutuhkan|dicari|membutuhkan)\s+(?:seorang\s+|karyawan\s+|sebagai\s+)?"
    r"([A-Za-z][A-Za-z/&\- ]{2,40}?)"
    r"(?=\s+(?:dibutuhkan|untuk|yang|di|dengan|akan|bertanggung|bertugas|guna|agar)\b|[,.:;])",
    re.I,
)
INFO_SENT_RE = re.compile(
    r"\b(alamat|lokasi|penempatan|ditempatkan|hubungi|kontak|whatsapp|wa|telp|telepon|email|e-mail|kirim|lamaran|cv)\b",
    re.I,
)
EMAIL_RE = re.compile(r"[\w.+-]+@[\w-]+(?:\.[\w-]+)+")
URL_RE = re.compile(r"(?:https?://|www\.)[^\s,;|]+", re.I)
PHONE_RE = re.compile(r"(?:\+62|62|0)8\d{1,3}[\s\-]?\d{3,4}[\s\-]?\d{2,5}|\b0\d{2,3}[\s\-]\d{6,8}\b")


def log(msg: str) -> None:
    print(time.strftime("[%H:%M:%S]"), msg, flush=True)


# --------------------------------------------------------------------------- #
# HTTP
# --------------------------------------------------------------------------- #
class Fetcher:
    def __init__(self, delay: float, contact: str):
        self.delay = max(2.0, delay)
        self.last = 0.0
        self.count = 0
        self.s = requests.Session()
        ua = UA + (f" kontak: {contact}" if contact else "")
        self.s.headers.update({
            "User-Agent": ua,
            "Accept-Language": "id-ID,id;q=0.9",
            "Accept": "text/html,application/xhtml+xml",
        })
        self.rp = robotparser.RobotFileParser()
        self.rp_loaded = False

    def _wait(self) -> None:
        wait = self.delay - (time.time() - self.last)
        if wait > 0:
            time.sleep(wait)

    def load_robots(self) -> None:
        try:
            self._wait()
            r = self.s.get(f"{BASE}/robots.txt", timeout=30)
            self.last = time.time()
            if r.status_code == 200:
                self.rp.parse(r.text.splitlines())
                self.rp_loaded = True
        except requests.RequestException as exc:
            log(f"robots.txt gagal dibaca ({exc}); lanjut dengan hati-hati")

    def get(self, url: str) -> str:
        if self.rp_loaded and not self.rp.can_fetch(UA, url):
            raise PermissionError(f"Diblokir robots.txt: {url}")
        last_err = "tidak diketahui"
        for attempt in range(1, 4):
            self._wait()
            try:
                r = self.s.get(url, timeout=30)
                self.last = time.time()
                self.count += 1
                if r.status_code == 200:
                    return r.text
                last_err = f"HTTP {r.status_code}"
                if r.status_code in (429, 500, 502, 503, 504):
                    time.sleep(10 * attempt)
                    continue
                break  # 404 dsb: tidak perlu diulang
            except requests.RequestException as exc:
                self.last = time.time()
                last_err = str(exc)
                time.sleep(5 * attempt)
        raise RuntimeError(last_err)


# --------------------------------------------------------------------------- #
# Parsing HTML
# --------------------------------------------------------------------------- #
def decode_cfemail(hexstr: str) -> str:
    key = int(hexstr[:2], 16)
    return "".join(chr(int(hexstr[i:i + 2], 16) ^ key) for i in range(2, len(hexstr), 2))


def decode_cloudflare_emails(soup: BeautifulSoup) -> None:
    """Email di dalam deskripsi dikaburkan Cloudflare ([email protected]); kembalikan ke aslinya."""
    for el in soup.select("[data-cfemail]"):
        try:
            el.replace_with(decode_cfemail(el["data-cfemail"]))
        except (ValueError, KeyError):
            pass
    for a in soup.select('a[href*="/cdn-cgi/l/email-protection#"]'):
        try:
            a.replace_with(decode_cfemail(a["href"].split("#", 1)[1]))
        except (ValueError, IndexError):
            pass


def parse_list_page(html: str) -> list[dict]:
    soup = BeautifulSoup(html, "html.parser")
    items, seen = [], set()
    for a in soup.select('a[href*="/lowongan-dalam-negeri/lowongan/"]'):
        href = a.get("href", "").split("?")[0].rstrip("/")
        if not UUID_RE.search(href):
            continue
        url = href if href.startswith("http") else BASE + href
        if url in seen:
            continue
        seen.add(url)
        title = a.get_text(" ", strip=True)
        company = ""
        h = a.find_parent(["h4", "h3", "h2"])
        if h is not None:
            for s in h.find_all_next(string=True):
                t = s.strip()
                if t and t != title:
                    company = t
                    break
        items.append({"url": url, "title": title, "company": company})
    return items


def _section(lines: list[str], idx: dict[str, int], start: str, ends: tuple[str, ...]) -> list[str]:
    if start not in idx:
        return []
    begin = idx[start] + 1
    stops = [idx[e] for e in ends if e in idx and idx[e] > idx[start]]
    end = min(stops) if stops else len(lines)
    return lines[begin:end]


def parse_detail_page(html: str) -> dict:
    soup = BeautifulSoup(html, "html.parser")
    decode_cloudflare_emails(soup)
    h1 = soup.find("h1")
    title = h1.get_text(" ", strip=True) if h1 else ""

    company = ""
    link = soup.select_one('a[href*="/perusahaan/"]')
    if link is not None and link.parent is not None:
        for t in link.parent.get_text("\n", strip=True).split("\n"):
            if t and t.lower() != "lihat profil perusahaan":
                company = t
                break

    lines = [ln.strip() for ln in soup.get_text("\n", strip=True).split("\n") if ln.strip()]
    idx: dict[str, int] = {}
    for i, ln in enumerate(lines):
        key = ln.lower().rstrip(":").strip()
        if key in ("deskripsi pekerjaan", "persyaratan khusus", "persyaratan umum", "keterampilan",
                   "lihat profil perusahaan", "lowongan dari") and key not in idx:
            idx[key] = i
    ends = ("persyaratan khusus", "persyaratan umum", "keterampilan", "lihat profil perusahaan", "lowongan dari")
    desk = _section(lines, idx, "deskripsi pekerjaan", ends)
    khusus = _section(lines, idx, "persyaratan khusus",
                      ("persyaratan umum", "keterampilan", "lihat profil perusahaan", "lowongan dari"))
    return {"title": title, "company": company, "desk": desk, "khusus": khusus}


# --------------------------------------------------------------------------- #
# Pembersihan & fitur
# --------------------------------------------------------------------------- #
CTRL_RE = re.compile(r"[\u200b-\u200f\u2060\ufeff\x00-\x08\x0b\x0c\x0e-\x1f]")
BULLET_RE = re.compile(r"^[\-\*\u2022\u25cf\u25aa\u25e6\u00b7\uf0b7\u2023\u2043]+\s*")


def clean_text(text: str) -> str:
    text = CTRL_RE.sub("", text).replace("\xa0", " ")
    for pat in BOILERPLATE_RE:
        text = pat.sub(" ", text)
    return re.sub(r"\s+", " ", text).strip()


def clean_lines(lines: list[str]) -> str:
    out = []
    for ln in lines:
        ln = BULLET_RE.sub("", CTRL_RE.sub("", ln).replace("\xa0", " ").strip())
        ln = clean_text(ln)
        if ln:
            out.append(ln)
    return re.sub(r"\s+", " ", " ".join(out)).strip()


def detect_language(text: str) -> str:
    tokens = re.findall(r"[a-zA-Z]+", text.lower())
    if len(tokens) < 8:
        return "ragu"
    id_hits = sum(t in ID_WORDS for t in tokens)
    en_hits = sum(t in EN_WORDS for t in tokens)
    if en_hits >= 4 and en_hits >= 2 * id_hits:
        return "en"
    if id_hits / len(tokens) < 0.06:
        return "ragu"
    return "id"


def _prefixes(text: str) -> set[str]:
    return {w[:5] for w in re.findall(r"[a-z]{4,}", text.lower()) if w not in TITLE_STOP}


def title_mismatch(title: str, desc: str) -> bool:
    """True jika deskripsi menyebut posisi eksplisit yang tidak nyambung dengan judul."""
    title_p = _prefixes(title)
    if not title_p:
        return False
    for m in ROLE_RE.finditer(desc[:500]):
        role_p = _prefixes(m.group(1))
        if role_p and not (role_p & title_p):
            return True
    return False


def extract_info(desc: str) -> str:
    parts: list[str] = []
    for sent in re.split(r"(?<=[.!?])\s+", desc):
        sent = sent.strip()
        if sent and len(sent) <= 300 and INFO_SENT_RE.search(sent) and sent not in parts:
            parts.append(sent)
    joined = " ".join(parts)
    for rx in (EMAIL_RE, URL_RE, PHONE_RE):
        for m in rx.findall(desc):
            m = m.strip(".,;:)")
            if m and m not in joined and m not in parts:
                parts.append(m)
    return " | ".join(parts)


def norm_key(text: str) -> str:
    return re.sub(r"\s+", " ", text.lower()).strip()


class Builder:
    """Mengubah record mentah -> baris CSV, sekaligus dedup & statistik."""

    def __init__(self, today: str):
        self.today = today
        self.rows: list[dict] = []
        self.seen_desc: set[str] = set()
        self.seen_key: set[tuple] = set()
        self.stats: Counter = Counter()

    def add(self, raw: dict, list_item: dict) -> str:
        title = clean_text(raw.get("title") or list_item.get("title", ""))
        company = clean_text(list_item.get("company") or raw.get("company", ""))
        desc = clean_lines(list(raw.get("desk", [])) + list(raw.get("khusus", [])))

        lang = detect_language(desc) if desc else "ragu"
        if lang == "en":
            self.stats["bahasa_inggris_dilewati"] += 1
            return "en"

        dkey = norm_key(desc)
        key3 = (norm_key(company), norm_key(title), dkey)
        if dkey and (dkey in self.seen_desc or key3 in self.seen_key):
            self.stats["duplikat"] += 1
            return "dup"
        if dkey:
            self.seen_desc.add(dkey)
            self.seen_key.add(key3)

        info = extract_info(desc)
        cek = len(desc) < 50 or lang == "ragu" or title_mismatch(title, desc)
        gabungan = " ".join(p for p in (company, title, desc, info) if p)
        self.rows.append({
            "id": "", "label": 0, "nama_perusahaan": company, "posisi_lowongan": title,
            "deskripsi_lowongan": desc, "informasi_tambahan": info, "teks_gabungan": gabungan,
            "sumber_lowongan": "karirhub", "link_lowongan": list_item["url"],
            "tanggal_ambil": self.today, "narasi_asli": "", "ocr_mentah": "",
            "perlu_cek_manual": "True" if cek else "False",
        })
        if cek:
            self.stats["perlu_cek"] += 1
        return "ok"


def write_csv(rows: list[dict]) -> None:
    OUT_CSV.parent.mkdir(parents=True, exist_ok=True)
    tmp = OUT_CSV.with_suffix(".csv.tmp")
    with tmp.open("w", encoding="utf-8", newline="") as f:
        w = csv.DictWriter(f, fieldnames=COLUMNS, quoting=csv.QUOTE_MINIMAL)
        w.writeheader()
        for i, row in enumerate(rows, 1):
            out = dict(row)
            out["id"] = f"ASLI-{i:04d}"
            w.writerow({c: ("" if out.get(c) is None else out[c]) for c in COLUMNS})
    tmp.replace(OUT_CSV)


# --------------------------------------------------------------------------- #
# State (checkpoint)
# --------------------------------------------------------------------------- #
def load_jsonl(path: Path) -> list[dict]:
    if not path.exists():
        return []
    out = []
    for ln in path.read_text(encoding="utf-8").splitlines():
        try:
            out.append(json.loads(ln))
        except json.JSONDecodeError:
            pass
    return out


def append_jsonl(path: Path, obj: dict) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("a", encoding="utf-8") as f:
        f.write(json.dumps(obj, ensure_ascii=False) + "\n")


# --------------------------------------------------------------------------- #
# Sampling
# --------------------------------------------------------------------------- #
def is_priority(item: dict) -> bool:
    return bool(PRIORITY_RE.search(f"{item.get('company', '')} {item.get('title', '')}"))


def pick_candidates(new_items: list[dict], selected: set[str], comp_count: Counter,
                    rng: random.Random) -> list[dict]:
    prio, rest = [], []
    for it in new_items:
        if it["url"] in selected:
            continue
        key = norm_key(it["company"])
        p = is_priority(it)
        if comp_count[key] >= (PRIO_CAP if p else NORMAL_CAP):
            continue
        comp_count[key] += 1
        selected.add(it["url"])
        (prio if p else rest).append(it)
    rng.shuffle(prio)
    rng.shuffle(rest)
    out: list[dict] = []
    while prio or rest:  # selang-seling 2 prioritas : 3 biasa
        for _ in range(2):
            if prio:
                out.append(prio.pop())
        for _ in range(3):
            if rest:
                out.append(rest.pop())
    return out


# --------------------------------------------------------------------------- #
# Main
# --------------------------------------------------------------------------- #
def main() -> int:
    ap = argparse.ArgumentParser(description="Scraper lowongan asli Karirhub (LokerLens)")
    ap.add_argument("--max-items", type=int, default=1500, help="target jumlah baris (default 1500)")
    ap.add_argument("--total-pages", type=int, default=1187, help="jumlah halaman daftar (default 1187)")
    ap.add_argument("--delay", type=float, default=2.0, help="jeda antar request, minimal 2 detik")
    ap.add_argument("--seed", type=int, default=42, help="seed pengacakan halaman")
    ap.add_argument("--contact", default="", help="email kontak untuk ditaruh di User-Agent (opsional)")
    ap.add_argument("--show", type=int, default=3, help="tampilkan N baris contoh di akhir")
    ap.add_argument("--fresh", action="store_true", help="hapus state/checkpoint dan mulai dari awal")
    args = ap.parse_args()

    if args.fresh and STATE_DIR.exists():
        shutil.rmtree(STATE_DIR)
    STATE_DIR.mkdir(parents=True, exist_ok=True)

    list_cache = {rec["page"]: rec["items"] for rec in load_jsonl(LIST_STATE)}
    detail_cache = {rec["url"]: rec for rec in load_jsonl(DETAIL_STATE) if "error" not in rec}
    log(f"State: {len(list_cache)} halaman daftar & {len(detail_cache)} detail sudah tersimpan")

    rng = random.Random(args.seed)
    page_order = list(range(1, args.total_pages + 1))
    rng.shuffle(page_order)  # acak tapi deterministik -> sebaran halaman merata & bisa resume

    fetcher = Fetcher(args.delay, args.contact)
    fetcher.load_robots()
    builder = Builder(date.today().isoformat())

    selected: set[str] = set()
    comp_count: Counter = Counter()
    queue: deque = deque()
    ptr = 0
    pages_opened_net = detail_opened_net = 0
    errors: list[str] = []
    candidates_total = 0
    fetched_since_save = 0

    def refill() -> bool:
        """Ambil batch halaman daftar berikutnya -> isi antrean kandidat."""
        nonlocal ptr, pages_opened_net, candidates_total
        need = max(1, args.max_items - len(builder.rows))
        batch = min(40, max(3, math.ceil(need * OVERSAMPLE / PAGES_PER_CAND)))
        new_items: list[dict] = []
        taken = 0
        while taken < batch and ptr < len(page_order):
            page = page_order[ptr]
            ptr += 1
            if page in list_cache:
                items = list_cache[page]
            else:
                try:
                    html = fetcher.get(f"{LIST_URL}?page={page}")
                except Exception as exc:  # noqa: BLE001
                    errors.append(f"daftar hal.{page}: {exc}")
                    log(f"GAGAL halaman daftar {page}: {exc}")
                    continue
                pages_opened_net += 1
                items = parse_list_page(html)
                list_cache[page] = items
                append_jsonl(LIST_STATE, {"page": page, "items": items})
                log(f"Halaman daftar {page}: {len(items)} lowongan (request ke-{fetcher.count})")
            taken += 1
            new_items.extend(items)
        candidates_total += len(new_items)
        queue.extend(pick_candidates(new_items, selected, comp_count, rng))
        return taken > 0

    interrupted = False
    try:
        while len(builder.rows) < args.max_items:
            if not queue and not refill():
                log("Halaman daftar habis sebelum target tercapai.")
                break
            if not queue:
                continue
            item = queue.popleft()
            url = item["url"]
            raw = detail_cache.get(url)
            if raw is None:
                try:
                    raw = parse_detail_page(fetcher.get(url))
                    raw["url"] = url
                except Exception as exc:  # noqa: BLE001
                    errors.append(f"{url}: {exc}")
                    log(f"GAGAL detail {url}: {exc}")
                    append_jsonl(DETAIL_STATE, {"url": url, "error": str(exc)})
                    continue
                detail_cache[url] = raw
                append_jsonl(DETAIL_STATE, raw)
                detail_opened_net += 1
                fetched_since_save += 1
            status = builder.add(raw, item)
            log(f"[{len(builder.rows)}/{args.max_items}] {status:>3} | {item['company'][:30]} | {item['title'][:40]}")
            if status == "en":
                snippet = clean_lines(list(raw.get("desk", [])) + list(raw.get("khusus", [])))[:160]
                log(f"      teks dilewati (dianggap Inggris): {snippet}")
            if fetched_since_save >= SAVE_EVERY:
                write_csv(builder.rows)
                fetched_since_save = 0
    except KeyboardInterrupt:
        interrupted = True
        log("Dihentikan manual. Menyimpan progres... (jalankan lagi untuk melanjutkan)")

    rows = builder.rows[: args.max_items]
    write_csv(rows)

    # ------------------------------ ringkasan ------------------------------ #
    words = [len(r["deskripsi_lowongan"].split()) for r in rows]
    print("\n" + "=" * 60)
    print("RINGKASAN" + (" (DIHENTIKAN MANUAL)" if interrupted else ""))
    print("=" * 60)
    print(f"Halaman daftar dibuka        : {len(list_cache)} (baru di sesi ini: {pages_opened_net})")
    print(f"Halaman detail dibuka        : {len(detail_cache)} (baru di sesi ini: {detail_opened_net})")
    print(f"Lowongan terkumpul (kandidat): {candidates_total}")
    print(f"Baris tersimpan              : {len(rows)}  -> {OUT_CSV}")
    print(f"Duplikat dibuang             : {builder.stats['duplikat']}")
    print(f"Berbahasa Inggris dilewati   : {builder.stats['bahasa_inggris_dilewati']}")
    print(f"Perlu dicek manual           : {sum(r['perlu_cek_manual'] == 'True' for r in rows)}")
    print(f"Request gagal                : {len(errors)}")
    if words:
        print(f"Panjang deskripsi (kata)     : rata-rata {statistics.mean(words):.1f}, median {statistics.median(words):.0f}")
    if len(rows) < args.max_items and not interrupted:
        print(f"CATATAN: baris tersimpan ({len(rows)}) < target ({args.max_items}); "
              f"naikkan --total-pages bila situs bertambah halaman, atau jalankan ulang.")
    for e in errors[:10]:
        print("  galat:", e)

    if args.show and rows:
        print(f"\n{min(args.show, len(rows))} baris contoh:")
        for r in rows[: args.show]:
            print("-" * 60)
            print("id        :", r["id"] or "ASLI-%04d" % (rows.index(r) + 1))
            print("perusahaan:", r["nama_perusahaan"])
            print("posisi    :", r["posisi_lowongan"])
            print("deskripsi :", r["deskripsi_lowongan"][:350] + ("..." if len(r["deskripsi_lowongan"]) > 350 else ""))
            print("info tmbhn:", r["informasi_tambahan"][:200])
            print("perlu_cek :", r["perlu_cek_manual"])
            print("link      :", r["link_lowongan"])
    return 0


if __name__ == "__main__":
    sys.exit(main())
