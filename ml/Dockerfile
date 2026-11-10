# Image API ML LokerLens (FastAPI + model klasifikasi).
# Dibangun dari folder ml/:  docker build -t lokerlens-ml ./ml
#
# Port 8001 HANYA untuk jaringan internal Docker (dipanggil container web lewat
# ML_API_URL=http://ml:8001). Jangan dibuka ke internet: API ini tanpa login.
# Kebutuhan RAM: sekitar 150-300 MB (cukup batas 512 MB).
FROM python:3.13-slim

# Tanpa file .pyc, log langsung tampil di log container
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1

WORKDIR /app

# Pasang paket API saja (tanpa easyocr/scraping), dipisah supaya cache build terpakai
COPY requirements-api.txt .
RUN pip install -r requirements-api.txt

# Kode API, modul inference, dan model (model_lokerlens.joblib ikut di git, ±170 KB)
COPY api/ api/
COPY src/ src/
COPY models/ models/

# Jalankan sebagai pengguna biasa, bukan root
RUN useradd --system --no-create-home lokerlens
USER lokerlens

EXPOSE 8001

# Coolify/Docker menandai container sehat jika /health menjawab
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD python -c "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8001/health', timeout=4)" || exit 1

# 0.0.0.0 supaya bisa dihubungi container lain di jaringan internal (bukan hanya dari dalam container ini)
CMD ["uvicorn", "api.app:app", "--host", "0.0.0.0", "--port", "8001"]
