"""Burndown chart LokerLens (effort per sprint).

Menggambar dua garis effort per sprint, mengikuti format laporan tim:
- Estimated Effort: total effort rencana per sprint.
- Actual Effort: total effort yang dirasakan tim per sprint (hasil retrospektif).

Cara menjalankan (dari folder root repo):
    pip install matplotlib
    python docs/burndown/burndown_chart.py

Gambar disimpan sebagai docs/burndown/burndown_lokerlens.png.
Untuk mengubah data, cukup ganti huruf ukuran di daftar FITUR di bawah.
"""

from pathlib import Path

import matplotlib

matplotlib.use("Agg")  # simpan ke file tanpa membuka jendela
import matplotlib.pyplot as plt

# Bobot T-shirt size
BOBOT = {"S": 1, "M": 2, "L": 3, "XL": 4, "XXL": 5}

# (nama fitur, sprint, ukuran estimasi, ukuran actual)
FITUR = [
    # Sprint 1
    ("Scraping Data Lowongan Kerja", 1, "L", "XXL"),  # scraping lebih sulit dari perkiraan
    ("Pelatihan Model Klasifikasi", 1, "XL", "XL"),
    ("Register", 1, "M", "M"),
    ("Login", 1, "M", "M"),
    ("Periksa Lowongan Kerja", 1, "XL", "XL"),
    ("Meninjau Lowongan Kerja Prioritas", 1, "L", "L"),
    ("Menindaklanjuti Laporan", 1, "L", "L"),
    ("Buat Surat Teguran/Laporan Polisi", 1, "L", "L"),
    # Sprint 2
    ("Lupa Password", 2, "M", "M"),
    ("Beranda", 2, "S", "S"),
    ("Status Pengaduan", 2, "M", "M"),
    ("Riwayat Pengaduan", 2, "M", "M"),
    ("Dashboard Analis", 2, "M", "M"),
    ("Dashboard Kepala Bidang", 2, "M", "M"),
    ("Arsip Kasus", 2, "M", "M"),
    # Sprint 3
    ("Pengujian Fungsional (BDD, black box)", 3, "L", "M"),
    ("Pengujian Model Klasifikasi", 3, "M", "M"),
    ("Pengujian Non-Fungsional", 3, "M", "M"),
    ("User Acceptance Testing (UAT)", 3, "M", "S"),
    ("Perbaikan Bug dan Finalisasi", 3, "L", "L"),
]

WARNA_ESTIMASI = "#cc1f1f"
WARNA_AKTUAL = "#1f4e6b"


def effort_per_sprint(kolom_ukuran):
    """Jumlahkan bobot per sprint. kolom_ukuran: 2 = estimasi, 3 = actual."""
    daftar_sprint = sorted({f[1] for f in FITUR})
    return daftar_sprint, [
        sum(BOBOT[f[kolom_ukuran]] for f in FITUR if f[1] == sprint)
        for sprint in daftar_sprint
    ]


def gambar_garis(ax, label_x, nilai, pembanding, warna, label, di_atas_kalau_sama):
    """Gambar satu garis dan tulis angkanya di setiap titik.

    Supaya angka dua garis tidak saling menimpa, angka ditulis di atas titik
    kalau nilainya lebih besar dari garis pembanding, dan di bawah kalau lebih kecil.
    """
    ax.plot(label_x, nilai, color=warna, marker="o", linewidth=2, label=label)
    for x, y, y_lain in zip(label_x, nilai, pembanding):
        di_atas = y > y_lain or (y == y_lain and di_atas_kalau_sama)
        geser_y = 12 if di_atas else -12
        ax.annotate(str(y), (x, y), textcoords="offset points", xytext=(0, geser_y),
                    ha="center", va="center", color=warna, fontweight="bold")


def main():
    daftar_sprint, estimasi = effort_per_sprint(2)
    _, aktual = effort_per_sprint(3)
    label_x = [f"Sprint {s}" for s in daftar_sprint]

    fig, ax = plt.subplots(figsize=(8, 5))
    gambar_garis(ax, label_x, estimasi, aktual, WARNA_ESTIMASI, "Estimated Effort", True)
    gambar_garis(ax, label_x, aktual, estimasi, WARNA_AKTUAL, "Actual Effort", False)

    ax.set_title("Burndown Chart LokerLens", fontweight="bold")
    ax.set_ylabel("Effort (poin)")
    ax.set_ylim(0, max(estimasi + aktual) + 6)
    ax.margins(x=0.1)
    ax.grid(axis="y", linestyle="--", alpha=0.4)
    ax.legend(loc="upper right")
    fig.tight_layout()

    keluaran = Path(__file__).with_name("burndown_lokerlens.png")
    fig.savefig(keluaran, dpi=150)
    print(f"Estimated Effort: {estimasi}")
    print(f"Actual Effort   : {aktual}")
    print(f"Gambar disimpan di {keluaran}")


if __name__ == "__main__":
    main()
