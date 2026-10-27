<x-app-layout>
    <div class="max-w-4xl mx-auto py-4 space-y-6">
        <!-- Breadcrumb & Navigasi Atas -->
        <div class="flex items-center justify-between text-xs text-gray-400">
            <div class="flex items-center space-x-2">
                <a href="{{ route('periksa-lowongan.index') }}" class="hover:text-teal-400 transition">Periksa Lowongan</a>
                <span>›</span>
                <span class="text-white font-medium">Tiket {{ $pengaduan->nomor_tiket }}</span>
            </div>
            <a href="{{ route('periksa-lowongan.index') }}" class="text-teal-400 hover:underline flex items-center gap-1 font-medium">
                ← Periksa Lowongan Lain
            </a>
        </div>

        @php
            $klasifikasi = $pengaduan->hasilKlasifikasi;
            $score = $klasifikasi ? $klasifikasi->confidence_score : 0;
            $ambang = config('lokerlens.ambang_prioritas');
            $lowongan = $pengaduan->lowonganKerja;
            $perusahaan = $lowongan->perusahaan ?? null;
            // Aturan tingkat risiko & warna ada di model HasilKlasifikasi (sama dengan detail Analis)
            $isPalsu = $klasifikasi?->is_palsu;
            $labelRisiko = $klasifikasi?->label_risiko;
            $warna = $klasifikasi?->warna_risiko ?? ['teks' => '', 'bar' => '', 'badge' => ''];
            $warnaTeks = $warna['teks'];
            $warnaBar = $warna['bar'];
            $warnaBadge = $warna['badge'];
            // Lebar bar selalu 0–100%
            $lebarBar = max(0, min(100, (float) $score));
        @endphp

        @if(! $klasifikasi)
        <!-- 1a. API ML mati/timeout: pengaduan tersimpan, klasifikasi menyusul -->
        <div class="bg-white rounded-card shadow-xl p-6 sm:p-8 text-navy-900 border border-gray-100">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-100 text-risk-mid border border-amber-200">
                    {{ \App\Constants\LokerLensCopy::MENUNGGU_KLASIFIKASI }}
                </span>
                <span class="text-xs text-gray-500 font-mono">
                    ID Tiket: <strong>{{ $pengaduan->nomor_tiket }}</strong>
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-navy-900">
                Hasil Pemeriksaan
            </h1>
            <p class="text-sm text-gray-600 mt-0.5 mb-4">
                {{ $perusahaan->nama_perusahaan ?? '-' }} • {{ $lowongan->posisi_lowongan }}
            </p>
            <div class="bg-amber-50 border-l-4 border-risk-mid p-4 rounded-r-lg text-sm text-amber-900 leading-relaxed" role="alert">
                {{ \App\Constants\LokerLensCopy::KLASIFIKASI_TERTUNDA }}
            </div>
        </div>
        @else

        <!-- 1. Kartu Ringkasan Risiko (Gambar 2.7) -->
        <div class="bg-white rounded-card shadow-xl p-6 sm:p-8 text-navy-900 border border-gray-100">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                        Analisis selesai
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $warnaBadge }}">
                        {{ $labelRisiko }}
                    </span>
                </div>
                <span class="text-xs text-gray-500 font-mono">
                    ID Tiket: <strong>{{ $pengaduan->nomor_tiket }}</strong>
                </span>
            </div>

            <!-- Teks Resmi BDD: "Hasil Pemeriksaan" -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 border-b border-gray-100 pb-6 mb-6">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-navy-900">
                        Hasil Pemeriksaan
                    </h1>
                    <h2 class="text-lg font-bold {{ $warnaTeks }} mt-1 flex items-center gap-1.5">
                        @unless($isPalsu)
                            <!-- Ikon centang untuk hasil asli -->
                            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        @endunless
                        <span>{{ $klasifikasi->kategori_hasil }}</span>
                    </h2>
                    <p class="text-sm text-gray-600 mt-0.5">
                        {{ $perusahaan->nama_perusahaan ?? '-' }} • {{ $lowongan->posisi_lowongan }}
                    </p>
                </div>

                <!-- Skor Confidence Besar -->
                <div class="flex items-center gap-4 bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <div>
                        <div class="text-4xl sm:text-5xl font-black {{ $warnaTeks }} font-mono tracking-tight">
                            {{ number_format($score, 0) }}%
                        </div>
                        <div class="text-xs font-bold uppercase tracking-wider text-gray-600 mt-1">
                            Probabilitas penipuan
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bar Skor (lebar = skor, warna = tingkat risiko) -->
            <div class="mb-6">
                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                    <div class="h-3 rounded-full transition-all duration-500 {{ $warnaBar }}" style="width: {{ $lebarBar }}%"></div>
                </div>
                <div class="flex justify-between text-[11px] text-gray-400 mt-1">
                    <span>0% (Asli/Aman)</span>
                    <span>50% (Perlu Diwaspadai)</span>
                    <span>100% (Sangat Mencurigakan)</span>
                </div>
            </div>

            @if($isPalsu)
            <!-- Banner Peringatan Merah Resmi (NFR-SAF-01 & BR-04) -->
            <div class="bg-red-50 border-l-4 border-risk-high p-4 rounded-r-lg text-xs sm:text-sm text-red-900 leading-relaxed">
                <div class="flex items-start space-x-2.5">
                    <svg class="w-5 h-5 text-risk-high shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <strong class="font-bold">PERINGATAN SISTEM:</strong> Jangan melakukan pembayaran, transfer dana, atau mengirim data pribadi sensitif (KTP/KK) sebelum identitas perekrut dan keaslian lowongan terverifikasi secara resmi.
                        <p class="text-xs text-red-700 mt-1 italic">
                            * Catatan: Hasil analisis sistem LokerLens merupakan rekomendasi pendukung keputusan berbasis kecerdasan buatan, bukan penetapan penipuan secara hukum.
                        </p>
                    </div>
                </div>
            </div>
            @else
            <!-- Kotak Info Hijau untuk hasil asli (NFR-SAF-01 & BR-04) -->
            <div class="bg-emerald-50 border-l-4 border-risk-low p-4 rounded-r-lg text-xs sm:text-sm text-gray-700 leading-relaxed">
                <div class="flex items-start space-x-2.5">
                    <svg class="w-5 h-5 text-risk-low shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        Lowongan ini tidak menunjukkan ciri penipuan yang kuat. Tetap pastikan lowongan berasal dari situs atau akun resmi perusahaan dan jangan pernah membayar biaya apa pun untuk melamar.
                        <p class="text-xs text-gray-500 mt-1 italic">
                            * Catatan: Hasil analisis sistem LokerLens merupakan rekomendasi pendukung keputusan berbasis kecerdasan buatan, bukan penetapan penipuan secara hukum.
                        </p>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- 2. Informasi Lowongan yang Diperiksa -->
        <div class="bg-white rounded-card shadow-lg p-6 text-navy-900 border border-gray-100">
            <h3 class="text-base font-bold text-navy-900 border-b border-gray-100 pb-3 mb-4">
                Informasi Lowongan
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm mb-4">
                <div>
                    <span class="text-gray-500 block text-xs">Nama Perusahaan:</span>
                    <strong class="text-navy-900 text-sm font-semibold">{{ $perusahaan->nama_perusahaan ?? '-' }}</strong>
                </div>
                <div>
                    <span class="text-gray-500 block text-xs">Posisi Ditawarkan:</span>
                    <strong class="text-navy-900 text-sm font-semibold">{{ $lowongan->posisi_lowongan }}</strong>
                </div>
                <div>
                    <span class="text-gray-500 block text-xs">Link Lowongan:</span>
                    @if($lowongan->link_lowongan)
                        <a href="{{ $lowongan->link_lowongan }}" target="_blank" rel="noopener noreferrer" class="text-teal-600 hover:underline break-all">
                            {{ $lowongan->link_lowongan }}
                        </a>
                    @else
                        <span class="text-gray-400 italic">Tidak dilampirkan</span>
                    @endif
                </div>
                <div>
                    <span class="text-gray-500 block text-xs">Informasi Tambahan / Kontak:</span>
                    <span class="text-gray-800">{{ $lowongan->informasi_tambahan ?? '-' }}</span>
                </div>
            </div>

            <div>
                <span class="text-gray-500 block text-xs mb-1">Kutipan Deskripsi Lowongan:</span>
                <div class="bg-gray-50 p-4 rounded-control border border-gray-200 text-xs sm:text-sm text-gray-700 leading-relaxed font-sans max-h-48 overflow-y-auto">
                    {{ $lowongan->deskripsi_lowongan }}
                </div>
            </div>
        </div>

        @if($klasifikasi)
        <!-- 3. Indikasi Bahaya yang Terdeteksi & Kata Pemicu (Wajib BDD Hook: id/class 'kata-pemicu') -->
        <div class="bg-white rounded-card shadow-lg p-6 text-navy-900 border border-gray-100">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                <h3 class="text-base font-bold text-navy-900">
                    Indikasi Bahaya yang Terdeteksi
                </h3>
                <span class="text-xs bg-red-100 text-risk-high px-2 py-0.5 rounded font-bold">
                    {{ $klasifikasi && $klasifikasi->indikatorMencurigakan ? $klasifikasi->indikatorMencurigakan->count() : 0 }} Indikator
                </span>
            </div>

            <!-- Kontainer Resmi BDD hook kata-pemicu -->
            <div id="kata-pemicu" class="kata-pemicu space-y-3" data-testid="kata-pemicu">
                @if($klasifikasi && $klasifikasi->indikatorMencurigakan && $klasifikasi->indikatorMencurigakan->count() > 0)
                    @foreach($klasifikasi->indikatorMencurigakan as $ind)
                        <div class="bg-red-50/70 border border-red-200 rounded-control p-3.5 flex items-start space-x-3">
                            <span class="w-6 h-6 rounded-full bg-risk-high text-white flex items-center justify-center text-xs shrink-0 mt-0.5 font-bold">!</span>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs sm:text-sm font-bold text-red-950">
                                        {{ $ind->nama_indikator }}
                                    </h4>
                                    <span class="text-[11px] font-semibold text-risk-high bg-white px-2 py-0.5 rounded border border-red-200">
                                        {{ $ind->kategori_indikator }}
                                    </span>
                                </div>
                                <p class="text-xs text-red-800 mt-1 font-mono">
                                    {{-- Kata pemicu dari model = nama indikatornya sendiri; data lama/seeder memakai kata pertama --}}
                                    Kata / frasa pemicu terdeteksi: <strong>"{{ $ind->kategori_indikator === \App\Models\IndikatorMencurigakan::KATEGORI_KATA_PEMICU ? $ind->nama_indikator : strtolower(explode(' ', $ind->nama_indikator)[0]) }}"</strong>
                                </p>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Tidak ada kata yang mendorong hasil ke arah penipuan -->
                    <p class="text-sm text-gray-600">Tidak ada kata pemicu yang mendorong hasil ke arah penipuan.</p>
                @endif
            </div>
        </div>
        @endif

        <!-- 4. Rekomendasi Tindak Lanjut -->
        <div class="bg-navy-900 rounded-card shadow-lg p-6 text-white border border-navy-800">
            <h3 class="text-base font-bold text-teal-400 mb-3">
                Rekomendasi Tindak Lanjut
            </h3>
            <ul class="text-xs sm:text-sm text-gray-300 space-y-2 list-disc list-inside">
                <li><strong>Verifikasi kanal resmi:</strong> Cari situs web perusahaan dengan domain berbayar/resmi dan hubungi call center pusat.</li>
                <li><strong>Batasi uang & data:</strong> Jangan pernah mentransfer uang dengan alasan reservasi tiket, seragam, atau materi tes.</li>
                <li><strong>Simpan bukti komunikasi:</strong> Ambil tangkapan layar (screenshot) obrolan atau email tawaran kerja sebagai arsip bukti.</li>
            </ul>

            <div class="mt-6 pt-4 border-t border-navy-800 flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('beranda') }}" class="px-5 py-2 rounded-control bg-navy-800 hover:bg-navy-700 text-gray-200 text-xs font-semibold transition">
                    Lihat Riwayat
                </a>
                <a href="{{ route('periksa-lowongan.index') }}" class="px-6 py-2 rounded-control bg-teal-600 hover:bg-teal-500 text-white text-xs font-semibold shadow transition flex items-center space-x-1.5">
                    <span>Periksa Lowongan Lain</span>
                    <span>→</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
