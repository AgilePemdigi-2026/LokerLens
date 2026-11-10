<x-app-layout>
    <div class="max-w-4xl mx-auto py-4" x-data="{
        submitting: false,
        progress: 0,
        stage: 1,
        resetForm() {
            document.getElementById('form-periksa').reset();
        },
        handleSubmit(e) {
            const nama = document.getElementById('nama_perusahaan').value.trim();
            const posisi = document.getElementById('posisi_lowongan').value.trim();
            const deskripsi = document.getElementById('deskripsi_lowongan').value.trim();

            if (!nama || !posisi || deskripsi.length < 50) {
                // Biarkan HTML5 validation / backend validation menangani atau tampilkan error
                return true;
            }

            this.submitting = true;
            let timer = setInterval(() => {
                if (this.progress < 90) {
                    this.progress += 15;
                    if (this.progress >= 30) this.stage = 2;
                    if (this.progress >= 70) this.stage = 3;
                } else {
                    clearInterval(timer);
                }
            }, 300);
            return true;
        }
    }">
        <!-- Header Halaman (Bagian 6.3) -->
        <div class="mb-6">
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Periksa Lowongan Kerja
            </h1>
            <p class="text-sm text-gray-300 mt-1">
                Kenali indikasi penipuan sebelum melanjutkan proses lamaran kerja.
            </p>
        </div>

        <!-- Notifikasi Error Validasi Resmi (FR-PL-02, FR-PL-10, NFR-SAF-05) -->
        @if ($errors->any())
            <div class="mb-6 bg-red-900/40 border-l-4 border-risk-high p-4 rounded-r-xl shadow-lg">
                <div class="flex items-center space-x-3 text-red-200">
                    <svg class="h-6 w-6 text-risk-high shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="font-bold text-sm">Data belum lengkap, mohon lengkapi terlebih dahulu</p>
                        <p class="text-xs text-red-300 mt-0.5">Pastikan Nama Perusahaan, Posisi, dan Deskripsi Lowongan (minimal 50 karakter) telah terisi dengan benar.</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Panel Progres Loading Simulasi (Gambar 2.6) -->
        <div x-show="submitting" x-cloak class="mb-6 bg-navy-900 border border-teal-500/30 rounded-card p-6 text-white shadow-2xl animate-pulse">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold text-teal-400 flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5 text-teal-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Sedang menganalisis lowongan...
                </h3>
                <span class="text-sm font-extrabold text-teal-300 font-mono" x-text="progress + '%'"></span>
            </div>
            <p class="text-xs text-gray-300 mb-4 leading-relaxed">
                Kami memeriksa pola deskripsi, informasi perusahaan, dan indikasi bahaya. Formulir dikunci sementara agar pemeriksaan tidak diulang dua kali.
            </p>
            <div class="w-full bg-navy-800 rounded-full h-3 overflow-hidden p-0.5 border border-navy-700 mb-4">
                <div class="bg-gradient-to-r from-teal-500 to-teal-300 h-2 rounded-full transition-all duration-300" :style="'width: ' + progress + '%'"></div>
            </div>
            <!-- Tiga Tahap Indikator -->
            <div class="grid grid-cols-3 gap-2 text-xs">
                <div class="flex items-center space-x-1.5" :class="stage >= 1 ? 'text-teal-400 font-semibold' : 'text-gray-500'">
                    <span>✓</span>
                    <span>Informasi diterima</span>
                </div>
                <div class="flex items-center space-x-1.5" :class="stage >= 2 ? 'text-teal-400 font-semibold' : 'text-gray-500'">
                    <span x-text="stage >= 2 ? '✓' : '○'"></span>
                    <span>Memeriksa indikasi bahaya</span>
                </div>
                <div class="flex items-center space-x-1.5" :class="stage >= 3 ? 'text-teal-400 font-semibold' : 'text-gray-500'">
                    <span x-text="stage >= 3 ? '✓' : '○'"></span>
                    <span>Menyusun hasil</span>
                </div>
            </div>
        </div>

        <!-- Kartu Utama: Formulir Analisis Lowongan -->
        <div class="bg-white rounded-card shadow-xl p-6 sm:p-8 text-navy-900 border border-gray-100 relative">
            <div class="flex justify-between items-start border-b border-gray-100 pb-4 mb-6">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-navy-900">
                        Formulir Analisis Lowongan
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                        Lengkapi informasi lowongan untuk mengenali potensi penipuan.
                    </p>
                </div>
                <span class="text-xs font-semibold text-gray-400 bg-gray-100 px-2.5 py-1 rounded">
                    <span class="text-risk-high">*</span> Wajib diisi
                </span>
            </div>

            {{-- Selama memeriksa, isian dikunci dengan readonly (bukan disabled): kolom yang disabled tidak ikut terkirim ke server --}}
            <form id="form-periksa" method="POST" action="{{ route('periksa-lowongan.store') }}" @submit="handleSubmit($event)" class="space-y-6">
                @csrf

                <!-- Link Lowongan Kerja (Opsional) -->
                <div>
                    <label for="link_lowongan" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                        Link Lowongan Kerja <span class="text-gray-400 font-normal lowercase">(opsional)</span>
                    </label>
                    <input type="url" id="link_lowongan" name="link_lowongan" value="{{ old('link_lowongan') }}" :readonly="submitting"
                        placeholder="https://www.linkedin.com/jobs/view/..."
                        class="w-full px-4 py-2.5 rounded-control border border-gray-300 text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none transition disabled:bg-gray-100" />
                    <p class="text-[11px] text-gray-500 mt-1">
                        Sumber: LinkedIn, JobStreet, Glints, Instagram, WhatsApp, dan situs lainnya.
                    </p>
                </div>

                <!-- Baris Dua Kolom: Nama Perusahaan & Posisi Lowongan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Nama Perusahaan (Wajib, BDD hook) -->
                    <div>
                        <label for="nama_perusahaan" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Nama Perusahaan <span class="text-risk-high">*</span>
                        </label>
                        <input type="text" id="nama_perusahaan" name="nama_perusahaan" value="{{ old('nama_perusahaan') }}" required :readonly="submitting"
                            placeholder="Contoh: PT Maju Bersama Sejahtera"
                            class="w-full px-4 py-2.5 rounded-control border text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none transition disabled:bg-gray-100 {{ $errors->has('nama_perusahaan') ? 'border-risk-high' : 'border-gray-300' }}" />
                    </div>

                    <!-- Posisi Lowongan (Wajib, BDD hook) -->
                    <div>
                        <label for="posisi_lowongan" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Posisi Lowongan <span class="text-risk-high">*</span>
                        </label>
                        <input type="text" id="posisi_lowongan" name="posisi_lowongan" value="{{ old('posisi_lowongan') }}" required :readonly="submitting"
                            placeholder="Contoh: Staff Administrasi / Customer Service"
                            class="w-full px-4 py-2.5 rounded-control border text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none transition disabled:bg-gray-100 {{ $errors->has('posisi_lowongan') ? 'border-risk-high' : 'border-gray-300' }}" />
                    </div>
                </div>

                <!-- Deskripsi Lowongan (Wajib, Min. 50 Karakter, BDD hook) -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label for="deskripsi_lowongan" class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                            Deskripsi Lowongan <span class="text-risk-high">*</span>
                        </label>
                        <span class="text-xs text-gray-500 font-medium">Minimal 50 karakter</span>
                    </div>
                    <textarea id="deskripsi_lowongan" name="deskripsi_lowongan" rows="5" required minlength="50" :readonly="submitting"
                        placeholder="Salin dan tempel deskripsi lowongan, kualifikasi, benefit, dan instruksi pendaftaran di sini..."
                        class="w-full px-4 py-3 rounded-control border text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none transition disabled:bg-gray-100 {{ $errors->has('deskripsi_lowongan') ? 'border-risk-high' : 'border-gray-300' }}">{{ old('deskripsi_lowongan') }}</textarea>
                </div>

                <!-- Informasi Tambahan & Kontak (Opsional) -->
                <div>
                    <label for="informasi_tambahan" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                        Informasi Tambahan & Kontak <span class="text-gray-400 font-normal lowercase">(opsional)</span>
                    </label>
                    <input type="text" id="informasi_tambahan" name="informasi_tambahan" value="{{ old('informasi_tambahan') }}" :readonly="submitting"
                        placeholder="Cantumkan kontak perekrut (email, WhatsApp), lokasi wawancara, atau informasi lain yang perlu diperiksa..."
                        class="w-full px-4 py-2.5 rounded-control border border-gray-300 text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none transition disabled:bg-gray-100" />
                </div>

                <!-- Checklist Cepat Indikasi Bahaya (Blok Gelap di dalam kartu - Bagian 6.3) -->
                <div class="bg-navy-900 text-white rounded-control p-5">
                    <div class="mb-3">
                        <h4 class="text-sm font-bold text-teal-400">
                            Checklist Cepat Indikasi Bahaya
                        </h4>
                        <p class="text-xs text-gray-300">
                            Tandai kondisi yang Anda temukan pada lowongan.
                        </p>
                    </div>

                    <div class="space-y-2.5 text-xs sm:text-sm">
                        <!-- 1. Permintaan biaya -->
                        <label class="flex items-start space-x-3 cursor-pointer hover:text-teal-200">
                            <input type="checkbox" name="checklist[]" value="biaya" @click="submitting && $event.preventDefault()"
                                class="w-4 h-4 mt-0.5 rounded border-gray-600 text-teal-600 focus:ring-teal-500 bg-navy-800">
                            <span>Ada permintaan biaya pendaftaran / tes / seragam</span>
                        </label>

                        <!-- 2. Email gratisan -->
                        <label class="flex items-start space-x-3 cursor-pointer hover:text-teal-200">
                            <input type="checkbox" name="checklist[]" value="email_gratis" @click="submitting && $event.preventDefault()"
                                class="w-4 h-4 mt-0.5 rounded border-gray-600 text-teal-600 focus:ring-teal-500 bg-navy-800">
                            <span>Menggunakan alamat email gratisan (gmail/yahoo/hotmail)</span>
                        </label>

                        <!-- 3. Lokasi wawancara mencurigakan -->
                        <label class="flex items-start space-x-3 cursor-pointer hover:text-teal-200">
                            <input type="checkbox" name="checklist[]" value="lokasi" @click="submitting && $event.preventDefault()"
                                class="w-4 h-4 mt-0.5 rounded border-gray-600 text-teal-600 focus:ring-teal-500 bg-navy-800">
                            <span>Lokasi interview di luar / penginapan / alamat mencurigakan</span>
                        </label>

                        <!-- 4. Gaji tidak masuk akal -->
                        <label class="flex items-start space-x-3 cursor-pointer hover:text-teal-200">
                            <input type="checkbox" name="checklist[]" value="gaji" @click="submitting && $event.preventDefault()"
                                class="w-4 h-4 mt-0.5 rounded border-gray-600 text-teal-600 focus:ring-teal-500 bg-navy-800">
                            <span>Menawarkan gaji tidak masuk akal tanpa kualifikasi spesifik</span>
                        </label>
                    </div>
                </div>

                <!-- Tombol Aksi: Reset Form & Periksa Sekarang (Bagian 6.3 & 9) -->
                <div class="flex flex-col-reverse sm:flex-row items-center justify-between pt-4 border-t border-gray-100 gap-3">
                    <button type="button" @click="resetForm()" :disabled="submitting"
                        class="w-full sm:w-auto px-5 py-2.5 rounded-control border border-gray-300 text-gray-700 hover:bg-gray-100 text-sm font-medium transition flex items-center justify-center space-x-2 disabled:opacity-50">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Reset Form</span>
                    </button>

                    <button type="submit" :disabled="submitting"
                        class="w-full sm:w-auto px-7 py-2.5 rounded-control bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold shadow-lg transition flex items-center justify-center space-x-2 disabled:opacity-60 disabled:cursor-not-allowed">
                        <template x-if="!submitting">
                            <div class="flex items-center space-x-2">
                                <span>Periksa Sekarang</span>
                                <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </template>
                        <template x-if="submitting">
                            <div class="flex items-center space-x-2">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Sedang Memeriksa...</span>
                            </div>
                        </template>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
