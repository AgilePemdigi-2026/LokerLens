<x-app-layout>
    <div class="max-w-5xl mx-auto py-4 space-y-6">
        @php
            $lowongan = $pengaduan->lowonganKerja;
            $perusahaan = $lowongan->perusahaan ?? null;
            $tindakLanjut = $pengaduan->tindakLanjutKasus;
        @endphp

        <!-- Navigasi & Breadcrumb -->
        <div class="flex items-center justify-between text-xs text-gray-400">
            <div class="flex items-center space-x-2">
                <a href="{{ route('tindak-lanjut.index') }}" class="hover:text-teal-400 transition font-medium">← Kembali ke Laporan</a>
                <span>›</span>
                <span class="text-white font-medium">Formulir Laporan Polisi</span>
            </div>
            <span class="px-2.5 py-1 rounded bg-red-900/60 border border-risk-high text-red-300 font-bold text-xs uppercase">
                KRITIS (Sindikat)
            </span>
        </div>

        <!-- Header Halaman Resmi BDD & Hi-Fi (Teks Wajib: "Buat Laporan Polisi") -->
        <div class="bg-navy-900 border border-navy-800 rounded-card p-6 sm:p-8 text-white shadow-xl">
            <p class="text-xs uppercase tracking-widest font-bold text-teal-400 mb-1">
                E-LAPORAN PENEGAKAN HUKUM SIBER
            </p>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Buat Laporan Polisi
            </h1>
            <p class="text-xs sm:text-sm text-gray-300 mt-1 max-w-3xl leading-relaxed">
                Formulir pembentukan laporan resmi indikasi penipuan rekrutmen tenaga kerja kepada pihak kepolisian / penyidik tindak pidana siber.
            </p>

            <!-- Banner Pemberitahuan Kewenangan -->
            <div class="mt-4 bg-navy-800/80 border-l-4 border-teal-500 p-3.5 rounded-r-lg text-xs text-gray-300 flex items-start space-x-2.5">
                <svg class="w-4 h-4 text-teal-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>
                    <strong>Pemberitahuan Kewenangan:</strong> Dokumen ini bersifat resmi dan memiliki kekuatan hukum administrasi ketenagakerjaan. Hanya Kepala Bidang berwenang mengesahkan dan menerbitkan laporan ini.
                </span>
            </div>
        </div>

        <!-- Flash Alert Error/Success -->
        @if(session('error'))
            <div class="bg-risk-high text-white px-4 py-3 rounded-card text-xs sm:text-sm font-semibold shadow">
                {{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div class="bg-teal-700 text-white px-4 py-3 rounded-card text-xs sm:text-sm font-semibold shadow">
                {{ session('success') }}
            </div>
        @endif

        <!-- Formulir Pembuatan Laporan Polisi (6 Panel Bernomor - Bagian 6.6) -->
        <form method="POST" id="form-surat" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <!-- [ 1. Data Kasus ] (READ-ONLY - FR-ST-03) -->
            <div class="bg-white rounded-card shadow-lg p-6 text-navy-900 border border-gray-100">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <h3 class="text-base font-bold text-navy-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-navy-900 text-teal-400 flex items-center justify-center text-xs font-mono">1</span>
                        <span>Data Kasus</span>
                    </h3>
                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded font-mono font-medium">Read-Only</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm">
                    <div>
                        <span class="text-gray-500 block text-xs">Nomor Tiket Kasus:</span>
                        <strong class="font-mono text-teal-800 text-sm">{{ $pengaduan->nomor_tiket }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Tanggal Validasi Disposisi:</span>
                        <span class="font-medium text-gray-800">{{ $tindakLanjut ? $tindakLanjut->tanggal_keputusan->translatedFormat('d F Y') : now()->translatedFormat('d F Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- [ 2. Nama Perusahaan (Terlapor) ] -->
            <div class="bg-white rounded-card shadow-lg p-6 text-navy-900 border border-gray-100">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <h3 class="text-base font-bold text-navy-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-navy-900 text-teal-400 flex items-center justify-center text-xs font-mono">2</span>
                        <span>Nama Perusahaan (Terlapor)</span>
                    </h3>
                    <span class="text-xs bg-red-100 text-risk-high px-2 py-0.5 rounded font-semibold">Non-AHU / Tak Terdaftar</span>
                </div>
                <div class="text-xs sm:text-sm space-y-1">
                    <strong class="text-base text-navy-900 block font-bold">
                        {{ $perusahaan->nama_perusahaan ?? 'PT Mandiri Sukses Global' }} (Terindikasi Fiktif / Non-AHU)
                    </strong>
                    <p class="text-gray-500 text-xs">
                        Posisi Terkait: {{ $lowongan->posisi_lowongan }} • Kontak: {{ $lowongan->informasi_tambahan ?? '-' }}
                    </p>
                </div>
            </div>

            <!-- [ 3. Kronologi Kejadian ] (BDD Hook: textarea name="kronologi") -->
            <div class="bg-white rounded-card shadow-lg p-6 text-navy-900 border border-gray-100">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <h3 class="text-base font-bold text-navy-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-navy-900 text-teal-400 flex items-center justify-center text-xs font-mono">3</span>
                        <span>Kronologi Kejadian</span>
                    </h3>
                    <span class="text-xs text-gray-400 font-medium">Dapat disunting oleh Kepala Bidang</span>
                </div>
                <div>
                    <label for="kronologi" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                        Uraian Kronologi Kejadian <span class="text-risk-high">*</span>
                    </label>
                    <textarea id="kronologi" name="kronologi" rows="6" required
                        placeholder="Uraikan kronologi pengaduan, modus operandi, bukti transaksi, dan kerugian yang ditimbulkan..."
                        class="w-full px-4 py-3 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none leading-relaxed">{{ old('kronologi', $surat->kronologi_kejadian ?? $kronologiDefault) }}</textarea>
                    @error('kronologi')
                        <p class="text-xs text-risk-high mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- [ 4. Detail Pelanggaran ] (Pasal-Pasal Hukum) -->
            <div class="bg-white rounded-card shadow-lg p-6 text-navy-900 border border-gray-100">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <h3 class="text-base font-bold text-navy-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-navy-900 text-teal-400 flex items-center justify-center text-xs font-mono">4</span>
                        <span>Detail Pelanggaran</span>
                    </h3>
                    <span class="text-xs text-teal-700 bg-teal-50 px-2 py-0.5 rounded font-semibold">Berdasarkan Regulasi Hukum</span>
                </div>
                <div class="space-y-3 text-xs sm:text-sm">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" name="pasal[]" value="378_kuhp" checked class="w-4 h-4 mt-0.5 text-teal-600 rounded border-gray-300 focus:ring-teal-500">
                        <div>
                            <strong class="text-navy-900 font-semibold block">Dugaan Penipuan Rekrutmen Kerja (Pasal 378 KUHP)</strong>
                            <span class="text-gray-500 text-xs">Tindakan menguntungkan diri sendiri secara melawan hukum dengan memakai nama palsu atau martabat palsu.</span>
                        </div>
                    </label>
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" name="pasal[]" value="28_ite" checked class="w-4 h-4 mt-0.5 text-teal-600 rounded border-gray-300 focus:ring-teal-500">
                        <div>
                            <strong class="text-navy-900 font-semibold block">Penyebaran Informasi Bohong/Hoaks melalui Media Digital (Pasal 28 ayat 1 UU ITE)</strong>
                            <span class="text-gray-500 text-xs">Menyebarkan informasi bohong yang mengakibatkan kerugian materiil konsumen/pelamar kerja dalam transaksi elektronik.</span>
                        </div>
                    </label>
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" name="pasal[]" value="13_2003" checked class="w-4 h-4 mt-0.5 text-teal-600 rounded border-gray-300 focus:ring-teal-500">
                        <div>
                            <strong class="text-navy-900 font-semibold block">Pungutan Biaya Penempatan Ilegal bagi Calon Tenaga Kerja (UU No. 13/2003)</strong>
                            <span class="text-gray-500 text-xs">Larangan pemungutan biaya dalam proses penempatan tenaga kerja di luar ketentuan perundang-undangan.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- [ 5. Bukti Pendukung ] (Lampiran Berkas & Upload Tambahan) -->
            <div class="bg-white rounded-card shadow-lg p-6 text-navy-900 border border-gray-100">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <h3 class="text-base font-bold text-navy-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-navy-900 text-teal-400 flex items-center justify-center text-xs font-mono">5</span>
                        <span>Bukti Pendukung</span>
                    </h3>
                    <span class="text-xs text-gray-500">Otomatis Terbawa dari Pengaduan</span>
                </div>

                <!-- Berkas yang Terlampir -->
                                <div class="space-y-2 mb-4">
                    @forelse($pengaduan->buktiPendukung as $bukti)
                        <div class="p-3 bg-gray-50 rounded-control border border-gray-200 flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 bg-red-100 text-risk-high rounded font-mono font-bold text-[10px]">{{ strtoupper($bukti->tipe_file) }}</span>
                                <span class="font-medium text-navy-900">{{ $bukti->nama_file }} ({{ number_format($bukti->ukuran_file / 1048576, 1) }} MB)</span>
                            </div>
                            <span class="text-[11px] font-semibold text-ok bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Terlampir</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-500">Belum ada berkas bukti.</p>
                    @endforelse
                </div>

                <!-- Input Tambah Berkas (NFR-SEC-07: pdf, jpg, png) -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                        + Klik untuk tambah file tambahan (PDF, JPG, PNG, maks. 10MB)
                    </label>
                    <input type="file" name="bukti_tambahan[]" multiple accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-control file:border-0 file:text-xs file:font-semibold file:bg-navy-900 file:text-white hover:file:bg-navy-800 cursor-pointer" />
                </div>
            </div>

            <!-- [ 6. Tujuan Kepolisian ] (BDD Hook: select name="tujuan_kepolisian") -->
            <div class="bg-white rounded-card shadow-lg p-6 text-navy-900 border border-gray-100">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <h3 class="text-base font-bold text-navy-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-navy-900 text-teal-400 flex items-center justify-center text-xs font-mono">6</span>
                        <span>Tujuan Kepolisian</span>
                    </h3>
                    <span class="text-xs font-semibold text-risk-high">* Wajib Diisi</span>
                </div>
                <div>
                    <label for="tujuan_kepolisian" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                        Pilih Instansi Kepolisian Penerima Laporan <span class="text-risk-high">*</span>
                    </label>
                    <select id="tujuan_kepolisian" name="tujuan_kepolisian" required
                        class="w-full px-4 py-2.5 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none bg-white">
                        <option value="Subdit Siber Ditreskrimsus Polda Metro Jaya / Wilayah Terkait" {{ old('tujuan_kepolisian', $surat->tujuan_instansi ?? '') === 'Subdit Siber Ditreskrimsus Polda Metro Jaya / Wilayah Terkait' ? 'selected' : '' }}>
                            Subdit Siber Ditreskrimsus Polda Metro Jaya / Wilayah Terkait
                        </option>
                        <option value="Direktorat Tindak Pidana Siber Bareskrim Polri" {{ old('tujuan_kepolisian', $surat->tujuan_instansi ?? '') === 'Direktorat Tindak Pidana Siber Bareskrim Polri' ? 'selected' : '' }}>
                            Direktorat Tindak Pidana Siber Bareskrim Polri
                        </option>
                        <option value="Satreskrim Polres Wilayah Setempat" {{ old('tujuan_kepolisian', $surat->tujuan_instansi ?? '') === 'Satreskrim Polres Wilayah Setempat' ? 'selected' : '' }}>
                            Satreskrim Polres Wilayah Setempat
                        </option>
                    </select>
                </div>
            </div>

            <!-- Blok Pejabat Penandatangan (Tanda Tangan Digital) -->
            <div class="bg-gray-50 rounded-card p-6 border border-gray-200 text-navy-900 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <span class="text-[11px] uppercase tracking-wider font-bold text-gray-500 block">Pejabat Pengesah Laporan</span>
                    <strong class="text-base font-bold text-navy-900 block mt-0.5">{{ Auth::user()->name }}</strong>
                    <span class="text-xs text-gray-600">Kepala Bidang Pengawasan Ketenagakerjaan Digital</span>
                </div>
                <div class="px-4 py-2 bg-white rounded-lg border border-teal-500/50 text-teal-700 text-xs font-bold font-mono shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span>TERVERIFIKASI TANDA TANGAN ELEKTRONIK</span>
                </div>
            </div>

            <!-- Tombol Aksi: Simpan Draft & Terbitkan Laporan (Bagian 6.6 & 9) -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-navy-800">
                <button type="submit" formaction="{{ route('buat-surat.draft', $pengaduan->id_pengaduan) }}"
                    class="w-full sm:w-auto px-6 py-2.5 rounded-control border border-gray-300 bg-white text-gray-800 hover:bg-gray-50 text-xs font-semibold shadow transition">
                    Simpan Draft
                </button>

                <button type="submit" formaction="{{ route('buat-surat.terbitkan', $pengaduan->id_pengaduan) }}"
                    class="w-full sm:w-auto px-8 py-2.5 rounded-control bg-teal-600 hover:bg-teal-500 text-white text-xs font-bold shadow-xl transition flex items-center justify-center space-x-2">
                    <span>Terbitkan Laporan</span>
                    <span>→</span>
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
