"""Inference LokerLens: preprocessing, prediksi, kata pemicu, indikator rule-based.

Dipakai oleh notebook training (ml/notebooks) dan back end / API FastAPI.
    from lokerlens_infer import muat_model, periksa
"""
import re

import joblib
import numpy as np

# ---------- stopword ----------
try:  # opsional: pip install Sastrawi
    from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory
    STOPWORDS = set(StopWordRemoverFactory().get_stop_words())
except Exception:
    STOPWORDS = set("yang dan di ke dari untuk dengan pada ini itu atau adalah dalam akan juga oleh sebagai kami kita anda dapat bisa serta para atas".split())

# ---------- regex ----------
URL_RE = re.compile(r"https?://\S+|www\.\S+")
EMAIL_RE = re.compile(r"[\w.+-]+@([\w-]+(?:\.[\w-]+)+)")
WA_RE = re.compile(r"(?<!\d)(?:\+?62|0)8\d[\d\s-]{7,12}")
RP_RE = re.compile(r"\brp\.?\s?\d[\d.,]*(?:\s?(?:juta|jt|ribu|rb|k)\b)?", re.I)
FREE_MAIL = ("gmail.", "yahoo.", "ymail.", "hotmail.", "outlook.", "live.", "rocketmail.")


def _email_token(m):
    return " emailgratisan " if m.group(1).lower().startswith(FREE_MAIL) else " emailresmi "


def preprocess(text) -> str:
    t = str(text if text is not None else "").lower()
    t = EMAIL_RE.sub(_email_token, t)
    t = URL_RE.sub(" tokenurl ", t)
    t = WA_RE.sub(" nomorwa ", t)
    t = RP_RE.sub(" nominalrp ", t)
    t = re.sub(r"\d+", " ", t)
    t = re.sub(r"[^a-z\s]", " ", t)
    return " ".join(w for w in t.split() if len(w) > 1 and w not in STOPWORDS)


def gabung_teks(nama_perusahaan="", posisi="", deskripsi="", info_tambahan="") -> str:
    return " . ".join(str(x) for x in [nama_perusahaan, posisi, deskripsi, info_tambahan] if x is not None and str(x).strip())


# ---------- indikator rule-based ----------
def _nominal_rp(text):
    vals = []
    for m in RP_RE.finditer(text.lower()):
        s = m.group(0)
        num = re.search(r"\d[\d.,]*", s).group(0).replace(".", "").replace(",", "")
        if not num.isdigit():
            continue
        v = int(num)
        if re.search(r"juta|jt", s):
            v *= 1_000_000
        elif re.search(r"ribu|rb|k\b", s):
            v *= 1_000
        vals.append(v)
    return vals


def indikator_rule_based(raw_text: str):
    t = raw_text.lower()
    hasil = []
    if re.search(r"(biaya|bayar|membayar|transfer|iuran|deposit)[^.]{0,60}(administrasi|pendaftaran|seragam|tes|pelatihan|training|wawancara|interview|registrasi)", t) \
       or re.search(r"(administrasi|pendaftaran|seragam|registrasi)[^.]{0,30}(biaya|bayar|transfer|rp)", t):
        hasil.append("Indikasi biaya administrasi/pendaftaran (permintaan pembayaran)")
    if any(d in e.lower() for e in re.findall(r"[\w.+-]+@[\w.-]+", t) for d in FREE_MAIL):
        hasil.append("Domain email tidak resmi (email gratisan)")
    gaji = [v for v in _nominal_rp(t) if v >= 5_000_000]
    if gaji and re.search(r"tanpa pengalaman|fresh ?graduate|tanpa syarat|tidak perlu pengalaman|pengalaman tidak diperlukan|lulusan (sma|smk)", t):
        hasil.append("Iming-iming gaji tidak wajar tanpa kualifikasi spesifik")
    if re.search(r"(wawancara|interview)[^.]{0,40}(hotel|penginapan|kafe|cafe|rumah|apartemen)", t):
        hasil.append("Lokasi wawancara mencurigakan (di luar kantor)")
    return hasil


# ---------- kata pemicu ----------
def _bobot_fitur(clf):
    """Bobot per fitur ke arah kelas PALSU (positif = makin mencurigakan)."""
    if hasattr(clf, "calibrated_classifiers_"):          # SVM terkalibrasi
        ws = []
        for c in clf.calibrated_classifiers_:
            est = getattr(c, "estimator", None) or getattr(c, "base_estimator", None)
            ws.append(est.coef_[0])
        return np.mean(ws, axis=0)
    if hasattr(clf, "coef_"):                            # Logistic Regression
        return clf.coef_[0]
    if hasattr(clf, "feature_log_prob_"):                # Naive Bayes
        return clf.feature_log_prob_[1] - clf.feature_log_prob_[0]
    if hasattr(clf, "feature_importances_"):             # Random Forest (tidak berarah)
        return clf.feature_importances_
    raise ValueError("Model tidak didukung untuk kata pemicu")


def kata_pemicu(pipe, teks_bersih: str, top_n=5):
    vec, clf = pipe.named_steps["tfidf"], pipe.named_steps["clf"]
    x = vec.transform([teks_bersih])
    kontrib = x.multiply(_bobot_fitur(clf)).tocoo()
    fitur = vec.get_feature_names_out()
    pasang = sorted(zip(kontrib.col, kontrib.data), key=lambda p: -p[1])
    kandidat = [fitur[i] for i, v in pasang[: top_n * 3] if v > 0]
    hasil = []
    for k in kandidat:                                   # buang unigram yang sudah ada di bigram terpilih
        if any((k != o and k in o.split()) for o in kandidat):
            continue
        hasil.append(k)
    return hasil[:top_n]


# ---------- API utama untuk back end ----------
def muat_model(path="model_lokerlens.joblib"):
    return joblib.load(path)


def periksa(pipe, nama_perusahaan="", posisi="", deskripsi="", info_tambahan="", ambang_prioritas=0.80):
    """Model hanya membaca DESKRIPSI; indikator rule-based membaca semua field form."""
    if not str(deskripsi).strip():
        raise ValueError("deskripsi wajib diisi")
    raw = gabung_teks(nama_perusahaan, posisi, deskripsi, info_tambahan)
    bersih = preprocess(deskripsi)
    p = float(pipe.predict_proba([bersih])[0][1])        # probabilitas PALSU
    if p >= ambang_prioritas:
        risiko, kategori = "tinggi", "Lowongan berpotensi penipuan"
    elif p >= 0.5:
        risiko, kategori = "sedang", "Lowongan berpotensi penipuan"
    else:
        risiko, kategori = "rendah", "Lowongan kemungkinan asli"
    return {
        "kategori": kategori,
        "tingkat_risiko": risiko,
        "confidence_score": round(p * 100, 1),
        "prioritas_tinggi": p >= ambang_prioritas,
        "kata_pemicu": kata_pemicu(pipe, bersih),
        "indikator_rule_based": indikator_rule_based(raw),
    }
