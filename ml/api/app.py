"""API klasifikasi LokerLens (FastAPI).

Format request/response mengikuti docs/architecture.md §5.

Cara menjalankan (dari dalam folder ml/, venv aktif):
    uvicorn api.app:app --port 8001

Endpoint:
    GET  /health   -> cek API hidup dan model apa yang dimuat
    POST /predict  -> klasifikasi satu teks deskripsi lowongan
"""
import json
import sys
from pathlib import Path

import sklearn
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel

# Folder ml/ (satu tingkat di atas folder api/)
ML_DIR = Path(__file__).resolve().parent.parent

# Pakai preprocessing yang SAMA PERSIS dengan notebook training:
# fungsinya diimpor dari ml/src/lokerlens_infer.py, bukan disalin.
sys.path.insert(0, str(ML_DIR / "src"))
from lokerlens_infer import STOPWORDS, kata_pemicu, muat_model, preprocess  # noqa: E402

# Ambang label asli/palsu (probabilitas palsu >= 0.5 berarti "palsu").
# Ambang PRIORITAS TINGGI tidak ditentukan di sini, tetapi di Laravel
# (config/lokerlens.php), sesuai docs/architecture.md §5.3.
AMBANG_LABEL = 0.5

# Token pengganti dari preprocess() diubah ke teks yang bisa dibaca pengguna.
TOKEN_TERBACA = {
    "nominalrp": "nominal Rp",
    "nomorwa": "nomor WA",
    "tokenurl": "tautan",
    "emailgratisan": "email gratisan",
    "emailresmi": "email resmi",
}


# ---------- dijalankan SEKALI saat API dinyalakan ----------

# Model dilatih TANPA Sastrawi (daftar stopword cadangan). Kalau Sastrawi
# terpasang, kata penting seperti "tanpa" ikut terbuang dan hasilnya berubah.
if "tanpa" in STOPWORDS:
    sys.exit("Sastrawi terpasang, padahal model dilatih tanpa Sastrawi. "
             "Jalankan: pip uninstall Sastrawi")

META = json.loads((ML_DIR / "models" / "model_lokerlens_meta.json").read_text(encoding="utf-8"))
if sklearn.__version__ != META["sklearn_version"]:
    print(f"PERINGATAN: scikit-learn {sklearn.__version__}, model dilatih dengan "
          f"{META['sklearn_version']}. Jalankan: pip install -r requirements.txt")

MODEL = muat_model(ML_DIR / "models" / "model_lokerlens.joblib")
VERSI_MODEL = f"{META['model_final']} TF-IDF ({META['tanggal_latih']})"

app = FastAPI(title="LokerLens ML API")


# ---------- bentuk data request ----------

class PermintaanPrediksi(BaseModel):
    deskripsi: str


# ---------- fungsi bantu ----------

def buat_terbaca(kata: str) -> str:
    """Ganti token internal, mis. "gaji nominalrp" -> "gaji nominal Rp"."""
    return " ".join(TOKEN_TERBACA.get(k, k) for k in kata.split())


def kata_pemicu_dengan_bobot(teks_bersih: str, jumlah: int = 5) -> list:
    """Ambil kata pemicu (logika dari lokerlens_infer) lalu tambahkan bobotnya.

    Bobot = nilai TF-IDF kata di teks ini x koefisien kata di model.
    Makin besar bobotnya, makin kuat kata itu mendorong hasil ke "palsu".
    """
    tfidf = MODEL.named_steps["tfidf"]
    koefisien = MODEL.named_steps["clf"].coef_[0]
    vektor = tfidf.transform([teks_bersih])

    hasil = []
    for kata in kata_pemicu(MODEL, teks_bersih, top_n=jumlah):
        i = tfidf.vocabulary_[kata]
        bobot = vektor[0, i] * koefisien[i]
        hasil.append({"kata": buat_terbaca(kata), "bobot": round(float(bobot), 4)})
    return hasil


# ---------- endpoint ----------

@app.get("/health")
def health():
    return {"status": "ok", "versi_model": VERSI_MODEL}


@app.post("/predict")
def predict(data: PermintaanPrediksi):
    teks_bersih = preprocess(data.deskripsi)
    if not teks_bersih:
        raise HTTPException(status_code=422, detail="deskripsi kosong atau tidak berisi kata")

    # predict_proba -> [peluang asli, peluang palsu]
    peluang_palsu = float(MODEL.predict_proba([teks_bersih])[0][1])

    return {
        "probabilitas_penipuan": round(peluang_palsu, 4),
        "label": "palsu" if peluang_palsu >= AMBANG_LABEL else "asli",
        "kata_pemicu": kata_pemicu_dengan_bobot(teks_bersih),
        "versi_model": VERSI_MODEL,
    }
