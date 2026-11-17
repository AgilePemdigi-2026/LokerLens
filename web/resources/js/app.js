import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Kunci sessionStorage untuk mengingat kolom yang sedang diketik sebelum halaman dimuat ulang
const KUNCI_FOKUS = 'lokerlens:filter-fokus';

/**
 * Filter otomatis untuk form GET di halaman daftar (issue #25).
 * Pakai: <form method="GET" x-data="filterOtomatis"> ... </form>
 *
 * - Dropdown (select) diganti  → form langsung dikirim.
 * - Kolom teks diketik         → form dikirim setelah berhenti mengetik ±0,5 detik.
 * - Enter dan tombol "Cari"    → tetap berjalan seperti biasa (juga tanpa JavaScript).
 * Pilihan filter tetap terpilih setelah dimuat ulang karena nilainya diisi ulang oleh server (request()).
 */
Alpine.data('filterOtomatis', () => ({
    jeda: null,

    init() {
        // Kembalikan kursor ke kolom pencarian yang tadi diketik, supaya bisa lanjut mengetik
        try {
            const nama = sessionStorage.getItem(KUNCI_FOKUS);
            sessionStorage.removeItem(KUNCI_FOKUS);
            const kolom = nama ? this.$el.elements.namedItem(nama) : null;
            if (kolom && typeof kolom.focus === 'function') {
                kolom.focus();
                const panjang = kolom.value.length;
                kolom.setSelectionRange?.(panjang, panjang);
            }
        } catch (e) {
            // sessionStorage tidak tersedia: abaikan, filter tetap berjalan
        }

        this.$el.addEventListener('change', (event) => {
            if (event.target.tagName === 'SELECT') {
                this.kirim();
            }
        });

        this.$el.addEventListener('input', (event) => {
            const kolom = event.target;
            if (kolom.tagName !== 'INPUT' || !['text', 'search'].includes(kolom.type)) {
                return;
            }
            clearTimeout(this.jeda);
            this.jeda = setTimeout(() => this.kirim(kolom), 500);
        });

        // Enter atau tombol "Cari": batalkan pencarian tertunda agar tidak terkirim dua kali
        this.$el.addEventListener('submit', () => clearTimeout(this.jeda));
    },

    kirim(kolomFokus = null) {
        clearTimeout(this.jeda);
        if (kolomFokus) {
            try {
                sessionStorage.setItem(KUNCI_FOKUS, kolomFokus.name);
            } catch (e) {
                // abaikan
            }
        }
        // requestSubmit menjalankan validasi dan event submit; submit() untuk browser lama
        if (typeof this.$el.requestSubmit === 'function') {
            this.$el.requestSubmit();
        } else {
            this.$el.submit();
        }
    },
}));

Alpine.start();
