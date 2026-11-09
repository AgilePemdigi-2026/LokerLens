<x-app-layout>
    <div class="space-y-6 py-2">

        {{-- ============================================================
             1. HERO BANNER — Judul & Akses Cepat Eksekutif
             Sesuai design.md §3.5 & §5 (Navy Dark Mode, font Playfair Display)
        ============================================================ --}}
        <div class="bg-navy-900 border border-navy-800 rounded-2xl p-7 sm:p-10 text-white shadow-2xl relative overflow-hidden">
            <div class="absolute right-0 top-0 w-80 h-80 bg-teal-600/5 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                <div>
                    <p class="text-xs uppercase tracking-widest font-bold text-teal-400 mb-2 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
                        PUSAT PENGAMBILAN KEPUTUSAN & PENEGAKAN HUKUM
                    </p>
                    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                        Dashboard <span class="font-serif italic text-teal-300">Kepala Bidang</span>
                    </h1>
                    <p class="text-sm text-gray-400 mt-2 max-w-xl leading-relaxed">
                        Evaluasi laporan terindikasi pidana ketenagakerjaan dari Analis,
                        tetapkan keputusan tindak lanjut hukum, dan awasi penerbitan laporan kepolisian resmi.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <a href="{{ route('tindak-lanjut.index') }}"
                       class="px-5 py-2.5 bg-teal-600 hover:bg-teal-500 text-white text-sm font-semibold rounded-control shadow transition flex items-center gap-2 whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span>Buka Antrean Laporan</span>
                        @if($jumlahMenunggu > 0)
                            <span class="bg-risk-high text-white text-xs px-2 py-0.5 rounded-full font-bold ml-1">
                                {{ $jumlahMenunggu }}
                            </span>
                        @endif
                    </a>
                </div>
            </div>
        </div>

        {{-- ============================================================
             2. KARTU STATISTIK UTAMA (4 METRIK TAHAP KEPALA BIDANG)
             Kriteria Selesai: Menampilkan ringkasan status tahap Kepala Bidang
             (Menunggu Keputusan, Tindak Lanjut Disetujui, Tidak Ditindaklanjuti, Selesai)
        ============================================================ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- 1. Menunggu Keputusan --}}
            <div class="bg-white rounded-card shadow-md p-5 border-l-4 border-amber-500 text-navy-900 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                        Perlu Keputusan
                    </span>
                    <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-base">
                        ⚖️
                    </span>
                </div>
                <div class="text-3xl font-black text-navy-900 font-mono" data-testid="stat-menunggu-keputusan">
                    {{ $jumlahMenunggu }}
                </div>
                <p class="text-xs font-bold text-navy-900 mt-1">Menunggu Keputusan</p>
                <p class="text-[11px] text-gray-500 mt-0.5">Laporan menunggu evaluasi dan penetapan</p>
                <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                    <span class="text-gray-400">Prioritas: Mendesak</span>
                    <a href="{{ route('tindak-lanjut.index', ['status' => 'Menunggu Keputusan']) }}" class="text-teal-600 hover:underline font-semibold">Tindaklanjuti →</a>
                </div>
            </div>

            {{-- 2. Tindak Lanjut Disetujui --}}
            <div class="bg-white rounded-card shadow-md p-5 border-l-4 border-blue-600 text-navy-900 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                        Eskalasi Hukum
                    </span>
                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-base">
                        📝
                    </span>
                </div>
                <div class="text-3xl font-black text-blue-700 font-mono" data-testid="stat-tindak-lanjut-disetujui">
                    {{ $jumlahDisetujui }}
                </div>
                <p class="text-xs font-bold text-navy-900 mt-1">Tindak Lanjut Disetujui</p>
                <p class="text-[11px] text-gray-500 mt-0.5">Disetujui untuk pelaporan ke kepolisian</p>
                <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                    <span class="text-gray-400">Tahap: Buat Surat</span>
                    <a href="{{ route('tindak-lanjut.index', ['status' => 'Tindak Lanjut Disetujui']) }}" class="text-teal-600 hover:underline font-semibold">Lihat draf →</a>
                </div>
            </div>

            {{-- 3. Tidak Ditindaklanjuti --}}
            <div class="bg-white rounded-card shadow-md p-5 border-l-4 border-gray-400 text-navy-900 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-700 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                        Diarsipkan
                    </span>
                    <span class="w-8 h-8 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-base">
                        📁
                    </span>
                </div>
                <div class="text-3xl font-black text-gray-700 font-mono" data-testid="stat-tidak-ditindaklanjuti">
                    {{ $jumlahDitolak }}
                </div>
                <p class="text-xs font-bold text-navy-900 mt-1">Tidak Ditindaklanjuti</p>
                <p class="text-[11px] text-gray-500 mt-0.5">Tidak cukup bukti pidana & diarsipkan</p>
                <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                    <span class="text-gray-400">Status: Diarsipkan</span>
                    <a href="{{ route('tindak-lanjut.index', ['status' => 'Tidak Ditindaklanjuti']) }}" class="text-teal-600 hover:underline font-semibold">Buka arsip →</a>
                </div>
            </div>

            {{-- 4. Kasus Selesai / Dokumen Terbit --}}
            <div class="bg-white rounded-card shadow-md p-5 border-l-4 border-ok text-navy-900 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                        Surat Terbit
                    </span>
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 text-ok flex items-center justify-center font-bold text-base">
                        🚔
                    </span>
                </div>
                <div class="text-3xl font-black text-ok font-mono" data-testid="stat-selesai">
                    {{ $jumlahSelesai }}
                </div>
                <p class="text-xs font-bold text-navy-900 mt-1">Kasus Selesai</p>
                <p class="text-[11px] text-gray-500 mt-0.5">Laporan polisi resmi berhasil diterbitkan</p>
                <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                    <span class="text-gray-400">Surat Resmi: {{ $jumlahSuratTerbit }}</span>
                    <a href="{{ route('tindak-lanjut.index', ['status' => 'Selesai']) }}" class="text-teal-600 hover:underline font-semibold">Lihat kasus →</a>
                </div>
            </div>

        </div>

        {{-- ============================================================
             3. DUA KOLOM: ANTREAN KEPUTUSAN & DOKUMEN SURAT TERBIT
        ============================================================ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Kolom Kiri (2 Kolom): Antrean Menunggu Keputusan & Riwayat Kasus --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Tabel Antrean Menunggu Keputusan --}}
                <div class="bg-white rounded-card shadow-md border border-gray-100 p-6 text-navy-900">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                        <div>
                            <h2 class="text-lg font-bold text-navy-900 flex items-center gap-2">
                                <span>Antrean Menunggu Keputusan</span>
                                @if($jumlahMenunggu > 0)
                                    <span class="bg-amber-100 text-amber-800 text-xs px-2.5 py-0.5 rounded-full font-bold">
                                        {{ $jumlahMenunggu }} Laporan Mendesak
                                    </span>
                                @endif
                            </h2>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Kasus yang telah selesai diverifikasi Analis dan memerlukan keputusan eskalasi hukum
                            </p>
                        </div>
                        <a href="{{ route('tindak-lanjut.index') }}" class="text-xs text-teal-600 hover:underline font-semibold shrink-0">
                            Lihat Semua →
                        </a>
                    </div>

                    @if($antreanKeputusan->isEmpty())
                        <div class="py-12 text-center">
                            <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3 text-xl">
                                ✓
                            </div>
                            <h3 class="text-sm font-bold text-navy-900">Semua Laporan Telah Diputuskan</h3>
                            <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                                Tidak ada laporan baru yang menunggu keputusan. Antrean eksekutif dalam keadaan bersih.
                            </p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-100 mt-2">
                            @foreach($antreanKeputusan as $kasus)
                                @php
                                    $lowongan   = $kasus->lowonganKerja;
                                    $perusahaan = $lowongan?->perusahaan;
                                    $klasifikasi = $kasus->hasilKlasifikasi;
                                    $tinjauan   = $klasifikasi?->tinjauanAnalis;
                                @endphp
                                <div class="py-4 hover:bg-gray-50/60 rounded-xl px-2 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="space-y-1.5 flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-xs font-bold text-navy-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                                {{ $kasus->nomor_tiket }}
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                {{ $kasus->tanggal_pengaduan ? $kasus->tanggal_pengaduan->translatedFormat('d M Y, H:i') : '-' }} WIB
                                            </span>
                                            @if($klasifikasi)
                                                <span class="px-2 py-0.5 text-[11px] font-bold rounded {{ $klasifikasi->label_risiko === 'Tinggi' ? 'bg-red-100 text-risk-high border border-red-200' : 'bg-amber-100 text-risk-mid border border-amber-200' }}">
                                                    Skor: {{ number_format($klasifikasi->confidence_score, 1) }}%
                                                </span>
                                            @endif
                                        </div>

                                        <div class="truncate">
                                            <h3 class="text-sm font-bold text-navy-900 truncate">
                                                {{ $lowongan->posisi_lowongan ?? 'Posisi Tidak Disebutkan' }}
                                            </h3>
                                            <p class="text-xs text-gray-600 truncate">
                                                Perusahaan: <span class="font-medium text-navy-900">{{ $perusahaan->nama_perusahaan ?? 'Perusahaan Tidak Disebutkan' }}</span>
                                            </p>
                                        </div>

                                        @if($tinjauan && $tinjauan->catatan_tinjauan)
                                            <p class="text-xs text-gray-600 line-clamp-1 italic bg-amber-50/50 border-l-2 border-amber-400 pl-2 py-0.5">
                                                Catatan Analis: "{{ Str::limit($tinjauan->catatan_tinjauan, 90) }}"
                                            </p>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                        <a href="{{ route('tindak-lanjut.detail', $kasus->id_pengaduan) }}"
                                           class="px-4 py-2 bg-navy-900 hover:bg-navy-800 text-white text-xs font-bold rounded-control transition flex items-center gap-1.5 shadow-xs">
                                            <span>Beri Keputusan</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Riwayat Keputusan Terbaru --}}
                <div class="bg-white rounded-card shadow-md border border-gray-100 p-6 text-navy-900">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                        <div>
                            <h2 class="text-lg font-bold text-navy-900">Kasus yang Telah Diputuskan</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Daftar keputusan eksekutif yang telah ditetapkan sebelumnya</p>
                        </div>
                        <a href="{{ route('tindak-lanjut.index') }}" class="text-xs text-teal-600 hover:underline font-semibold">
                            Buka Seluruh Laporan →
                        </a>
                    </div>

                    @if($riwayatKeputusan->isEmpty())
                        <div class="py-8 text-center text-xs text-gray-400">
                            Belum ada riwayat penetapan keputusan.
                        </div>
                    @else
                        <div class="divide-y divide-gray-100 mt-2">
                            @foreach($riwayatKeputusan as $rk)
                                @php
                                    $lowonganRk   = $rk->lowonganKerja;
                                    $perusahaanRk = $lowonganRk?->perusahaan;
                                    $tl           = $rk->tindakLanjutKasus;
                                    $statusRk     = $rk->status_pengaduan;
                                @endphp
                                <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                    <div class="space-y-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-bold text-navy-900">{{ $rk->nomor_tiket }}</span>
                                            <x-badge-status :status="$statusRk" size="xs" />
                                        </div>
                                        <p class="text-gray-700 truncate">
                                            <span class="font-semibold">{{ $lowonganRk->posisi_lowongan ?? '-' }}</span>
                                            • {{ $perusahaanRk->nama_perusahaan ?? '-' }}
                                        </p>
                                        @if($tl && $tl->catatan_keputusan)
                                            <p class="text-gray-500 italic truncate max-w-lg">
                                                "{{ Str::limit($tl->catatan_keputusan, 80) }}"
                                            </p>
                                        @endif
                                    </div>
                                    <div class="shrink-0 flex items-center gap-2">
                                        <a href="{{ route('tindak-lanjut.detail', $rk->id_pengaduan) }}"
                                           class="text-teal-600 hover:underline font-semibold">
                                            Detail Laporan →
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

            {{-- Kolom Kanan (1 Kolom): Surat Terbit & Ringkasan Alur --}}
            <div class="space-y-6">

                {{-- Dokumen Laporan Polisi Terbit Terbaru --}}
                <div class="bg-white rounded-card shadow-md border border-gray-100 p-6 text-navy-900">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <h2 class="text-sm font-bold text-navy-900">Laporan Polisi Terbit</h2>
                        </div>
                        <span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded font-bold border border-emerald-200">
                            {{ $jumlahSuratTerbit }} Dokumen
                        </span>
                    </div>

                    @if($suratTerbaru->isEmpty())
                        <div class="py-8 text-center text-xs text-gray-400">
                            Belum ada dokumen surat polisi yang diterbitkan.
                        </div>
                    @else
                        <div class="space-y-3 mt-3">
                            @foreach($suratTerbaru as $surat)
                                @php
                                    $pengaduanSurat = $surat->tindakLanjutKasus?->pengaduan;
                                    $perusahaanSurat = $pengaduanSurat?->lowonganKerja?->perusahaan;
                                @endphp
                                <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-2 text-xs">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <span class="font-mono font-bold text-teal-800 block truncate">
                                                {{ $surat->nomor_surat }}
                                            </span>
                                            <p class="text-[11px] text-gray-500">
                                                Terbit: {{ $surat->tanggal_terbit ? $surat->tanggal_terbit->translatedFormat('d M Y') : '-' }}
                                            </p>
                                        </div>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 shrink-0">
                                            Resmi
                                        </span>
                                    </div>

                                    <div class="text-gray-700 pt-1 border-t border-gray-200">
                                        <p class="text-[11px] truncate">
                                            Terlapor: <strong>{{ $perusahaanSurat->nama_perusahaan ?? 'Pihak Terlapor' }}</strong>
                                        </p>
                                        <p class="text-[11px] text-gray-500">
                                            Instansi: {{ $surat->tujuan_instansi }}
                                        </p>
                                    </div>

                                    @if($pengaduanSurat)
                                        <div class="pt-1">
                                            <a href="{{ route('buat-surat.terbit', ['pengaduanId' => $pengaduanSurat->id_pengaduan, 'suratId' => $surat->id_surat]) }}"
                                               class="text-teal-600 hover:underline font-semibold block text-right text-[11px]">
                                                Lihat Dokumen Resmi →
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Rasio Keputusan Eksekutif --}}
                <div class="bg-navy-900 text-white rounded-card shadow-md p-6 border border-navy-800 space-y-4">
                    <h2 class="text-sm font-bold text-teal-300">Rasio Keputusan Eksekutif</h2>
                    <p class="text-xs text-gray-400 leading-relaxed">
                        Perbandingan keputusan penanganan laporan yang telah diproses oleh Kepala Bidang.
                    </p>

                    @php
                        $pctDisetujui = $totalKeputusan > 0 ? round((($jumlahDisetujui + $jumlahSelesai) / $totalKeputusan) * 100) : 0;
                        $pctDitolak   = $totalKeputusan > 0 ? round(($jumlahDitolak / $totalKeputusan) * 100) : 0;
                    @endphp

                    <div class="space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-300">Penindakan Pidana (Disetujui / Selesai):</span>
                            <span class="font-bold text-teal-300">{{ $jumlahDisetujui + $jumlahSelesai }} ({{ $pctDisetujui }}%)</span>
                        </div>
                        <div class="w-full bg-navy-800 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-teal-500 h-2.5 rounded-full" style="width: {{ $pctDisetujui }}%"></div>
                        </div>

                        <div class="flex justify-between text-xs pt-2">
                            <span class="text-gray-300">Arsip Kasus (Tidak Ditindaklanjuti):</span>
                            <span class="font-bold text-gray-400">{{ $jumlahDitolak }} ({{ $pctDitolak }}%)</span>
                        </div>
                        <div class="w-full bg-navy-800 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-gray-500 h-2.5 rounded-full" style="width: {{ $pctDitolak }}%"></div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-navy-800 text-[11px] text-gray-400 flex items-center justify-between">
                        <span>Total Keputusan Selesai:</span>
                        <strong class="text-white">{{ $totalKeputusan }} Kasus</strong>
                    </div>
                </div>

                {{-- Prinsip Yuridis Pengawasan --}}
                <div class="bg-white rounded-card shadow-sm border border-gray-200 p-5 text-xs text-navy-900 space-y-2">
                    <div class="flex items-center gap-2 text-teal-800 font-bold">
                        <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Prinsip Yuridis Pengawasan</span>
                    </div>
                    <p class="text-gray-600 leading-relaxed text-[11px]">
                        Keputusan Kepala Bidang berpedoman pada <strong>UU Ketenagakerjaan No. 13/2003</strong> dan
                        <strong>Permenaker No. 39/2016</strong>. Setiap persetujuan tindak lanjut akan secara otomatis
                        mengizinkan penerbitan draf Laporan Polisi untuk diserahkan ke Korwas PPNS Kepolisian RI.
                    </p>
                </div>

            </div>

        </div>

    </div>
</x-app-layout>

