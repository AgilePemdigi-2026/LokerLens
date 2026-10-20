<x-app-layout>
    <div class="max-w-5xl mx-auto py-4 space-y-6">
        @php
            $pengaduan = $klasifikasi->pengaduan;
            $lowongan = $pengaduan->lowonganKerja;
            $perusahaan = $lowongan->perusahaan ?? null;
            $tinjauan = $klasifikasi->tinjauanAnalis;
        @endphp

        <!-- Navigasi Atas & Tombol Kembali -->
        <div class="flex items-center justify-between text-xs text-gray-400">
            <div class="flex items-center space-x-2">
                <a href="{{ route('tinjau-lowongan.index') }}" class="hover:text-teal-400 transition font-medium">← Kembali ke Antrean</a>
                <span>›</span>
                <span class="text-white font-medium">Tiket {{ $pengaduan->nomor_tiket }}</span>
            </div>
            <span class="px-2.5 py-1 rounded bg-risk-high/20 border border-risk-high text-red-300 font-bold text-[11px] tracking-wider uppercase">
                PRIORITAS: SEGERA (IMMEDIATE)
            </span>
        </div>

        <!-- Pita Konteks Kasus Aktif (Bagian 6.4.2) -->
        <div class="bg-navy-900 border border-navy-800 rounded-card p-4 sm:p-5 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-lg">
            <div>
                <!-- Teks Wajib BDD: "Detail Lowongan Terpilih" -->
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white flex items-center gap-3">
                    <span>Detail Lowongan Terpilih</span>
                    <span class="text-sm font-mono text-teal-400 bg-navy-800 px-2 py-0.5 rounded border border-navy-700">
                        {{ $pengaduan->nomor_tiket }}
                    </span>
                </h1>
                <p class="text-xs text-gray-300 mt-1">
                    {{ $perusahaan->nama_perusahaan ?? 'Perusahaan' }} • Dilaporkan pada {{ $pengaduan->tanggal_pengaduan->translatedFormat('d F Y, H:i') }} WIB
                </p>
            </div>
            <div class="text-right sm:border-l sm:border-navy-800 sm:pl-4">
                <span class="text-[11px] text-gray-400 block">Status Alur Kasus</span>
                <span class="inline-block mt-0.5 px-2.5 py-1 rounded text-xs font-bold {{ $pengaduan->warna_badge ?? 'bg-yellow-100 text-yellow-800' }}">
                    {{ $pengaduan->status_pengaduan }}
                </span>
            </div>
        </div>

        <!-- 1. Kartu Klasifikasi AI & Confidence Score (Wajib BDD: "Confidence Score") -->
        <div class="bg-white rounded-card shadow-xl p-6 sm:p-8 text-navy-900 border border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-gray-100 gap-4 mb-6">
                <div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                        Model Verifikasi AI Multivalue • LokerLens Sentinel
                    </span>
                    <h2 class="text-2xl font-bold text-risk-high mt-2">
                        Lowongan berpotensi penipuan
                    </h2>
                    <p class="text-sm text-gray-600 mt-0.5">
                        {{ $perusahaan->nama_perusahaan ?? 'Perusahaan' }} • {{ $lowongan->posisi_lowongan }}
                    </p>
                </div>

                <!-- Blok BDD: Confidence Score -->
                <div class="bg-red-50 p-4 rounded-xl border border-red-200 text-center sm:text-right shrink-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-700 block">
                        Confidence Score
                    </span>
                    <div class="text-4xl font-black text-risk-high font-mono tracking-tight">
                        {{ number_format($klasifikasi->confidence_score, 0) }}%
                    </div>
                    <span class="text-[11px] font-bold text-risk-high uppercase tracking-wider">
                        KRITIS (Tingkat Bahaya)
                    </span>
                </div>
            </div>

            <!-- Peringatan Sistem (Merah) -->
            <div class="bg-red-50 border-l-4 border-risk-high p-4 rounded-r-lg text-xs sm:text-sm text-red-900 leading-relaxed mb-6">
                <strong>Peringatan Sistem:</strong> Sistem mendeteksi kombinasi anomali tinggi pada pola rekrutmen. Verifikasi manual oleh Analis Ketenagakerjaan diperlukan sebelum eskalasi ke Kepala Bidang.
            </div>

            <!-- Grid 2 Kolom: Informasi Lowongan & Temuan Sistem -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Kolom Kiri: Informasi Lowongan Lengkap -->
                <div class="space-y-3 text-xs sm:text-sm">
                    <h3 class="font-bold text-navy-900 text-sm border-b pb-2">Informasi Lowongan</h3>
                    <div>
                        <span class="text-gray-500 block text-xs">Nama Perusahaan:</span>
                        <strong class="text-navy-900 font-semibold">{{ $perusahaan->nama_perusahaan ?? '-' }}</strong>
                        <span class="inline-block ml-2 text-[10px] bg-red-100 text-risk-high px-1.5 py-0.5 rounded font-semibold">
                            Badan Hukum Belum Terverifikasi
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Posisi Lowongan:</span>
                        <span class="text-gray-900 font-medium">{{ $lowongan->posisi_lowongan }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Kontak Recruiter / WhatsApp:</span>
                        <span class="text-gray-900 font-mono">{{ $lowongan->informasi_tambahan ?? 'WhatsApp / Email tidak resmi terdeteksi' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Link Rujukan:</span>
                        @if($lowongan->link_lowongan)
                            <a href="{{ $lowongan->link_lowongan }}" target="_blank" class="text-teal-600 hover:underline break-all">{{ $lowongan->link_lowongan }}</a>
                        @else
                            <span class="text-gray-400 italic">Tidak tersedia</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs mb-1">Kutipan Deskripsi Lowongan:</span>
                        <div class="bg-gray-50 p-3 rounded border text-xs text-gray-700 max-h-36 overflow-y-auto leading-relaxed">
                            {{ $lowongan->deskripsi_lowongan }}
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Ringkasan Analisis Sistem -->
                <div class="space-y-3 text-xs sm:text-sm">
                    <h3 class="font-bold text-navy-900 text-sm border-b pb-2">Ringkasan Analisis Sistem</h3>
                    <div class="space-y-2">
                        <div class="p-2.5 bg-gray-50 rounded border border-gray-200 flex justify-between items-center">
                            <span class="text-gray-700">NLP Pattern Matching:</span>
                            <span class="font-bold text-risk-high">Skor 95/100 (Anomali)</span>
                        </div>
                        <div class="p-2.5 bg-gray-50 rounded border border-gray-200 flex justify-between items-center">
                            <span class="text-gray-700">Pemeriksaan Domain Email:</span>
                            <span class="font-bold text-risk-mid">Free Webmail (Gmail/Yahoo)</span>
                        </div>
                        <div class="p-2.5 bg-gray-50 rounded border border-gray-200 flex justify-between items-center">
                            <span class="text-gray-700">Basis Data Nomor Telepon:</span>
                            <span class="font-bold text-risk-high">Ditandai Laporan Penipuan</span>
                        </div>
                        <div class="p-2.5 bg-gray-50 rounded border border-gray-200 flex justify-between items-center">
                            <span class="text-gray-700">Legalitas AHU & Kemenaker:</span>
                            <span class="font-bold text-risk-high">Not Registered / Tak Terdaftar</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Kartu Indikator Mencurigakan (Wajib BDD: "Indikator Mencurigakan") -->
        <div class="bg-white rounded-card shadow-lg p-6 sm:p-8 text-navy-900 border border-gray-100">
            <h3 class="text-lg font-bold text-navy-900 border-b border-gray-100 pb-3 mb-4">
                Indikator Mencurigakan
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @if($klasifikasi->indikatorMencurigakan && $klasifikasi->indikatorMencurigakan->count() > 0)
                    @foreach($klasifikasi->indikatorMencurigakan as $ind)
                        <div class="border border-red-200 bg-red-50/50 rounded-control p-4">
                            <span class="text-[10px] uppercase font-bold text-risk-high bg-white px-2 py-0.5 rounded border border-red-200">
                                {{ $ind->kategori_indikator }}
                            </span>
                            <h4 class="text-sm font-bold text-navy-900 mt-2">{{ $ind->nama_indikator }}</h4>
                            <p class="text-xs text-gray-600 mt-1">Bobot kecurigaan: <strong>{{ $ind->bobot_indikator }}%</strong></p>
                        </div>
                    @endforeach
                @else
                    <div class="border border-red-200 bg-red-50/50 rounded-control p-4">
                        <span class="text-[10px] uppercase font-bold text-risk-high bg-white px-2 py-0.5 rounded border border-red-200">Biaya Fiktif</span>
                        <h4 class="text-sm font-bold text-navy-900 mt-2">Permintaan Biaya Administrasi Fiktif</h4>
                        <p class="text-xs text-gray-600 mt-1">Perekrut meminta transfer biaya tes / seragam sebelum kontrak.</p>
                    </div>
                    <div class="border border-red-200 bg-red-50/50 rounded-control p-4">
                        <span class="text-[10px] uppercase font-bold text-risk-high bg-white px-2 py-0.5 rounded border border-red-200">Email Tidak Resmi</span>
                        <h4 class="text-sm font-bold text-navy-900 mt-2">Domain Email Tidak Resmi</h4>
                        <p class="text-xs text-gray-600 mt-1">Perusahaan mengatasnamakan korporat namun menggunakan webmail publik gratis.</p>
                    </div>
                    <div class="border border-red-200 bg-red-50/50 rounded-control p-4">
                        <span class="text-[10px] uppercase font-bold text-risk-high bg-white px-2 py-0.5 rounded border border-red-200">Gaji Janggal</span>
                        <h4 class="text-sm font-bold text-navy-900 mt-2">Gaji Tidak Masuk Akal</h4>
                        <p class="text-xs text-gray-600 mt-1">Tawaran upah tinggi tanpa persyaratan kualifikasi atau pengalaman kerja.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- 3. Bukti Pendukung & Lampiran Dokumen (Bagian 6.4.2) -->
        <div class="bg-white rounded-card shadow-lg p-6 sm:p-8 text-navy-900 border border-gray-100">
            <h3 class="text-lg font-bold text-navy-900 border-b border-gray-100 pb-3 mb-4">
                Bukti Pendukung & Lampiran Dokumen
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 rounded-control border border-gray-200 bg-gray-50 flex flex-col justify-between">
                    <div>
                        <div class="text-xs font-bold text-navy-900 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Tangkapan Layar Chat WA
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">Percakapan instruksi transfer dana tiket rekrutmen.</p>
                    </div>
                    <span class="mt-3 text-[11px] font-semibold text-teal-700 hover:underline cursor-pointer">Lihat Bukti (Lampiran)</span>
                </div>
                <div class="p-4 rounded-control border border-gray-200 bg-gray-50 flex flex-col justify-between">
                    <div>
                        <div class="text-xs font-bold text-navy-900 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Salinan Surat Panggilan (PDF)
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">Dokumen panggilan interview dengan logo instansi palsu.</p>
                    </div>
                    <span class="mt-3 text-[11px] font-semibold text-teal-700 hover:underline cursor-pointer">Unduh Dokumen (PDF)</span>
                </div>
                <div class="p-4 rounded-control border border-gray-200 bg-gray-50 flex flex-col justify-between">
                    <div>
                        <div class="text-xs font-bold text-navy-900 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                            Log Ekstraksi Teks (OCR)
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">Pencocokan keyword anomali pada gambar lampiran.</p>
                    </div>
                    <span class="mt-3 text-[11px] font-semibold text-teal-700 hover:underline cursor-pointer">Lihat Full Log</span>
                </div>
            </div>
        </div>

        <!-- 4. Formulir Keputusan & Rekomendasi Tindak Lanjut (BDD Hooks: catatan_tinjauan, hasil_tinjauan, Lanjutkan) -->
        <div class="bg-navy-900 text-white rounded-card shadow-xl p-6 sm:p-8 border border-navy-800">
            <div class="border-b border-navy-800 pb-4 mb-6">
                <!-- Teks Wajib BDD: "Menindaklanjuti Laporan Lowongan Kerja yang Mencurigakan" -->
                <h3 class="text-xl font-bold text-teal-400">
                    Formulir Catatan Analis
                </h3>
                <p class="text-xs text-gray-300 mt-0.5">
                    Hasil tinjauan ini akan diteruskan untuk <strong class="text-white">Menindaklanjuti Laporan Lowongan Kerja yang Mencurigakan</strong> oleh Kepala Bidang.
                </p>
            </div>

            <form method="POST" action="{{ route('tinjau-lowongan.simpan', $klasifikasi->id_klasifikasi) }}" class="space-y-5">
                @csrf

                <!-- Catatan Hasil Audit Investigasi (BDD Hook: catatan_tinjauan) -->
                <div>
                    <label for="catatan_tinjauan" class="block text-xs font-bold uppercase tracking-wider text-teal-300 mb-1">
                        Catatan Hasil Audit Investigasi <span class="text-risk-high">*</span>
                    </label>
                    <textarea id="catatan_tinjauan" name="catatan_tinjauan" rows="4" required
                        placeholder="Tuliskan temuan audit, verifikasi AHU/Kemenaker, kontak terkonfirmasi, dan rekomendasi langkah eskalasi..."
                        class="w-full px-4 py-3 rounded-control border border-navy-700 bg-navy-800 text-white text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none placeholder-gray-500">{{ old('catatan_tinjauan', $tinjauan->catatan_tinjauan ?? '') }}</textarea>
                    @error('catatan_tinjauan')
                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Hasil Tinjauan (BDD Hook: hasil_tinjauan) -->
                <div>
                    <label for="hasil_tinjauan" class="block text-xs font-bold uppercase tracking-wider text-teal-300 mb-1">
                        Hasil Tinjauan <span class="text-risk-high">*</span>
                    </label>
                    <select id="hasil_tinjauan" name="hasil_tinjauan" required
                        class="w-full sm:w-80 px-3.5 py-2.5 rounded-control border border-navy-700 bg-navy-800 text-white text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                        <option value="">Pilih Hasil Tinjauan</option>
                        <option value="Mencurigakan" {{ old('hasil_tinjauan', $tinjauan->hasil_tinjauan ?? '') === 'Mencurigakan' ? 'selected' : '' }}>Mencurigakan</option>
                        <option value="Perlu Penyelidikan Lapangan" {{ old('hasil_tinjauan', $tinjauan->hasil_tinjauan ?? '') === 'Perlu Penyelidikan Lapangan' ? 'selected' : '' }}>Perlu Penyelidikan Lapangan</option>
                        <option value="Bukan Penipuan" {{ old('hasil_tinjauan', $tinjauan->hasil_tinjauan ?? '') === 'Bukan Penipuan' ? 'selected' : '' }}>Bukan Penipuan (Lolos Verifikasi)</option>
                    </select>
                    @error('hasil_tinjauan')
                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Checkbox Notifikasi Darurat -->
                <div class="pt-1">
                    <label class="flex items-start space-x-2 text-xs text-gray-300 cursor-pointer">
                        <input type="checkbox" name="notifikasi_darurat" value="1" class="mt-0.5 rounded border-navy-700 text-teal-600 focus:ring-teal-500 bg-navy-800">
                        <span>Kirimkan notifikasi darurat peringatan merah ke seluruh pencari kerja yang menyimpan lowongan ini</span>
                    </label>
                </div>

                <!-- Tombol Aksi (BDD Hooks: Kembali ke Antrean, Tandai Blacklist, Lanjutkan) -->
                <div class="flex flex-col-reverse sm:flex-row items-center justify-between pt-4 border-t border-navy-800 gap-3">
                    <a href="{{ route('tinjau-lowongan.index') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-control border border-navy-700 text-gray-300 hover:bg-navy-800 text-xs font-semibold transition text-center">
                        Kembali ke Antrean
                    </a>

                    <div class="flex items-center space-x-3 w-full sm:w-auto">
                        {{-- TODO(TBD): Perilaku "Tandai Terkonfirmasi Penipuan (Blacklist)" belum dijelaskan di SRS.
                             §6.4.2 poin 7: "Tanyakan ke user; jangan mengarang efeknya."
                             Tombol ini di-disable sampai spesifikasi dikonfirmasi. --}}
                        <button type="button" disabled
                            class="w-full sm:w-auto px-5 py-2.5 rounded-control border border-red-700/50 text-red-300 text-xs font-semibold transition opacity-60 cursor-not-allowed"
                            title="TODO(TBD): Perilaku belum ditentukan — tanyakan ke user">
                            Tandai Terkonfirmasi Penipuan (Blacklist)
                        </button>

                        <button type="submit" class="w-full sm:w-auto px-7 py-2.5 rounded-control bg-teal-600 hover:bg-teal-500 text-white text-xs font-semibold shadow-lg transition flex items-center justify-center space-x-1.5">
                            <span>Lanjutkan</span>
                            <span class="text-[10px] text-teal-200 font-normal">(Menindaklanjuti Laporan)</span>
                            <span>→</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
