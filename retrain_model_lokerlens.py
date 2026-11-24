# Jalankan dari root project LokerLens:
# python retrain_model_lokerlens.py

import json
import sys
from pathlib import Path
from datetime import datetime

import joblib
import numpy as np
import pandas as pd
from sklearn.pipeline import Pipeline
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import (
    accuracy_score, precision_score, recall_score, f1_score, roc_auc_score
)

ROOT = Path(__file__).resolve().parent
ML = ROOT / "ml"
DATASET = ML / "data" / "processed" / "dataset_lokerlens_bersih.csv"
OLD_MODEL = ML / "models" / "model_lokerlens.joblib"
META = ML / "models" / "model_lokerlens_meta.json"
EXTERNAL_CANDIDATES = [
    ML / "data" / "external" / "dataset_uji_eksternal.csv",
    ML / "data" / "eval" / "uji_eksternal.csv",
]
REPORTS = ML / "reports"
CANDIDATE_MODEL = ML / "models" / "model_lokerlens_candidate.joblib"

sys.path.insert(0, str(ML / "src"))
from lokerlens_infer import preprocess  # gunakan preprocessing project yang sama


def metrics(y_true, proba, threshold=0.50):
    pred = (proba >= threshold).astype(int)
    return {
        "accuracy": accuracy_score(y_true, pred),
        "precision_palsu": precision_score(y_true, pred, zero_division=0),
        "recall_palsu": recall_score(y_true, pred, zero_division=0),
        "f1_palsu": f1_score(y_true, pred, zero_division=0),
        "roc_auc": roc_auc_score(y_true, proba) if len(np.unique(y_true)) == 2 else np.nan,
    }, pred


