<x-app-layout>
    <div class="space-y-6 py-2">

        {{-- ============================================================
             1. HERO BANNER — Judul & Akses Cepat
             Sesuai design.md §3.4 & §5 (Navy Dark Mode, font Playfair Display)
        ============================================================ --}}
        <div class="bg-navy-900 border border-navy-800 rounded-2xl p-7 sm:p-10 text-white shadow-2xl relative overflow-hidden">
            <div class="absolute right-0 top-0 w-80 h-80 bg-teal-600/5 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                <div>
                    <p class="text-xs uppercase tracking-widest font-bold text-teal-400 mb-2 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
                        PUSAT PENGAWASAN KETENAGAKERJAAN
                    </p>
                    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                        Dashboard <span class="font-serif italic text-teal-300">Monitoring Analis</span>
                    </h1>
                    <p class="text-sm text-gray-400 mt-2 max-w-xl leading-relaxed">
                        Pantau antrean lowongan mencurigakan, verifikasi temuan indikator bahaya AI,
                        dan kelola hasil audit sebelum eskalasi ke Kepala Bidang.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <a href="{{ route('tinjau-lowongan.index') }}"
                       class="px-5 py-2.5 bg-teal-600 hover:bg-teal-500 text-white text-sm font-semibold rounded-control shadow transition flex items-center gap-2 whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <span>Buka Antrean Pemantauan</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ============================================================
             2. KARTU STATISTIK UTAMA (4 METRIK RINGKASAN)
             Menampilkan jumlah kasus per status tahap Analis & hasil tinjauan
        ============================================================ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- 1. Kasus Perlu Tinjauan --}}
            <div class="bg-white rounded-card shadow-md p-5 border-l-4 border-amber-500 text-navy-900 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                        Antrean Baru
                    </span>
                    <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                        ⏳
                    </span>
                </div>
                <div class="text-3xl font-black text-navy-900 font-mono" data-testid="stat-perlu-tinjauan">
                    {{ $jumlahPerluTinjauan }}
                </div>
                <p class="text-xs font-bold text-navy-900 mt-1">Perlu Tinjauan</p>
                <p class="text-[11px] text-gray-500 mt-0.5">Kasus prioritas menunggu pemeriksaan awal</p>
                <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                    <span class="text-gray-400">Tindakan: Segera</span>
                    <a href="{{ route('tinjau-lowongan.index', ['status' => 'Perlu Tinjauan']) }}" class="text-teal-600 hover:underline font-semibold">
                        Lihat antrean →
                    </a>
                </div>
            </div>

            {{-- 2. Kasus Sedang Ditinjau --}}
            <div class="bg-white rounded-card shadow-md p-5 border-l-4 border-teal-600 text-navy-900 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200">
                        Investigasi Aktif
                    </span>
                    <span class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center font-bold">
                        🔍
                    </span>
                </div>
                <div class="text-3xl font-black text-teal-700 font-mono" data-testid="stat-sedang-ditinjau">
                    {{ $jumlahSedangDitinjau }}
                </div>
                <p class="text-xs font-bold text-navy-900 mt-1">Sedang Ditinjau</p>
                <p class="text-[11px] text-gray-500 mt-0.5">Kasus dalam tahap analisis bukti</p>
                <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                    <span class="text-gray-400">Status: Berjalan</span>
                    <a href="{{ route('tinjau-lowongan.index', ['status' => 'Sedang Ditinjau']) }}" class="text-teal-600 hover:underline font-semibold">
                        Lanjutkan →
                    </a>
                </div>
            </div>

            {{-- 3. Hasil Tinjauan: Terkonfirmasi Mencurigakan --}}
            <div class="bg-white rounded-card shadow-md p-5 border-l-4 border-risk-high text-navy-900 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-red-700 bg-red-50 px-2 py-0.5 rounded border border-red-200">
                        Hasil Terbukti
                    </span>
                    <span class="w-8 h-8 rounded-lg bg-red-50 text-risk-high flex items-center justify-center font-bold">
                        ⚠️
                    </span>
                </div>
                <div class="text-3xl font-black text-risk-high font-mono" data-testid="stat-terkonfirmasi">
                    {{ $jumlahTerkonfirmasi }}
                </div>
                <p class="text-xs font-bold text-navy-900 mt-1">Terkonfirmasi Mencurigakan</p>
                <p class="text-[11px] text-gray-500 mt-0.5">Diteruskan ke Kepala Bidang</p>
                <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                    <span class="text-gray-400">Eskalasi: Selesai</span>
                    <span class="text-xs text-gray-500 font-medium">Tindak Lanjut</span>
                </div>
            </div>

            {{-- 4. Hasil Tinjauan: Tidak Terbukti --}}
            <div class="bg-white rounded-card shadow-md p-5 border-l-4 border-ok text-navy-900 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                        Hasil Aman
                    </span>
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 text-ok flex items-center justify-center font-bold">
                        ✓
                    </span>
                </div>
                <div class="text-3xl font-black text-ok font-mono" data-testid="stat-tidak-terbukti">
                    {{ $jumlahTidakTerbukti }}
                </div>
                <p class="text-xs font-bold text-navy-900 mt-1">Tidak Terbukti</p>
                <p class="text-[11px] text-gray-500 mt-0.5">Laporan valid normal & kasus ditutup</p>
                <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                    <span class="text-gray-400">Status: Diarsip</span>
                    <span class="text-xs text-gray-500 font-medium">Selesai</span>
                </div>
            </div>

        </div>

        {{-- ============================================================
             3. DUA KOLOM: ANTREAN MENDESAK & RIWAYAT / WIDGET
        ============================================================ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- KOLOM KIRI (2 Kolom): Kasus Prioritas Dalam Antrean & Riwayat --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Tabel Antrean Kasus Prioritas --}}
                <div class="bg-white rounded-card shadow-md border border-gray-100 p-6 text-navy-900">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                        <div>
                            <h2 class="text-lg font-bold text-navy-900 flex items-center gap-2">
                                <span>Kasus Prioritas Dalam Antrean</span>
                                <span class="bg-red-100 text-risk-high text-xs px-2.5 py-0.5 rounded-full font-bold">
                                    {{ $totalAntrean }} Kasus
                                </span>
                            </h2>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Kasus prioritas tinggi (skor ≥ {{ config('lokerlens.ambang_prioritas', 70) }}%) yang membutuhkan tindakan Analis
                            </p>
                        </div>
                        <a href="{{ route('tinjau-lowongan.index') }}" class="text-xs text-teal-600 font-semibold hover:underline flex items-center gap-1">
                            <span>Buka Semua</span>
                            <span>→</span>
                        </a>
                    </div>

                    @if($antreanPrioritas->isEmpty())
                        <div class="py-10 text-center text-gray-400 text-xs sm:text-sm">
                            <p class="font-medium text-gray-600">Tidak ada antrean kasus mendesak saat ini.</p>
                            <p class="text-[11px] text-gray-400 mt-1">Semua lowongan prioritas tinggi telah dievaluasi.</p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-100">
                            @foreach($antreanPrioritas as $klas)
                                @php
                                    $peng = $klas->pengaduan;
                                    $low  = $peng->lowonganKerja;
                                    $per  = $low->perusahaan ?? null;
                                    $score = (int) $klas->confidence_score;
                                    $statusKasus = $peng->status_pengaduan;
                                @endphp
                                <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-gray-50/50 -mx-2 px-2 rounded-lg transition">
                                    <div class="space-y-1.5 flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-xs font-bold text-teal-800 bg-teal-50 border border-teal-200 px-2 py-0.5 rounded">
                                                {{ $peng->nomor_tiket }}
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                {{ $peng->tanggal_pengaduan ? $peng->tanggal_pengaduan->translatedFormat('d M Y') : '-' }}
                                            </span>
                                            <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold {{ $statusKasus === \App\Enums\StatusKasus::SedangDitinjau ? 'bg-teal-50 text-teal-700 border border-teal-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                                {{ $statusKasus->value ?? $statusKasus }}
                                            </span>
                                        </div>
                                        <h3 class="text-sm font-bold text-navy-900 truncate">
                                            {{ $low->posisi_lowongan ?? '-' }}
                                        </h3>
                                        <p class="text-xs text-gray-600 truncate">
                                            {{ $per->nama_perusahaan ?? '-' }}
                                        </p>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0">
                                        <div class="text-left sm:text-right">
                                            <span class="text-xl font-black font-mono {{ $score >= 75 ? 'text-risk-high' : 'text-amber-600' }}">
                                                {{ $score }}%
                                            </span>
                                            <span class="block text-[10px] uppercase font-bold text-gray-400">Keyakinan AI</span>
                                        </div>
                                        <a href="{{ route('tinjau-lowongan.detail', $klas->id_klasifikasi) }}"
                                           class="px-4 py-2 bg-navy-900 hover:bg-navy-800 text-white text-xs font-semibold rounded-control shadow transition whitespace-nowrap">
                                            Tinjau Kasus
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Riwayat Tinjauan Selesai Terakhir --}}
                <div class="bg-white rounded-card shadow-md border border-gray-100 p-6 text-navy-900">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                        <h2 class="text-base font-bold text-navy-900">
                            Tinjauan Terakhir yang Selesai
                        </h2>
                        <span class="text-xs text-gray-400 font-medium">
                            Total: {{ $totalSelesaiDitinjau }} kasus
                        </span>
                    </div>

                    @if($tinjauanTerakhir->isEmpty())
                        <div class="py-6 text-center text-gray-400 text-xs">
                            Belum ada riwayat tinjauan yang diselesaikan.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($tinjauanTerakhir as $tinjau)
                                @php
                                    $klasTinjau = $tinjau->hasilKlasifikasi;
                                    $pengTinjau = $klasTinjau->pengaduan ?? null;
                                    $lowTinjau  = $pengTinjau->lowonganKerja ?? null;
                                    $perTinjau  = $lowTinjau->perusahaan ?? null;
                                    $isCuriga   = str_contains($tinjau->hasil_tinjauan, 'Terkonfirmasi Mencurigakan');
                                @endphp
                                <div class="p-4 bg-gray-50 rounded-control border border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-xs font-bold text-teal-800">
                                                {{ $pengTinjau->nomor_tiket ?? '-' }}
                                            </span>
                                            <span class="text-xs text-gray-400">•</span>
                                            <span class="text-xs text-gray-500">
                                                {{ $tinjau->tanggal_tinjauan ? $tinjau->tanggal_tinjauan->diffForHumans() : '-' }}
                                            </span>
                                            <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold border {{ $isCuriga ? 'bg-red-50 text-risk-high border-red-200' : 'bg-emerald-50 text-ok border-emerald-200' }}">
                                                {{ $tinjau->hasil_tinjauan }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-800 font-semibold">
                                            {{ $perTinjau->nama_perusahaan ?? '-' }} — {{ $lowTinjau->posisi_lowongan ?? '-' }}
                                        </p>
                                        <p class="text-[11px] text-gray-500 italic line-clamp-2">
                                            "{{ $tinjau->catatan_tinjauan }}"
                                        </p>
                                    </div>
                                    <span class="text-xs font-semibold text-teal-700 bg-teal-50 px-2.5 py-1 rounded border border-teal-200 shrink-0 self-start sm:self-center whitespace-nowrap">
                                        Sudah Diputuskan
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

            {{-- KOLOM KANAN (1 Kolom): Widget Distribusi & Panduan --}}
            <div class="space-y-6">

                {{-- Distribusi Beban Kerja --}}
                <div class="bg-white rounded-card shadow-md border border-gray-100 p-6 text-navy-900">
                    <h2 class="text-base font-bold text-navy-900 pb-3 border-b border-gray-100">
                        Distribusi Beban Kerja
                    </h2>

                    @php
                        $pctPerlu = $totalAntrean > 0 ? round(($jumlahPerluTinjauan / $totalAntrean) * 100) : 0;
                        $pctSedang = $totalAntrean > 0 ? round(($jumlahSedangDitinjau / $totalAntrean) * 100) : 0;
                        $pctCuriga = $totalSelesaiDitinjau > 0 ? round(($jumlahTerkonfirmasi / $totalSelesaiDitinjau) * 100) : 0;
                        $pctAman = $totalSelesaiDitinjau > 0 ? round(($jumlahTidakTerbukti / $totalSelesaiDitinjau) * 100) : 0;
                    @endphp

                    <div class="space-y-4 mt-4">
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-semibold text-gray-700">Perlu Tinjauan (Antrean)</span>
                                <span class="font-bold text-navy-900">{{ $pctPerlu }}% ({{ $jumlahPerluTinjauan }})</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-amber-500 h-2.5 rounded-full transition-all duration-300" style="width: {{ $pctPerlu }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-semibold text-gray-700">Sedang Ditinjau (Proses)</span>
                                <span class="font-bold text-navy-900">{{ $pctSedang }}% ({{ $jumlahSedangDitinjau }})</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-teal-600 h-2.5 rounded-full transition-all duration-300" style="width: {{ $pctSedang }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-semibold text-gray-700">Hasil: Terkonfirmasi Penipuan</span>
                                <span class="font-bold text-navy-900">{{ $pctCuriga }}% ({{ $jumlahTerkonfirmasi }})</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-risk-high h-2.5 rounded-full transition-all duration-300" style="width: {{ $pctCuriga }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-semibold text-gray-700">Hasil: Tidak Terbukti / Aman</span>
                                <span class="font-bold text-navy-900">{{ $pctAman }}% ({{ $jumlahTidakTerbukti }})</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-ok h-2.5 rounded-full transition-all duration-300" style="width: {{ $pctAman }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Panduan Evaluasi Indikator (SRS Table B.3 / BR-05) --}}
                <div class="bg-navy-900 rounded-card shadow-md border border-navy-800 p-6 text-white">
                    <h2 class="text-base font-bold text-teal-400 mb-2">
                        Panduan Evaluasi Indikator
                    </h2>
                    <p class="text-xs text-gray-400 mb-4 leading-relaxed">
                        Pastikan memeriksa keempat kriteria anomali sebelum menetapkan status hasil tinjauan:
                    </p>
                    <ul class="text-xs space-y-2.5 text-gray-300">
                        <li class="flex items-start gap-2">
                            <span class="w-4 h-4 rounded-full bg-teal-600/30 text-teal-400 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">1</span>
                            <span><strong>Biaya fiktif:</strong> permintaan uang seragam, akomodasi, tes, atau reservasi hotel.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="w-4 h-4 rounded-full bg-teal-600/30 text-teal-400 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">2</span>
                            <span><strong>Kanal kontak tidak resmi:</strong> penggunaan email gratisan (gmail/yahoo) & chat pribadi.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="w-4 h-4 rounded-full bg-teal-600/30 text-teal-400 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">3</span>
                            <span><strong>Ketidakwajaran kompensasi:</strong> gaji fantastis tanpa syarat pengalaman.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="w-4 h-4 rounded-full bg-teal-600/30 text-teal-400 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">4</span>
                            <span><strong>Legalitas entitas:</strong> cek status terdaftar perusahaan di basis data Kemenaker.</span>
                        </li>
                    </ul>
                </div>

                {{-- Pintasan Filter Cepat --}}
                <div class="bg-white rounded-card shadow-md border border-gray-100 p-5 text-navy-900">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">
                        Pintasan Filter Antrean
                    </h3>
                    <div class="space-y-2">
                        <a href="{{ route('tinjau-lowongan.index', ['filter_skor' => 'Skor Tinggi']) }}"
                           class="block px-3 py-2 rounded-control bg-gray-50 hover:bg-gray-100 text-xs font-semibold text-gray-700 transition flex items-center justify-between">
                            <span>Kasus Skor Tinggi (≥ 75%)</span>
                            <span class="text-risk-high font-bold">→</span>
                        </a>
                        <a href="{{ route('tinjau-lowongan.index', ['status' => 'Perlu Tinjauan']) }}"
                           class="block px-3 py-2 rounded-control bg-gray-50 hover:bg-gray-100 text-xs font-semibold text-gray-700 transition flex items-center justify-between">
                            <span>Antrean Perlu Tinjauan</span>
                            <span class="text-amber-600 font-bold">→</span>
                        </a>
                        <a href="{{ route('tinjau-lowongan.index', ['status' => 'Sedang Ditinjau']) }}"
                           class="block px-3 py-2 rounded-control bg-gray-50 hover:bg-gray-100 text-xs font-semibold text-gray-700 transition flex items-center justify-between">
                            <span>Kasus Sedang Ditinjau</span>
                            <span class="text-teal-600 font-bold">→</span>
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-app-layout>

