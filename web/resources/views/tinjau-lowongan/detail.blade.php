<x-app-layout>
    <div class="max-w-5xl mx-auto py-4 space-y-6">
        @php
            $pengaduan = $klasifikasi->pengaduan;
            $lowongan = $pengaduan->lowonganKerja;
            $perusahaan = $lowongan->perusahaan ?? null;
            $tinjauan = $klasifikasi->tinjauanAnalis;
            // Tingkat risiko & warna dari model HasilKlasifikasi (sama dengan halaman Hasil Pemeriksaan)
            $warna = $klasifikasi->warna_risiko;
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
                        {{ $klasifikasi->metode_analisis ?: '-' }}
                    </span>
                    <h2 class="text-2xl font-bold {{ $warna['teks'] }} mt-2">
                        {{ $klasifikasi->kategori_hasil ?: '-' }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-0.5">
                        {{ $perusahaan->nama_perusahaan ?? 'Perusahaan' }} • {{ $lowongan->posisi_lowongan }}
                    </p>
                </div>

                <!-- Blok BDD: Confidence Score -->
                <div class="{{ $warna['kotak'] }} p-4 rounded-xl border text-center sm:text-right shrink-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-700 block">
                        Confidence Score
                    </span>
                    <div class="text-4xl font-black {{ $warna['teks'] }} font-mono tracking-tight">
                        {{ number_format($klasifikasi->confidence_score, 0) }}%
                    </div>
                    <span class="text-[11px] font-bold {{ $warna['teks'] }} uppercase tracking-wider">
                        {{ $klasifikasi->label_risiko }}
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
                        {{-- Status dari tabel perusahaan (Terdaftar / Tidak Terdaftar / Belum Diverifikasi) --}}
                        <span class="inline-block ml-2 text-[10px] px-1.5 py-0.5 rounded font-semibold {{ ($perusahaan->status_terdaftar ?? null) === 'Terdaftar' ? 'bg-emerald-100 text-risk-low' : 'bg-gray-100 text-gray-700' }}">
                            {{ $perusahaan->status_terdaftar ?? '-' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Posisi Lowongan:</span>
                        <span class="text-gray-900 font-medium">{{ $lowongan->posisi_lowongan }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Kontak Recruiter / WhatsApp:</span>
                        <span class="text-gray-900 font-mono">{{ $lowongan->informasi_tambahan ?: '-' }}</span>
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
                    {{-- Semua nilai dari tabel hasil_klasifikasi --}}
                    <div class="space-y-2">
                        <div class="p-2.5 bg-gray-50 rounded border border-gray-200 flex justify-between items-center gap-3">
                            <span class="text-gray-700">Probabilitas Penipuan:</span>
                            <span class="font-bold {{ $warna['teks'] }}">{{ number_format($klasifikasi->confidence_score, 2) }} / 100</span>
                        </div>
                        <div class="p-2.5 bg-gray-50 rounded border border-gray-200 flex justify-between items-center gap-3">
                            <span class="text-gray-700">Tingkat Risiko:</span>
                            <span class="font-bold {{ $warna['teks'] }}">{{ $klasifikasi->label_risiko }}</span>
                        </div>
                        <div class="p-2.5 bg-gray-50 rounded border border-gray-200 flex justify-between items-center gap-3">
                            <span class="text-gray-700">Kategori Hasil:</span>
                            <span class="font-bold text-navy-900 text-right">{{ $klasifikasi->kategori_hasil ?: '-' }}</span>
                        </div>
                        <div class="p-2.5 bg-gray-50 rounded border border-gray-200 flex justify-between items-center gap-3">
                            <span class="text-gray-700">Metode / Versi Model:</span>
                            <span class="font-bold text-navy-900 text-right">{{ $klasifikasi->metode_analisis ?: '-' }}</span>
                        </div>
                        <div class="p-2.5 bg-gray-50 rounded border border-gray-200 flex justify-between items-center gap-3">
                            <span class="text-gray-700">Tanggal Klasifikasi:</span>
                            <span class="font-bold text-navy-900 text-right">{{ $klasifikasi->tanggal_klasifikasi ? $klasifikasi->tanggal_klasifikasi->translatedFormat('d F Y, H:i') . ' WIB' : '-' }}</span>
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
                            {{-- Bobot kata pemicu model = kontribusi x 100, bukan persen --}}
                            <p class="text-xs text-gray-600 mt-1">Bobot: <strong>{{ $ind->bobot_indikator !== null ? number_format($ind->bobot_indikator, 2) : '-' }}</strong></p>
                        </div>
                    @endforeach
                @else
                    <p class="text-sm text-gray-900">-</p>
                @endif
            </div>
        </div>

        <!-- 3. Data Pengaduan (data asli yang dimasukkan Masyarakat + hasil model) -->
        @php
            // Hanya kata pemicu dari model ML (bukan indikator lain dari seeder)
            $kataPemicu = $klasifikasi->indikatorMencurigakan
                ->where('kategori_indikator', \App\Models\IndikatorMencurigakan::KATEGORI_KATA_PEMICU);
        @endphp
        <div class="bg-white rounded-card shadow-lg p-6 sm:p-8 text-navy-900 border border-gray-100">
            <h3 class="text-lg font-bold text-navy-900 border-b border-gray-100 pb-3 mb-4">
                Data Pengaduan
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm mb-4">
                <div>
                    <span class="text-gray-500 block text-xs">Nomor Tiket:</span>
                    <strong class="text-navy-900 font-mono">{{ $pengaduan->nomor_tiket ?: '-' }}</strong>
                </div>
                <div>
                    <span class="text-gray-500 block text-xs">Tanggal Pengaduan:</span>
                    <span class="text-gray-900">{{ $pengaduan->tanggal_pengaduan ? $pengaduan->tanggal_pengaduan->translatedFormat('d F Y, H:i') . ' WIB' : '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500 block text-xs">Link Lowongan:</span>
                    @if($lowongan->link_lowongan)
                        <a href="{{ $lowongan->link_lowongan }}" target="_blank" rel="noopener noreferrer" class="text-teal-600 hover:underline break-all">{{ $lowongan->link_lowongan }}</a>
                    @else
                        <span class="text-gray-900">-</span>
                    @endif
                </div>
                <div>
                    <span class="text-gray-500 block text-xs">Sumber Lowongan:</span>
                    <span class="text-gray-900">{{ $lowongan->sumber_lowongan ?: '-' }}</span>
                </div>
            </div>

            <div class="mb-4">
                <span class="text-gray-500 block text-xs mb-1">Teks Lowongan Lengkap:</span>
                <div class="bg-gray-50 p-4 rounded-control border border-gray-200 text-xs sm:text-sm text-gray-700 leading-relaxed whitespace-pre-line break-words">{{ $lowongan->deskripsi_lowongan ?: '-' }}</div>
            </div>

            <div class="mb-4">
                <span class="text-gray-500 block text-xs mb-1">Informasi Tambahan:</span>
                <div class="bg-gray-50 p-4 rounded-control border border-gray-200 text-xs sm:text-sm text-gray-700 leading-relaxed whitespace-pre-line break-words">{{ $lowongan->informasi_tambahan ?: '-' }}</div>
            </div>

            <div>
                <span class="text-gray-500 block text-xs mb-1">Kata Pemicu dari Model (bobot):</span>
                @if($kataPemicu->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach($kataPemicu as $pemicu)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-control border border-red-200 bg-red-50/50 text-xs">
                                <strong class="text-navy-900">{{ $pemicu->nama_indikator }}</strong>
                                <span class="font-mono text-risk-high">{{ $pemicu->bobot_indikator !== null ? number_format($pemicu->bobot_indikator, 2) : '-' }}</span>
                            </span>
                        @endforeach
                    </div>
                @else
                    <span class="text-gray-900 text-sm">-</span>
                @endif
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

            @if($pengaduan->status_pengaduan !== \App\Enums\StatusKasus::SedangDitinjau)
                {{-- Tinjauan sudah selesai: kasus tidak bisa ditinjau ulang --}}
                <div class="bg-navy-800 border border-navy-700 rounded-control p-4 text-xs sm:text-sm text-gray-300">
                    Tinjauan untuk kasus ini sudah selesai. Status saat ini:
                    <strong class="text-white">{{ $pengaduan->status_pengaduan }}</strong>.
                    @if($tinjauan)
                        Hasil tinjauan: <strong class="text-white">{{ $tinjauan->hasil_tinjauan }}</strong>.
                    @endif
                </div>
                <div class="pt-4">
                    <a href="{{ route('tinjau-lowongan.index') }}" class="inline-block px-5 py-2.5 rounded-control border border-navy-700 text-gray-300 hover:bg-navy-800 text-xs font-semibold transition">
                        Kembali ke Antrean
                    </a>
                </div>
            @else
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
                        @foreach(\App\Enums\HasilTinjauan::cases() as $hasil)
                            <option value="{{ $hasil->value }}" {{ old('hasil_tinjauan') === $hasil->value ? 'selected' : '' }}>{{ $hasil->value }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">
                        "Terkonfirmasi Mencurigakan" diteruskan ke Kepala Bidang. "Tidak Terbukti" menutup kasus.
                    </p>
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
            @endif
        </div>
    </div>
</x-app-layout>