def main():
    for path in (DATASET, OLD_MODEL, META):
        if not path.exists():
            raise FileNotFoundError(f"File tidak ditemukan: {path}")

    ext_path = next((p for p in EXTERNAL_CANDIDATES if p.exists()), None)
    if ext_path is None:
        raise FileNotFoundError(
            "Dataset uji eksternal tidak ditemukan. Letakkan di "
            "ml/data/external/dataset_uji_eksternal.csv"
        )

    # Siapkan data training dengan aturan yang sama seperti notebook evaluasi.
    df = pd.read_csv(DATASET)[["id", "label", "deskripsi"]]
    df = df.dropna(subset=["label", "deskripsi"]).copy()
    df["label"] = df["label"].astype(int)
    df["teks_bersih"] = df["deskripsi"].astype(str).map(preprocess)
    df = df[df["teks_bersih"].str.split().str.len() >= 5]
    df = df.drop_duplicates("teks_bersih").reset_index(drop=True)

    if df["label"].nunique() != 2:
        raise ValueError("Dataset training harus memiliki label 0 dan 1.")

    print("DATA TRAINING SETELAH PEMBERSIHAN")
    print(f"Total: {len(df)}")
    print(f"Asli (0): {(df.label == 0).sum()}")
    print(f"Palsu (1): {(df.label == 1).sum()}")

    # Konfigurasi mengikuti notebook evaluasi LokerLens.
    new_model = Pipeline([
        ("tfidf", TfidfVectorizer(
            ngram_range=(1, 2),
            min_df=2,
            max_df=0.9,
            max_features=20000,
            sublinear_tf=True,
        )),
        ("clf", LogisticRegression(
            max_iter=2000,
            class_weight="balanced",
            random_state=42,
        )),
    ])
    new_model.fit(df["teks_bersih"], df["label"])

    # Simpan sebagai kandidat; model produksi lama tidak ditimpa otomatis.
    ML.joinpath("models").mkdir(parents=True, exist_ok=True)
    joblib.dump(new_model, CANDIDATE_MODEL)

    # Bandingkan model lama dan kandidat pada kumpulan eksternal yang sama.
    ext = pd.read_csv(ext_path).dropna(subset=["deskripsi", "label"]).copy()
    ext["label"] = ext["label"].astype(int)
    ext_text = ext["deskripsi"].astype(str).map(preprocess)

    if ext["label"].nunique() != 2:
        raise ValueError("Data eksternal harus memiliki label 0 dan 1.")

    old_model = joblib.load(OLD_MODEL)
    old_proba = old_model.predict_proba(ext_text)[:, 1]
    new_proba = new_model.predict_proba(ext_text)[:, 1]

    old_metrics, old_pred = metrics(ext["label"].values, old_proba)
    new_metrics, new_pred = metrics(ext["label"].values, new_proba)

    comparison = pd.DataFrame(
        [old_metrics, new_metrics],
        index=["Model lama", "Model hasil pelatihan ulang"],
    )
    REPORTS.mkdir(parents=True, exist_ok=True)
    comparison.to_csv(REPORTS / "perbandingan_model_lama_baru.csv", encoding="utf-8-sig")

    detail = ext.copy()
    detail["pred_model_lama"] = old_pred
    detail["pred_model_baru"] = new_pred
    detail["proba_palsu_model_lama"] = old_proba
    detail["proba_palsu_model_baru"] = new_proba
    detail.to_csv(REPORTS / "hasil_uji_model_lama_baru.csv", index=False, encoding="utf-8-sig")

    meta = json.loads(META.read_text(encoding="utf-8"))
    candidate_meta = {
        "model_final": "Logistic Regression",
        "tanggal_pelatihan_ulang": datetime.now().isoformat(timespec="seconds"),
        "dataset": DATASET.name,
        "jumlah_data_setelah_pembersihan": int(len(df)),
        "jumlah_asli": int((df.label == 0).sum()),
        "jumlah_palsu": int((df.label == 1).sum()),
        "kolom_teks": "deskripsi",
        "parameter_tfidf": {
            "ngram_range": [1, 2], "min_df": 2, "max_df": 0.9,
            "max_features": 20000, "sublinear_tf": True
        },
        "parameter_logistic_regression": {
            "max_iter": 2000, "class_weight": "balanced", "random_state": 42
        },
        "ambang_evaluasi": 0.50,
        "model_produksi_lama": str(OLD_MODEL.relative_to(ROOT)),
        "dataset_uji_eksternal": str(ext_path.relative_to(ROOT)),
        "catatan": "Kandidat model; model produksi lama tidak ditimpa otomatis."
    }
    (ML / "models" / "model_lokerlens_candidate_meta.json").write_text(
        json.dumps(candidate_meta, ensure_ascii=False, indent=2), encoding="utf-8"
    )

    print("\nPERBANDINGAN PADA DATA EKSTERNAL")
    print(comparison.round(4).to_string())
    print(f"\nJumlah data eksternal: {len(ext)} "
          f"(asli {(ext.label == 0).sum()}, palsu {(ext.label == 1).sum()})")
    print(f"Kandidat model: {CANDIDATE_MODEL}")
    print(f"Laporan: {REPORTS / 'perbandingan_model_lama_baru.csv'}")
    print(f"Detail prediksi: {REPORTS / 'hasil_uji_model_lama_baru.csv'}")

    if len(df) == 824:
        print(
            "\nCATATAN: Setelah preprocessing, jumlah data menjadi 824, sama dengan "
            "jumlah data yang tercatat pada pelatihan sebelumnya. Jadi proses ini "
            "merupakan pelatihan ulang dengan data yang sama, bukan penambahan "
            "informasi pelatihan baru."
        )
    else:
        print(
            "\nCatatan: jumlah data setelah preprocessing berbeda dari 824 data "
            "pada metadata model lama. Periksa perbedaan dataset sebelum "
            "menetapkan kandidat sebagai model produksi."
        )

    print(
        "\nPENTING: Hasil pada data eksternal ini adalah perbandingan kandidat, "
        "bukan bukti generalisasi final. Label dan keragaman sumber data tetap "
        "perlu dipastikan."
    )


if __name__ == "__main__":
    main()
