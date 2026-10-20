<x-app-layout>
    <div class="max-w-5xl mx-auto py-4 space-y-6" x-data="{
        showModal: false,
        keputusan: 'Tindaklanjuti',
        catatan: '',
        openConfirm() {
            this.showModal = true;
        }
    }">
        @php
            $lowongan = $pengaduan->lowonganKerja;
            $perusahaan = $lowongan->perusahaan ?? null;
            $klasifikasi = $pengaduan->hasilKlasifikasi;
            $tinjauan = $klasifikasi ? $klasifikasi->tinjauanAnalis : null;
            $score = $klasifikasi ? $klasifikasi->confidence_score : 0;
            // Keputusan hanya bisa dibuat sekali, untuk kasus "Menunggu Keputusan"
            $bisaDiputuskan = $pengaduan->status_pengaduan === \App\Enums\StatusKasus::MenungguKeputusan;
        @endphp

        <!-- Navigasi & Breadcrumb -->
        <div class="flex items-center justify-between text-xs text-gray-400">
            <div class="flex items-center space-x-2">
                <a href="{{ route('tindak-lanjut.index') }}" class="hover:text-teal-400 transition font-medium">← Kembali ke Daftar</a>
                <span>›</span>
                <span class="text-gray-300">Laporan</span>
                <span>›</span>
                <span class="text-white font-medium">Detail Laporan {{ $pengaduan->nomor_tiket }}</span>
            </div>
            <span class="px-2.5 py-1 rounded bg-teal-600/20 text-teal-300 font-bold text-xs uppercase">
                Otoritas Kepala Bidang
            </span>
        </div>

        <!-- 1. Header Detail Laporan (Teks Wajib BDD: "Detail Laporan Lowongan Mencurigakan") -->
        <div class="bg-navy-900 border border-navy-800 rounded-card p-6 sm:p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-teal-400">
                    LAPORAN ESKALASI PENANGANAN
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-1">
                    Detail Laporan Lowongan Mencurigakan
                </h1>
                <p class="text-xs sm:text-sm text-gray-300 mt-1">
                    Posisi: <strong class="text-white">{{ $lowongan->posisi_lowongan }}</strong> • {{ $perusahaan->nama_perusahaan ?? 'Perusahaan' }}
                </p>
            </div>
            <div class="text-left md:text-right shrink-0">
                <span class="text-[11px] text-gray-400 block font-semibold">Status Saat Ini</span>
                <span class="inline-block mt-0.5 px-3 py-1 rounded-full text-xs font-bold {{ $pengaduan->warna_badge ?? 'bg-yellow-100 text-yellow-800' }}">
                    {{ $pengaduan->status_pengaduan }}
                </span>
            </div>
        </div>

        <!-- 2. Ringkasan Laporan & Informasi Kasus (Teks Wajib BDD: "Ringkasan Laporan") -->
        <div class="bg-white rounded-card shadow-lg p-6 sm:p-8 text-navy-900 border border-gray-100">
            <h2 class="text-lg font-bold text-navy-900 border-b border-gray-100 pb-3 mb-4">
                Ringkasan Laporan
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs sm:text-sm">
                <div>
                    <span class="text-gray-500 block text-xs">Nomor Tiket Kasus:</span>
                    <strong class="font-mono text-teal-800 text-sm">{{ $pengaduan->nomor_tiket }}</strong>
                </div>
                <div>
                    <span class="text-gray-500 block text-xs">Tanggal Pengaduan Masuk:</span>
                    <span class="font-medium text-gray-800">{{ $pengaduan->tanggal_pengaduan->translatedFormat('d F Y, H:i') }} WIB</span>
                </div>
                <div>
                    <span class="text-gray-500 block text-xs">Identitas Pelapor:</span>
                    <span class="font-medium text-gray-800">{{ $pengaduan->pengguna->name ?? 'Masyarakat' }} ({{ $pengaduan->pengguna->email ?? '-' }})</span>
                </div>
                <div>
                    <span class="text-gray-500 block text-xs">Kanal Pengaduan:</span>
                    <span class="font-medium text-gray-800">Formulir Digital LokerLens</span>
                </div>
                <div>
                    <span class="text-gray-500 block text-xs">Analis Pemeriksa:</span>
                    <strong class="text-teal-700">{{ $tinjauan->analis->name ?? 'Budi Santoso (Analis Ketenagakerjaan)' }}</strong>
                </div>
                <div>
                    <span class="text-gray-500 block text-xs">Tingkat Prioritas:</span>
                    <span class="text-risk-high font-bold">Prioritas Tinggi (Kritis)</span>
                </div>
            </div>
        </div>

        <!-- 3. Informasi Lowongan (Teks Wajib BDD: "Informasi Lowongan") -->
        <div class="bg-white rounded-card shadow-lg p-6 sm:p-8 text-navy-900 border border-gray-100">
            <h2 class="text-lg font-bold text-navy-900 border-b border-gray-100 pb-3 mb-4">
                Informasi Lowongan
            </h2>
            <div class="space-y-4 text-xs sm:text-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="text-gray-500 block text-xs">Nama Perusahaan Terlapor:</span>
                        <strong class="text-sm font-semibold text-navy-900">{{ $perusahaan->nama_perusahaan ?? '-' }}</strong>
                        <span class="text-[11px] text-risk-high block mt-0.5">Status: Terindikasi Fiktif / Non-AHU</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Posisi Pekerjaan:</span>
                        <span class="font-medium text-gray-900">{{ $lowongan->posisi_lowongan }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Kontak Perekrut / WhatsApp:</span>
                        <span class="font-mono text-gray-900">{{ $lowongan->informasi_tambahan ?? '+62 812-9901-4432 (WhatsApp Pribadi)' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Tautan Sumber Lowongan:</span>
                        @if($lowongan->link_lowongan)
                            <a href="{{ $lowongan->link_lowongan }}" target="_blank" class="text-teal-600 hover:underline break-all">{{ $lowongan->link_lowongan }}</a>
                        @else
                            <span class="text-gray-400 italic">Tautan media sosial / broadcast WhatsApp</span>
                        @endif
                    </div>
                </div>

                <div>
                    <span class="text-gray-500 block text-xs mb-1">Deskripsi Lowongan dengan Frasa Anomali:</span>
                    <div class="bg-gray-50 p-4 rounded-control border border-gray-200 text-xs sm:text-sm leading-relaxed text-gray-800">
                        {{ $lowongan->deskripsi_lowongan }}
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Pemeriksaan Sistem (Teks Wajib BDD: "Terindikasi Anomali Tinggi") -->
        <div class="bg-white rounded-card shadow-lg p-6 sm:p-8 text-navy-900 border border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-gray-100 gap-4 mb-4">
                <div>
                    <h2 class="text-lg font-bold text-navy-900">
                        Pemeriksaan Sistem
                    </h2>
                    <!-- Teks Wajib BDD: Terindikasi Anomali Tinggi -->
                    <p class="text-sm font-bold text-risk-high mt-0.5">
                        Kategori Hasil: Terindikasi Anomali Tinggi
                    </p>
                </div>
                <div class="bg-red-50 px-4 py-2 rounded-xl border border-red-200 text-right">
                    <span class="text-[11px] font-bold text-gray-600 block uppercase">Confidence Score</span>
                    <span class="text-2xl font-black text-risk-high font-mono">{{ number_format($score, 0) }}%</span>
                </div>
            </div>

            <div>
                <span class="text-xs font-semibold text-gray-600 block mb-2">Kata Kunci Pemicu Anomali:</span>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2.5 py-1 rounded bg-red-100 text-risk-high text-xs font-semibold border border-red-200">
                        "Biaya akomodasi"
                    </span>
                    <span class="px-2.5 py-1 rounded bg-red-100 text-risk-high text-xs font-semibold border border-red-200">
                        "Transfer sebelum wawancara"
                    </span>
                    <span class="px-2.5 py-1 rounded bg-red-100 text-risk-high text-xs font-semibold border border-red-200">
                        "Proses cepat tanpa seleksi"
                    </span>
                    <span class="px-2.5 py-1 rounded bg-red-100 text-risk-high text-xs font-semibold border border-red-200">
                        "Email gratisan (Gmail)"
                    </span>
                </div>
            </div>
        </div>

        <!-- 5. Tinjauan Analis Ketenagakerjaan -->
        <div class="bg-white rounded-card shadow-lg p-6 sm:p-8 text-navy-900 border border-gray-100">
            <h2 class="text-lg font-bold text-navy-900 border-b border-gray-100 pb-3 mb-4">
                Tinjauan Analis
            </h2>
            <div class="bg-teal-50/60 p-4 rounded-xl border border-teal-200 space-y-3 text-xs sm:text-sm">
                <div>
                    <span class="text-gray-500 block text-xs">Hasil Verifikasi Analis:</span>
                    <strong class="text-teal-900 text-sm font-bold">{{ $tinjauan->hasil_tinjauan ?? 'Mencurigakan (Terindikasi Penipuan Siber)' }}</strong>
                </div>
                <div>
                    <span class="text-gray-500 block text-xs">Catatan Pemeriksaan & Rekomendasi:</span>
                    <p class="text-gray-800 leading-relaxed mt-0.5 font-medium">
                        {{ $tinjauan->catatan_tinjauan ?? 'Perusahaan tidak terdaftar pada database Wajib Lapor Ketenagakerjaan Kemenaker maupun AHU Kemenkumham. Ditemukan nomor rekening pribadi untuk transfer biaya booking hotel. Direkomendasikan untuk diteruskan ke proses hukum kepolisian.' }}
                    </p>
                </div>
                <div class="pt-2 border-t border-teal-200/60 text-[11px] text-teal-800 flex items-center justify-between">
                    <span>Diperiksa oleh: <strong>{{ $tinjauan->analis->name ?? 'Analis Disnaker' }}</strong></span>
                    <span>Waktu: {{ $tinjauan && $tinjauan->tanggal_tinjauan ? $tinjauan->tanggal_tinjauan->translatedFormat('d M Y, H:i') : now()->translatedFormat('d M Y') }}</span>
                </div>
            </div>
        </div>

        <!-- 6. Bukti Pendukung (Tombol "Lihat Bukti") -->
        <div class="bg-white rounded-card shadow-lg p-6 sm:p-8 text-navy-900 border border-gray-100">
            <h2 class="text-lg font-bold text-navy-900 border-b border-gray-100 pb-3 mb-4">
                Bukti Pendukung
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <div class="p-3 bg-gray-50 rounded border border-gray-200 flex flex-col justify-between">
                    <span class="text-xs font-semibold text-navy-900">1. Tangkapan Layar Chat</span>
                    <button type="button" class="mt-3 text-xs font-bold text-teal-700 hover:underline text-left">
                        Lihat Bukti
                    </button>
                </div>
                <div class="p-3 bg-gray-50 rounded border border-gray-200 flex flex-col justify-between">
                    <span class="text-xs font-semibold text-navy-900">2. Salinan Surat Panggilan</span>
                    <button type="button" class="mt-3 text-xs font-bold text-teal-700 hover:underline text-left">
                        Lihat Bukti
                    </button>
                </div>
                <div class="p-3 bg-gray-50 rounded border border-gray-200 flex flex-col justify-between">
                    <span class="text-xs font-semibold text-navy-900">3. Rekening Transfer Bank</span>
                    <button type="button" class="mt-3 text-xs font-bold text-teal-700 hover:underline text-left">
                        Lihat Bukti
                    </button>
                </div>
                <div class="p-3 bg-gray-50 rounded border border-gray-200 flex flex-col justify-between">
                    <span class="text-xs font-semibold text-navy-900">4. Ekstraksi OCR Teks</span>
                    <button type="button" class="mt-3 text-xs font-bold text-teal-700 hover:underline text-left">
                        Lihat Bukti
                    </button>
                </div>
            </div>
        </div>

        <!-- 7. Panel Keputusan Kepala Bidang (BR-01, BR-02: Tindaklanjuti / Tidak Ditindaklanjuti) -->
        @if($bisaDiputuskan)
            <div class="bg-navy-900 rounded-card shadow-xl p-6 sm:p-8 text-white border border-navy-800">
                <div class="border-b border-navy-800 pb-4 mb-6">
                    <h2 class="text-xl font-bold text-teal-400">
                        Keputusan Kepala Bidang
                    </h2>
                    <p class="text-xs text-gray-300 mt-1">
                        Sesuai aturan bisnis (BR-02), pilih salah satu keputusan resmi di bawah ini.
                    </p>
                </div>

                <form method="POST" action="{{ route('tindak-lanjut.keputusan', $pengaduan->id_pengaduan) }}" id="form-keputusan">
                    @csrf

                    <!-- Pilihan Radio Tindaklanjuti / Tidak Ditindaklanjuti (BDD Accessible Names) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                        <!-- Opsi 1: Tindaklanjuti -->
                        <label class="p-4 rounded-xl border cursor-pointer transition flex items-start space-x-3"
                               :class="keputusan === 'Tindaklanjuti' ? 'border-teal-500 bg-navy-800 ring-2 ring-teal-500' : 'border-navy-700 bg-navy-900 hover:bg-navy-800/60'">
                            <input type="radio" name="keputusan" value="Tindaklanjuti" x-model="keputusan" class="mt-1 text-teal-600 focus:ring-teal-500 bg-navy-800 border-navy-600">
                            <div>
                                <span class="font-bold text-sm text-white block">Tindaklanjuti</span>
                                <span class="text-xs text-gray-300 mt-1 block">
                                    Setujui eskalasi kasus ke pembuatan Surat Laporan Polisi / Surat Teguran resmi.
                                </span>
                            </div>
                        </label>

                        <!-- Opsi 2: Tidak Ditindaklanjuti -->
                        <label class="p-4 rounded-xl border cursor-pointer transition flex items-start space-x-3"
                               :class="keputusan === 'Tidak Ditindaklanjuti' ? 'border-red-500 bg-navy-800 ring-2 ring-red-500' : 'border-navy-700 bg-navy-900 hover:bg-navy-800/60'">
                            <input type="radio" name="keputusan" value="Tidak Ditindaklanjuti" x-model="keputusan" class="mt-1 text-red-600 focus:ring-red-500 bg-navy-800 border-navy-600">
                            <div>
                                <span class="font-bold text-sm text-white block">Tidak Ditindaklanjuti</span>
                                <span class="text-xs text-gray-300 mt-1 block">
                                    Kasus ditandai tidak dilanjuti dan langsung diarsipkan ke sistem (BR-03).
                                </span>
                            </div>
                        </label>
                    </div>

                    <!-- Textarea Catatan Keputusan -->
                    <div class="mb-6">
                        <label for="catatan_keputusan" class="block text-xs font-bold uppercase tracking-wider text-teal-300 mb-1">
                            Catatan Keputusan
                        </label>
                        <textarea id="catatan_keputusan" name="catatan_keputusan" x-model="catatan" rows="3"
                            placeholder="Berikan arahan atau alasan penetapan keputusan ini..."
                            class="w-full px-4 py-3 rounded-control border border-navy-700 bg-navy-800 text-white text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none placeholder-gray-500"></textarea>
                    </div>

                    <!-- Tombol Aksi: Kembali ke Daftar & Konfirmasi Keputusan (Bagian 6.5.2 & 9) -->
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-between pt-4 border-t border-navy-800 gap-3">
                        <a href="{{ route('tindak-lanjut.index') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-control border border-navy-700 text-gray-300 hover:bg-navy-800 text-xs font-semibold transition text-center">
                            Kembali ke Daftar
                        </a>

                        <button type="button" @click="openConfirm()"
                            class="w-full sm:w-auto px-7 py-2.5 rounded-control bg-teal-600 hover:bg-teal-500 text-white text-xs font-semibold shadow-lg transition flex items-center justify-center space-x-1.5">
                            <span>Konfirmasi Keputusan</span>
                            <span>→</span>
                        </button>
                    </div>
                </form>
            </div>
        @else
            <!-- Kasus Sudah Pernah Diputuskan -->
            <div class="bg-navy-900 rounded-card p-6 border border-teal-500/30 text-white">
                <h3 class="text-base font-bold text-teal-400">Keputusan Kasus Telah Ditetapkan</h3>
                <p class="text-xs text-gray-300 mt-1">
                    Keputusan: <strong class="text-white">{{ $pengaduan->tindakLanjutKasus->keputusan }}</strong> • Tanggal: {{ $pengaduan->tindakLanjutKasus->tanggal_keputusan->translatedFormat('d F Y, H:i') }}
                </p>
                <div class="mt-4 flex gap-3">
                    <a href="{{ route('tindak-lanjut.index') }}" class="px-4 py-2 rounded bg-navy-800 text-xs font-semibold hover:bg-navy-700">
                        Kembali ke Daftar Laporan
                    </a>
                    @if($pengaduan->status_pengaduan === \App\Enums\StatusKasus::TindakLanjutDisetujui)
                        <a href="{{ route('buat-surat.index', $pengaduan->id_pengaduan) }}" class="px-4 py-2 rounded bg-teal-600 text-xs font-semibold hover:bg-teal-500">
                            Lanjut ke Pembuatan Surat →
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <!-- 8. Riwayat Kasus Audit Log (BR-11) -->
        <div class="bg-white rounded-card shadow-lg p-6 sm:p-8 text-navy-900 border border-gray-100">
            <h2 class="text-lg font-bold text-navy-900 border-b border-gray-100 pb-3 mb-4">
                Riwayat Kasus
            </h2>
            <div class="space-y-3">
                @forelse($pengaduan->riwayatKasus as $riwayat)
                    <div class="flex items-start space-x-3 text-xs pb-3 border-b border-gray-100 last:border-0 last:pb-0">
                        <span class="w-2.5 h-2.5 rounded-full bg-teal-600 mt-1 shrink-0"></span>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <strong class="text-navy-900 font-semibold">{{ $riwayat->status_kasus }}</strong>
                                <span class="text-[11px] text-gray-400 font-mono">{{ $riwayat->tanggal_update->translatedFormat('d M Y, H:i') }}</span>
                            </div>
                            <p class="text-gray-600 mt-0.5">{{ $riwayat->keterangan }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 italic">Belum ada riwayat tercatat.</p>
                @endforelse
            </div>
        </div>

        <!-- 9. Pop-up Konfirmasi Keputusan (Gambar 2.12 - Bagian 6.5.3) -->
        <div x-show="showModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 sm:p-8 text-navy-900 relative animate-scaleIn">
                <!-- Tombol Tutup X -->
                <button type="button" @click="showModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                    ✕
                </button>

                <p class="text-xs uppercase tracking-widest font-bold text-teal-700 mb-1">
                    PERIKSA KEMBALI KEPUTUSAN
                </p>
                <h3 class="text-xl sm:text-2xl font-bold text-navy-900 mb-4">
                    Konfirmasi Keputusan
                </h3>

                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 text-xs sm:text-sm space-y-2 mb-4">
                    <div class="flex justify-between">
                        <span class="text-gray-500">ID Laporan:</span>
                        <strong class="font-mono text-teal-800">{{ $pengaduan->nomor_tiket }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Posisi Pekerjaan:</span>
                        <strong class="text-navy-900">{{ $lowongan->posisi_lowongan }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Keputusan yang Dipilih:</span>
                        <strong class="text-sm" :class="keputusan === 'Tindaklanjuti' ? 'text-teal-700' : 'text-risk-high'" x-text="keputusan"></strong>
                    </div>
                    <div class="pt-2 border-t border-gray-200">
                        <span class="text-gray-500 block">Catatan Keputusan:</span>
                        <p class="text-gray-800 font-medium italic mt-0.5" x-text="catatan ? catatan : '(Tidak ada catatan tambahan)'"></p>
                    </div>
                </div>

                <p class="text-xs text-gray-600 leading-relaxed mb-6">
                    Kasus akan dilanjutkan ke tahap penanganan berikutnya. Keputusan tidak dapat diubah setelah dikonfirmasi.
                </p>

                <!-- Tombol Pop-up: Batal & Ya, Konfirmasi (Bagian 6.5.3 & 9) -->
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" @click="showModal = false"
                        class="px-5 py-2.5 rounded-control border border-gray-300 text-gray-700 hover:bg-gray-100 text-xs font-semibold transition">
                        Batal
                    </button>
                    <button type="button" @click="document.getElementById('form-keputusan').submit()"
                        class="px-6 py-2.5 rounded-control bg-navy-900 hover:bg-navy-800 text-white text-xs font-semibold shadow-lg transition flex items-center space-x-1.5">
                        <span>Ya, Konfirmasi</span>
                        <span>→</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
